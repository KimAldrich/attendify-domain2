<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EventPublicController extends Controller
{
    /**
     * GET /events
     * Main Events page (All Events + My Events tab).
     */
    public function index(Request $request)
    {
        $statusFilter = $request->get('status');
        $user         = $request->user();

        $eventsQuery = Event::query()
            ->with('owner')
            ->when(
                $statusFilter,
                fn ($query) => $query->where('status', $statusFilter),
                fn ($query) => $query->whereIn('status', ['published', 'ongoing', 'finished'])
            )
            ->where(function ($query) use ($user) {
                // Admins bypass visibility; faculty can see all non-admin events except admin-only (none defined here)
                if ($user && $user->hasRole('admin')) {
                    return;
                }

                $query->where('visibility', 'public');

                if ($user) {
                    if ($user->hasRole('faculty')) {
                        $query->orWhereIn('visibility', ['institution', 'faculty_only']);
                    } elseif ($user->hasRole('student')) {
                        $query->orWhere('visibility', 'institution');
                    }
                }
            })
            ->orderBy('start_at');

        $events = $eventsQuery->paginate(12)->appends($request->query());

        $myEvents = collect(); // will be filled later for logged-in users

        if ($request->user()) {
            // TODO: derive myEvents from registrations + roles
        }

        return view('events.index', compact('events', 'myEvents'));
    }

    /**
     * GET /events/my
     * Dedicated "My Events" page for logged-in users.
     */
    public function myEvents(Request $request)
    {
        $user = $request->user();

        $myEvents = collect(); // TODO: derive from registrations + roles

        return view('events.my-events', compact('myEvents', 'user'));
    }

    /**
     * GET /events/{event:slug}
     * Public single event page.
     */
    public function show(Request $request, Event $event)
    {
        $user = $request->user();

        // Eager-load common relationships for the show page.
        $event->load([
            'owner',
            'coOrganizers',
            'days',
            'tracks',
            'activities',
            'speakers',
            'gallery',
        ]);

        $existingRegistration = null;
        if ($user) {
            $existingRegistration = $event->registrations()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();
        }

        $restrictedStatuses = ['draft', 'archived'];
        $isAdmin            = $user?->hasRole('admin');
        $isOwner            = $user && $event->owner_id === $user->id;
        $isCoOrganizer      = $user && $event->coOrganizers?->contains('id', $user->id);

        // Visibility gate (admins/owners/co-organizers bypass)
        if (! $this->userCanViewEvent($user, $event) && ! ($isAdmin || $isOwner || $isCoOrganizer)) {
            abort(403, 'You are not allowed to view this event.');
        }

        if (in_array($event->status, $restrictedStatuses, true) && ! ($isAdmin || $isOwner || $isCoOrganizer)) {
            abort(403, 'This event is not available.');
        }

        return view('events.show', [
            'event'                => $event,
            'existingRegistration' => $existingRegistration,
        ]);
    }

    /**
     * POST /events/{event:slug}/register
     * Handles registration for logged-in and no-account visitors.
     */
    public function register(Request $request, Event $event)
    {
        $user = $request->user();

        Log::info('[EventPublicController] Registration attempt', [
            'event_id' => $event->id,
            'user_id'  => $user?->id,
            'guest'    => $user ? false : true,
        ]);

        // Timezone-aware window checks
        $now      = now('Asia/Manila');
        $regOpen  = $event->reg_open_at?->timezone('Asia/Manila');
        $regClose = $event->reg_close_at?->timezone('Asia/Manila');

        if ($regOpen && $now->lt($regOpen)) {
            return back()
                ->withErrors(['registration' => 'Registration has not opened yet.'])
                ->withInput()
                ->withFragment('registration-section');
        }
        if ($regClose && $now->gt($regClose)) {
            return back()
                ->withErrors(['registration' => 'Registration for this event is already closed.'])
                ->withInput()
                ->withFragment('registration-section');
        }

        // Eligibility check (server-side mirror of the Blade logic)
        $eligibility = $this->checkEligibility($event, $user);
        if (! $eligibility['ok']) {
            return back()
                ->withErrors(['registration' => $eligibility['message']])
                ->withInput()
                ->withFragment('registration-section');
        }

        // Capacity check
        $capacity       = $event->capacity;
        $currentCount   = $event->active_registration_count;
        $waitlisted     = false;
        $statusFallback = 'pending';

        if ($capacity && $currentCount >= $capacity) {
            if ($event->enable_waitlist) {
                $waitlisted = true;
                $statusFallback = 'waitlisted';
            } else {
                return back()
                    ->withErrors(['registration' => 'This event has reached its maximum capacity.'])
                    ->withInput()
                    ->withFragment('registration-section');
            }
        }

        // Validation: guest vs logged-in
        $rules = [
            'payment_proof' => $event->requires_payment_proof
                ? ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']
                : ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
        $messages = [];

        $guestMode = ! $user;
        if ($guestMode) {
            $rules['guest_name']  = ['required', 'string', 'max:255'];
            $rules['guest_email'] = ['required', 'email', 'max:255', 'unique:users,email'];
            $messages['guest_email.unique'] = 'You already have an account on Attendify. Please log in to your account.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            Log::warning('[EventPublicController] Registration validation failed', [
                'event_id' => $event->id,
                'user_id'  => $user?->id,
                'errors'   => $validator->errors()->all(),
            ]);

            return back()
                ->withErrors($validator)
                ->withInput()
                ->withFragment('registration-section');
        }

        $validated = $validator->validated();

        // Debug upload error info for payment proof (temporary)
        if ($event->requires_payment_proof && $request->hasFile('payment_proof')) {
            $fileDebug = $request->file('payment_proof');
            Log::error('[EventPublicController] Payment proof upload debug', [
                'event_id'     => $event->id,
                'user_id'      => $user?->id,
                'error'        => $fileDebug->getError(),
                'message'      => $fileDebug->getErrorMessage(),
                'size'         => $fileDebug->getSize(),
                'client_mime'  => $fileDebug->getClientMimeType(),
                'client_name'  => $fileDebug->getClientOriginalName(),
            ]);
        }

        // Duplicate guard: block only if an active/queued registration exists
        $blockedStatuses = ['pending', 'waitlisted', 'approved'];
        $existingActive = $event->registrations()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->when(! $user && isset($validated['guest_email']), fn ($q) => $q->where('email', $validated['guest_email']))
            ->whereIn('status', $blockedStatuses)
            ->first();

        if ($existingActive) {
            $statusText = $existingActive->status ? ucfirst($existingActive->status) : 'Pending';

            return back()
                ->withErrors(['registration' => "You have a pending registration for this event: {$statusText}. Please check your email for further updates."])
                ->withInput()
                ->withFragment('registration-section');
        }

        // Build registration payload
        $attendeeType = $guestMode
            ? 'no_account'
            : ($user->hasRole('student') ? 'student' : ($user->hasRole('faculty') ? 'faculty' : 'guest'));

        $status = $waitlisted
            ? 'waitlisted'
            : ($event->auto_approve_registrations ? 'approved' : $statusFallback);

        $registrationData = [
            'event_id'   => $event->id,
            'user_id'    => $user?->id,
            'attendee_type' => $attendeeType,
            'status'     => $status,
            'display_name' => $guestMode ? $validated['guest_name'] : ($user?->display_name ?? $user?->name),
            'email'        => $guestMode ? $validated['guest_email'] : $user?->email,
        ];

        if ($request->hasFile('payment_proof')) {
            $userSlug = $user?->slug ?? 'guest-'.Str::lower(Str::random(6));
            $dir      = "events/payment_proof/{$event->id}";
            $file     = $request->file('payment_proof');
            $ext      = $file->getClientOriginalExtension();
            $filename = $userSlug.'.'.$ext;

            try {
                Log::info('[EventPublicController] Uploading payment proof to R2', [
                    'event_id' => $event->id,
                    'user_id'  => $user?->id,
                    'dir'      => $dir,
                    'filename' => $filename,
                    'mime'     => $file->getClientMimeType(),
                    'size_kb'  => round($file->getSize() / 1024, 1),
                ]);

                Storage::disk('r2')->putFileAs($dir, $file, $filename);
                $registrationData['proof_of_payment_path'] = $dir.'/'.$filename;
                Log::info('[EventPublicController] Payment proof uploaded to R2', [
                    'event_id' => $event->id,
                    'user_id'  => $user?->id,
                    'path'     => $registrationData['proof_of_payment_path'],
                ]);
            } catch (\Throwable $e) {
                Log::error('[EventPublicController] Payment proof upload failed', [
                    'event_id' => $event->id,
                    'user_id'  => $user?->id,
                    'dir'      => $dir,
                    'filename' => $filename,
                    'error'    => $e->getMessage(),
                ]);

                return back()
                    ->withErrors(['registration' => 'Upload failed. Please try again with a smaller file or check your connection.'])
                    ->withInput()
                    ->withFragment('registration-section');
            }
        }

        $event->registrations()->create($registrationData);

        if ($user) {
            $this->notifyUserRegistrationStatus($event, $user, $status);
        }

        $message = $status === 'approved'
            ? 'Registration approved automatically.'
            : ($status === 'waitlisted'
                ? 'You have been added to the waitlist for this event.'
                : 'Registration submitted. Please wait for approval.');

        return back()
            ->with('registration_status', $message)
            ->withFragment('registration-section');
    }

    private function notifyUserRegistrationStatus(Event $event, Authenticatable $user, string $status): void
    {
        $title = sprintf('Event Registration %s: %s', ucfirst($status), $event->title);

        $messages = [
            'pending'    => "We received your registration for {$event->title}. Status: Pending as of ".now()->format('M d, Y H:i').". You'll get an update once it's reviewed.",
            'waitlisted' => "You are on the waitlist for {$event->title} as of ".now()->format('M d, Y H:i').". We'll notify you if a slot opens.",
            'approved'   => "Good news! Your registration for {$event->title} is Approved as of ".now()->format('M d, Y H:i').". Check the event page for details.",
        ];

        $message = $messages[$status] ?? "Your registration for {$event->title} is now {$status}.";

        Notification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'message' => $message,
            'link'    => route('events.show', $event),
            'status'  => 'unread',
        ]);
    }

    /**
     * DELETE /events/{event:slug}/registration
     * Cancel/withdraw registration for logged-in users.
     */
    public function cancelRegistration(Request $request, Event $event)
    {
        $user = $request->user();
        $registration = $event->registrations()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'waitlisted'])
            ->first();

        if (! $registration) {
            return back()
                ->withErrors(['registration' => 'No active registration found to cancel.'])
                ->withFragment('registration-section');
        }

        $registration->update(['status' => 'cancelled']);

        return back()
            ->with('registration_status', 'Your registration has been cancelled.')
            ->withFragment('registration-section');
    }

    /**
     * Check if a given user can view an event based on visibility.
     */
    protected function userCanViewEvent(?Authenticatable $user, Event $event): bool
    {
        $visibility = $event->visibility ?? 'public';

        // Admins always bypass
        if ($user && $user->hasRole('admin')) {
            return true;
        }

        return match ($visibility) {
            'public'      => true,
            'institution' => $user && ($user->hasRole('student') || $user->hasRole('faculty')),
            'faculty_only'=> $user && $user->hasRole('faculty'),
            default       => false,
        };
    }

    /**
     * Server-side eligibility check mirroring the Blade logic.
     */
    protected function checkEligibility(Event $event, ?Authenticatable $user): array
    {
        $aud = $event->target_audience_json ?? [];

        $allCampuses    = ! empty($aud['all_campuses']);
        $allDepartments = ! empty($aud['all_departments']);
        $allOffices     = ! empty($aud['all_offices']);
        $allowVisitors  = ! empty($aud['allow_no_account']);

        $requiredCampusIds   = $aud['campuses']    ?? [];
        $requiredDeptIds     = $aud['departments'] ?? [];
        $requiredOfficeIds   = $aud['offices']     ?? [];
        $requiredYearLevels  = $aud['year_levels'] ?? [];

        $audienceConfigured = $allCampuses || $allDepartments || $allOffices
            || ! empty($requiredCampusIds) || ! empty($requiredDeptIds)
            || ! empty($requiredOfficeIds) || ! empty($requiredYearLevels);

        // Bypass
        if ($user && ($user->hasRole('admin') || $event->owner_id === $user->id || $event->coOrganizers?->contains('id', $user->id))) {
            return ['ok' => true, 'message' => ''];
        }

        if (! $audienceConfigured) {
            return ['ok' => true, 'message' => ''];
        }

        // Guest/no-account
        if (! $user) {
            if ($allowVisitors) {
                return ['ok' => true, 'message' => ''];
            }

            return ['ok' => false, 'message' => 'Please sign in to register for this event.'];
        }

        // Logged-in checks
        $isStudent = $user->hasRole('student');
        $campusId  = $isStudent
            ? $user->student_campus_id
            : ($user->faculty_campus_id ?? $user->student_campus_id);

        $deptId    = $isStudent
            ? $user->student_department_id
            : ($user->faculty_department_id ?? $user->student_department_id);

        $officeId  = $user->faculty_office_id;
        $yearLevel = $user->year_level;

        $missingCritical = false;

        // Campus
        if ($allCampuses || empty($requiredCampusIds)) {
            $campusOk = true;
        } else {
            if (! $campusId) {
                $missingCritical = true;
            }
            $campusOk = $campusId && in_array($campusId, $requiredCampusIds);
        }

        // Department
        if ($allDepartments || empty($requiredDeptIds)) {
            $deptOk = true;
        } else {
            if (! $deptId) {
                $missingCritical = true;
            }
            $deptOk = $deptId && in_array($deptId, $requiredDeptIds);
        }

        // Office
        if ($allOffices || empty($requiredOfficeIds)) {
            $officeOk = true;
        } else {
            if (! $officeId) {
                $missingCritical = true;
            }
            $officeOk = $officeId && in_array($officeId, $requiredOfficeIds);
        }

        // Year level
        if (empty($requiredYearLevels)) {
            $yearOk = true;
        } else {
            if (! $yearLevel) {
                $missingCritical = true;
            }
            $yearOk = $yearLevel && in_array($yearLevel, $requiredYearLevels);
        }

        if ($campusOk && $deptOk && $officeOk && $yearOk) {
            return ['ok' => true, 'message' => ''];
        }

        if ($missingCritical) {
            return ['ok' => false, 'message' => 'Please complete your profile to check eligibility.'];
        }

        return ['ok' => false, 'message' => 'You are not eligible to register for this event.'];
    }
}
