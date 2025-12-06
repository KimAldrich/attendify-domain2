{{-- resources/views/events/manage/_event-preview-actual.blade.php --}}
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

<div
    class="relative bg-slate-950/95 border border-slate-800 shadow-lg overflow-hidden
           h-[80vh] max-h-[800px]"
    x-data="{
        title: @js($titleText),
        subtitle: @js($subtitleText),
        isTitleLong: @js(mb_strlen($titleText) > 40),
        isSubtitleLong: @js(mb_strlen($subtitleText) > 60),
        overHero: true,
        heroHeight: 0,
        parallaxOffset: 0,

        galleryCarouselPhotos: @js($galleryCarouselPhotos),
        galleryCarouselIndex: 0,

        galleryAlbums: @js($galleryAlbums),
        galleryActiveAlbum: '__all__',

        galleryLightboxOpen: false,
        galleryLightboxAlbumKey: null,
        galleryLightboxIndex: 0,

        init() {
            this.$nextTick(() => {
                if (this.$refs.hero) {
                    this.heroHeight = this.$refs.hero.offsetHeight || 0;
                }
                if (this.$refs.scrollContainer) {
                    this.onScroll();
                }
            });
        },

        onScroll() {
            if (!this.$refs.scrollContainer) return;

            const top = this.$refs.scrollContainer.scrollTop || 0;
            this.parallaxOffset = -top * 0.3;
            this.overHero = top < (this.heroHeight - 72);
        },
        gallerySetAlbum(key) {
            if (!this.galleryAlbums.length) return;
            this.galleryActiveAlbum = key;
        },

        galleryGetAlbumByKey(key) {
            if (!this.galleryAlbums.length) return null;
            return this.galleryAlbums.find(a => a.key === key) || null;
        },

        galleryGetActiveAlbum() {
            if (!this.galleryAlbums.length) return null;
            return this.galleryGetAlbumByKey(this.galleryActiveAlbum) || this.galleryAlbums[0];
        },

        galleryOpenLightbox(albumKey, index) {
            const album = this.galleryGetAlbumByKey(albumKey);
            if (!album || !album.photos.length) return;

            this.galleryLightboxAlbumKey = albumKey;
            this.galleryLightboxIndex    = index;
            this.galleryLightboxOpen     = true;
            document.body.classList.add('overflow-hidden');
        },

        galleryCloseLightbox() {
            this.galleryLightboxOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        galleryGetLightboxAlbum() {
            if (!this.galleryAlbums.length) return null;
            return this.galleryGetAlbumByKey(this.galleryLightboxAlbumKey) || this.galleryGetActiveAlbum();
        },

        galleryNext() {
            const album = this.galleryGetLightboxAlbum();
            if (!album || !album.photos.length) return;

            this.galleryLightboxIndex = (this.galleryLightboxIndex + 1) % album.photos.length;
        },

        galleryPrev() {
            const album = this.galleryGetLightboxAlbum();
            if (!album || !album.photos.length) return;

            this.galleryLightboxIndex =
                (this.galleryLightboxIndex - 1 + album.photos.length) % album.photos.length;
        },
    }"
