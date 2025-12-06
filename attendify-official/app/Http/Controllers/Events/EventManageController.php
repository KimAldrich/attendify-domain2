<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventManageController extends Controller
{
    use AuthorizesRequests;
    /**
     * GET /events/manage
     * List of events the user can manage (owner or co-organizer),
     * organized into status tabs.
     */
    public function index(Request $request)
{
    $user = $request->user();

    // Which tab is active? (defaults to "published")
    $activeStatus = $request->query('status', 'published');

    // Valid status keys and labels for tabs
    $statusLabels = [
        'draft'    => 'Drafts',
        'published'=> 'Published',
        'ongoing'  => 'Ongoing',
        'finished' => 'Finished',
        'archived' => 'Archived',
    ];

    if (! array_key_exists($activeStatus, $statusLabels)) {
        $activeStatus = 'published';
    }

    // Search term
    $search = trim($request->query('q', ''));

    // Per-page (15 / 30 / 50)
    $perPageInput = (int) $request->query('per_page', 15);
    $perPage = in_array($perPageInput, [15, 30, 50], true) ? $perPageInput : 15;

    // Decide if this user is an "admin" who can see all events.
    // Adjust these role names to match your Spatie setup.
    $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);

    if ($isAdmin) {
        // Admins: can see all events
        $baseQuery = Event::query();
    } else {
        // Non-admins: only events they own or co-organize
        $managedEventIds = EventUserRole::query()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'co_organizer'])
            ->pluck('event_id');

        $baseQuery = Event::query()
            ->where(function ($q) use ($user, $managedEventIds) {
                $q->where('owner_id', $user->id)
                  ->orWhereIn('id', $managedEventIds);
            });
    }

    // Apply search filter if any
    if ($search !== '') {
        $baseQuery->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('subtitle', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Clone for counts BEFORE status filter
    $countsQuery = (clone $baseQuery);

    // Load events only for the active status
    $events = (clone $baseQuery)
        ->where('status', $activeStatus)
        ->orderByDesc('start_at')
        ->orderByDesc('created_at')
        ->paginate($perPage)
        ->withQueryString();

    // Counts per status for tab badges
    $countsByStatus = $countsQuery
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

    $roleCandidates = User::role(['student', 'faculty'])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $candidateOptions = $roleCandidates->map(function ($user) {
        return [
            'id'    => $user->id,
            'name'  => $user->full_name,
            'email' => $user->email,
        ];
    });

    $campuses = Campus::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'abbrev', 'name']);

    $departments = Department::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'abbrev', 'name']);

    $offices = Office::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name']);

    // Year levels 1–4 + "5+"
    $yearLevels = collect([
        ['value' => 'year:1', 'label' => '1st Year', 'group' => 'Year level'],
        ['value' => 'year:2', 'label' => '2nd Year', 'group' => 'Year level'],
        ['value' => 'year:3', 'label' => '3rd Year', 'group' => 'Year level'],
        ['value' => 'year:4', 'label' => '4th Year', 'group' => 'Year level'],
        ['value' => 'year:5_plus', 'label' => '5th Year and above', 'group' => 'Year level'],
    ]);

    $campusOptions = $campuses->map(fn ($c) => [
        'value' => 'campus:'.$c->id,
        'label' => $c->abbrev
        ? "{$c->name} ({$c->abbrev}) campus"
        : "{$c->name} campus",
        'group' => 'Campus',
    ]);

    $departmentOptions = $departments->map(fn ($d) => [
        'value' => 'department:'.$d->id,
        'label' => $d->abbrev
        ? "{$d->name} ({$d->abbrev})"
        : $d->name,
        'group' => 'Department / Program',
    ]);

    $officeOptions = $offices->map(fn ($o) => [
        'value' => 'office:'.$o->id,
        'label' => $o->name,
        'group' => 'Office',
    ]);

    // "All" options (these will later map to all_campuses, all_departments, all_offices)
    $allOptions = collect([
        [
            'value' => 'all_campuses',
            'label' => 'All campuses',
            'group' => 'All in category',
        ],
        [
            'value' => 'all_departments',
            'label' => 'All departments / programs',
            'group' => 'All in category',
        ],
        [
            'value' => 'all_offices',
            'label' => 'All offices',
            'group' => 'All in category',
        ],
    ]);

    $audienceOptions = $allOptions
        ->concat($campusOptions)
        ->concat($departmentOptions)
        ->concat($officeOptions)
        ->concat($yearLevels)
        ->values()
        ->all();

    return view('events.manage.index', [
        'user'           => $user,
        'events'         => $events,
        'activeStatus'   => $activeStatus,
        'statusLabels'   => $statusLabels,
        'countsByStatus' => $countsByStatus,
        'search'         => $search,
        'perPage'        => $perPage,
        'isAdmin'        => $isAdmin,
        'audienceOptions' => $audienceOptions,
        'roleCandidateOptions' => $candidateOptions,
        'campuses'       => $campuses,
        'departments'    => $departments,
        'offices'        => $offices,
        'roleCandidates' => $roleCandidates,
    ]);
}

