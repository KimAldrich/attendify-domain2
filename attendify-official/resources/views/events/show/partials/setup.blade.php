{{-- resources/views/events/show/partials/setup.blade.php --}}
@php
    /** @var \App\Models\Event $event */

    use Illuminate\Support\Str;
    use Carbon\CarbonImmutable;

    // --------------------------------------------------------------
    // Event status (for header badge)
    // --------------------------------------------------------------
    $rawStatus = $event->status ?? 'draft';

    $statusLabelMap = [
        'draft'    => 'Draft',
        'published'=> 'Oncoming', // display only; still technically published
        'ongoing'  => 'Ongoing',
        'finished' => 'Finished',
        'archived' => 'Archived',
    ];

    $statusLabel = $statusLabelMap[$rawStatus] ?? Str::title($rawStatus);

    // --------------------------------------------------------------
    // Basic labels
    // --------------------------------------------------------------
    $titleText    = $event->title ?: 'Untitled event';
    $subtitleText = $event->subtitle ?: 'Add a compelling subtitle to introduce your event.';

    $eventTypeLabel = $event->event_type
        ? Str::of($event->event_type)->replace('_', ' ')->title()
        : 'General Event';

    $ownerName = optional($event->owner)->display_name ?: 'Your name here';

    $hasBanner = ! empty($event->banner_image_path);

    // Description (hero summary) text + "is long" flag
    $descText = $event->description
        ?: 'No description has been added yet. This area will show the main description of your event.';
    $descIsLong = mb_strlen(strip_tags($descText)) > 350;

    // --------------------------------------------------------------
    // Special guests
    // --------------------------------------------------------------
    $specialGuests    = $event->specialGuests ?? collect();
    $hasSpecialGuests = $specialGuests->isNotEmpty();

    // --------------------------------------------------------------
    // Target audience JSON + derived collections for display
    // --------------------------------------------------------------
    $aud = $event->target_audience_json ?? [];

    $tags = collect($aud['tags'] ?? [])
        ->filter()
        ->map(fn ($t) => Str::title($t))
        ->values();

    $yearLevels = collect($aud['year_levels'] ?? [])
        ->filter()
        ->unique()
        ->sort()
        ->values();

    // Load specific campuses / departments / offices by ID
    $campuses    = collect();
    $departments = collect();
    $offices     = collect();

    if (! empty($aud['campuses'])) {
        $campuses = \App\Models\Campus::whereIn('id', $aud['campuses'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    if (! empty($aud['departments'])) {
        $departments = \App\Models\Department::whereIn('id', $aud['departments'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    if (! empty($aud['offices'])) {
        $offices = \App\Models\Office::whereIn('id', $aud['offices'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    $allCampuses    = ! empty($aud['all_campuses']);
    $allDepartments = ! empty($aud['all_departments']);
    $allOffices     = ! empty($aud['all_offices']);
    $allowVisitors  = ! empty($aud['allow_no_account']);

    $hasAnySpecific =
        $allCampuses || $allDepartments || $allOffices ||
        $campuses->isNotEmpty() || $departments->isNotEmpty() || $offices->isNotEmpty() ||
        $yearLevels->isNotEmpty() || $tags->isNotEmpty();

    // --------------------------------------------------------------
    // Eligibility + registration badges
    // --------------------------------------------------------------
    $user          = auth()->user();
    $publicUrl     = route('events.show', $event->slug);

    // Arrays for stricter audience filtering
    $requiredCampusIds   = $aud['campuses']    ?? [];
    $requiredDeptIds     = $aud['departments'] ?? [];
    $requiredOfficeIds   = $aud['offices']     ?? [];
    $requiredYearLevels  = $aud['year_levels'] ?? [];

    $audienceConfigured = $allCampuses || $allDepartments || $allOffices
        || ! empty($requiredCampusIds) || ! empty($requiredDeptIds)
        || ! empty($requiredOfficeIds) || ! empty($requiredYearLevels);

    // User role flags
    $isAdmin       = $user?->hasRole('admin');
    $isOwner       = $user && $event->owner_id === $user->id;
    $isCoOrganizer = $user && $event->coOrganizers?->contains('id', $user->id);

    // Eligibility defaults
    // States: open_to_all / eligible / not_eligible / profile_incomplete / login_required
    $eligibilityState = 'open_to_all';
    $eligibilityLabel = 'Open to everyone';
    $eligibilityTone  = 'neutral';

    // Bypass: admin / owner / co-organizer are always eligible
    if ($isAdmin || $isOwner || $isCoOrganizer) {
        $eligibilityState = 'eligible';
        $eligibilityLabel = $isAdmin
            ? 'Admin'
            : 'Organizer';
        $eligibilityTone  = 'positive';
    } else {
        // Only run strict audience checks if something is configured
        if ($audienceConfigured) {
            if (! $user) {
                $eligibilityState = 'login_required';
                $eligibilityLabel = 'You need to sign-in';
                $eligibilityTone  = 'neutral';
            } else {
                // Prefer student fields; fall back to faculty if student not set
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

                // Campus match
                if ($allCampuses || empty($requiredCampusIds)) {
                    $campusOk = true;
                } else {
                    if (! $campusId) {
                        $missingCritical = true;
                    }
                    $campusOk = $campusId && in_array($campusId, $requiredCampusIds);
                }

                // Department match
                if ($allDepartments || empty($requiredDeptIds)) {
                    $deptOk = true;
                } else {
                    if (! $deptId) {
                        $missingCritical = true;
                    }
                    $deptOk = $deptId && in_array($deptId, $requiredDeptIds);
                }

                // Office match
                if ($allOffices || empty($requiredOfficeIds)) {
                    $officeOk = true;
                } else {
                    if (! $officeId) {
                        $missingCritical = true;
                    }
                    $officeOk = $officeId && in_array($officeId, $requiredOfficeIds);
                }

                // Year level match
                if (empty($requiredYearLevels)) {
                    $yearOk = true;
                } else {
                    if (! $yearLevel) {
                        $missingCritical = true;
                    }
                    $yearOk = $yearLevel && in_array($yearLevel, $requiredYearLevels);
                }

                if ($campusOk && $deptOk && $officeOk && $yearOk) {
                    $eligibilityState = 'eligible';
                    $eligibilityLabel = 'You can join';
                    $eligibilityTone  = 'positive';
                } else {
                    if ($missingCritical) {
                        $eligibilityState = 'profile_incomplete';
                        $eligibilityLabel = 'Update your profile first';
                        $eligibilityTone  = 'warning';
                    } else {
                        $eligibilityState = 'not_eligible';
                        $eligibilityLabel = 'You cannot join';
                        $eligibilityTone  = 'negative';
                    }
                }
            }
        }
    }

    // Registration window
    $now      = CarbonImmutable::now('Asia/Manila');
    $regOpen  = $event->reg_open_at?->timezone('Asia/Manila');
    $regClose = $event->reg_close_at?->timezone('Asia/Manila');

    $registrationState = 'not_configured';
    $registrationLabel = 'Registration window not configured';
    $registrationTone  = 'warning';

    if (! $regOpen && ! $regClose) {
        $registrationState = 'not_configured';
        $registrationLabel = 'Registration window not configured';
        $registrationTone  = 'warning';
    } elseif (! $regOpen && $regClose) {
        if ($now->gt($regClose)) {
            $registrationState = 'closed';
            $registrationLabel = 'Registration closed';
            $registrationTone  = 'negative';
        } else {
            $registrationState = 'open';
            $registrationLabel = 'Registration open';
            $registrationTone  = 'positive';
        }
    } else {
        // reg_open is set
        if ($regOpen && $now->lt($regOpen)) {
            $registrationState = 'not_yet_open';
            $registrationLabel = 'Registration not yet open';
            $registrationTone  = 'neutral';
        } elseif ($regClose && $now->gt($regClose)) {
            $registrationState = 'closed';
            $registrationLabel = 'Registration closed';
            $registrationTone  = 'negative';
        } else {
            $registrationState = 'open';
            $registrationLabel = 'Registration open';
            $registrationTone  = 'positive';
        }
    }

    // Badge classes per tone
    $toneClasses = [
        'positive' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'negative' => 'bg-red-50 text-red-700 border border-red-200',
        'warning'  => 'bg-amber-50 text-amber-800 border border-amber-200',
        'neutral'  => 'bg-slate-50 text-slate-700 border border-slate-200',
    ];

    // --------------------------------------------------------------
    // Program data
    // --------------------------------------------------------------
    $days = $event->days
    ->sortBy(function ($day) {
        return $day->date ? CarbonImmutable::parse($day->date) : CarbonImmutable::now()->addYears(50);
    });
    $activitiesByDay = $event->activities
        ->sortBy('start_time')
        ->groupBy('day_id');

        // --------------------------------------------------------------
    // Extra user flags
    // --------------------------------------------------------------
    $isLoggedIn        = (bool) $user;
    $hasCompleteProfile = $user ? (bool) $user->info_status : false;

    // If there is a configured audience and no user:
    // - if allow_no_account is true, tell them they can register without account
    // - otherwise, ask them to sign in
    if ($audienceConfigured && ! $isAdmin && ! $isOwner && ! $isCoOrganizer) {
        if (! $user) {
            if ($allowVisitors) {
                $eligibilityState = 'open_to_all';
                $eligibilityLabel = 'You can register without an account';
                $eligibilityTone  = 'positive';
            } else {
                $eligibilityState = 'login_required';
                $eligibilityLabel = 'Sign in to check eligibility';
                $eligibilityTone  = 'neutral';
            }
        } elseif (! $hasCompleteProfile) {
            // Logged-in but incomplete profile
            $eligibilityState = 'profile_incomplete';
            $eligibilityLabel = 'Complete your profile to join';
            $eligibilityTone  = 'warning';
        }
    }

       // --------------------------------------------------------------
    // Registration window (display + config state)
    // --------------------------------------------------------------
    $now        = CarbonImmutable::now('Asia/Manila');
    $regOpen    = $event->reg_open_at?->timezone('Asia/Manila');
    $regClose   = $event->reg_close_at?->timezone('Asia/Manila');
    $eventStart = $event->start_at?->timezone('Asia/Manila');

    // Effective registration end:
    // - prefer reg_close_at
    // - else fall back to event start_at
    $regEndEffective = $regClose ?: $eventStart;

    // True if organizer has set at least an end point (or event start)
    $registrationWindowConfigured = (bool) ($regOpen || $regEndEffective);

    // Label for "From – To"
    if (! $registrationWindowConfigured) {
        // State 0: not configured at all
        $registrationRangeLabel = 'The organizer has not configured the registration period yet.';
    } else {
        // If no explicit start, we treat as "open now"
        $startLabel = $regOpen
            ? $regOpen->format('M d, Y · h:i A')
            : 'Open now';

        if ($regEndEffective) {
            $endLabel = $regEndEffective->format('M d, Y · h:i A');
            $registrationRangeLabel = "{$startLabel} until {$endLabel}";
        } else {
            $registrationRangeLabel = $startLabel;
        }
    }

    // Figure out whether registration is open / not yet open / closed
    $registrationState = 'not_configured';
    $registrationLabel = 'Registration window not configured';
    $registrationTone  = 'warning';

    if (! $registrationWindowConfigured) {
        $registrationState = 'not_configured';
        $registrationLabel = 'Registration window not configured';
        $registrationTone  = 'warning';
    } else {
        // If reg_open is set and we're before it → not yet open
        if ($regOpen && $now->lt($regOpen)) {
            $registrationState = 'not_yet_open';
            $registrationLabel = 'Registration not yet open';
            $registrationTone  = 'neutral';
        } else {
            // Consider it opened; now check the effective end
            if ($regEndEffective && $now->gt($regEndEffective)) {
                $registrationState = 'closed';
                $registrationLabel = 'Registration closed';
                $registrationTone  = 'negative';
            } else {
                $registrationState = 'open';
                $registrationLabel = 'Registration open';
                $registrationTone  = 'positive';
            }
        }
    }

    // --------------------------------------------------------------
    // Capacity information
    // --------------------------------------------------------------
    $hasCapacityLimit           = ! is_null($event->capacity) && $event->capacity > 0;
    $currentRegistrationsCount  = $event->registrations()->count(); // can refine later by status
    $capacityFull               = $hasCapacityLimit && $currentRegistrationsCount >= $event->capacity;

    // --------------------------------------------------------------
    // Registration instructions (with \n treated as newlines)
    // --------------------------------------------------------------
    $registrationInstructionsRaw = (string) ($event->registration_instructions ?? '');
    $registrationInstructions    = str_replace('\n', "\n", $registrationInstructionsRaw);
    $hasRegistrationInstructions = trim($registrationInstructions) !== '';

    // --------------------------------------------------------------
    // Registration FORM state machine (for preview)
    //   0 = registration not configured / not yet open
    //   1 = registration closed / not eligible / profile/sign-in issue
    //   2 = capacity full and no waitlist
    //   3 = eligible logged-in user form
    //   4 = no-account guest form (allow_no_account = true)
    // --------------------------------------------------------------
    $registrationFormState = null;

    if (! $registrationWindowConfigured || $registrationState === 'not_yet_open') {
        // Coming soon / not yet open → state 0
        $registrationFormState = 0;
    } elseif ($registrationState === 'closed') {
        // Registration closed by date/time → block form with a clear message
        $registrationFormState = 1;
    } else {
        // Registration is OPEN by schedule; now check profile/eligibility/capacity
        if ($isLoggedIn) {
            if (! $hasCompleteProfile && ! $isAdmin && ! $isOwner && ! $isCoOrganizer) {
                $registrationFormState = 1;
            } elseif ($eligibilityState === 'not_eligible') {
                $registrationFormState = 1;
            } elseif ($hasCapacityLimit && $capacityFull && ! $event->enable_waitlist) {
                $registrationFormState = 2;
            } else {
                $registrationFormState = 3;
            }
        } else {
            if (! $allowVisitors) {
                $registrationFormState = 1; // must sign in
            } elseif ($hasCapacityLimit && $capacityFull && ! $event->enable_waitlist) {
                $registrationFormState = 2;
            } else {
                $registrationFormState = 4;
            }
        }
    }

        // --------------------------------------------------------------
    // Event organizers & contacts
    // --------------------------------------------------------------
    /** @var \App\Models\User|null $ownerUser */
    $ownerUser = $event->owner;

    // Co-organizers come from the pivot
    $coOrganizerUsers = $event->coOrganizers ?? collect();

    // Staff from EventUserRole pivot, mapped to distinct users
    $staffUsers = $event->userRoles()
        ->where('role', 'staff')
        ->with('user')
        ->get()
        ->pluck('user')
        ->filter()
        ->unique('id')
        ->values();

    // Merge co-organizers + staff into a single flat collection with role labels
    $otherOrganizers = collect();

    foreach ($coOrganizerUsers as $coOrg) {
        $otherOrganizers->push([
            'user' => $coOrg,
            'role' => 'Co-organizer',
        ]);
    }

    foreach ($staffUsers as $staff) {
        $otherOrganizers->push([
            'user' => $staff,
            'role' => 'Staff',
        ]);
    }

    $hasAnyOrganizers = (bool) ($ownerUser || $otherOrganizers->isNotEmpty());

    // --------------------------------------------------------------
    // Gallery (for preview)
    // --------------------------------------------------------------
    $galleryPhotos = $event->gallery ?? collect();
    $hasGallery    = $galleryPhotos->isNotEmpty();

    $galleryCarouselPhotos = [];
    $previewAlbums         = collect();
    $galleryAlbums         = [];

    if ($hasGallery) {
        // Random order for carousel
        $shuffled = $galleryPhotos->shuffle();

        $galleryCarouselPhotos = $shuffled
            ->map(function ($photo) {
                return [
                    'id'      => $photo->id,
                    'url'     => $photo->image_url ?? asset('images/branding/attendify-brand.png'),
                    'caption' => $photo->caption ?? '',
                ];
            })
            ->values()
            ->all();

        // Group by album_name (''/null => "Uncategorized")
        $grouped = $galleryPhotos
            ->groupBy(fn ($photo) => $photo->album_name ?? '')
            ->map(function ($photos, $rawName) {
                $name  = $rawName === '' ? null : $rawName;
                $label = $name ?: 'Uncategorized';

                return [
                    'key'    => $name ?? '__uncategorized__',
                    'name'   => $name,
                    'label'  => $label,
                    'count'  => $photos->count(),
                    'cover'  => $photos->first(),
                    'photos' => $photos,
                ];
            })
            ->values();

        // Pseudo "All photos" album first
        $allAlbum = [
            'key'    => '__all__',
            'name'   => '__all__',
            'label'  => 'All photos',
            'count'  => $galleryPhotos->count(),
            'cover'  => $galleryPhotos->first(),
            'photos' => $galleryPhotos,
        ];

        $previewAlbums = collect([$allAlbum])->merge($grouped);

        // JS-friendly galleryAlbums payload for Alpine
        $galleryAlbums = $previewAlbums
            ->map(function ($album) {
                return [
                    'key'    => $album['key'],
                    'label'  => $album['label'],
                    'count'  => $album['count'],
                    'cover'  => $album['cover']
                        ? ($album['cover']->image_url ?? asset('images/branding/attendify-brand.png'))
                        : null,
                    'photos' => $album['photos']->map(function ($photo) {
                        return [
                            'id'      => $photo->id,
                            'url'     => $photo->image_url ?? asset('images/branding/attendify-brand.png'),
                            'caption' => $photo->caption ?? '',
                            'album'   => $photo->album_name ?? null,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

@endphp