>
    {{-- HERO IMAGE --}}
    <div
        class="absolute inset-x-0 top-0 h-[70vh] max-h-[640px] will-change-transform bg-white"
        x-ref="hero"
        :style="`transform: translateY(${parallaxOffset}px);`"
    >
        <img
            src="{{ $event->hero_image_url }}"
            alt="Event hero image"
            class="w-full h-full object-cover"
        >

        {{-- Bottom fade ON the hero image --}}
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-24
                   bg-gradient-to-t from-[#021530]/90 via-[#021530]/50 to-transparent"
        ></div>
    </div>

    {{-- SCROLL LAYER --}}
    <div
        class="relative h-full overflow-y-auto no-scrollbar"
        x-ref="scrollContainer"
        @scroll="onScroll"
    >
        <div class="h-[70vh] max-h-[640px]"></div>

        {{-- STICKY TITLE BAR --}}
        <div class="sticky top-0 -mt-16 z-20">
            <div
                class="px-4 sm:px-6 pt-5 pb-3"
                :class="overHero
                    ? 'bg-gradient-to-t from-[#021530]/95 via-[#04214f]/40 to-transparent'
                    : 'bg-[#021530]'"
            >
                <div class="space-y-1">
                    {{-- Title + status badge row --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="overflow-hidden flex-1 min-w-0">
                            <div
                                class="text-xl sm:text-2xl font-semibold text-white whitespace-nowrap"
                                :class="isTitleLong ? 'marquee' : ''"
                            >
                                {{ $titleText }}
                            </div>
                        </div>

                        {{-- Status pill: white bg, blue text --}}
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full
                                       bg-white text-[#0052CC] border border-slate-200
                                       text-[0.65rem] sm:text-[0.7rem] font-semibold
                                       tracking-[0.16em] uppercase shadow-sm"
                            >
                                <span>{{ $statusLabel }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Date row --}}
                    <div class="flex items-center gap-2 text-[0.65rem] font-semibold tracking-[0.18em] uppercase text-slate-200/80">
                        <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                        <span>
                            @if ($event->start_at)
                                {{ $event->start_at->format('M d, Y · h:i A') }}
                                @if ($event->end_at)
                                    — {{ $event->end_at->format('M d, Y · h:i A') }}
                                @endif
                            @else
                                No date set yet
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>


        {{-- MAIN CONTENT --}}
        <div
            class="bg-white mt-[-1px]
                   px-4 sm:px-6 pt-0 pb-8
                   text-[13px] text-slate-800 space-y-6"
        >

{{-- HERO SUMMARY SECTION --}}
<section
    class="-mx-4 sm:-mx-6 bg-slate-900/5 border-b border-slate-200/80
           px-4 sm:px-6 pb-4 pt-8 sm:py-5"
    x-data="{ shareCopied: false }"
>
    <div class="flex flex-col sm:flex-row gap-4 items-start">
        {{-- Banner + vertical divider (only if banner exists) --}}
@if ($hasBanner)
    <div class="flex items-stretch gap-4 flex-shrink-0 w-full sm:w-auto">
        {{-- Responsive banner --}}
        <div
            class="w-full h-56         {{-- full width on mobile --}}
                   sm:w-52 sm:h-80    {{-- fixed size on sm and larger --}}
                   rounded-xl overflow-hidden bg-slate-200 shadow-md
                   ring-1 ring-slate-300 relative"
        >
            <img
                src="{{ $event->banner_image_url }}"
                alt="Event banner"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-x-4 bottom-0 h-4 bg-gradient-to-t from-slate-900/85 via-slate-900/0 to-transparent rounded-t-full"></div>
        </div>

        {{-- vertical divider only on sm+ --}}
        <div class="hidden sm:block w-px bg-slate-200/80 self-stretch"></div>
    </div>
@endif

        {{-- Text / info column --}}
        <div class="flex-1 space-y-3 min-w-0">
            {{-- Top: tags row (event type + eligibility + registration) --}}
            <div class="flex flex-wrap items-center gap-2 text-[11px] sm:text-xs">
                {{-- Event type --}}
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#0052CC] text-slate-50 shadow-sm">
                    <x-heroicon-o-tag class="w-3.5 h-3.5" />
                    <span>Event Type: {{ $eventTypeLabel }}</span>
                </span>

                {{-- Eligibility badge --}}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full {{ $toneClasses[$eligibilityTone] ?? $toneClasses['neutral'] }}"
                >
                    @if ($eligibilityState === 'eligible')
                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                    @elseif ($eligibilityState === 'not_eligible')
                        <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                    @elseif ($eligibilityState === 'profile_incomplete')
                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                    @else
                        <x-heroicon-o-user class="w-3.5 h-3.5" />
                    @endif
                    <span>{{ $eligibilityLabel }}</span>
                </span>

                {{-- Registration status badge --}}
                <span
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full {{ $toneClasses[$registrationTone] ?? $toneClasses['neutral'] }}"
                >
                    @if ($registrationState === 'open')
                        <x-heroicon-o-play-circle class="w-3.5 h-3.5" />
                    @elseif ($registrationState === 'not_yet_open')
                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                    @elseif ($registrationState === 'closed')
                        <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                    @else
                        <x-heroicon-o-cog-6-tooth class="w-3.5 h-3.5" />
                    @endif
                    <span>{{ $registrationLabel }}</span>
                </span>
            </div>

            {{-- Owner + buttons row --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                {{-- Hosted by / copy success --}}
                <div class="text-center md:text-left min-w-0">
                    <p class="text-sm sm:text-base text-slate-700">
                        <strong>Hosted by: </strong>{{ $ownerName }}
                    </p>

                    {{-- Share success message under the host name --}}
                    <div
                        x-show="shareCopied"
                        x-transition
                        class="mt-1 text-[11px] text-emerald-700 flex items-center justify-center md:justify-start gap-1"
                    >
                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                        <span>Event link copied to your clipboard</span>
                    </div>
                </div>

                {{-- Buttons: Registration + Copy link --}}
                <div class="flex flex-wrap justify-center md:justify-end gap-2">
                    {{-- Scroll to registration --}}
                    <button
                        type="button"
                        class="inline-flex items-center px-3 py-1.5 text-[11px] sm:text-xs font-medium
                            border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                            rounded-lg"
                        @click="
                            (() => {
                                const container = $refs.scrollContainer;
                                if (!container) return;

                                const el = container.querySelector('#registration-section, #registration');
                                if (!el) return;

                                const offset = 96; // pseudo header height inside the preview
                                const containerTop = container.getBoundingClientRect().top;
                                const elTop = el.getBoundingClientRect().top;

                                const target = container.scrollTop + (elTop - containerTop) - offset;

                                container.scrollTo({ top: target, behavior: 'smooth' });
                            })()
                        "
                    >
                        <x-heroicon-o-clipboard-document-list class="w-4 h-4 mr-1.5" />
                        <span>Registration</span>
                    </button>

                        @if ($hasGallery)
                        {{-- Scroll to event photos --}}
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 text-[11px] sm:text-xs font-medium
                                border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                                rounded-lg"
                            @click="
                                (() => {
                                    const container = $refs.scrollContainer;
                                    if (!container) return;

                                    const el = container.querySelector('#photos-section');
                                    if (!el) return;

                                    const offset = 96; // align similar to registration jump
                                    const containerTop = container.getBoundingClientRect().top;
                                    const elTop = el.getBoundingClientRect().top;

                                    const target = container.scrollTop + (elTop - containerTop) - offset;

                                    container.scrollTo({ top: target, behavior: 'smooth' });
                                })()
                            "
                        >
                            <x-heroicon-o-photo class="w-4 h-4 mr-1.5" />
                            <span>Gallery</span>
                        </button>
                    @endif

                    {{-- Copy event link --}}
                    <button
                        type="button"
                        class="inline-flex items-center px-3 py-1.5 text-[11px] sm:text-xs font-medium
                               border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                               rounded-lg"
                        onclick="(function() {
                            var url = '{{ $publicUrl }}';
                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(url);
                            } else {
                                var tmp = document.createElement('input');
                                tmp.value = url;
                                document.body.appendChild(tmp);
                                tmp.select();
                                document.execCommand('copy');
                                document.body.removeChild(tmp);
                            }
                        })();"
                        @click="shareCopied = true; setTimeout(() => shareCopied = false, 2000)"
                    >
                        <x-heroicon-o-clipboard-document class="w-4 h-4 mr-1.5" />
                        <span>Copy event link</span>
                    </button>
                </div>
            </div>

            {{-- Subtitle in dark-blue band --}}
            @if ($subtitleText)
                <div class="mt-2 rounded-lg bg-[#021530] px-3 py-2">
                    <div class="overflow-hidden">
                        <div
                            class="text-xs sm:text-sm font-semibold text-white whitespace-nowrap"
                            :class="isSubtitleLong ? 'marquee' : ''"
                        >
                            {{ $subtitleText }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Description with expand/collapse (arrow only, no fade overlay) --}}
            <div
                x-data="{ expanded: false }"
                class="text-[12px] sm:text-[13px] leading-relaxed text-slate-700"
            >
                <p
                    :class="expanded ? '' : 'max-h-64 overflow-hidden pr-1'"
                    class="whitespace-pre-line"
                >
                    {{ $descText }}
                </p>

                @if ($descIsLong)
                    <button
                        type="button"
                        class="mt-1 inline-flex items-center gap-1 h-6 px-2 rounded-full
                            border border-slate-300 text-slate-600 hover:bg-slate-50"
                        @click="expanded = !expanded"
                    >
                        <span class="inline-flex items-center gap-1" x-show="!expanded">
                            <span>Read More</span>
                            <x-heroicon-o-chevron-down class="w-3 h-3" />
                        </span>

                        <span class="inline-flex items-center gap-1" x-show="expanded">
                            <span>Read Less</span>
                            <x-heroicon-o-chevron-up class="w-3 h-3" />
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</section>


            {{-- WHO CAN ATTEND --}}
            <div class="-mx-4 sm:-mx-6 mb-4">
                <div class="relative flex">
                    {{-- Left ribbon: about half width on large, full width on small --}}
                    <div class="relative w-full sm:w-3/4 lg:w-1/2">
                        <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                        <div class="relative flex items-center px-4 sm:px-6 h-12">
                            <h2 class="text-[1.1rem] sm:text-lg font-semibold text-white tracking-tight flex items-center">
                                <x-heroicon-o-users class="w-5 h-5 mr-2 text-white/90" />
                                Who can attend
                            </h2>
                        </div>
                        <div
                            class="absolute top-0 right-[-24px]
                                w-0 h-0
                                border-t-[24px] border-b-[24px] border-l-[24px]
                                border-t-transparent border-b-transparent border-l-[#053b88]"
                        ></div>
                    </div>

                    <div class="hidden lg:block flex-1"></div>
                </div>
            </div>

            <section class="space-y-4">
                @if (! $hasAnySpecific)
                    {{-- Fully open event --}}
                    <div class="grid grid-cols-1 items-stretch">
                        <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-9 h-9 rounded-full bg-[#0052CC]/10 flex items-center justify-center">
                                    <x-heroicon-o-globe-alt class="w-5 h-5 text-[#0052CC]" />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-slate-900">
                                    <strong>Open to everyone at the institution</strong>
                                </p>
                                <p class="text-[13px] text-slate-700 leading-relaxed">
                                    Any student, faculty member, staff, or approved guest may join this event
                                    @if ($allowVisitors)
                                        , including no-account visitors who register using their name and email.
                                    @else
                                        . Attendees will need to sign in with their account to register.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- GRID OF AUDIENCE CARDS --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 items-stretch">
                        {{-- All campuses / departments / offices --}}
                        @if ($allCampuses || $allDepartments || $allOffices)
                            <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-9 h-9 rounded-full bg-emerald-500/10 flex items-center justify-center">
                                        <x-heroicon-o-globe-alt class="w-5 h-5 text-emerald-600" />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        <strong>Broad eligibility</strong>
                                    </p>
                                    <p class="text-[13px] text-slate-700 leading-relaxed">
                                        This event accepts participants from
                                        <span class="font-medium">
                                            @php
                                                $allLabels = [];
                                                if ($allCampuses)    $allLabels[] = 'all campuses';
                                                if ($allDepartments) $allLabels[] = 'all departments / programs';
                                                if ($allOffices)     $allLabels[] = 'all offices';
                                            @endphp
                                            <b>{{ implode(', ', $allLabels) }}</b>.
                                        </span>
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- Specific campuses --}}
                        @if ($campuses->isNotEmpty())
                            <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-9 h-9 rounded-full bg-sky-500/10 flex items-center justify-center">
                                        <x-heroicon-o-building-office class="w-5 h-5 text-sky-600" />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        <strong>Eligible campuses</strong>
                                    </p>
                                    <p class="text-[13px] text-slate-700 leading-relaxed">
                                        Only participants from these campuses may register:
                                    </p>
                                    <ul class="text-[13px] text-slate-800 list-disc list-inside">
                                        @foreach ($campuses as $campus)
                                            <li>
                                                <b>
                                                    {{ $campus->name }}
                                                    @if ($campus->abbrev)
                                                        ({{ $campus->abbrev }})
                                                    @endif
                                                </b>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Specific departments --}}
                        @if ($departments->isNotEmpty())
                            <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-9 h-9 rounded-full bg-indigo-500/10 flex items-center justify-center">
                                        <x-heroicon-o-academic-cap class="w-5 h-5 text-indigo-600" />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        <strong>Eligible departments / programs</strong>
                                    </p>
                                    <p class="text-[13px] text-slate-700 leading-relaxed">
                                        Registration is limited to:
                                    </p>
                                    <ul class="text-[13px] text-slate-800 list-disc list-inside">
                                        @foreach ($departments as $dept)
                                            <li>
                                                <b>
                                                    {{ $dept->name }}
                                                    @if ($dept->abbrev)
                                                        ({{ $dept->abbrev }})
                                                    @endif
                                                </b>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Specific offices --}}
                        @if ($offices->isNotEmpty())
                            <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-9 h-9 rounded-full bg-amber-500/10 flex items-center justify-center">
                                        <x-heroicon-o-building-library class="w-5 h-5 text-amber-600" />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        <strong>Eligible offices</strong>
                                    </p>
                                    <p class="text-[13px] text-slate-700 leading-relaxed">
                                        Staff from these offices may register:
                                    </p>
                                    <ul class="text-[13px] text-slate-800 list-disc list-inside">
                                        @foreach ($offices as $office)
                                            <li><b>{{ $office->name }}</b></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Year levels --}}
                        @if ($yearLevels->isNotEmpty())
                            <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-9 h-9 rounded-full bg-violet-500/10 flex items-center justify-center">
                                        <x-heroicon-o-academic-cap class="w-5 h-5 text-violet-600" />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        <strong>Eligible year levels</strong>
                                    </p>
                                    <p class="text-[13px] text-slate-700 leading-relaxed">
                                        This event is intended for:
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($yearLevels as $lvl)
                                            @php
                                                $label = (int) $lvl >= 5
                                                    ? '5th year and above'
                                                    : $lvl.'ᵗʰ year';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-violet-50 text-violet-800 border border-violet-100 text-[11px]">
                                                <b>{{ $label }}</b>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Visitors / login requirement --}}
                        <div class="flex gap-3 p-4 rounded-lg border border-slate-200 bg-slate-50 h-full">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-9 h-9 rounded-full bg-slate-500/10 flex items-center justify-center">
                                    @if ($allowVisitors)
                                        <x-heroicon-o-user-plus class="w-5 h-5 text-slate-700" />
                                    @else
                                        <x-heroicon-o-lock-closed class="w-5 h-5 text-slate-700" />
                                    @endif
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-slate-900">
                                    <strong>
                                        @if ($allowVisitors)
                                            Visitors and no-account attendees
                                        @else
                                            Account required to register
                                        @endif
                                    </strong>
                                </p>
                                <p class="text-[13px] text-slate-700 leading-relaxed">
                                    @if ($allowVisitors)
                                        Guests without an Attendify account may still register by providing
                                        their name and email. They will receive confirmations and certificates
                                        via email only.
                                    @else
                                        Only logged-in students, faculty, and staff can register for this event.
                                        Guests without an Attendify account cannot register directly.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            {{-- SPECIAL GUESTS --}}
            @if ($hasSpecialGuests)
                <div class="-mx-4 sm:-mx-6 mt-6 mb-6">
                    <div class="relative flex">
                        <div class="relative w-full sm:w-3/4 lg:w-1/2">
                            <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                            <div class="relative flex items-center px-4 sm:px-6 h-12">
                                <h2 class="text-[1.1rem] sm:text-xl font-semibold text-white tracking-tight flex items-center">
                                    <x-heroicon-o-sparkles class="w-5 h-5 mr-2 text-white/90" />
                                    Meet our special guests
                                </h2>
                            </div>

                            <div
                                class="absolute top-0 right-[-24px]
                                    w-0 h-0
                                    border-t-[24px] border-b-[24px] border-l-[24px]
                                    border-t-transparent border-b-transparent border-l-[#053b88]"
                            ></div>
                        </div>
                        <div class="hidden lg:block flex-1"></div>
                    </div>
                </div>

                <section class="mb-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-6">
                        @foreach ($specialGuests as $guest)
                            @php
                                $initials = collect(explode(' ', $guest->name ?? ''))
                                    ->filter()
                                    ->map(fn ($p) => mb_substr($p, 0, 1))
                                    ->take(2)
                                    ->implode('');
                            @endphp

                            <article class="flex items-center gap-4 sm:gap-5">
                                {{-- Avatar + hover description bubble --}}
                                <div class="relative group flex-shrink-0">
                                    <div
                                        class="w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden bg-slate-100
                                               border border-slate-200 shadow-sm flex items-center justify-center"
                                    >
                                        @if ($guest->photo_url)
                                            <img
                                                src="{{ $guest->photo_url }}"
                                                alt="{{ $guest->name }}"
                                                class="w-full h-full object-cover"
                                            >
                                        @else
                                            <span class="text-lg sm:text-xl font-semibold text-slate-500">
                                                {{ $initials ?: 'SG' }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ($guest->description)
                                        <div
                                            class="pointer-events-none opacity-0 group-hover:opacity-100 transition duration-200
                                                   absolute left-full top-1/2 -translate-y-1/2 ml-3 w-64 z-30
                                                   rounded-xl bg-slate-900 text-white text-xs leading-relaxed
                                                   shadow-xl border border-slate-700 p-3
                                                   hidden md:block"
                                        >
                                            {{ $guest->description }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Name + title --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-base sm:text-lg font-semibold text-slate-900 truncate">
                                        {{ $guest->name }}
                                    </h4>

                                    @if ($guest->title)
                                        <p
                                            class="mt-1 text-xs sm:text-sm text-slate-600 leading-snug
                                                   max-h-[4.5em] overflow-hidden"
                                        >
                                            {{ $guest->title }}
                                        </p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- PROGRAM SECTION --}}
            <div class="-mx-4 sm:-mx-6 mt-8 mb-4">
                <div class="relative flex">
                    <div class="relative w-full sm:w-3/4 lg:w-1/2">
                        <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                        <div class="relative flex items-center px-4 sm:px-6 h-12">
                            <h2 class="text-[1.1rem] sm:text-lg font-semibold text-white tracking-tight flex items-center">
                                <x-heroicon-o-clipboard-document-list class="w-5 h-5 mr-2 text-white/90" />
                                Program schedule
                            </h2>
                        </div>

                        <div
                            class="absolute top-0 right-[-24px]
                                w-0 h-0
                                border-t-[24px] border-b-[24px] border-l-[24px]
                                border-t-transparent border-b-transparent border-l-[#053b88]"
                        ></div>
                    </div>

                    <div class="hidden lg:block flex-1"></div>
                </div>
            </div>

            @forelse ($days as $day)
                @php
                    /** @var \App\Models\EventDay $day */

                    $dayActivities = $activitiesByDay[$day->id] ?? collect();

                    // Split into morning / afternoon (12:00 NN cutoff, Asia/Manila)
                    $morningActivities = $dayActivities->filter(function ($activity) {
                        if (! $activity->start_time) {
                            return false;
                        }

                        $start = $activity->start_time->copy()->timezone('Asia/Manila');
                        return $start->format('H:i') < '12:00';
                    });

                    $afternoonActivities = $dayActivities->diff($morningActivities);

                    // Day label: Monday, December 15, 2025
                    $dayDate = $day->date
                        ? CarbonImmutable::parse($day->date)->timezone('Asia/Manila')
                        : null;

                    $dayLabel = $dayDate
                        ? $dayDate->format('l, F j, Y')
                        : 'Date to be announced';
                @endphp

                <div class="-mx-4 sm:-mx-6 border-t border-slate-200">
                    <div class="px-4 sm:px-6 py-3 bg-slate-900/95 text-white flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-sm sm:text-base font-semibold">
                                Day {{ $loop->iteration }}
                            </span>
                            <span class="text-xs sm:text-[13px] text-slate-200">
                                {{ $dayLabel }}
                            </span>
                        </div>

                        @if ($dayActivities->isNotEmpty())
                            <span class="hidden sm:inline-flex text-[11px] uppercase tracking-[0.16em] text-slate-200/80">
                                {{ $dayActivities->count() }} activities
                            </span>
                        @endif
                    </div>

                    <div class="px-4 sm:px-6 py-4 bg-white">
                        @if ($dayActivities->isEmpty())
                            <p class="text-[13px] text-slate-600 italic">
                                No activities have been scheduled for this day yet.
                            </p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Morning column --}}
                                <div>
                                    <h4 class="text-xs font-semibold tracking-[0.18em] uppercase text-slate-500 mb-3 flex items-center gap-2">
                                        <span class="inline-block h-1.5 w-1.5 bg-amber-400"></span>
                                        Morning
                                    </h4>

                                    @if ($morningActivities->isEmpty())
                                        <p class="text-[12px] text-slate-500 italic">
                                            No morning activities scheduled.
                                        </p>
                                    @else
                                        @foreach ($morningActivities as $activity)
                                            @php
                                                /** @var \App\Models\EventActivity $activity */

                                                $start = $activity->start_time
                                                    ? $activity->start_time->copy()->timezone('Asia/Manila')->format('g:i A')
                                                    : null;
                                                $end = $activity->end_time
                                                    ? $activity->end_time->copy()->timezone('Asia/Manila')->format('g:i A')
                                                    : null;

                                                $timeLabel = $start && $end
                                                    ? "{$start} – {$end}"
                                                    : ($start ?? 'Time TBA');

                                                $track = $activity->track;
                                                if ($track) {
                                                    $venueBase = $track->name ?? 'Venue';
                                                    $venue = $track->location
                                                        ? "{$venueBase} @ {$track->location}"
                                                        : $venueBase;
                                                } else {
                                                    $venue = 'Venue TBA';
                                                }
                                            @endphp

                                            <article class="relative pl-5 pb-4 last:pb-0 border-l border-slate-200 last:border-l-0">
                                                <div class="absolute -left-[5px] top-2">
                                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#0052CC] border border-white shadow-sm"></span>
                                                </div>

                                                <div class="space-y-1">
                                                    <h5 class="text-[13px] sm:text-[14px] font-semibold text-slate-900">
                                                        {{ $activity->title ?: 'Untitled activity' }}
                                                    </h5>
                                                    <p class="text-[11px] sm:text-[12px] text-slate-600 flex flex-wrap gap-2">
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-o-clock class="w-3.5 h-3.5 text-slate-400" />
                                                            <span>{{ $timeLabel }}</span>
                                                        </span>
                                                        <span class="text-slate-300">•</span>
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-slate-400" />
                                                            <span>{{ $venue }}</span>
                                                        </span>
                                                    </p>
                                                    @if ($activity->description)
                                                        <p class="text-[12px] text-slate-700 leading-snug">
                                                            {{ $activity->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </article>
                                        @endforeach
                                    @endif
                                </div>

                                {{-- Afternoon column --}}
                                <div>
                                    <h4 class="text-xs font-semibold tracking-[0.18em] uppercase text-slate-500 mb-3 flex items-center gap-2">
                                        <span class="inline-block h-1.5 w-1.5 bg-sky-400"></span>
                                        Afternoon
                                    </h4>

                                    @if ($afternoonActivities->isEmpty())
                                        <p class="text-[12px] text-slate-500 italic">
                                            No afternoon activities scheduled.
                                        </p>
                                    @else
                                        @foreach ($afternoonActivities as $activity)
                                            @php
                                                /** @var \App\Models\EventActivity $activity */

                                                $start = $activity->start_time
                                                    ? $activity->start_time->copy()->timezone('Asia/Manila')->format('g:i A')
                                                    : null;
                                                $end = $activity->end_time
                                                    ? $activity->end_time->copy()->timezone('Asia/Manila')->format('g:i A')
                                                    : null;

                                                $timeLabel = $start && $end
                                                    ? "{$start} – {$end}"
                                                    : ($start ?? 'Time TBA');

                                                $track = $activity->track;
                                                if ($track) {
                                                    $venueBase = $track->name ?? 'Venue';
                                                    $venue = $track->location
                                                        ? "{$venueBase} @ {$track->location}"
                                                        : $venueBase;
                                                } else {
                                                    $venue = 'Venue TBA';
                                                }
                                            @endphp

                                            <article class="relative pl-5 pb-4 last:pb-0 border-l border-slate-200 last:border-l-0">
                                                <div class="absolute -left-[5px] top-2">
                                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#0052CC] border border-white shadow-sm"></span>
                                                </div>

                                                <div class="space-y-1">
                                                    <h5 class="text-[13px] sm:text-[14px] font-semibold text-slate-900">
                                                        {{ $activity->title ?: 'Untitled activity' }}
                                                    </h5>
                                                    <p class="text-[11px] sm:text-[12px] text-slate-600 flex flex-wrap gap-2">
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-o-clock class="w-3.5 h-3.5 text-slate-400" />
                                                            <span>{{ $timeLabel }}</span>
                                                        </span>
                                                        <span class="text-slate-300">•</span>
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-slate-400" />
                                                            <span>{{ $venue }}</span>
                                                        </span>
                                                    </p>
                                                    @if ($activity->description)
                                                        <p class="text-[12px] text-slate-700 leading-snug">
                                                            {{ $activity->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </article>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="-mx-4 sm:-mx-6 border-t border-slate-200">
                    <div class="px-4 sm:px-6 py-4 bg-white">
                        <p class="text-[13px] text-slate-600 italic">
                            No program days have been added yet. Once you create days and activities in the Program tab,
                            they will appear here in a formal listing.
                        </p>
                    </div>
                </div>
            @endforelse

                        {{-- REGISTRATION SECTION --}}
            <div id="registration-section" class="-mx-4 sm:-mx-6 mt-8 mb-4">
                <div class="relative flex">
                    <div class="relative w-full sm:w-3/4 lg:w-1/2">
                        <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                        <div class="relative flex items-center px-4 sm:px-6 h-12">
                            <h2 class="text-[1.1rem] sm:text-lg font-semibold text-white tracking-tight flex items-center">
                                <x-heroicon-o-ticket class="w-5 h-5 mr-2 text-white/90" />
                                Event registration
                            </h2>
                        </div>

                        <div
                            class="absolute top-0 right-[-24px]
                                w-0 h-0
                                border-t-[24px] border-b-[24px] border-l-[24px]
                                border-t-transparent border-b-transparent border-l-[#053b88]"
                        ></div>
                    </div>

                    <div class="hidden lg:block flex-1"></div>
                </div>
            </div>

            {{-- Registration summary: window, capacity, instructions --}}
            <section class="space-y-4 mb-6">
                <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                    {{-- Top row: period + capacity --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Registration period --}}
                        <div class="space-y-1">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-[0.18em] flex items-center gap-2">
                                <x-heroicon-o-clock class="w-4 h-4 text-slate-400" />
                                Registration period
                            </p>

                            @if (! $registrationWindowConfigured)
                                <p class="text-[13px] text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                                    The organizer has not set up the registration schedule yet. Please check back soon.
                                </p>
                            @else
                                <p class="text-[13px] text-slate-800">
                                    {{ $registrationRangeLabel }}
                                </p>

                                @if ($registrationState === 'open')
                                    <p class="text-[12px] text-emerald-700 flex items-center gap-1 mt-1">
                                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                        Registration is currently open.
                                    </p>
                                @elseif ($registrationState === 'not_yet_open')
                                    <p class="text-[12px] text-sky-700 flex items-center gap-1 mt-1">
                                        <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                        Registration is scheduled but not yet open.
                                    </p>
                                @elseif ($registrationState === 'closed')
                                    <p class="text-[12px] text-red-700 flex items-center gap-1 mt-1">
                                        <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                                        Registration for this event is already closed.
                                    </p>
                                @endif
                            @endif
                        </div>

                        {{-- Capacity --}}
                        <div class="space-y-1">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-[0.18em] flex items-center gap-2">
                                <x-heroicon-o-users class="w-4 h-4 text-slate-400" />
                                Capacity
                            </p>

                            @if (! $hasCapacityLimit)
                                <p class="text-[13px] text-slate-800">
                                    <span class="font-medium">Open attendance.</span>
                                    <span class="text-slate-600">
                                        No specific capacity limit has been set for this event.
                                    </span>
                                </p>
                            @else
                                <p class="text-[13px] text-slate-800">
                                    <span class="font-medium">
                                        {{ $currentRegistrationsCount }} / {{ $event->capacity }}
                                    </span>
                                    <span class="text-slate-600">
                                        registered
                                    </span>
                                </p>

                                <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    @php
                                        $pct = $event->capacity > 0
                                            ? min(100, round(($currentRegistrationsCount / $event->capacity) * 100))
                                            : 0;
                                    @endphp
                                    <div
                                        class="h-full bg-[#0052CC]"
                                        style="width: {{ $pct }}%;"
                                    ></div>
                                </div>

                                @if ($capacityFull && ! $event->enable_waitlist)
                                    <p class="text-[12px] text-red-700 flex items-center gap-1 mt-1">
                                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                                        This event has reached its maximum capacity.
                                    </p>
                                @elseif ($capacityFull && $event->enable_waitlist)
                                    <p class="text-[12px] text-amber-700 flex items-center gap-1 mt-1">
                                        <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                        Capacity is full, but a waitlist may still be available.
                                    </p>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Instructions row under the two columns --}}
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-[0.18em] flex items-center gap-2 mb-1.5">
                            <x-heroicon-o-information-circle class="w-4 h-4 text-slate-400" />
                            Instructions for registering
                        </p>

                        @if ($hasRegistrationInstructions)
                            <p class="text-[13px] text-slate-700 whitespace-pre-line">
                                {{ $registrationInstructions }}
                            </p>
                        @else
                            <p class="text-[13px] text-slate-500 italic">
                                The organizer has not added any special instructions for registration yet.
                            </p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- REGISTRATION FORM (states 0–4) --}}
            <section class="mb-10">
                @if ($registrationFormState === 0)
                    {{-- State 0: Registration not yet configured --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-800 flex items-start gap-2">
                        <x-heroicon-o-clock class="w-4 h-4 mt-0.5" />
                        <div>
                            <p class="font-medium">Registration coming soon</p>
                            <p>
                                The organizer has not set up the registration schedule for this event yet.
                                Once the registration period is configured, you’ll be able to submit your registration here.
                            </p>
                        </div>
                    </div>

                @elseif ($registrationFormState === 1)
                    {{-- State 1: Closed / not eligible / must sign in / profile incomplete --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900 flex items-start gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 mt-0.5" />
                        <div>
                            @if ($registrationState === 'closed')
                                <p class="font-medium mb-1">Registration for this event is closed</p>
                                <p>
                                    The registration period for this event has already ended.
                                    New registrations can no longer be submitted.
                                </p>
                            @elseif (! $isLoggedIn && ! $allowVisitors)
                                <p class="font-medium mb-1">Sign in to register</p>
                                <p>
                                    This event is restricted to specific audiences. Please sign in with your Attendify account
                                    to check your eligibility and submit your registration.
                                </p>
                            @elseif ($isLoggedIn && ! $hasCompleteProfile && ! $isAdmin && ! $isOwner && ! $isCoOrganizer)
                                <p class="font-medium mb-1">Complete your profile to continue</p>
                                <p>
                                    We need a few more details about you (campus, department, year level, and similar fields)
                                    before you can join events.
                                    <a
                                        href="{{ route('profile.me') }}"
                                        class="font-semibold underline hover:text-amber-900"
                                    >
                                        Update your profile
                                    </a>
                                    then return to this page.
                                </p>
                            @elseif ($eligibilityState === 'not_eligible')
                                <p class="font-medium mb-1">You’re not in the eligible audience</p>
                                <p>
                                    Based on the event’s campus, department, office, and year-level filters,
                                    your account is outside the eligible audience for this event.
                                    If you believe this is a mistake, please contact the organizers.
                                </p>
                            @else
                                <p class="font-medium mb-1">Registration unavailable</p>
                                <p>
                                    Registration is not currently available for your account. Please check back later
                                    or contact the event organizers for assistance.
                                </p>
                            @endif
                        </div>
                    </div>
                
                @elseif ($registrationFormState === 2)
                    {{-- State 2: Capacity full, waitlist disabled --}}
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-800 flex items-start gap-2">
                        <x-heroicon-o-x-mark class="w-4 h-4 mt-0.5" />
                        <div>
                            <p class="font-medium mb-1">Event capacity reached</p>
                            <p>
                                This event has already reached its maximum number of participants
                                @if ($hasCapacityLimit)
                                    ({{ $currentRegistrationsCount }} / {{ $event->capacity }} registrations).
                                @endif
                                Since the waitlist feature is not enabled, new registrations can no longer be accepted.
                            </p>
                        </div>
                    </div>

                @elseif ($registrationFormState === 3)
                    {{-- State 3: Eligible logged-in user form --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900 mb-2">
                            Request your registration ticket
                        </h3>
                        <p class="text-[13px] text-slate-600 mb-3">
                            You’re eligible to join this event using your Attendify account.
                            Confirm your registration details below and apply for a ticket.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('events.register', $event->slug) }}"
                            enctype="multipart/form-data"
                            class="space-y-4"
                        >
                            @csrf

                            {{-- If payment proof is required, show upload field --}}
                            @if ($event->requires_payment_proof)
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-slate-700">
                                        Proof of payment
                                    </label>
                                    <input
                                        type="file"
                                        name="payment_proof"
                                        accept="image/*,.pdf"
                                        class="block w-full text-[13px] text-slate-700
                                               file:mr-3 file:py-1.5 file:px-3
                                               file:rounded-md file:border-0
                                               file:text-xs file:font-medium
                                               file:bg-slate-100 file:text-slate-700
                                               hover:file:bg-slate-200
                                               border border-slate-300 rounded-md"
                                        required
                                    >
                                    <p class="text-[11px] text-slate-500">
                                        Upload a clear photo or PDF of your payment receipt for verification.
                                    </p>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-[13px] text-slate-700 border-t border-slate-100 pt-3">
                                <div class="flex flex-col">
                                    <span class="font-medium">
                                        {{ $user?->display_name }}
                                    </span>
                                    <span class="text-[12px] text-slate-500">
                                        {{ $user?->email }}
                                    </span>
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-lg text-[13px] font-semibold
                                           bg-[#0052CC] text-white hover:bg-[#0040a3] shadow-sm"
                                >
                                    <x-heroicon-o-ticket class="w-4 h-4 mr-1.5" />
                                    Apply Registration Ticket
                                </button>
                            </div>
                        </form>
                    </div>

                @elseif ($registrationFormState === 4)
                    {{-- State 4: Guest / no-account registration form --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900 mb-2">
                            Register without an account
                        </h3>
                        <p class="text-[13px] text-slate-600 mb-3">
                            This event allows guests without an Attendify account. Provide your name and email address
                            to receive confirmations and your participation certificate.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('events.register', $event->slug) }}"
                            enctype="multipart/form-data"
                            class="space-y-4"
                        >
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">
                                        Full name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="guest_name"
                                        required
                                        class="block w-full rounded-md border-slate-300 text-[13px]
                                               shadow-sm focus:border-[#0052CC] focus:ring-[#0052CC]"
                                        placeholder="Juan Dela Cruz"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">
                                        Email address <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        name="guest_email"
                                        required
                                        class="block w-full rounded-md border-slate-300 text-[13px]
                                               shadow-sm focus:border-[#0052CC] focus:ring-[#0052CC]"
                                        placeholder="you@example.com"
                                    >
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Your participation certificate and updates will be sent to this email.
                                    </p>
                                </div>
                            </div>

                            @if ($event->requires_payment_proof)
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-slate-700">
                                        Proof of payment
                                    </label>
                                    <input
                                        type="file"
                                        name="payment_proof"
                                        accept="image/*,.pdf"
                                        class="block w-full text-[13px] text-slate-700
                                               file:mr-3 file:py-1.5 file:px-3
                                               file:rounded-md file:border-0
                                               file:text-xs file:font-medium
                                               file:bg-slate-100 file:text-slate-700
                                               hover:file:bg-slate-200
                                               border border-slate-300 rounded-md"
                                        required
                                    >
                                </div>
                            @endif

                            <div class="flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-lg text-[13px] font-semibold
                                           bg-[#0052CC] text-white hover:bg-[#0040a3] shadow-sm"
                                >
                                    <x-heroicon-o-ticket class="w-4 h-4 mr-1.5" />
                                    Apply Registration Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>

 {{-- EVENT ORGANIZERS & CONTACTS --}}
@if ($hasAnyOrganizers)
    <div class="-mx-4 sm:-mx-6 mt-8 mb-4">
        <div class="relative flex">
            <div class="relative w-full sm:w-3/4 lg:w-1/2">
                <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                <div class="relative flex items-center px-4 sm:px-6 h-12">
                    <h2 class="text-[1.1rem] sm:text-lg font-semibold text-white tracking-tight flex items-center">
                        <x-heroicon-o-identification class="w-5 h-5 mr-2 text-white/90" />
                        Event organizers & contacts
                    </h2>
                </div>

                <div
                    class="absolute top-0 right-[-24px]
                        w-0 h-0
                        border-t-[24px] border-b-[24px] border-l-[24px]
                        border-t-transparent border-b-transparent border-l-[#053b88]"
                ></div>
            </div>

            <div class="hidden lg:block flex-1"></div>
        </div>
    </div>

    <section class="mb-10">
        <p class="text-[13px] text-slate-700">
            For inquiries, clarifications, or issues related to this event, you may contact any of the organizers listed below.
            Click a name to view their full profile.
        </p>

        <div class="mt-4 space-y-6">
            {{-- Primary organizer: full-width, more prominent --}}
            @if ($ownerUser)
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] uppercase text-slate-500 mb-2">
                        Primary organizer
                    </p>

<article class="border border-slate-300 bg-white shadow-sm px-5 py-4 flex items-stretch gap-4">
    {{-- Left: avatar + text --}}
    <div class="flex-1 flex items-center gap-4">
        <a
            href="{{ route('profile.show', $ownerUser) }}"
            class="flex-shrink-0"
        >
            <img
                src="{{ $ownerUser->photo_url }}"
                alt="{{ $ownerUser->display_name }}"
                class="w-14 h-14 rounded-full object-cover border border-slate-200"
            >
        </a>

        <div class="min-w-0">
            <a
                href="{{ route('profile.show', $ownerUser) }}"
                class="block text-sm sm:text-[15px] font-semibold text-slate-900 truncate hover:text-[#0052CC]"
            >
                {{ $ownerUser->display_name }}
            </a>

            <p class="text-[12px] text-slate-600 truncate">
                {{ $ownerUser->email }}
            </p>

            @if ($ownerUser->cp_no)
                <p class="text-[12px] text-slate-500 flex items-center gap-1 mt-0.5">
                    <x-heroicon-o-phone class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ $ownerUser->cp_no }}</span>
                </p>
            @endif
        </div>
    </div>

    {{-- Right: centered role pill --}}
    <div class="flex items-center">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border border-emerald-500/70 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700 bg-emerald-50">
            Owner
        </span>
    </div>
</article>

                </div>
            @endif

            {{-- Other organizers & staff: smaller grid tiles --}}
            @if ($otherOrganizers->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] uppercase text-slate-500 mb-2">
                        Co-organizers & event staff
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($otherOrganizers as $item)
                            @php
                                /** @var \App\Models\User $person */
                                $person = $item['user'];
                                $roleLabel = $item['role'];
                            @endphp

<article class="border border-slate-200 bg-slate-50 px-3 py-2.5 flex items-stretch gap-3 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
    {{-- Left: avatar + text --}}
    <div class="flex-1 flex items-center gap-3">
        <a
            href="{{ route('profile.show', $person) }}"
            class="flex-shrink-0"
        >
            <img
                src="{{ $person->photo_url }}"
                alt="{{ $person->display_name }}"
                class="w-10 h-10 rounded-full object-cover border border-slate-200"
            >
        </a>

        <div class="min-w-0">
            <a
                href="{{ route('profile.show', $person) }}"
                class="block text-[13px] font-semibold text-slate-900 truncate hover:text-[#0052CC]"
            >
                {{ $person->display_name }}
            </a>
            <p class="text-[11px] text-slate-600 truncate">
                {{ $person->email }}
            </p>

            @if ($person->cp_no)
                <p class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                    <x-heroicon-o-phone class="w-3 h-3 text-slate-400" />
                    <span>{{ $person->cp_no }}</span>
                </p>
            @endif
        </div>
    </div>

    {{-- Right: centered role pill (co-organizer / staff) --}}
    <div class="flex items-center">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border border-slate-300 text-[10px] font-medium uppercase tracking-[0.14em] text-slate-600 bg-white">
            {{ $roleLabel }}
        </span>
    </div>
</article>

                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif

@if ($hasGallery)
    @php
        // Randomized carousel photos (include album label for overlay)
        $carouselPhotos = $galleryPhotos
            ->shuffle()
            ->take(12)
            ->map(function ($p) {
                /** @var \App\Models\EventGallery $p */
                $albumLabel = $p->album_name ?: 'Uncategorized';
                return [
                    'id'          => $p->id,
                    'url'         => $p->image_url,
                    'caption'     => $p->caption,
                    'album_label' => $albumLabel,
                ];
            })
            ->values();

        // Group by album_name (''/null => Uncategorized)
        $albumsGrouped = $galleryPhotos
            ->groupBy(function ($photo) {
                return $photo->album_name ?? '';
            });

        $albumsForPreview = collect();

        // 1) Pseudo-album: All photos
        $albumsForPreview->push([
            'key'    => '__all__',
            'name'   => '__all__',
            'label'  => 'All photos',
            'count'  => $galleryPhotos->count(),
            'cover'  => $galleryPhotos->first()?->image_url,
            'photos' => $galleryPhotos->map(fn ($p) => [
                'id'      => $p->id,
                'url'     => $p->image_url,
                'caption' => $p->caption,
            ])->values(),
        ]);

        // 2) Real albums
        foreach ($albumsGrouped as $rawName => $photos) {
            $name  = $rawName === '' ? null : $rawName;
            $label = $name ?: 'Uncategorized';

            $albumsForPreview->push([
                'key'    => $name ?? 'uncategorized',
                'name'   => $name,
                'label'  => $label,
                'count'  => $photos->count(),
                'cover'  => $photos->first()?->image_url,
                'photos' => $photos->map(fn ($p) => [
                    'id'      => $p->id,
                    'url'     => $p->image_url,
                    'caption' => $p->caption,
                ])->values(),
            ]);
        }
    @endphp

    {{-- RIBBON HEADER: EVENT PHOTOS --}}
    <div id="event-photos-section" class="-mx-4 sm:-mx-6 mt-8 mb-4">
        <div class="relative flex">
            <div class="relative w-full sm:w-3/4 lg:w-1/2">
                <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                <div class="relative flex items-center px-4 sm:px-6 h-12">
                    <h2 class="text-[1.1rem] sm:text-lg font-semibold text-white tracking-tight flex items-center">
                        <x-heroicon-o-photo class="w-5 h-5 mr-2 text-white/90" />
                        Event Gallery
                    </h2>
                </div>

                <div
                    class="absolute top-0 right-[-24px]
                        w-0 h-0
                        border-t-[24px] border-b-[24px] border-l-[24px]
                        border-t-transparent border-b-transparent border-l-[#053b88]"
                ></div>
            </div>

            <div class="hidden lg:block flex-1"></div>
        </div>
    </div>

    <section
        class="mb-10"
        x-data="{
            albums: @js($albumsForPreview),
            carouselPhotos: @js($carouselPhotos),

            // null => View A (albums list)
            // non-null => View B (photos in that album)
            activeAlbumKey: null,

            // Lightbox state
            lightboxOpen: false,
            lightboxAlbumKey: null,
            lightboxIndex: 0,

            // Carousel state
            carouselIndex: 0,

            get currentAlbum() {
                if (!this.activeAlbumKey) return null;
                return this.albums.find(a => a.key === this.activeAlbumKey) || null;
            },

            openAlbum(key) {
                this.activeAlbumKey = key;
                this.lightboxAlbumKey = key;
                this.lightboxIndex = 0;
            },

            openLightbox(albumKey, idx) {
                this.lightboxAlbumKey = albumKey;
                this.lightboxIndex = idx;
                this.lightboxOpen = true;
            },

            closeLightbox() {
                this.lightboxOpen = false;
            },

            nextPhoto() {
                const album = this.albums.find(a => a.key === this.lightboxAlbumKey);
                if (!album || !album.photos.length) return;
                this.lightboxIndex = (this.lightboxIndex + 1 + album.photos.length) % album.photos.length;
            },

            prevPhoto() {
                const album = this.albums.find(a => a.key === this.lightboxAlbumKey);
                if (!album || !album.photos.length) return;
                this.lightboxIndex = (this.lightboxIndex - 1 + album.photos.length) % album.photos.length;
            },

            nextCarousel() {
                if (!this.carouselPhotos.length) return;
                this.carouselIndex = (this.carouselIndex + 1) % this.carouselPhotos.length;
            },

            prevCarousel() {
                if (!this.carouselPhotos.length) return;
                this.carouselIndex = (this.carouselIndex - 1 + this.carouselPhotos.length) % this.carouselPhotos.length;
            },

            init() {
                if (this.carouselPhotos.length > 1) {
                    setInterval(() => {
                        this.nextCarousel();
                    }, 8000); // longer interval (8s)
                }
            },
        }"
    >
        {{-- CAROUSEL: all photos, random order, with controls + album label --}}
        <div class="px-4 sm:px-6 mb-3">
            <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-900/95">
                <div class="aspect-[16/9] sm:aspect-[21/9] w-full overflow-hidden relative">
                    <template x-if="carouselPhotos.length">
                        <div
                            class="h-full w-full flex transition-transform duration-500"
                            :style="`transform: translateX(-${carouselIndex * 100}%);`"
                        >
                            <template x-for="(photo, idx) in carouselPhotos" :key="photo.id">
                                <div class="w-full h-full flex-shrink-0 relative">
                                    <img
                                        :src="photo.url"
                                        class="w-full h-full object-cover"
                                        :alt="photo.caption || 'Event photo'"
                                    >
                                    {{-- Bottom-left overlay: album name + optional caption --}}
                                    <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/70 via-black/40 to-transparent text-white">
                                        <p class="text-[11px] font-semibold tracking-[0.16em] uppercase text-white/80">
                                            <span x-text="photo.album_label"></span>
                                        </p>
                                        <p
                                            class="text-xs text-white line-clamp-2"
                                            x-show="photo.caption"
                                            x-text="photo.caption"
                                        ></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!carouselPhotos.length">
                        <div class="w-full h-full flex items-center justify-center text-xs text-slate-400 bg-slate-900/80">
                            <x-heroicon-o-photo class="w-5 h-5 mr-1" />
                            <span>No photos available yet</span>
                        </div>
                    </template>

                    {{-- Carousel controls --}}
                    <button
                        type="button"
                        class="absolute inset-y-0 left-2 my-auto w-9 h-9 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg:black/80 hover:bg-black/80"
                        @click="prevCarousel()"
                        x-show="carouselPhotos.length > 1"
                    >
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                    </button>

                    <button
                        type="button"
                        class="absolute inset-y-0 right-2 my-auto w-9 h-9 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80"
                        @click="nextCarousel()"
                        x-show="carouselPhotos.length > 1"
                    >
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- BREADCRUMB (below carousel) --}}
        <div class="flex items-center justify-between px-4 sm:px-6 mb-2">
            <nav class="text-[12px] text-slate-600 flex items-center gap-1">
                {{-- View A: just label --}}
                <template x-if="!activeAlbumKey">
                    <span class="font-semibold text-slate-800">
                        Event Albums
                    </span>
                </template>

                {{-- View B: Event photos / Album Name --}}
                <template x-if="activeAlbumKey">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="text-slate-600 hover:text-sky-600 font-medium"
                            @click="activeAlbumKey = null"
                        >
                            Event Albums
                        </button>
                        <span class="text-slate-400">/</span>
                        <span
                            class="font-semibold text-slate-900"
                            x-text="currentAlbum?.label || ''"
                        ></span>
                    </div>
                </template>
            </nav>
        </div>

        {{-- VIEW A: ALBUM STRIP (full-width bg, 2 rows, horizontal scroll, thin scrollbar) --}}
        <div class="-mx-4 sm:-mx-6" x-show="!activeAlbumKey" x-transition>
            <div class="bg-[#021530] border-y border-slate-800 px-4 sm:px-6 py-3">
                <div class="overflow-x-auto thin-scrollbar pb-2 -mx-2 px-2">
                    <div
                        class="grid grid-rows-2 grid-flow-col auto-cols-[11rem] gap-3"
                    >
                        <template x-for="album in albums" :key="album.key">
                            <button
                                type="button"
                                class="group relative h-full w-full text-left border border-slate-500/60 rounded-lg bg-slate-900/40 hover:border-sky-400 hover:bg-slate-900/70 hover:shadow-sm overflow-hidden flex flex-col"
                                @click="openAlbum(album.key)"
                            >
                                <div class="relative">
                                    <template x-if="album.cover">
                                        <div>
                                            <template x-if="album.count > 1">
                                                <div>
                                                    <div class="absolute inset-0 translate-x-1 translate-y-1 rounded-md border border-slate-600 bg-slate-900/60"></div>
                                                    <div class="absolute inset-0 translate-x-0.5 -translate-y-0.5 rounded-md border border-slate-600 bg-slate-900/40"></div>
                                                </div>
                                            </template>
                                            <div class="relative">
                                                <img
                                                    :src="album.cover"
                                                    alt=""
                                                    class="w-full aspect-[4/3] object-cover rounded-md"
                                                >
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!album.cover">
                                        <div class="w-full aspect-[4/3] flex items-center justify-center text-xs text-slate-300 bg-slate-900/60">
                                            <x-heroicon-o-photo class="w-5 h-5 mr-1" />
                                            <span>No photos</span>
                                        </div>
                                    </template>
                                </div>

                                <div class="px-3 py-2 border-t border-slate-600/80 mt-auto">
                                    <div class="flex items-center justify-between gap-2">
                                        <p
                                            class="text-xs font-semibold text-white truncate group-hover:text-sky-300"
                                            x-text="album.label"
                                        ></p>
                                        <p
                                            class="text-[11px] text-slate-200 whitespace-nowrap"
                                            x-text="album.count + (album.count === 1 ? ' photo' : ' photos')"
                                        ></p>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- subtle scrollbar underline --}}
                <div class="mt-1 h-px bg-slate-700/80"></div>
            </div>
        </div>

        {{-- VIEW B: PHOTOS STRIP (full-width, taller, 2 rows, horizontal scroll, thin scrollbar) --}}
        <div class="-mx-4 sm:-mx-6 mt-3" x-show="activeAlbumKey" x-transition>
            <template x-if="currentAlbum && currentAlbum.photos.length">
                <div class="bg-slate-50 border-y border-slate-200 px-4 sm:px-6 py-4">
                    <div class="overflow-x-auto thin-scrollbar pb-2 -mx-2 px-2">
                        <div
                            class="grid grid-rows-2 grid-flow-col auto-cols-[13rem] gap-3 h-72"
                        >
                            <template x-for="(photo, idx) in currentAlbum.photos" :key="photo.id">
                                <div class="w-full h-full flex flex-col">
                                    <img
                                        :src="photo.url"
                                        :alt="photo.caption || 'Event photo'"
                                        class="w-full h-40 rounded-md border border-slate-200 bg-slate-50 cursor-pointer object-cover"
                                        @click="openLightbox(currentAlbum.key, idx)"
                                    >

                                    <template x-if="photo.caption">
                                        <p
                                            class="mt-1 text-[11px] text-slate-700 line-clamp-2"
                                            x-text="photo.caption"
                                        ></p>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- scrollbar underline --}}
                        <div class="mt-1 h-px bg-slate-200"></div>
                    </div>
                </div>
            </template>

            <template x-if="currentAlbum && !currentAlbum.photos.length">
                <p class="text-[13px] text-slate-500 italic py-4 px-4 sm:px-6">
                    This album has no photos yet.
                </p>
            </template>
        </div>

        {{-- LIGHTBOX MODAL --}}
        <div
            x-show="lightboxOpen"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/70"
        >
            <div
                class="relative bg-slate-950/95 border border-slate-700 rounded-xl w-full max-w-3xl mx-4 shadow-2xl"
                @click.away="closeLightbox()"
            >
                <button
                    type="button"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90"
                    @click="closeLightbox()"
                >
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>

                {{-- Top bar: album label + index --}}
                <div class="flex items-center justify-between px-4 pt-4 pb-2 text-[12px] text-slate-100">
                    <div>
                        <p
                            class="font-semibold"
                            x-text="(albums.find(a => a.key === lightboxAlbumKey)?.label) || 'Event photos'"
                        ></p>
                    </div>
                    <div x-show="albums.find(a => a.key === lightboxAlbumKey)">
                        <span
                            x-text="
                                (lightboxIndex + 1) + ' / ' +
                                (albums.find(a => a.key === lightboxAlbumKey).photos.length)
                            "
                        ></span>
                    </div>
                </div>

                {{-- Body: arrows + image --}}
                <div class="px-4 pb-4 flex items-center gap-3">
                    <button
                        type="button"
                        class="w-9 h-9 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90"
                        @click.stop="prevPhoto()"
                    >
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                    </button>

                    <div class="flex-1 max-h-[70vh]">
                        <template x-if="albums.find(a => a.key === lightboxAlbumKey)">
                            <img
                                :src="albums.find(a => a.key === lightboxAlbumKey).photos[lightboxIndex]?.url"
                                :alt="albums.find(a => a.key === lightboxAlbumKey).photos[lightboxIndex]?.caption || 'Event photo'"
                                class="w-full max-h-[70vh] object-contain rounded-lg bg-black"
                            >
                        </template>

                        <template x-if="albums.find(a => a.key === lightboxAlbumKey)?.photos[lightboxIndex]?.caption">
                            <p
                                class="mt-2 text-[12px] text-slate-100"
                                x-text="albums.find(a => a.key === lightboxAlbumKey).photos[lightboxIndex].caption"
                            ></p>
                        </template>
                    </div>

                    <button
                        type="button"
                        class="w-9 h-9 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90"
                        @click.stop="nextPhoto()"
                    >
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    </section>
@endif


        </div>
    </div>
</div>
