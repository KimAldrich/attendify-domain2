@php
  $viewerIsAdmin = auth()->user()?->hasRole('admin');
@endphp

<div class="relative bg-white rounded-xl border p-4 flex flex-col">
  {{-- Warning Banner --}}
  {{-- Warning Banner --}}
  @if($viewerIsOwner && !$infoComplete)
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50">
      <div class="px-3 py-2.5 flex flex-col sm:flex-row sm:items-center sm:gap-3">
        {{-- Icon + text --}}
        <div class="flex-1 flex items-start gap-2">
          <i class="bi bi-exclamation-triangle-fill text-amber-500 mt-0.5 text-base"></i>
          <div>
            <p class="text-xs sm:text-sm font-medium text-amber-900">
              Some changes have happened. Please review your profile information.
            </p>
            <p class="mt-0.5 text-[11px] sm:text-xs text-amber-800">
              Keeping your details up to date helps us show accurate information across the system.
            </p>
          </div>
        </div>

        {{-- Action --}}
        <div class="mt-2 sm:mt-0 sm:ml-4">
          <button
            type="button"
            wire:click="openEdit"
            class="inline-flex items-center justify-center rounded-md border border-amber-400 bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-200"
          >
            Complete now
          </button>
        </div>
      </div>
    </div>
  @endif

  <div class="flex flex-col gap-1 pr-20">
    <div class="text-xl font-semibold">{{ $profile->full_name }}</div>
    <p class="text-sm text-slate-700">
        <span class="font-semibold">Email:</span>
        {{ $profile->email }}
    </p>

    <div class="mt-2 flex flex-wrap gap-2">
      @foreach($profile->roles as $r)
        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700">
          {{ ucfirst($r->name) }}
        </span>
      @endforeach
      
    @if(($roleTab ?? null) === 'student' && ($is_moderator ?? false))
      <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-700">
        Moderator
      </span>
    @endif
    </div>

    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
      <div><span class="font-semibold">Member since:</span> {{ optional($profile->created_at)->format('M j, Y') ?? '—' }}</div>
    </div>
  </div>

  <div class="mt-4">
    <label class="block text-xs font-medium text-gray-500 mb-1">Bio</label>
    <div class="min-h-[72px] p-3 rounded-md border bg-gray-50 text-sm text-gray-700 break-words whitespace-pre-line">
      @if(filled($bio))
        {{ $bio }}
      @else
        <span class="text-gray-400 italic">The user hasn’t written an introduction yet.</span>
      @endif
    </div>
      @if($viewerIsOwner)
        <div class="mt-4">
          <button
            type="button"
            wire:click="openEdit"
            class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            <i class="bi bi-pencil"></i>
            <span>Edit profile</span>
          </button>
        </div>
      @endif
  </div>