public function store(Request $request)
{
    $user = $request->user();

    // Basic validation for now (we can expand later)
    $data = $request->validateWithBag('createEvent', [
        'title'       => ['required', 'string', 'max:120'],
        'subtitle'    => ['required', 'string', 'max:180'],
        'event_type'  => ['required', 'string', 'max:100'],
        'visibility'  => ['required', Rule::in(['public', 'institution', 'faculty_only'])],

        'capacity_type' => ['nullable', Rule::in(['no_limit', 'limited'])],
        'capacity'      => ['nullable', 'integer', 'min:1', 'max:10000'],

        'start_at'   => ['required', 'date'],
        'end_at'     => ['nullable', 'date', 'after_or_equal:start_at'],

        'reg_open_at'  => ['nullable', 'date'],
        'reg_close_at' => ['nullable', 'date', 'after_or_equal:reg_open_at'],

        'audience'               => ['array'],
        'audience.selected'      => ['array'],
        'audience.selected.*'    => ['string', 'max:50'],
        'audience.allow_no_account' => ['nullable'],
        'audience.tags'          => ['nullable', 'string', 'max:255'],

        'co_organizers'   => ['array'],
        'co_organizers.*' => ['integer', 'exists:users,id'],
        'staff'           => ['array'],
        'staff.*'         => ['integer', 'exists:users,id'],

        // NEW – creation may also accept images
        'hero_image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'], // ~3MB
        'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // ~5MB
    ]);

    // Handle capacity type: if "no_limit", ignore capacity
    $capacityType = $data['capacity_type'] ?? 'no_limit';
    unset($data['capacity_type']);

    if ($capacityType === 'no_limit') {
        $data['capacity'] = null;
    }

    // --- Audience tokens (same as before) ---------------------------------
    $audience    = $data['audience'] ?? [];
    $selected    = collect($audience['selected'] ?? []);

    $campusIds    = [];
    $departmentIds= [];
    $officeIds    = [];
    $yearLevels   = [];
    $allCampuses  = false;
    $allDepts     = false;
    $allOffices   = false;

    foreach ($selected as $token) {
        if (str_starts_with($token, 'campus:')) {
            $campusIds[] = (int) substr($token, strlen('campus:'));
        } elseif (str_starts_with($token, 'department:')) {
            $departmentIds[] = (int) substr($token, strlen('department:'));
        } elseif (str_starts_with($token, 'office:')) {
            $officeIds[] = (int) substr($token, strlen('office:'));
        } elseif (str_starts_with($token, 'year:')) {
            $suffix = substr($token, strlen('year:')); // e.g. "1", "5_plus"
            if ($suffix === '5_plus') {
                $yearLevels[] = 5;
            } else {
                $yearLevels[] = (int) $suffix;
            }
        } elseif ($token === 'all_campuses') {
            $allCampuses = true;
        } elseif ($token === 'all_departments') {
            $allDepts = true;
        } elseif ($token === 'all_offices') {
            $allOffices = true;
        }
    }

    $tags = [];
    if (! empty($audience['tags'])) {
        $tags = array_filter(array_map('trim', explode(',', $audience['tags'])));
    }

    $targetAudience = [
        'campuses'         => array_values(array_unique($campusIds)),
        'departments'      => array_values(array_unique($departmentIds)),
        'offices'          => array_values(array_unique($officeIds)),
        'year_levels'      => array_values(array_unique($yearLevels)),
        'allow_no_account' => ! empty($audience['allow_no_account']),
        'tags'             => $tags,
        'all_campuses'     => $allCampuses,
        'all_departments'  => $allDepts,
        'all_offices'      => $allOffices,
    ];

    // --- Slug: title + -xxxxxx (random 6 chars) ----------------------------
    $baseSlug = Str::slug($data['title'] ?? 'event');
    // keep room for "-xxxxxx"
    $baseSlug = Str::limit($baseSlug, 248, '');

    do {
        $random = Str::lower(Str::random(6));   // e.g. "a9f3k2"
        $slug   = "{$baseSlug}-{$random}";
    } while (Event::where('slug', $slug)->exists());

    // --- File uploads to R2 -----------------------------------------------
    $heroPath   = null;
    $bannerPath = null;

    if ($request->hasFile('hero_image')) {
        $file     = $request->file('hero_image');
        $dir      = 'events/hero';
        $filename = $slug.'-hero.'.$file->getClientOriginalExtension();

        Storage::disk('r2')->putFileAs($dir, $file, $filename);

        $heroPath = $dir.'/'.$filename; // relative path saved in DB
    }

    if ($request->hasFile('banner_image')) {
        $file     = $request->file('banner_image');
        $dir      = 'events/banner';
        $filename = $slug.'-banner.'.$file->getClientOriginalExtension();

        Storage::disk('r2')->putFileAs($dir, $file, $filename);

        $bannerPath = $dir.'/'.$filename;
    }

    // --- Create event ------------------------------------------------------
    $event = new Event();
    $event->fill([
        'title'              => $data['title'],
        'subtitle'           => $data['subtitle'] ?? null,
        'event_type'         => $data['event_type'] ?? null,
        'visibility'         => $data['visibility'],
        'capacity'           => $data['capacity'] ?? null,
        'start_at'           => $data['start_at'] ?? null,
        'end_at'             => $data['end_at'] ?? null,
        'hero_image_path'    => $heroPath,
        'banner_image_path'  => $bannerPath,
    ]);

    $event->slug     = $slug;
    $event->owner_id = $user->id;
    $event->status   = 'draft'; // All new events start as draft
    $event->target_audience_json = $targetAudience;

    $event->save();

    // --- Roles (same as before) -------------------------------------------
    EventUserRole::firstOrCreate([
        'event_id' => $event->id,
        'user_id'  => $user->id,
    ], [
        'role' => 'owner',
    ]);

    // Co-organizers
    $coOrganizers = collect($data['co_organizers'] ?? [])
        ->filter()
        ->unique();

    foreach ($coOrganizers as $coOrganizerId) {
        if ((int) $coOrganizerId === (int) $user->id) {
            continue;
        }

        EventUserRole::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id'  => $coOrganizerId,
            ],
            [
                'role' => 'co_organizer',
            ]
        );
    }

    // Staff
    $staff = collect($data['staff'] ?? [])
        ->filter()
        ->unique();

    foreach ($staff as $staffId) {
        if ((int) $staffId === (int) $user->id) {
            continue;
        }

        EventUserRole::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id'  => $staffId,
            ],
            [
                'role' => 'staff',
            ]
        );
    }

    return redirect()
        ->route('events.manage.details', $event)
        ->with('status', 'Event created as draft. You can now configure details, audience, and program.');
}

    /**
     * GET /events/manage/{event}
     * Simple redirect to the Details tab.
     */
    public function redirectToDetails(Event $event)
{
    $this->authorize('manage', $event);

    return redirect()->route('events.manage.details', $event);
}

