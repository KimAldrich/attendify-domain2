<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class EventRegistrationController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/registration
     */
    public function index(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $status = $request->query('status', 'all');
        $search = $request->query('search', '');
        $sort   = $request->query('sort', 'desc'); // created_at sort direction
        $type   = $request->query('type', 'all');

        if (! in_array($sort, ['asc', 'desc'], true)) {
            $sort = 'desc';
        }

        // Base query for this event's registrations
        $registrationsQuery = EventRegistration::query()
            ->where('event_id', $event->id)
            ->with('user');

        // Filter by status if not "all"
        $validStatuses = ['pending', 'approved', 'rejected', 'waitlisted', 'cancelled',];
        if (in_array($status, $validStatuses, true)) {
            $registrationsQuery->where('status', $status);
        }

        // Filter by attendee type if not "all"
        $validTypes = [
            'visitor' => 'no_account',
            'guest'   => 'guest',
            'student' => 'student',
            'faculty' => 'faculty',
        ];
        if ($type !== 'all' && isset($validTypes[$type])) {
            $registrationsQuery->where('attendee_type', $validTypes[$type]);
        }

        // Optional search by user name/email (logged-in attendees only)
        if ($search !== '') {
            $registrationsQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Group by status priority then created_at
        $statusOrder = "CASE status
            WHEN 'pending' THEN 1
            WHEN 'waitlisted' THEN 2
            WHEN 'approved' THEN 3
            WHEN 'rejected' THEN 4
            WHEN 'cancelled' THEN 5
            ELSE 6 END";

        $registrations = $registrationsQuery
            ->orderByRaw($statusOrder)
            ->orderBy('created_at', $sort)
            ->paginate(20)
            ->withQueryString();

        // Status counts for summary cards and (potential) future use
        $statusCounts = EventRegistration::selectRaw('status, COUNT(*) as count')
            ->where('event_id', $event->id)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $settingsLockedStatuses = ['ongoing', 'finished', 'archived'];
        $settingsLocked         = in_array($event->status, $settingsLockedStatuses, true);
        $hasNonRejected         = EventRegistration::where('event_id', $event->id)
            ->where('status', '!=', 'rejected')
            ->exists();
        $paymentProofLocked     = $hasNonRejected;

        // Totals for summary cards (pending + approved count towards capacity)
        $activeStatuses     = EventRegistration::ACTIVE_STATUSES;
        $totalRegistrations = EventRegistration::where('event_id', $event->id)
            ->whereIn('status', $activeStatuses)
            ->count();
        $pendingCount    = $statusCounts['pending']    ?? 0;
        $approvedCount   = $statusCounts['approved']   ?? 0;
        $rejectedCount   = $statusCounts['rejected']   ?? 0;
        $waitlistedCount = $statusCounts['waitlisted'] ?? 0;

        // Helpful flag for the "close when event starts" checkbox
        $closeWhenEventStarts = false;
        if ($event->start_at && $event->reg_close_at) {
            $closeWhenEventStarts = $event->reg_close_at->equalTo($event->start_at);
        }

        return view('events.manage.registration', [
            'event'                => $event,
            'registrations'        => $registrations,
            'statusFilter'         => $status,
            'search'               => $search,
            'sortDirection'        => $sort,
            'statusCounts'         => $statusCounts,
            'closeWhenEventStarts' => $closeWhenEventStarts,
            'totalRegistrations'   => $totalRegistrations,
            'pendingCount'         => $pendingCount,
            'approvedCount'        => $approvedCount,
            'rejectedCount'        => $rejectedCount,
            'waitlistedCount'      => $waitlistedCount,
            'settingsLocked'       => $settingsLocked,
            'paymentProofLocked'   => $paymentProofLocked,
            'typeFilter'           => $type,
        ]);
    }


    public function approve(Event $event, EventRegistration $registration)
    {
        $this->authorize('manage', $event);

        $this->ensureRegistrationMatchesEvent($event, $registration);

        if (! in_array($registration->status, ['pending', 'waitlisted'], true)) {
            return $this->redirectToTable($event, 'Only pending or waitlisted registrations can be approved.');
        }

        // When promoting a waitlisted record, ensure capacity is not exceeded.
        if (
            $registration->status === 'waitlisted'
            && $event->capacity
            && $event->active_registration_count >= $event->capacity
        ) {
            return $this->redirectToTable($event, 'Cannot approve because the event is already at capacity.');
        }

        $registration->update(['status' => 'approved']);
        $this->notifyRegistrationStatus($registration, 'approved');

        // If a slot becomes available (e.g., from prior rejections), pull from waitlist.
        $this->promoteWaitlist($event);

        return $this->redirectToTable($event, 'Registration approved.');
    }

    public function reject(Event $event, EventRegistration $registration)
    {
        $this->authorize('manage', $event);

        $this->ensureRegistrationMatchesEvent($event, $registration);

        if (! in_array($registration->status, ['pending', 'waitlisted', 'approved'], true)) {
            return $this->redirectToTable($event, 'Only pending, waitlisted, or approved registrations can be rejected.');
        }

        $wasActive = in_array($registration->status, EventRegistration::ACTIVE_STATUSES, true);

        $registration->update(['status' => 'rejected']);
        $this->notifyRegistrationStatus($registration, 'rejected');

        // If we rejected someone who was holding a slot, pull from waitlist.
        if ($wasActive) {
            $this->promoteWaitlist($event);
        }

        return $this->redirectToTable($event, 'Registration rejected.');
    }

    public function waitlist(Event $event, EventRegistration $registration)
    {
        $this->authorize('manage', $event);

        $this->ensureRegistrationMatchesEvent($event, $registration);

        if (! $event->enable_waitlist) {
            return $this->redirectToTable($event, 'Waitlisting is disabled for this event.');
        }

        if (! $event->capacity) {
            return $this->redirectToTable($event, 'Capacity is unlimited; waitlisting is not necessary.');
        }

        if ($event->active_registration_count < $event->capacity) {
            return $this->redirectToTable($event, 'There is still capacity available; no need to waitlist this attendee.');
        }

        if ($registration->status !== 'pending') {
            return $this->redirectToTable($event, 'Only pending registrations can be moved to the waitlist.');
        }

        $registration->update(['status' => 'waitlisted']);
        $this->notifyRegistrationStatus($registration, 'waitlisted');

        return $this->redirectToTable($event, 'Registration moved to waitlist.');
    }

    /**
     * PUT /events/manage/{event}/registration/settings
     */
    public function updateSettings(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $activeCount = $event->active_registration_count;
        Log::error('[EventRegistrationController] updateSettings attempt', [
            'event_id'        => $event->id,
            'status'          => $event->status,
            'input'           => $request->only(['capacity_mode', 'capacity', 'allow_no_account', 'requires_payment_proof', 'auto_approve_registrations', 'enable_waitlist']),
            'active_count'    => $activeCount,
        ]);
        $lockedStatuses = ['ongoing', 'finished', 'archived'];
        if (in_array($event->status, $lockedStatuses, true)) {
            Log::error('[EventRegistrationController] updateSettings blocked by status', [
                'event_id' => $event->id,
                'status'   => $event->status,
            ]);
            return back()
                ->withErrors([
                    'capacity' => 'Registration settings cannot be edited once the event is ongoing, finished, or archived.',
                ], 'updateRegistrationSettings')
                ->with('open_settings', true)
                ->withFragment('registrations');
        }

        // capacity_mode: "unlimited" or "limited"
        $validated = $request->validateWithBag('updateRegistrationSettings', [
            'capacity_mode'              => ['required', 'in:unlimited,limited'],
            'capacity'                   => ['nullable', 'integer', 'min:1', 'required_if:capacity_mode,limited'],
            'reg_open_at'                => ['nullable', 'date'],
            'reg_close_at'               => ['nullable', 'date', 'after_or_equal:reg_open_at'],
            'registration_instructions'  => ['nullable', 'string', 'max:2000'],
            'close_when_event_starts'    => ['nullable', 'boolean'],
            'allow_no_account'           => ['nullable', 'boolean'],
        ], [
            'capacity_mode.required'       => 'Please choose whether capacity is limited or open attendance.',
            'capacity.required_if'         => 'Please enter a capacity when limiting attendance.',
            'capacity.integer'             => 'Capacity must be a whole number.',
            'capacity.min'                 => 'Capacity must be at least 1 attendee.',
            'reg_open_at.date'             => 'Registration open time must be a valid date/time.',
            'reg_close_at.date'            => 'Registration close time must be a valid date/time.',
            'reg_close_at.after_or_equal'  => 'Registration close time must be after or the same as the open time.',
            'registration_instructions.max'=> 'Instructions must not be longer than 2000 characters.',
        ]);

        // Handle capacity from capacity_mode
        $capacityMode = $validated['capacity_mode'];
        $newCapacity = $capacityMode === 'unlimited'
            ? null
            : (int) $validated['capacity'];

        if ($newCapacity !== null && $newCapacity < $activeCount) {
            Log::error('[EventRegistrationController] updateSettings blocked by capacity shrink', [
                'event_id'      => $event->id,
                'new_capacity'  => $newCapacity,
                'active_count'  => $activeCount,
            ]);
            return back()
                ->withErrors([
                    'capacity' => "Capacity cannot be lower than the {$activeCount} pending/approved registrations already on record.",
                ], 'updateRegistrationSettings')
                ->withInput();
        }

        $event->capacity = $newCapacity;

        // Handle "close when event starts" checkbox:
        if ($request->boolean('close_when_event_starts') && $event->start_at) {
            $event->reg_close_at = $event->start_at;
        } else {
            $event->reg_open_at  = $validated['reg_open_at'] ?? null;
            $event->reg_close_at = $validated['reg_close_at'] ?? null;
        }

        $event->registration_instructions = $validated['registration_instructions'] ?? null;

        // Boolean toggles: payment proof, auto-approve, waitlist
        $warnings = [];

        $newRequiresPaymentProof = $request->boolean('requires_payment_proof');
        if ($newRequiresPaymentProof !== $event->requires_payment_proof) {
            $hasNonRejected = EventRegistration::where('event_id', $event->id)
                ->where('status', '!=', 'rejected')
                ->exists();

            if ($hasNonRejected) {
                Log::error('[EventRegistrationController] updateSettings payment proof change skipped due to existing registrations', [
                    'event_id'        => $event->id,
                    'existing_count'  => EventRegistration::where('event_id', $event->id)->where('status', '!=', 'rejected')->count(),
                ]);
                // Keep existing value, but allow other fields to save
                $newRequiresPaymentProof = $event->requires_payment_proof;
                $warnings[] = 'Payment proof setting was not changed because registrations already exist.';
            }
        }
        $event->requires_payment_proof     = $newRequiresPaymentProof;
        $event->auto_approve_registrations = $request->boolean('auto_approve_registrations');
        $event->enable_waitlist            = $request->boolean('enable_waitlist');

        // Allow no-account registration flag lives in target_audience_json
        $audience = $event->target_audience_json;
        if (! is_array($audience)) {
            $audience = (array) $audience;
        }

        // Normalize structure to avoid losing other audience keys
        $audience = array_merge([
            'campuses'         => [],
            'departments'      => [],
            'offices'          => [],
            'year_levels'      => [],
            'allow_no_account' => false,
            'tags'             => [],
            'all_campuses'     => false,
            'all_departments'  => false,
            'all_offices'      => false,
        ], $audience);

        $audience['allow_no_account'] = $request->boolean('allow_no_account');

        // Just set it on the same model and save once
        $event->target_audience_json = $audience;

        // Now persist **all** changes made above in one go
        $event->save();
        Log::error('[EventRegistrationController] Registration settings saved', [
            'event_id'           => $event->id,
            'allow_no_account'   => $audience['allow_no_account'],
            'capacity'           => $event->capacity,
            'requires_payment'   => $event->requires_payment_proof,
            'auto_approve'       => $event->auto_approve_registrations,
            'enable_waitlist'    => $event->enable_waitlist,
            'reg_open_at'        => $event->reg_open_at,
            'reg_close_at'       => $event->reg_close_at,
        ]);

        // If capacity increased (or unlimited) and waitlist is on, pull earliest waitlisted into open slots.
        $this->promoteWaitlist($event);

        return back()
            ->with('registration_settings_status', 'Registration settings updated.')
            ->with('registration_settings_warning', implode(' ', $warnings))
            ->with('open_settings', true)
            ->withFragment('registrations');
    }

    private function ensureRegistrationMatchesEvent(Event $event, EventRegistration $registration): void
    {
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
    }

    private function notifyRegistrationStatus(EventRegistration $registration, string $status): void
    {
        if (! $registration->user_id) {
            return;
        }

        $event = $registration->event;
        $title = sprintf('Event Registration %s: %s', ucfirst($status), $event->title);

        $messages = [
            'pending'    => "We received your registration for {$event->title}. Status: Pending as of ".now()->format('M d, Y H:i').". We'll update you once it's reviewed.",
            'waitlisted' => "You are on the waitlist for {$event->title} as of ".now()->format('M d, Y H:i').". We'll notify you if a slot opens.",
            'approved'   => "Good news! Your registration for {$event->title} is Approved as of ".now()->format('M d, Y H:i').". You can view event details and next steps in the link below.",
            'rejected'   => "Your registration for {$event->title} was marked as Rejected on ".now()->format('M d, Y H:i').". If you believe this is an error, please contact the organizer.",
        ];

        $message = $messages[$status] ?? "Your registration for {$event->title} is now {$status}.";

        Notification::create([
            'user_id' => $registration->user_id,
            'title'   => $title,
            'message' => $message,
            'link'    => route('events.show', $event),
            'status'  => 'unread',
        ]);
    }

    private function redirectToTable(Event $event, string $message)
    {
        return redirect()
            ->to(route('events.manage.registration', $event) . '#registrations')
            ->with('registration_table_status', $message);
    }

    private function availableSlots(Event $event): int
    {
        if (! $event->capacity) {
            return PHP_INT_MAX;
        }

        $available = $event->capacity - $event->active_registration_count;

        return $available > 0 ? $available : 0;
    }

    private function promoteWaitlist(Event $event): void
    {
        if (! $event->enable_waitlist) {
            return;
        }

        $slots = $this->availableSlots($event);

        if ($slots <= 0) {
            return;
        }

        $query = $event->waitlistedRegistrations()
            ->orderBy('created_at')
            ->orderBy('id');

        $registrations = $event->capacity
            ? $query->limit($slots)->get()
            : $query->get();

        foreach ($registrations as $waitlisted) {
            $newStatus = $event->auto_approve_registrations ? 'approved' : 'pending';
            $waitlisted->update([
                'status' => $newStatus,
            ]);
            $this->notifyRegistrationStatus($waitlisted, $newStatus);
        }
    }
}