{{-- PROFILE EDIT MODAL --}}
<div wire:teleport="#modal-root">
  <div x-data="{ open:false, teach:@entangle('draft.is_teaching').live }" x-cloak
       x-on:open-modal.window="if($event.detail.name==='profile-edit') open=true"
       x-on:close-modal.window="if($event.detail.name==='profile-edit') open=false"
       x-trap.noscroll="open"
       class="relative z-[2001]">

    <!-- Backdrop -->
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/40"></div>

    <!-- Dialog -->
    <div x-show="open" x-transition
         class="fixed inset-0 flex items-center justify-center p-3 sm:p-6">
      <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl border overflow-hidden">

        <!-- Sticky header -->
        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b px-4 sm:px-6 py-3 flex items-center justify-between">
          <div class="min-w-0">
            <div class="text-[15px] font-semibold truncate">Edit profile</div>
            <div class="mt-0.5 text-xs text-gray-500">
              <span class="inline-flex items-center gap-1">
                <i class="bi bi-info-circle"></i>
                Fields marked <span class="text-red-600">*</span> are required.
              </span>
            </div>
          </div>
          <button class="h-9 w-9 inline-flex items-center justify-center rounded-md hover:bg-gray-100"
              x-on:click="open=false"
              wire:loading.attr="disabled"
              wire:target="saveAll"
              aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <form wire:submit.prevent="saveAll"
          wire:key="profile-edit-{{ $profile->id }}-{{ $editOpen ? 'open' : 'closed' }}"
          wire:target="saveAll"
          wire:loading.attr="aria-busy">
        <!-- Scrollable body -->
        <div class="max-h-[70vh] overflow-y-auto p-4 sm:p-6 space-y-8">

          {{-- SHARED --}}
          <section>
            <h4 class="text-sm font-semibold text-gray-900">Basic information</h4>
            <p class="text-xs text-gray-500 mb-3">Your public name and primary contact number. Place an introduction for yourself too.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">First name <span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="draft.first_name" class="form-input w-full" placeholder="Juan">
                @error('draft.first_name')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Middle name</label>
                <input type="text" wire:model.defer="draft.middle_name" class="form-input w-full" placeholder="Santos">
                @error('draft.middle_name')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Last name <span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="draft.last_name" class="form-input w-full" placeholder="Dela Cruz">
                @error('draft.last_name')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">CP No. <span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="draft.cp_no" class="form-input w-full" placeholder="09xxxxxxxxx">
                @error('draft.cp_no')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Bio</label>
            @php $len = mb_strlen($draft['bio'] ?? ''); @endphp
            <div class="relative">
              <textarea
                rows="4"
                wire:model.defer="draft.bio"
                maxlength="150"
                class="form-textarea w-full pr-12"
                placeholder="Tell us a little about yourself…"
              ></textarea>

              <!-- Character counter (Livewire reactive) -->
              <span id="bio-count"
                    class="absolute bottom-1 right-2 text-[11px] {{ $len >= 140 ? 'text-red-600' : 'text-gray-400' }}">
                {{ $len }}/150
              </span>
            </div>

            @error('draft.bio')
              <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
          </div>
          </section>

          <section>
            <h4 class="text-sm font-semibold text-gray-900">Address</h4>
            <p class="text-xs text-gray-500 mb-3">Used to identify your campus/region. City and province are required.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">House / Street</label>
                <input type="text" wire:model.defer="draft.address_house" class="form-input w-full" placeholder="123 Sampaguita St.">
                @error('draft.address_house')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Barangay / Subdivision</label>
                <input type="text" wire:model.defer="draft.address_brgy" class="form-input w-full" placeholder="Brgy. San Roque">
                @error('draft.address_brgy')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">City / Municipality <span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="draft.address_city" class="form-input w-full" placeholder="Urdaneta City">
                @error('draft.address_city')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="draft.address_province" class="form-input w-full" placeholder="Pangasinan">
                @error('draft.address_province')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
            </div>
          </section>

          <section>
            <h4 class="text-sm font-semibold text-gray-900">Links & bio</h4>
            <p class="text-xs text-gray-500 mb-3">Optional links where people can reach you.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Facebook</label>
                <input type="url" wire:model.defer="draft.link_facebook" class="form-input w-full" placeholder="https://facebook.com/…">
                @error('draft.link_facebook')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">LinkedIn</label>
                <input type="url" wire:model.defer="draft.link_linkedin" class="form-input w-full" placeholder="https://linkedin.com/in/…">
                @error('draft.link_linkedin')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
              </div>
            </div>
          </section>

          {{-- ROLE-SPECIFIC --}}
          @php $role = $this->currentRole(); @endphp

          @if($role === 'guest')
            <section>
              <h4 class="text-sm font-semibold text-gray-900 mb-3">Guest</h4>
              <label class="block text-xs font-medium text-gray-600 mb-1">Organization <span class="text-red-600">*</span></label>
              <input type="text" wire:model.defer="draft.organization" class="form-input w-full" placeholder="The organization/group you represent...">
              @error('draft.organization')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
            </section>

          @elseif($role === 'student')
            <section>
              <h4 class="text-sm font-semibold text-gray-900 mb-3">Student</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Student number <span class="text-red-600">*</span></label>
                  <input type="text" wire:model.defer="draft.student_number" class="form-input w-full" placeholder="25UR0001">
                  @error('draft.student_number')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Year level <span class="text-red-600">*</span></label>
                  <select wire:model.defer="draft.year_level" class="form-select w-full">
                    <option value="">—</option>
                    @foreach($yearLevels as $lvl)
                      <option value="{{ $lvl }}">{{ $lvl }}</option>
                    @endforeach
                  </select>
                  @error('draft.year_level')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Department <span class="text-red-600">*</span></label>
                  <select wire:model.defer="draft.student_department_id"
                          class="form-select w-full"
                          wire:key="student-dept-{{ count($departments) }}">
                    <option value="">—</option>
                    @foreach($departments as $d)
                      <option value="{{ $d['id'] }}">{{ $d['label'] }}</option>
                    @endforeach
                  </select>
                  @error('draft.student_department_id')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Campus <span class="text-red-600">*</span></label>
                 <select wire:model.defer="draft.student_campus_id"
                          class="form-select w-full"
                          wire:key="student-campus-{{ count($campuses) }}">
                    <option value="">—</option>
                    @foreach($campuses as $c)
                      <option value="{{ $c['id'] }}">{{ $c['label'] }}</option>
                    @endforeach
                  </select>
                  @error('draft.student_campus_id')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                </div>
              </div>
            </section>

          @elseif($role === 'faculty')