/**
 * GET /events/manage/{event}/details
 */
public function details(Event $event)
{
    $this->authorize('manage', $event);

    $event->load(['days', 'tracks', 'speakers']);

    // --- Build audienceOptions (same as index/store) -----------------------
    $campuses = Campus::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'abbrev', 'name']);

    $departments = Department::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'abbrev', 'name']);

    $offices = Office::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name']);

    // Year levels 1–4 + "5+"
    $yearLevels = collect([
        ['value' => 'year:1', 'label' => '1st Year', 'group' => 'Year level'],
        ['value' => 'year:2', 'label' => '2nd Year', 'group' => 'Year level'],
        ['value' => 'year:3', 'label' => '3rd Year', 'group' => 'Year level'],
        ['value' => 'year:4', 'label' => '4th Year', 'group' => 'Year level'],
        ['value' => 'year:5_plus', 'label' => '5th Year and above', 'group' => 'Year level'],
    ]);

    $campusOptions = $campuses->map(fn ($c) => [
        'value' => 'campus:'.$c->id,
        'label' => $c->abbrev
            ? "{$c->name} ({$c->abbrev}) campus"
            : "{$c->name} campus",
        'group' => 'Campus',
    ]);

    $departmentOptions = $departments->map(fn ($d) => [
        'value' => 'department:'.$d->id,
        'label' => $d->abbrev
            ? "{$d->name} ({$d->abbrev})"
            : $d->name,
        'group' => 'Department / Program',
    ]);

    $officeOptions = $offices->map(fn ($o) => [
        'value' => 'office:'.$o->id,
        'label' => $o->name,
        'group' => 'Office',
    ]);

    $allOptions = collect([
        [
            'value' => 'all_campuses',
            'label' => 'All campuses',
            'group' => 'All in category',
        ],
        [
            'value' => 'all_departments',
            'label' => 'All departments / programs',
            'group' => 'All in category',
        ],
        [
            'value' => 'all_offices',
            'label' => 'All offices',
            'group' => 'All in category',
        ],
    ]);

    $audienceOptions = $allOptions
        ->concat($campusOptions)
        ->concat($departmentOptions)
        ->concat($officeOptions)
        ->concat($yearLevels)
        ->values()
        ->all();

    // --- Pre-select tokens from event->target_audience_json ----------------
    $aud = $event->target_audience_json ?? [];

    $audienceSelected = [];

    foreach ($aud['campuses'] ?? [] as $id) {
        $audienceSelected[] = 'campus:'.$id;
    }

    foreach ($aud['departments'] ?? [] as $id) {
        $audienceSelected[] = 'department:'.$id;
    }

    foreach ($aud['offices'] ?? [] as $id) {
        $audienceSelected[] = 'office:'.$id;
    }

    foreach ($aud['year_levels'] ?? [] as $level) {
        if ((int) $level >= 5) {
            $audienceSelected[] = 'year:5_plus';
        } else {
            $audienceSelected[] = 'year:'.$level;
        }
    }

    if (!empty($aud['all_campuses'])) {
        $audienceSelected[] = 'all_campuses';
    }
    if (!empty($aud['all_departments'])) {
        $audienceSelected[] = 'all_departments';
    }
    if (!empty($aud['all_offices'])) {
        $audienceSelected[] = 'all_offices';
    }

    $audienceAllowNoAccount = !empty($aud['allow_no_account']);

    return view('events.manage.details', [
        'event'                   => $event,
        'audienceOptions'         => $audienceOptions,
        'audienceSelected'        => $audienceSelected,
        'audienceAllowNoAccount'  => $audienceAllowNoAccount,
    ]);
}
/**
 * PUT /events/manage/{event}/details
 */
