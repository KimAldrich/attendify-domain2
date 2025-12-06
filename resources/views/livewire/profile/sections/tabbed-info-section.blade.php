{{-- resources/views/livewire/profile/sections/tabbed-info-section.blade.php --}}
@php
  $role = $roleTab ?? ($profile->roles->first()->name ?? 'guest');

  $roleLabels = [
    'student' => 'Student Information',
    'guest'   => 'Guest Information',
    'faculty' => 'Faculty Information',
    'admin'   => 'Admin Information',
  ];
  $tabLabel = $roleLabels[$role] ?? 'User Information';

  $isAdmin = $profile->roles->contains(fn($r) => $r->name === 'admin');

  // Collections may be plain arrays—normalize and find by id
  $findById = function ($list, $id) {
      return collect($list)->first(fn($x) => (int)($x['id'] ?? $x->id ?? 0) === (int)($id ?? 0));
  };

  $studentDept   = $findById($departments, $student_department_id);
  $studentCamp   = $findById($campuses,    $student_campus_id);
  $facultyDept   = $findById($departments, $faculty_department_id);
  $facultyCamp   = $findById($campuses,    $faculty_campus_id);
  $facultyOffice = $findById($offices,     $faculty_office_id);

  $showInfoTab  = !$isAdmin;                 // hide role info for admins
  $showResetTab = $viewerIsOwner ?? false;   // owner only
  $showFaceTab   = ($role === 'student') && ($viewerIsOwner ?? false);

  $defaultTab   = $showInfoTab ? 'info' : 'reset';

  // Small helpers for nicer display labels
  $label = fn($record) => $record
      ? (($record['label'] ?? null) ?: (($record['name'] ?? '') . (isset($record['abbrev']) && $record['abbrev'] ? ' ('.$record['abbrev'].')' : '')))
      : null;
  
  /** @var \App\Models\RoleUpgradeRequest|null $pendingRoleRequest */
  $pendingUpgrade = $pendingRoleRequest ?? null;

  $infoStatus          = $info_status ?? null;
  $hasStudentNumber    = !empty($student_number);
  $canManageFaceImage  = $showFaceTab && $hasStudentNumber && ((int)($infoStatus ?? 0) === 1);

  $faceImagePath = $profile->face_recognition_path ?? null;
  $faceImageUrl  = $faceImagePath ? Storage::disk('r2')->url($faceImagePath) : null;
@endphp