<section x-data="{ teach: $wire.entangle('draft.is_teaching').live }" class="space-y-3">
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1">
        Teaching staff? <span class="text-red-600">*</span>
      </label>

      <!-- IMPORTANT: x-model.number + numeric option values -->
      <select class="form-select w-full" x-model.number="teach">
        <option :value="0">No</option>
        <option :value="1">Yes</option>
      </select>
      @error('draft.is_teaching') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1">
        Campus <span class="text-red-600">*</span>
      </label>
      <select wire:model.defer="draft.faculty_campus_id" class="form-select w-full"
              wire:key="faculty-campus-{{ count($campuses) }}">
        <option value="">Select a Campus</option>
        @foreach($campuses as $c)
          <option value="{{ $c['id'] }}">{{ $c['label'] }}</option>
        @endforeach
      </select>
      @error('draft.faculty_campus_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <!-- Department when teach === 1 -->
    <div class="sm:col-span-2" x-cloak x-show="teach === 1 || teach === true">
      <label class="block text-xs font-medium text-gray-600 mb-1">
        Department <span class="text-red-600">*</span>
      </label>
      <select wire:model.defer="draft.faculty_department_id" class="form-select w-full"
              wire:key="faculty-dept-{{ count($departments) }}">
        <option value="">Select a Department</option>
        @foreach($departments as $d)
          <option value="{{ $d['id'] }}">{{ $d['label'] }}</option>
        @endforeach
      </select>
      @error('draft.faculty_department_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <!-- Office when teach === 0 -->
    <div class="sm:col-span-2" x-cloak x-show="teach === 0 || teach === false">
      <label class="block text-xs font-medium text-gray-600 mb-1">
        Office <span class="text-red-600">*</span>
      </label>
      <select wire:model.defer="draft.faculty_office_id" class="form-select w-full"
              wire:key="faculty-office-{{ count($offices) }}">
        <option value="">Select an Office</option>
        @foreach($offices as $o)
          <option value="{{ $o['id'] }}">{{ $o['label'] }}</option>
        @endforeach
      </select>
      @error('draft.faculty_office_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
  </div>
</section>

          @else
            {{-- admin: only shared, nothing extra --}}
          @endif
        </div>

        <!-- Sticky footer -->
        <div class="sticky bottom-0 bg-white/95 backdrop-blur border-t px-4 sm:px-6 py-3 flex items-center justify-end gap-2">
          <button type="button"
                class="h-9 px-3 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50"
                x-on:click="open=false"
                wire:loading.attr="disabled"
                wire:target="saveAll">
          Cancel
        </button>

          <button type="submit"
                  class="h-9 px-3 rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
                  wire:loading.attr="disabled"
                  wire:target="saveAll">
            <svg wire:loading wire:target="saveAll" class="w-4 h-4 animate-spin inline mr-1" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
            </svg>
            Save
          </button>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>


</div>
