<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Office;
use App\Models\RoleUpgradeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProfilePage extends Component
{
    use WithFileUploads;
    public User $profile;
    public bool $viewerIsOwner = false;
    public $photoUpload = null;
    public $faceRecognitionUpload = null;

    public ?RoleUpgradeRequest $pendingRoleRequest = null;

    public $roleApplicationType = 'student';
    public $roleCredential;

    // Shared fields
    public ?string $first_name = null;
    public ?string $middle_name = null;
    public ?string $last_name = null;

    public ?string $cp_no = null;

    public ?string $address_house = null;
    public ?string $address_brgy = null;
    public ?string $address_city = null;
    public ?string $address_province = null;

    public ?string $link_facebook = null;
    public ?string $link_linkedin = null;

    public ?string $bio = null;

    public ?string $photo_path = null;

    // Direct boolean column on users table
    public bool $info_status = false;

    // ── Role-specific (MySQL columns) ────────────────────────────────────────
    public ?string $organization = null; // guest

    public ?string $student_number = null;
    public ?string $year_level = null; // '1st'...'5th+'
    public ?int    $student_department_id = null;
    public ?int    $student_campus_id = null;
    public bool    $is_moderator = false;

    public bool    $is_teaching = false; // faculty
    public ?int    $faculty_department_id = null;
    public ?int    $faculty_office_id = null;
    public ?int    $faculty_campus_id = null;

    // UI
    public string $roleTab = 'student';
    public bool $editOpen = false;

    public array $yearLevels = ['1st','2nd','3rd','4th','5th+'];
    public $campuses;
    public $departments;
    public $offices;

    public array $draft = [];  // modal-only working copy

    // NEW: for Password tab branching (uses Firebase Auth provider list; no Firestore)
    public bool $hasPasswordProvider = false;

    protected $casts = [
        'draft.is_moderator'          => 'boolean',
        'draft.is_teaching'           => 'boolean',
        'draft.student_department_id' => 'integer',
        'draft.student_campus_id'     => 'integer',
        'draft.faculty_department_id' => 'integer',
        'draft.faculty_office_id'     => 'integer',
        'draft.faculty_campus_id'     => 'integer',
    ];

    protected $messages = [
        'photoUpload.required' => 'Please choose an image to upload.',
        'photoUpload.image'    => 'Your profile photo must be a valid image file (JPEG, PNG, GIF, or WebP).',
        'photoUpload.max'      => 'Your profile photo is too large. The maximum allowed size is 2 MB.',
    ];

    protected $rules = [
        'roleCredential'      => 'required|image|max:2048', // 2MB, adjust as needed
        'roleApplicationType' => 'required|in:student,faculty',
    ];

    public function mount(User $user)
    {
        $this->profile = $user->load('roles');
        $this->viewerIsOwner = Auth::id() === $this->profile->id;

        // choices
        $this->campuses = Campus::orderBy('name')->get(['id','name','abbrev'])
            ->map(function ($c) { $c->label = $c->abbrev ? "{$c->name} ({$c->abbrev})" : $c->name; return $c; });

        $this->departments = Department::orderBy('name')->get(['id','name','abbrev'])
            ->map(function ($d) { $d->label = $d->abbrev ? "{$d->name} ({$d->abbrev})" : $d->name; return $d; });

        $this->offices = Office::orderBy('name')->get(['id','name'])
            ->map(function ($o) { $o->label = $o->name; return $o; });

        $this->buildLookups();

        // Hydrate from MySQL only
        $u = $this->profile;

        $this->first_name   = $u->first_name;
        $this->middle_name  = $u->middle_name;
        $this->last_name    = $u->last_name;
        $this->cp_no        = $u->cp_no;

        $this->address_house    = $u->address_house ?? null;
        $this->address_brgy     = $u->address_brgy ?? null;
        $this->address_city     = $u->address_city ?? null;
        $this->address_province = $u->address_province ?? null;

        $this->link_facebook = $u->link_facebook ?? null;
        $this->link_linkedin = $u->link_linkedin ?? null;
        $this->bio           = $u->bio ?? null;

        $this->photo_path = $u->photo_path ?? null;

        $this->organization = $u->organization ?? null;

        $this->student_number        = $u->student_number ?? null;
        $this->year_level            = $u->year_level ?? null;
        $this->student_department_id = $u->student_department_id ?? null;
        $this->student_campus_id     = $u->student_campus_id ?? null;
        $this->is_moderator          = (bool) ($u->is_moderator ?? false);

        $this->is_teaching           = (bool) ($u->is_teaching ?? false);
        $this->faculty_department_id = $u->faculty_department_id ?? null;
        $this->faculty_office_id     = $u->faculty_office_id ?? null;
        $this->faculty_campus_id     = $u->faculty_campus_id ?? null;

        $this->info_status = (bool) ($u->info_status ?? false);

        $this->roleTab = $u->roles->first()->name ?? 'guest';

        $this->pendingRoleRequest = RoleUpgradeRequest::where('user_id', $this->profile->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        // Only Firebase Auth (providers), no Firestore:
        $this->hasPasswordProvider = $this->detectHasPasswordProvider($u->firebase_uid);
    }

    /** Firebase Auth provider check (cached 5 minutes). No Firestore involved. */
private function detectHasPasswordProvider(?string $firebaseUid): bool
{
    if (!$firebaseUid) return false;

    // 1) Fast local check: if you already wrote a row in user_providers, no need to call Firebase
    $dbHas = \App\Models\UserProvider::where('provider', 'password')
        ->where('provider_uid', $firebaseUid)
        ->exists();
    if ($dbHas) return true;

    // 2) Cached Firebase check
    return Cache::remember("fb:has_password:{$firebaseUid}", 300, function () use ($firebaseUid) {
        try {
            /** @var \Kreait\Firebase\Auth $auth */
            $auth = app(\Kreait\Firebase\Auth::class);
            $fbUser = $auth->getUser($firebaseUid);
            $providers = collect($fbUser->providerData ?? [])->pluck('providerId')->all();
            return in_array('password', $providers, true);
        } catch (\Throwable) {
            return false;
        }
    });
} 

    /* ---------- UI glue (keep yours) ---------- */
    public function openEdit()
    {
        $this->draft = [
            // shared
            'first_name'        => $this->first_name,
            'middle_name'       => $this->middle_name,
            'last_name'         => $this->last_name,
            'cp_no'             => $this->cp_no,
            'address_house'     => $this->address_house,
            'address_brgy'      => $this->address_brgy,
            'address_city'      => $this->address_city,
            'address_province'  => $this->address_province,
            'link_facebook'     => $this->link_facebook,
            'link_linkedin'     => $this->link_linkedin,
            'bio'               => $this->bio,

            // role-specific
            'organization'            => $this->organization,
            'student_number'          => $this->student_number,
            'year_level'              => $this->year_level,
            'student_department_id'   => $this->student_department_id,
            'student_campus_id'       => $this->student_campus_id,
            'is_moderator'            => $this->is_moderator,

            'is_teaching'             => (bool) $this->is_teaching,
            'faculty_department_id'   => $this->faculty_department_id,
            'faculty_office_id'       => $this->faculty_office_id,
            'faculty_campus_id'       => $this->faculty_campus_id,
        ];

        $this->resetValidation();
        $this->editOpen = true;
        $this->dispatch('open-modal', name:'profile-edit');
    }

    public function closeEdit()
    {
        $this->editOpen = false;
        $this->resetValidation();
        $this->reset('draft');
        $this->dispatch('close-modal', name:'profile-edit');
    }

    public function changePhoto(){ $this->dispatch('open-modal', name:'upload-avatar'); }
    public function reportUser(){ $this->dispatch('open-modal', name:'report-user'); }

    public function hydrate()
    {
        $this->buildLookups();
    }

    private function buildLookups(): void
    {
        $this->campuses = Campus::orderBy('name')
            ->get(['id','name','abbrev'])
            ->map(fn($c) => [
                'id'     => (int) $c->id,
                'name'   => $c->name,
                'abbrev' => $c->abbrev,
                'label'  => $c->abbrev ? "{$c->name} ({$c->abbrev})" : $c->name,
            ])->values()->all();

        $this->departments = Department::orderBy('name')
            ->get(['id','name','abbrev'])
            ->map(fn($d) => [
                'id'     => (int) $d->id,
                'name'   => $d->name,
                'abbrev' => $d->abbrev,
                'label'  => $d->abbrev ? "{$d->name} ({$d->abbrev})" : $d->name,
            ])->values()->all();

        $this->offices = Office::orderBy('name')
            ->get(['id','name'])
            ->map(fn($o) => [
                'id'    => (int) $o->id,
                'name'  => $o->name,
                'label' => $o->name,
            ])->values()->all();
    }

    private function makeDisplayName(): string
    {
        $first  = trim((string) $this->first_name ?? '');
        $middle = trim((string) $this->middle_name ?? '');
        $last   = trim((string) $this->last_name ?? '');

        return trim($first.' '.($middle ? $middle.' ' : '').$last) ?: ($this->profile->name ?? '');
    }

    public function getInfoCompleteProperty(): bool
    {
        return $this->info_status;
    }

    private function joinedAddress(): string
    {
        return collect([
            $this->address_house,
            $this->address_brgy,
            $this->address_city,
            $this->address_province,
        ])->filter(fn($v) => filled($v))->implode(', ');
    }

    private function currentRole(): string
    {
        return $this->profile->roles->first()->name ?? 'guest';
    }

    private function validateStudentNumberFormat(string $sn): bool
    {
        $parts = $this->parseStudentNumber($sn);
        if (!$parts) {
            $this->addError('draft.student_number', 'Incorrect Format. Must follow the student number format(25UR0001).');
            return false;
        }

        $yy = $parts['yy'];
        $cc = $parts['cc'];

        $currentYY = (int) date('y'); // e.g., 25
        if ($yy < 10 || $yy > $currentYY) {
            $this->addError('draft.student_number', "Year must be between 10 and {$currentYY}.");
            return false;
        }

        $validAbbrevs = collect($this->campuses)
            ->pluck('abbrev')
            ->filter()
            ->map(fn ($a) => strtoupper($a))
            ->all();

        if (!in_array($cc, $validAbbrevs, true)) {
            $this->addError('draft.student_number', 'Campus code (CC) must be a valid campus abbreviation.');
            return false;
        }

        return true;
    }

    private function attributesShared(): array
    {
        return [
            'cp_no' => 'mobile number',
            'link_facebook' => 'Facebook URL',
            'link_linkedin' => 'LinkedIn URL',
        ];
    }

    private function validatedShared(): array
    {
        $v = $this->validate($this->rulesShared(), [], $this->attributesShared());
        foreach (['first_name','middle_name','last_name','address_house','address_brgy','address_city','address_province','link_facebook','link_linkedin','bio'] as $k) {
            if (isset($v[$k]) && is_string($v[$k])) {
                $v[$k] = trim($v[$k]);
            }
        }
        return $v;
    }

    /** DB-based uniqueness check (no Firestore) */
    private function ensureUniqueStudentNumber(string $sn): bool
    {
        $sn = strtoupper(trim($sn));
        $exists = User::where('student_number', $sn)
            ->where('id', '!=', $this->profile->id)
            ->exists();

        if ($exists) {
            $this->addError('draft.student_number', 'This student number is already in use.');
            return false;
        }
        return true;
    }

    private function isComplete(): bool
    {
        if (!filled($this->first_name) || !filled($this->last_name)) return false;
        if (!filled($this->cp_no)) return false;
        if (!filled($this->address_city) || !filled($this->address_province)) return false;

        $role = $this->currentRole();

        if ($role === 'admin') return true;

        if ($role === 'guest') {
            return filled($this->organization);
        }

        if ($role === 'student') {
            return filled($this->student_number)
                && filled($this->year_level)
                && filled($this->student_department_id)
                && filled($this->student_campus_id);
        }

        if ($role === 'faculty') {
            if ($this->is_teaching === null) return false;
            if (!filled($this->faculty_campus_id)) return false;

            if ($this->is_teaching) {
                return filled($this->faculty_department_id);
            }
            return filled($this->faculty_office_id);
        }

        return filled($this->organization);
    }

    public function savePhotoUpload(): void
    {
        Log::info('[ProfilePage] savePhotoUpload called', [
            'user_id'       => $this->profile->id ?? null,
            'viewerIsOwner' => $this->viewerIsOwner,
        ]);

        if (! $this->viewerIsOwner) {
            Log::warning('[ProfilePage] Blocked avatar change: not owner');

            $this->addError('photoUpload', 'You are not allowed to change this photo.');
            return;
        }

        if (! $this->photoUpload) {
            Log::warning('[ProfilePage] savePhotoUpload called but photoUpload is null');
            $this->addError('photoUpload', 'No file selected.');
            return;
        }

        // Validate the uploaded image
        try {
            $this->validate([
                'photoUpload' => ['required', 'image', 'max:2048'], // 2048 KB = 2 MB
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProfilePage] Avatar validation failed', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $user = $this->profile->fresh();

        // Ensure the user has a slug (safety net)
        if (! $user->slug) {
            $user->slug = Str::slug($this->makeDisplayName() ?: 'user-'.$user->id);
            $user->save();
            Log::info('[ProfilePage] Generated slug for user', [
                'user_id' => $user->id,
                'slug'    => $user->slug,
            ]);
        }

        // Decide extension (keep original if possible)
        $extension = $this->photoUpload->getClientOriginalExtension() ?: 'jpg';

        $dir      = "profile/{$user->slug}";
        $filename = "avatar.{$extension}";
        $path     = "{$dir}/{$filename}";

        Log::info('[ProfilePage] Preparing to upload avatar to R2', [
            'path'      => $path,
            'disk'      => 'r2',
            'hasOld'    => (bool) $user->photo_path,
            'old_path'  => $user->photo_path,
        ]);

        // Optional: delete existing stored photo
        if ($user->photo_path) {
            try {
                Storage::disk('r2')->delete($user->photo_path);
                Log::info('[ProfilePage] Deleted old avatar from R2', [
                    'old_path' => $user->photo_path,
                ]);
            } catch (\Throwable $e) {
                Log::error('[ProfilePage] Failed to delete old avatar from R2', [
                    'old_path' => $user->photo_path,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        // Upload to Cloudflare R2
        try {
            Storage::disk('r2')->putFileAs(
                $dir,              // folder inside bucket
                $this->photoUpload,
                $filename
            );
            Log::info('[ProfilePage] Successfully uploaded avatar to R2', [
                'path' => $path,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProfilePage] Failed to upload avatar to R2', [
        'path'  => $path,
        'error' => $e->getMessage(),
            ]);
            $this->addError(
                'photoUpload',
                'Upload failed. Please check your connection or try a smaller image.'
            );
            return;
        }

        // Persist new path
        $user->photo_path = $path;
        $user->save();

        // Sync Livewire state
        $this->profile     = $user->loadMissing('roles');
        $this->photo_path  = $user->photo_path;
        $this->photoUpload = null;

        Log::info('[ProfilePage] Avatar updated successfully', [
            'user_id'    => $user->id,
            'photo_path' => $user->photo_path,
        ]);

        $this->dispatch('toast', type:'success', message:'Profile photo updated.');
        $this->dispatch('close-modal', name:'upload-avatar');
    }

    public function saveFaceRecognitionUpload(): void
    {
        Log::info('[ProfilePage] saveFaceRecognitionUpload called', [
            'user_id'       => $this->profile->id ?? null,
            'viewerIsOwner' => $this->viewerIsOwner,
        ]);

        if (! $this->viewerIsOwner) {
            Log::warning('[ProfilePage] Blocked face recognition upload: not owner');

            $this->addError('faceRecognitionUpload', 'You are not allowed to change this image.');
            return;
        }

        if (! $this->faceRecognitionUpload) {
            Log::warning('[ProfilePage] saveFaceRecognitionUpload called but faceRecognitionUpload is null');
            $this->addError('faceRecognitionUpload', 'No file selected.');
            return;
        }

        // Basic validation
        try {
            $this->validate([
                'faceRecognitionUpload' => ['required', 'image', 'max:4096'], // 4 MB
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProfilePage] Face recognition image validation failed', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $user = $this->profile->fresh();

        // Gate: must be a student with verified info
        if ($user->role !== 'student' && ! $user->hasRole('student')) {
            Log::warning('[ProfilePage] Blocked face recognition upload: user is not a student', [
                'user_id' => $user->id,
            ]);
            $this->addError('faceRecognitionUpload', 'Only student accounts can submit a facial-recognition image.');
            return;
        }

        if (empty($user->student_number)) {
            Log::warning('[ProfilePage] Blocked face recognition upload: missing student_number', [
                'user_id' => $user->id,
            ]);
            $this->addError('faceRecognitionUpload', 'You must have a valid student number before uploading this image.');
            return;
        }

        if ((int)($user->info_status ?? 0) !== 1) {
            Log::warning('[ProfilePage] Blocked face recognition upload: info_status not approved', [
                'user_id'     => $user->id,
                'info_status' => $user->info_status,
            ]);
            $this->addError('faceRecognitionUpload', 'Your information must be approved before uploading this image.');
            return;
        }

        // Decide extension
        $extension = $this->faceRecognitionUpload->getClientOriginalExtension() ?: 'jpg';

        $dir      = "image-recognition/{$user->student_number}";
        $filename = "image.{$extension}";
        $path     = "{$dir}/{$filename}";

        Log::info('[ProfilePage] Preparing to upload face recognition image to R2', [
            'user_id'    => $user->id,
            'path'       => $path,
            'disk'       => 'r2',
            'hasOld'     => (bool) $user->face_recognition_path,
            'old_path'   => $user->face_recognition_path,
        ]);

        // Optional: delete existing stored image
        if ($user->face_recognition_path) {
            try {
                Storage::disk('r2')->delete($user->face_recognition_path);
                Log::info('[ProfilePage] Deleted old face recognition image from R2', [
                    'old_path' => $user->face_recognition_path,
                ]);
            } catch (\Throwable $e) {
                Log::error('[ProfilePage] Failed to delete old face recognition image from R2', [
                    'old_path' => $user->face_recognition_path,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        // Upload to Cloudflare R2
        try {
            Storage::disk('r2')->putFileAs(
                $dir,
                $this->faceRecognitionUpload,
                $filename
            );

            Log::info('[ProfilePage] Successfully uploaded face recognition image to R2', [
                'user_id' => $user->id,
                'path'    => $path,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProfilePage] Failed to upload face recognition image to R2', [
                'user_id' => $user->id,
                'path'    => $path,
                'error'   => $e->getMessage(),
            ]);

            $this->addError(
                'faceRecognitionUpload',
                'Upload failed. Please check your connection or try a smaller image.'
            );
            return;
        }

        // Persist new path on the user
        $user->face_recognition_path = $path;
        $user->save();

        // Sync Livewire state
        $this->profile                = $user->loadMissing('roles');
        $this->faceRecognitionUpload  = null;

        Log::info('[ProfilePage] Face recognition image updated successfully', [
            'user_id'    => $user->id,
            'path'       => $user->face_recognition_path,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Facial-recognition image updated.');
        $this->dispatch('close-modal', name: 'upload-face-image');
    }

    public function openRoleApplication(string $role): void
    {
        if ($this->pendingRoleRequest) {
            $this->dispatch('toast', type: 'info', message: 'You already have a pending role upgrade request.');
            return;
        }

        $role = in_array($role, ['student', 'faculty']) ? $role : 'student';

        $this->resetErrorBag();
        $this->resetValidation();

        $this->roleApplicationType = $role;
        $this->roleCredential = null;

        $this->dispatch('open-modal', name: 'role-application');
    }

    public function submitRoleApplication(): void
    {
        $this->validate();

        $user = $this->profile;

        // Snapshot of their name at time of request
        $name = $this->profile->full_name
            ?? $user->name
            ?? $user->email;

        // File name format: role-<type>-user-<id>-<timestamp>.<ext>
        $ext      = $this->roleCredential->getClientOriginalExtension();
        $fileName = sprintf(
            'role-%s-user-%d-%s.%s',
            $this->roleApplicationType,
            $user->id,
            now()->format('YmdHis'),
            $ext
        );

        // Adjust 'r2' to your actual R2 disk name
        $path = $this->roleCredential->storeAs(
            "role-credentials/{$user->id}",
            $fileName,
            ['disk' => 'r2']
        );

        RoleUpgradeRequest::create([
            'user_id'    => $user->id,
            'name'       => $name,
            'type'       => $this->roleApplicationType,
            'creds_path' => $path,
            'status'     => 'pending',
        ]);

        $this->dispatch('close-modal', name: 'role-application');

        $this->dispatch('toast', type: 'success', message: 'Your role upgrade request has been submitted and is now pending review.');

        $this->roleCredential = null;       

        $this->pendingRoleRequest = RoleUpgradeRequest::where('user_id', $this->profile->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    public function saveAll(): void
    {
        $this->draft['is_teaching'] = (bool) ($this->draft['is_teaching'] ?? false);
        $role = $this->currentRole();

        // 1) Validate shared (draft.*)
        $shared = $this->validate($this->rulesShared())['draft'];

        // 2) Role-specific
        $rolePayload = [];
        if ($role === 'guest') {
            $data = $this->validate($this->rulesGuest())['draft'];
            $rolePayload = ['organization' => $data['organization']];
        } elseif ($role === 'student') {
            $data = $this->validate($this->rulesStudent())['draft'];
            $this->withStudentNumberValidator($data['student_number']);
            if (!$this->ensureUniqueStudentNumber($data['student_number'])) {
                throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
            }
            $rolePayload = [
                'student_number' => strtoupper(trim($data['student_number'])),
                'year_level'     => $data['year_level'],
                'student_department_id'  => (int) $data['student_department_id'],
                'student_campus_id'      => (int) $data['student_campus_id'],
                'is_moderator'   => (bool) ($data['is_moderator'] ?? false),
            ];
        } elseif ($role === 'faculty') {
            $data = $this->validate($this->rulesFaculty())['draft'];
            $this->withFacultyConditionalValidator($data);
            $rolePayload = [
                'is_teaching'           => (bool) $data['is_teaching'],
                'faculty_department_id' => $data['faculty_department_id'],
                'faculty_office_id'     => $data['faculty_office_id'],
                'faculty_campus_id'     => $data['faculty_campus_id'],
            ];
        }

        // 3) Update MySQL (single source of truth)
        $this->first_name        = $shared['first_name'];
        $this->middle_name       = $shared['middle_name'] ?? null;
        $this->last_name         = $shared['last_name'];
        $this->cp_no             = $shared['cp_no'];
        $this->address_house     = $shared['address_house'] ?? null;
        $this->address_brgy      = $shared['address_brgy'] ?? null;
        $this->address_city      = $shared['address_city'];
        $this->address_province  = $shared['address_province'];
        $this->link_facebook     = $shared['link_facebook'] ?? null;
        $this->link_linkedin     = $shared['link_linkedin'] ?? null;
        $this->bio               = $shared['bio'] ?? null;

        if ($role === 'guest') {
            $this->organization = $rolePayload['organization'] ?? null;
        } elseif ($role === 'student') {
            $this->student_number        = $rolePayload['student_number'];
            $this->year_level            = $rolePayload['year_level'];
            $this->student_department_id = $rolePayload['student_department_id'];
            $this->student_campus_id     = $rolePayload['student_campus_id'];
            $this->is_moderator          = $rolePayload['is_moderator'];
        } elseif ($role === 'faculty') {
            $this->is_teaching           = $rolePayload['is_teaching'];
            $this->faculty_department_id = $rolePayload['faculty_department_id'];
            $this->faculty_office_id     = $rolePayload['faculty_office_id'];
            $this->faculty_campus_id     = $rolePayload['faculty_campus_id'];
        }

        // Persist to DB
        $u = $this->profile->fresh();

        $u->first_name  = $this->first_name;
        $u->middle_name = $this->middle_name;
        $u->last_name   = $this->last_name;
        $u->name        = $this->makeDisplayName();     // display name
        $u->cp_no       = $this->cp_no;

        $u->address_house    = $this->address_house;
        $u->address_brgy     = $this->address_brgy;
        $u->address_city     = $this->address_city;
        $u->address_province = $this->address_province;
        $u->address          = $this->joinedAddress();

        $u->link_facebook = $this->link_facebook;
        $u->link_linkedin = $this->link_linkedin;
        $u->bio           = $this->bio;

        // role-specific
        $u->organization = $this->organization;

        $u->student_number        = $this->student_number;
        $u->year_level            = $this->year_level;
        $u->student_department_id = $this->student_department_id;
        $u->student_campus_id     = $this->student_campus_id;
        $u->is_moderator          = $this->is_moderator;

        $u->is_teaching           = $this->is_teaching;
        $u->faculty_department_id = $this->faculty_department_id;
        $u->faculty_office_id     = $this->faculty_office_id;
        $u->faculty_campus_id     = $this->faculty_campus_id;

        // completeness flag stored directly
        $u->info_status = $this->isComplete();

        $u->save();

        $this->profile = $u->loadMissing('roles');
        $this->info_status = (bool) $u->info_status;

        $this->dispatch('toast', type:'success', message:'Profile updated.');
        $this->closeEdit();
    }

    private function withStudentNumberValidator(string $sn): void
    {
        $sn = strtoupper(trim($sn));
        $parts = $this->parseStudentNumber($sn);
        if (!$parts) {
            $this->addError('draft.student_number', 'Incorrect format. Use YYCCNNNN (e.g., 25UR0001).');
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }

        $yy = (int) $parts['yy'];
        $cc = $parts['cc'];

        $currentYY = (int) date('y'); // e.g., 25
        if ($yy < 10 || $yy > $currentYY) {
            $this->addError('draft.student_number', "Year must be between 10 and {$currentYY}.");
        }

        $validAbbrevs = collect($this->campuses)
            ->pluck('abbrev')->filter()->map(fn ($a) => strtoupper($a))->all();

        if (!in_array($cc, $validAbbrevs, true)) {
            $this->addError('draft.student_number', 'Campus code must match a valid campus abbreviation.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    private function withFacultyConditionalValidator(array $data): void
    {
        $isTeaching = (bool) ($data['is_teaching'] ?? false);
        if ($isTeaching && empty($data['faculty_department_id'])) {
            $this->addError('draft.faculty_department_id', 'Department is required for teaching staff.');
        }
        if (!$isTeaching && empty($data['faculty_office_id'])) {
            $this->addError('draft.faculty_office_id', 'Office is required for non-teaching staff.');
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    // ── Validations ───────────────────────────────────────────────────────────
    private function rulesShared(): array
    {
        return [
            'draft.first_name'        => ['required','string','max:255'],
            'draft.middle_name'       => ['nullable','string','max:255'],
            'draft.last_name'         => ['required','string','max:255'],
            'draft.cp_no'             => ['required','string','regex:/^(09\d{9}|\+639\d{9})$/'],
            'draft.address_house'     => ['nullable','string','max:255'],
            'draft.address_brgy'      => ['nullable','string','max:255'],
            'draft.address_city'      => ['required','string','max:255'],
            'draft.address_province'  => ['required','string','max:255'],
            'draft.link_facebook'     => ['nullable','url'],
            'draft.link_linkedin'     => ['nullable','url'],
            'draft.bio'               => ['nullable','string','max:150'],
        ];
    }

    private function rulesGuest(): array
    {
        return ['draft.organization'=>['required','string','max:255']];
    }

    private function rulesStudent(): array
    {
        // DB uniqueness (ignore current user)
        $ignoreId = $this->profile?->id ?? 'NULL';
        return [
            'draft.student_number'        => ['required','string','max:50',"unique:users,student_number,{$ignoreId}"],
            'draft.year_level'            => ['required','in:1st,2nd,3rd,4th,5th+'],
            'draft.student_department_id' => ['required','integer','exists:departments,id'],
            'draft.student_campus_id'     => ['required','integer','exists:campuses,id'],
            'draft.is_moderator'          => ['boolean'],
        ];
    }

    private function rulesFaculty(): array
    {
        return [
            'draft.is_teaching'           => ['required','boolean'],
            'draft.faculty_campus_id'     => ['required','integer','exists:campuses,id'],
            'draft.faculty_department_id' => ['nullable','integer','exists:departments,id'],
            'draft.faculty_office_id'     => ['nullable','integer','exists:offices,id'],
        ];
    }

    private function parseStudentNumber(string $sn): ?array
    {
        $sn = strtoupper(trim($sn));
        if (!preg_match('/^(\d{2})([A-Z]{2,})(\d{4})$/', $sn, $m)) {
            return null;
        }
        return ['yy' => (int)$m[1], 'cc' => $m[2], 'nn' => $m[3]];
    }

    public function saveGuest(): void
    {
        $this->validate($this->rulesGuest());

        $this->organization = trim((string) $this->organization) ?: null;

        $u = $this->profile->fresh();
        $u->organization = $this->organization;
        $u->info_status  = $this->isComplete();
        $u->save();

        $this->profile = $u->loadMissing('roles');

        $this->dispatch('toast', type:'success', message:'Guest info saved.');
    }

    public function render()
    {
        return view('profile.page');
    }


}