public function updateDetails(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    $data = $request->validateWithBag('updateDetails', [
        'title'       => ['required', 'string', 'max:120'],
        'subtitle'    => ['required', 'string', 'max:180'],
        'event_type'  => ['required', 'string', 'max:100'],
        'description' => ['nullable', 'string', 'max:2000'],

        'status'     => ['required', Rule::in(['draft', 'published', 'ongoing', 'finished', 'archived'])],
        'visibility' => ['required', Rule::in(['public', 'institution', 'faculty_only'])],

        // Audience (same rules as store)
        'audience'                    => ['array'],
        'audience.selected'           => ['array'],
        'audience.selected.*'         => ['string', 'max:50'],
        'audience.allow_no_account'   => ['nullable'],
        'audience.tags'               => ['nullable', 'string', 'max:255'],

        'hero_image'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ]);

    // --- Audience tokens -> target_audience_json ---------------------------
    $audience = $data['audience'] ?? [];
    $selected = collect($audience['selected'] ?? []);

    $campusIds     = [];
    $departmentIds = [];
    $officeIds     = [];
    $yearLevels    = [];
    $allCampuses   = false;
    $allDepts      = false;
    $allOffices    = false;

    foreach ($selected as $token) {
        if (str_starts_with($token, 'campus:')) {
            $campusIds[] = (int) substr($token, strlen('campus:'));
        } elseif (str_starts_with($token, 'department:')) {
            $departmentIds[] = (int) substr($token, strlen('department:'));
        } elseif (str_starts_with($token, 'office:')) {
            $officeIds[] = (int) substr($token, strlen('office:'));
        } elseif (str_starts_with($token, 'year:')) {
            $suffix = substr($token, strlen('year:')); // e.g. "1", "5_plus"
            if ($suffix === '5_plus') {
                $yearLevels[] = 5;
            } else {
                $yearLevels[] = (int) $suffix;
            }
        } elseif ($token === 'all_campuses') {
            $allCampuses = true;
        } elseif ($token === 'all_departments') {
            $allDepts = true;
        } elseif ($token === 'all_offices') {
            $allOffices = true;
        }
    }

    $tags = [];
    if (!empty($audience['tags'])) {
        $tags = array_filter(array_map('trim', explode(',', $audience['tags'])));
    }

    $targetAudience = [
        'campuses'         => array_values(array_unique($campusIds)),
        'departments'      => array_values(array_unique($departmentIds)),
        'offices'          => array_values(array_unique($officeIds)),
        'year_levels'      => array_values(array_unique($yearLevels)),
        'allow_no_account' => !empty($audience['allow_no_account']),
        'tags'             => $tags,
        'all_campuses'     => $allCampuses,
        'all_departments'  => $allDepts,
        'all_offices'      => $allOffices,
    ];

    // Basic scalar fields
    $event->fill([
        'title'       => $data['title'],
        'subtitle'    => $data['subtitle'],
        'event_type'  => $data['event_type'],
        'description' => $data['description'] ?? null,
        'visibility'  => $data['visibility'],
    ]);

    $event->status = $data['status'];
    $event->target_audience_json = $targetAudience;

    // Hero image upload to R2
    if ($request->hasFile('hero_image')) {
        $file     = $request->file('hero_image');
        $dir      = 'events/hero';
        $filename = 'hero-'.$event->id.'-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

        if ($event->hero_image_path) {
            Storage::disk('r2')->delete($event->hero_image_path);
        }

        Storage::disk('r2')->putFileAs($dir, $file, $filename);
        $event->hero_image_path = $dir.'/'.$filename;
    }

    // Banner image upload to R2
    if ($request->hasFile('banner_image')) {
        $file     = $request->file('banner_image');
        $dir      = 'events/banner';
        $filename = 'banner-'.$event->id.'-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

        if ($event->banner_image_path) {
            Storage::disk('r2')->delete($event->banner_image_path);
        }

        Storage::disk('r2')->putFileAs($dir, $file, $filename);
        $event->banner_image_path = $dir.'/'.$filename;
    }

    $event->save();

    return back()->with('status', 'Event details updated.');
}

public function publish(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    if ($event->status === 'archived') {
        return back()->with('status', 'Archived events cannot be published.');
    }

    // Optionally: you can enforce that start_at is set before publishing, etc.

    $event->status = 'published';
    $event->save();

    return back()->with('status', 'Event published. The public page is now visible.');
}

public function destroy(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    $request->validate([
        'confirm_phrase' => ['required', 'in:delete event'],
    ]);

    $event->delete(); // uses soft deletes since you have deleted_at

    return redirect()
        ->route('events.manage.index')
        ->with('status', 'Draft event deleted.');
}

public function cancel(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    $request->validate([
        'confirm_phrase' => ['required', 'in:cancel event'],
    ]);

    // For now, "cancel" = archive the event.
    $event->status = 'archived';
    $event->save();

    return redirect()
        ->route('events.manage.details', $event)
        ->with('status', 'Event has been cancelled and archived.');
}

public function archive(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    $event->status = 'archived';
    $event->save();

    return redirect()
        ->route('events.manage.details', $event)
        ->with('status', 'Event archived.');
}

}