<div class="bg-white border h-full flex flex-col rounded-b-xl" 
     x-data="{ tab: '{{ $defaultTab }}' }">

  {{-- Tabs header --}}
  <div class="p-3 border-b flex gap-2 overflow-x-auto rounded-t-none">
    @if($showInfoTab)
      <button type="button"
              :class="tab==='info' 
                ? 'px-3 h-9 border text-sm bg-blue-50 border-blue-600 text-blue-700 font-medium' 
                : 'px-3 h-9 border text-sm bg-white border-gray-200 text-gray-700 hover:bg-gray-50'"
              x-on:click="tab='info'">
        {{ $tabLabel }}
      </button>
    @endif

    @if($showFaceTab)
      <button type="button"
              :class="tab==='face' 
                ? 'px-3 h-9 border text-sm bg-blue-50 border-blue-600 text-blue-700 font-medium' 
                : 'px-3 h-9 border text-sm bg-white border-gray-200 text-gray-700 hover:bg-gray-50'"
              x-on:click="tab='face'">
        Facial-Recognition Image
      </button>
    @endif

    @if($showResetTab)
      <button type="button"
              :class="tab==='reset' 
                ? 'px-3 h-9 border text-sm bg-blue-50 border-blue-600 text-blue-700 font-medium' 
                : 'px-3 h-9 border text-sm bg-white border-gray-200 text-gray-700 hover:bg-gray-50'"
              x-on:click="tab='reset'">
        Password Reset
      </button>
    @endif
  </div>

  <div class="p-4 sm:p-6 text-sm">
    {{-- ========= Tab: Role-based Information ========= --}}
    @if($showInfoTab)
      <div x-show="tab==='info'" class="space-y-8">
        @if($role === 'guest')
          <section>
            @php
              $org = isset($organization) ? trim((string)$organization) : null;
            @endphp

            <div class="mb-4">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Organization</h4>
              <p class="mt-1 text-[15px] text-slate-900">
                {{ ($org !== null && $org !== '') ? $org : '—' }}
              </p>
            </div>

            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Role Upgrade</h3>

            @if($pendingUpgrade)
              {{-- Pending request info (GitHub-like banner) --}}
              <div class="mt-2 mb-3 rounded-md border border-amber-300 bg-amber-50">
                <div class="px-3 py-2.5 flex items-center gap-2">
                  <i class="bi bi-hourglass-split text-amber-500 text-base"></i>
                  <div class="text-xs text-amber-900 leading-normal">
                    <p class="font-medium">
                      Your role upgrade request is currently being reviewed.
                    </p>
                    <p class="mt-0.5 text-[11px] text-amber-800">
                      Requested upgrade:
                      <span class="font-semibold text-amber-900">
                        {{ ucfirst($pendingUpgrade->type) }}
                      </span>
                      • Submitted {{ optional($pendingUpgrade->created_at)->format('M j, Y') ?? '—' }}.
                    </p>
                  </div>
                </div>
              </div>

              <p class="mt-1.5 text-[13px] leading-5 text-slate-600">
                You’ll receive an update once an administrator has verified your credentials
                and approved or declined your request.
              </p>
            @else
              {{-- No pending request → normal instructions + buttons --}}
              <p class="mt-1.5 text-[13px] leading-5 text-slate-600">
                If you belong to the university, you can request a role evaluation to upgrade your account
                from <span class="font-medium">Guest</span> to <span class="font-medium">Student</span> or
                <span class="font-medium">Faculty</span>. This helps us unlock the correct features for you.
              </p>

              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  wire:click="openRoleApplication('student')"
                  class="inline-flex items-center h-9 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                >
                  <i class="bi bi-mortarboard me-1.5"></i> I am a student
                </button>

                <button
                  type="button"
                  wire:click="openRoleApplication('faculty')"
                  class="inline-flex items-center h-9 px-3 rounded-md border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100"
                >
                  <i class="bi bi-person-badge me-1.5"></i> I am a faculty
                </button>
              </div>
            @endif
          </section>
        @endif

        @if($role === 'student')
          <section>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Enrollment Information</h3>
            <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-slate-500">Student No.</dt>
                <dd class="mt-0.5 text-[15px] text-slate-900">{{ $student_number ?: '—' }}</dd>
              </div>
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-slate-500">Year Level</dt>
                <dd class="mt-0.5 text-[15px] text-slate-900">{{ $year_level ?: '—' }}</dd>
              </div>
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-slate-500">Department</dt>
                <dd class="mt-0.5 text-[15px] text-slate-900">{{ $label($studentDept) ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-slate-500">University Campus</dt>
                <dd class="mt-0.5 text-[15px] text-slate-900">{{ $label($studentCamp) ?? '—' }}</dd>
              </div>
            </dl>
          </section>
        @endif

        @if($role === 'faculty')
          <section>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Assignment</h3>
            <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-slate-500">Teaching Staff</dt>
                <dd class="mt-0.5">
                  <span @class([
                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
                    ($is_teaching ?? false) ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-700'
                  ])>{{ ($is_teaching ?? false) ? 'Yes' : 'No' }}</span>
                </dd>
              </div>
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-slate-500">Campus</dt>
                <dd class="mt-0.5 text-[15px] text-slate-900">{{ $label($facultyCamp) ?? '—' }}</dd>
              </div>

              @if($is_teaching)
                <div>
                  <dt class="text-[11px] uppercase tracking-wide text-slate-500">Department</dt>
                  <dd class="mt-0.5 text-[15px] text-slate-900">{{ $label($facultyDept) ?? '—' }}</dd>
                </div>
              @else
                <div>
                  <dt class="text-[11px] uppercase tracking-wide text-slate-500">Office</dt>
                  <dd class="mt-0.5 text-[15px] text-slate-900">{{ $facultyOffice['label'] ?? ($facultyOffice['name'] ?? '—') }}</dd>
                </div>
              @endif
            </dl>
          </section>
        @endif
      </div>
    @endif

    @if($showFaceTab)
      <div x-show="tab==='face'">
        <section class="max-w-xl space-y-4">
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">
              Facial-Recognition Image
            </h3>
            <p class="mt-2 text-[13px] leading-5 text-slate-600">
              This photo is used for automated attendance and facial recognition. Use a clear,
              front-facing, and most recent image with good lighting and no heavy filters or obstructions.
            </p>
          </div>

          @if(! $canManageFaceImage)
            {{-- Locked state: requirements not met --}}
            <div class="rounded-md border border-amber-300 bg-amber-50 px-3 py-3 flex gap-3">
              <div class="mt-0.5">
                <i class="bi bi-exclamation-triangle-fill text-amber-500"></i>
              </div>
              <div class="text-[13px] text-amber-900 space-y-1">
                <p class="font-medium">
                  You can’t upload a facial-recognition image yet.
                </p>
                <ul class="list-disc list-inside text-amber-900/90 text-[12px] space-y-0.5">
                  <li>Your student information must be complete and approved.</li>
                  <li>You need a valid <span class="font-semibold">student number</span> on file.</li>
                  <li>Your profile information status must be <span class="font-semibold">verified</span>.</li>
                </ul>
                <p class="pt-1 text-[11px] text-amber-800">
                  Once these requirements are met, you’ll be able to upload your image here.
                </p>
              </div>
            </div>
          @else
            {{-- Ready state: show current image + upload button --}}
            <div class="rounded-md border border-slate-200 bg-slate-50/60 p-3 space-y-3">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                  <p class="text-[13px] font-medium text-slate-900">
                    Current facial-recognition image
                  </p>
                  <p class="text-[11px] text-slate-500">
                    This image is only used internally for face matching. It won’t be shown on your public profile.
                  </p>
                </div>

                <div class="flex sm:justify-end">
                  <button
                    type="button"
                    class="inline-flex items-center h-9 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700 text-xs font-medium"
                    x-on:click="$dispatch('open-modal', { name: 'upload-face-image' })"
                  >
                    <i class="bi bi-camera me-1.5"></i>
                    <span>{{ $faceImageUrl ? 'Update image' : 'Upload image' }}</span>
                  </button>
                </div>
              </div>

              <div class="border-t border-slate-200 pt-3">
                @if($faceImageUrl)
                  <div class="flex flex-col gap-2">
                    <div class="w-full max-w-sm rounded-lg border border-slate-200 bg-slate-100 overflow-hidden">
                      <img
                        src="{{ $faceImageUrl }}"
                        alt="Facial-recognition image"
                        class="w-full h-56 object-cover"
                        loading="lazy"
                      />
                    </div>
                    <p class="text-[11px] text-slate-500">
                      If your appearance has changed significantly, upload a new photo so recognition remains accurate.
                    </p>
                  </div>
                @else
                  <div class="w-full max-w-sm rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 flex flex-col items-center justify-center text-center gap-2">
                    <i class="bi bi-person-bounding-box text-2xl text-slate-400"></i>
                    <p class="text-[13px] font-medium text-slate-700">
                      No facial-recognition image on file yet
                    </p>
                    <p class="text-[12px] text-slate-500">
                      Upload a clear selfie to enable face-based attendance in supported events.
                    </p>
                  </div>
                @endif
              </div>
            </div>
          @endif
        </section>
      </div>
    @endif

    @if($showResetTab)
      <div x-show="tab==='reset'">
        <section class="max-w-lg">
  <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">
    {{ ($hasPasswordProvider ?? false) ? 'Password Reset' : 'Add a Password' }}
  </h3>

  @if(!($hasPasswordProvider ?? false))
    <p class="mt-2 text-[13px] leading-5 text-slate-600">
      Your account currently uses social sign-in. You can add a password so you can also sign in with email & password.
    </p>

    <div class="mt-3 space-y-3">
      <input id="pw"  type="password" class="form-input w-full" placeholder="New password">
      <input id="pw2" type="password" class="form-input w-full" placeholder="Confirm new password">
      <button id="btn-add-password"
              class="inline-flex items-center h-9 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700">
        <i class="bi bi-key me-1.5"></i> Add Password
      </button>
    </div>

    {{-- Client-side linkWithCredential (B2) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.13.1/firebase-app.js";
    import { getAuth, EmailAuthProvider, linkWithCredential, reauthenticateWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.13.1/firebase-auth.js";

    const cfg = {
      apiKey:     "{{ config('services.firebase.web.api_key') }}",
      authDomain: "{{ config('services.firebase.web.auth_domain') }}",
      projectId:  "{{ config('services.firebase.web.project_id') }}",
    };
    const app  = initializeApp(cfg);
    const auth = getAuth(app);
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.getElementById('btn-add-password')?.addEventListener('click', async () => {
      const pw  = document.getElementById('pw').value.trim();
      const pw2 = document.getElementById('pw2').value.trim();
      if (!pw || pw.length < 8) { alert('Password must be at least 8 characters.'); return; }
      if (pw !== pw2) { alert('Passwords do not match.'); return; }

      try {
        if (!auth.currentUser) {
          // If client not signed into Firebase yet, re-auth with Google (or your primary provider)
          await reauthenticateWithPopup(getAuth(), new GoogleAuthProvider());
        }
        const email = (auth.currentUser?.email) || "{{ $profile->email }}";
        if (!email) { alert('No email found for this account.'); return; }

        const cred = EmailAuthProvider.credential(email, pw);
        await linkWithCredential(getAuth().currentUser, cred);

        // Ping server to rotate remember token / set password_changed_at
        await fetch("{{ route('settings.security.password.linked') }}", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
          body: JSON.stringify({ linked: true })
        });

        alert('Password added. You can now reset it and sign in with email/password too.');
        window.location.reload();
      } catch (e) {
        console.error(e);
        alert('Could not add password. You may need to re-authenticate and try again.');
      }
    });
    </script>

  @else
    {{-- Already has password provider → show your existing reset form --}}
    <p class="mt-2 text-[13px] leading-5 text-slate-600">
      We’ll email a password reset link to your account address.
    </p>

    <form method="POST" action="{{ route('password.email.inapp') }}" class="mt-3 space-y-3">
      @csrf
      <label class="block text-[11px] uppercase tracking-wide text-slate-500">Email</label>
      <input type="email" class="form-input w-full bg-gray-50" value="{{ $profile->email }}" disabled>
      <input type="hidden" name="email" value="{{ $profile->email }}">
      <button type="submit"
              class="inline-flex items-center h-9 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700">
        <i class="bi bi-envelope-paper me-1.5"></i>
        Send Password Reset Link
      </button>
    </form>
  @endif
</section>
      </div>
    @endif
  </div>
</div>

{{-- ROLE UPGRADE MODAL --}}
<div wire:teleport="#modal-root">
  <div x-data="{ open:false }" x-cloak
       x-on:open-modal.window="
          if ($event.detail.name === 'role-application') {
            open = true;
          }
       "
       x-on:close-modal.window="
          if ($event.detail.name === 'role-application') {
            open = false;
          }
       "
       x-trap.noscroll="open"
       class="relative z-[2001]">

    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/40"></div>

    <div x-show="open" x-transition
         class="fixed inset-0 flex items-center justify-center p-3 sm:p-6">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border overflow-hidden">

        {{-- Sticky header --}}
        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b px-4 sm:px-6 py-3 flex items-center justify-between">
          <div class="min-w-0">
            <div class="text-[15px] font-semibold truncate">
              Request role upgrade
            </div>
            <div class="mt-0.5 text-xs text-gray-500">
              Submit a credential so an admin can verify your role.
            </div>
          </div>
          <button type="button"
                  class="h-8 w-8 inline-flex items-center justify-center rounded-md hover:bg-gray-100"
                  x-on:click="open = false"
                  aria-label="Close">
            <i class="bi bi-x-lg text-sm"></i>
          </button>
        </div>

        <form wire:submit.prevent="submitRoleApplication">
          {{-- Body --}}
          <div class="max-h-[70vh] overflow-y-auto p-4 sm:p-6 space-y-4 text-sm">
            <div class="text-xs text-gray-600">
              <p class="mb-1">
                You are requesting an upgrade to
                <span class="font-medium text-gray-900">
                  @if($roleApplicationType === 'student')
                    Student
                  @else
                    Faculty
                  @endif
                </span>.
              </p>
              <p>
                Please upload a clear photo of your valid school ID or official document
                that shows your affiliation with the university.
              </p>
            </div>

            <div class="space-y-2">
              <label class="block text-xs font-medium text-gray-700">
                Credential photo <span class="text-red-600">*</span>
              </label>

              <input type="file"
                     wire:model="roleCredential"
                     accept="image/*"
                     class="block w-full text-xs text-gray-700
                            file:mr-3 file:py-1.5 file:px-3
                            file:rounded-md file:border file:border-gray-300
                            file:bg-gray-50 file:text-xs file:font-medium
                            hover:file:bg-gray-100" />

              @error('roleCredential')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
              @enderror

              @if ($roleCredential)
                <div class="mt-2">
                  <p class="text-[11px] text-gray-500 mb-1">Preview</p>
                  <img src="{{ $roleCredential->temporaryUrl() }}"
                       alt="Credential preview"
                       class="border rounded-md max-h-40 object-contain">
                </div>
              @endif
            </div>

            <p class="text-[11px] text-gray-500">
              Your request will be marked as <span class="font-medium text-gray-800">pending</span>
              until an administrator reviews and approves it.
            </p>
          </div>

          {{-- Footer --}}
          <div class="sticky bottom-0 bg-white/95 backdrop-blur border-t px-4 sm:px-6 py-3 flex items-center justify-end gap-2">
            <button type="button"
                    class="h-8 px-3 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-medium"
                    x-on:click="open = false">
              Cancel
            </button>

            <button type="submit"
                    class="h-8 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60 text-xs font-medium"
                    wire:loading.attr="disabled"
                    wire:target="submitRoleApplication,roleCredential">
              <svg wire:loading wire:target="submitRoleApplication,roleCredential"
                   class="w-3.5 h-3.5 animate-spin inline mr-1"
                   viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="3"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
              </svg>
              Submit request
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

{{-- FACE RECOGNITION UPLOAD MODAL --}}
<div wire:teleport="#modal-root">
  <div
    x-data="{ open:false }"
    x-cloak
    x-on:open-modal.window="
      if ($event.detail.name === 'upload-face-image') {
        open = true;
      }
    "
    x-on:close-modal.window="
      if ($event.detail.name === 'upload-face-image') {
        open = false;
      }
    "
    x-trap.noscroll="open"
    class="relative z-[2002]"
  >
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/50"></div>

    <div
      x-show="open"
      x-transition
      class="fixed inset-0 flex items-center justify-center p-3 sm:p-6"
    >
      <div
        class="bg-white w-full max-w-md rounded-2xl shadow-2xl border overflow-hidden"
        x-on:click.stop
      >
        {{-- Header --}}
        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b px-4 sm:px-6 py-3 flex items-center justify-between">
          <div class="min-w-0">
            <div class="text-[15px] font-semibold truncate">
              Upload facial-recognition image
            </div>
            <div class="mt-0.5 text-xs text-gray-500">
              Use a formal, clear, front-facing, and most recent selfie with good lighting.
            </div>
          </div>
          <button
            type="button"
            class="h-8 w-8 inline-flex items-center justify-center rounded-md hover:bg-gray-100"
            x-on:click="open = false"
            aria-label="Close"
          >
            <i class="bi bi-x-lg text-sm"></i>
          </button>
        </div>

        {{-- Body --}}
        <form wire:submit.prevent="saveFaceRecognitionUpload">
          <div class="max-h-[70vh] overflow-y-auto p-4 sm:p-6 space-y-4 text-sm">
            <div class="space-y-2">
              <label class="block text-xs font-medium text-gray-700">
                Select image file <span class="text-red-600">*</span>
              </label>

              <input
                type="file"
                wire:model="faceRecognitionUpload"
                accept="image/*"
                class="block w-full text-xs text-gray-700
                       file:mr-3 file:py-1.5 file:px-3
                       file:rounded-md file:border file:border-gray-300
                       file:bg-gray-50 file:text-xs file:font-medium
                       hover:file:bg-gray-100"
              />

              @error('faceRecognitionUpload')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
              @enderror

              @if ($faceRecognitionUpload)
                <div class="mt-3 space-y-2">
                  <p class="text-[11px] text-gray-500">Preview</p>
                  <div class="w-full max-w-sm rounded-lg border border-slate-200 bg-slate-100 overflow-hidden">
                    <img
                      src="{{ $faceRecognitionUpload->temporaryUrl() }}"
                      alt="Facial recognition preview"
                      class="w-full h-56 object-cover"
                    />
                  </div>
                  <p class="text-[11px] text-gray-500">
                    Make sure your face is centered, with no heavy filters or sunglasses.
                  </p>
                </div>
              @endif
            </div>

            <p class="text-[11px] text-gray-500">
              Supported formats: JPG, PNG, WebP &middot; Max 4&nbsp;MB. This image is stored securely and
              used only for attendance and identity verification in Attendify.
            </p>
          </div>

          {{-- Footer --}}
          <div class="sticky bottom-0 bg-white/95 backdrop-blur border-t px-4 sm:px-6 py-3 flex items-center justify-end gap-2">
            <button
              type="button"
              class="h-8 px-3 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-medium"
              x-on:click="open = false"
            >
              Cancel
            </button>

            <button
              type="submit"
              class="h-8 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60 text-xs font-medium"
              wire:loading.attr="disabled"
              wire:target="saveFaceRecognitionUpload,faceRecognitionUpload"
            >
              <svg
                wire:loading
                wire:target="saveFaceRecognitionUpload,faceRecognitionUpload"
                class="w-3.5 h-3.5 animate-spin inline mr-1"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="3"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
              </svg>
              <span wire:loading.remove wire:target="saveFaceRecognitionUpload">
                Save image
              </span>
              <span wire:loading wire:target="saveFaceRecognitionUpload">
                Uploading…
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
