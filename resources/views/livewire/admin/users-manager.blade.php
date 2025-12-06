<!-- livewire/admin/users-manager.blade.php -->
<div class="min-h-[60vh] bg-[#f7f9fc]">
  {{-- STICKY: Tabs + intro (with bottom padding & subtle fade) --}}
  <div class="sticky top-[calc(var(--header-h)+4px)] z-10 bg-[#f7f9fc]/95 backdrop-blur border-b border-slate-200/60">
    <div class="overflow-x-auto whitespace-nowrap no-scrollbar">
      <div class="inline-flex gap-2 px-6 pt-4 pb-1">
        @php
          $tabs = [
            'verification' => 'Monitor User Verification',
            'status'       => 'Enable / Disable User',
            'roles'        => 'Manage User Roles',
            'info'         => 'Monitor User Information',
          ];
        @endphp

        @foreach($tabs as $key => $label)
          <button
            type="button"
            wire:click="setTab('{{ $key }}')"
            @class([
              'px-3 h-9 rounded-full border text-xs sm:text-sm transition inline-flex items-center gap-1',
              'bg-white border-blue-600 text-blue-700 font-medium shadow-sm' => $tab===$key,
              'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' => $tab!==$key,
            ])
            @if($tab===$key) aria-current="page" @endif
          >
            {{ $label }}
          </button>
        @endforeach
      </div>
    </div>

    {{-- Friendly description (sticky as well) --}}
    <div class="px-6 pb-3 text-xs sm:text-sm text-slate-600">
      @switch($tab)
        @case('verification')
          Track all users who signed up to the system and see when they verified their accounts.
          @break
        @case('status')
          Turn sign-in on or off for each user by enabling or disabling their account.
          @break
        @case('roles')
          Assign the correct role to verified users so they only see what they need.
          @break
        @case('info')
          Quickly view key user details or jump straight into a user’s profile.
          @break
      @endswitch
    </div>

    {{-- Subtle bottom fade so the edge isn’t abrupt --}}
    <div class="h-4 -mt-2 bg-gradient-to-b from-[#f7f9fc] to-transparent pointer-events-none"></div>
  </div>

  {{-- PERSISTENT SEARCH (carries across tabs) --}}
  <div class="px-6 mt-4">
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm px-4 py-3">
      <form class="flex flex-wrap items-end gap-3" @submit.prevent>
        <div class="flex-1 min-w-[220px]">
          <label class="block text-[11px] font-medium text-slate-600 mb-1">
            Search Filter
          </label>
          <div class="flex gap-2">
            <div class="flex-1">
              <input
                wire:key="global-q-{{ $q === '' ? 'empty' : md5($q) }}"
                type="text"
                placeholder="Search names, email, or address..."
                class="form-input w-full text-sm"
                autocomplete="off"
                wire:model.live.debounce.500ms="q"
              />
            </div>
            <button
              type="button"
              wire:click="$set('q','')"
              wire:loading.attr="disabled"
              class="h-9 px-3 rounded-lg border border-slate-300 text-slate-700 text-xs font-medium bg-slate-50 hover:bg-slate-100"
              @disabled($q==='')>
              Clear
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- TOP-LEVEL SKELETON WHEN SWITCHING TABS (covers both filters & table swap) --}}
  <div
    wire:loading.flex
    wire:target="tab,setTab"
    class="px-6 mt-4 flex flex-col gap-2"
  >
    <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
    <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
    <div class="h-40 bg-slate-100 rounded-lg animate-pulse"></div>
  </div>

  {{-- TAB CONTENT --}}
  <div
    wire:loading.remove
    wire:target="tab,setTab"
    wire:key="tab-{{ $tab }}"
    class="px-6 mt-4 pb-6"
  >
    {{-- TAB 1: Verification --}}
    @if($tab === 'verification')
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
        <div class="px-4 sm:px-5 pt-4 pb-2 border-b border-slate-100">
          {{-- Tab-specific filters (flex-full width) --}}
          <form class="flex flex-wrap gap-3 mb-2 items-end" @submit.prevent>
            <div class="flex-1 min-w-[200px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Verification</label>
              <select
                wire:key="verif-{{ $verif === '' ? 'any' : $verif }}"
                class="form-select w-full text-sm"
                wire:model.live="verif"
              >
                <option value="">Any</option>
                <option value="verified">Verified</option>
                <option value="unverified">Unverified</option>
              </select>
            </div>

            <div class="flex-1 min-w-[200px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Sort by</label>
              <select
                wire:key="sort-{{ $sort }}"
                class="form-select w-full text-sm"
                wire:model.live="sort"
              >
                <option value="created_at">Created date</option>
                <option value="email_verified_at">Verified date</option>
                <option value="name">Name</option>
                <option value="email">Email</option>
              </select>
            </div>

            <div class="w-[160px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Direction</label>
              <select
                wire:key="dir-{{ $dir }}"
                class="form-select w-full text-sm"
                wire:model.live="dir"
              >
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
              </select>
            </div>

            <div class="w-[140px]">
              <button
                type="button"
                wire:click="resetVerificationFilters"
                wire:loading.attr="disabled"
                class="h-9 w-full px-4 rounded-lg border border-slate-300 text-slate-700 text-xs font-medium bg-slate-50 hover:bg-slate-100"
              >
                Reset
              </button>
              <noscript>
                <button
                  type="submit"
                  formaction="{{ route('admin.users.index') }}"
                  formmethod="GET"
                  class="h-9 w-full mt-2 px-4 rounded-lg border border-blue-600 text-blue-600 text-xs font-medium"
                >
                  Apply
                </button>
              </noscript>
            </div>
          </form>
        </div>

        @php
          $pageSize = $users->perPage();
          $rowCount = $users->count();
          $pad = max(0, $pageSize - $rowCount);
        @endphp

        {{-- TABLE (hidden while loading) --}}
        <div wire:loading.remove wire:target="q,verif,sort,dir,page,perPage,resetVerificationFilters">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-200">
                <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                  <th class="px-4 py-2 text-left">Name</th>
                  <th class="px-4 py-2 text-left">Email</th>
                  <th class="px-4 py-2 text-left">Verified</th>
                  <th class="px-4 py-2 text-left">Created</th>
                  <th class="px-4 py-2 text-left">Verified at</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @forelse($users as $u)
                  @php
                    $isVerified = !is_null($u->email_verified_at);
                    $created   = $u->created_at ? \Illuminate\Support\Carbon::parse($u->created_at)->timezone($tz) : null;
                    $verified  = $u->email_verified_at ? \Illuminate\Support\Carbon::parse($u->email_verified_at)->timezone($tz) : null;
                  @endphp
                  <tr class="h-12 hover:bg-slate-50/70 transition-colors">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ $u->display_name }}</td>
                    <td class="px-4 py-2 text-slate-700">{{ $u->email }}</td>
                    <td class="px-4 py-2">
                      <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium border',
                        'bg-emerald-50 text-emerald-700 border-emerald-200' => $isVerified,
                        'bg-rose-50 text-rose-700 border-rose-200' => !$isVerified,
                      ])>
                        <span @class([
                          'w-1.5 h-1.5 rounded-full mr-1.5',
                          $isVerified ? 'bg-emerald-500' : 'bg-rose-500',
                        ])></span>
                        {{ $isVerified ? 'Yes' : 'No' }}
                      </span>
                    </td>
                    <td
                      class="px-4 py-2 whitespace-nowrap text-slate-700"
                      title="{{ $created ? $created->format('Y-m-d H:i') : '' }}"
                    >
                      {{ $created ? $created->format('M j, Y') : '—' }}
                    </td>
                    <td
                      class="px-4 py-2 whitespace-nowrap text-slate-700"
                      title="{{ $verified ? $verified->format('Y-m-d H:i') : '' }}"
                    >
                      {{ $verified ? $verified->format('M j, Y • g:i a') : '—' }}
                    </td>
                  </tr>
                @empty
                  <tr class="h-12">
                    <td colspan="5" class="px-4 py-6 text-center text-slate-500 text-xs">
                      No users match your filters.
                    </td>
                  </tr>
                @endforelse

                {{-- Filler rows to keep body ~15 rows tall --}}
                @for ($i = 0; $i < $pad; $i++)
                  <tr class="h-12">
                    <td class="px-4 py-2">&nbsp;</td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"></td>
                  </tr>
                @endfor
              </tbody>
            </table>
          </div>

          {{-- Firebase-style pager --}}
          @php
            $first = $users->firstItem() ?? 0;
            $last  = $users->lastItem() ?? 0;
            $total = $users->total();
          @endphp

          <div class="flex items-center justify-end gap-6 p-3 border-t border-slate-100 bg-slate-50/60 rounded-b-xl">
            <div class="flex items-center gap-2 text-xs sm:text-sm">
              <span class="text-slate-600">Rows per page:</span>
              <select
                class="form-select h-8 w-[80px] text-xs"
                wire:model.live="perPage"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <div class="text-xs sm:text-sm text-slate-600 min-w-[120px] text-right">
              {{ $first }}–{{ $last }} of {{ $total }}
            </div>

            <div class="flex items-center gap-1">
              <button
                type="button"
                wire:click="previousPage('page')"
                wire:loading.attr="disabled"
                class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                @disabled($users->onFirstPage())
                aria-label="Previous page"
              >&lsaquo;</button>

              <button
                type="button"
                wire:click="nextPage('page')"
                wire:loading.attr="disabled"
                class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                @disabled($users->onLastPage())
                aria-label="Next page"
              >&rsaquo;</button>
            </div>
          </div>
        </div>

        {{-- SKELETON --}}
        <div
          wire:loading.flex
          wire:target="q,verif,sort,dir,page,perPage,resetVerificationFilters"
          class="mt-2 flex flex-col gap-2 px-4 pb-4"
        >
          <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-40 bg-slate-100 rounded-lg animate-pulse"></div>
        </div>
      </div>
    @endif

    {{-- TAB 2: Enable / Disable --}}
    @if($tab === 'status')
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
        <div class="px-4 sm:px-5 pt-4 pb-2 border-b border-slate-100">
          {{-- Tab-specific filters --}}
          <form class="flex flex-wrap gap-3 mb-2 items-end" @submit.prevent>
            <div class="flex-1 min-w-[200px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Status</label>
              <select
                wire:key="status-filter-{{ $status === '' ? 'any' : $status }}"
                class="form-select w-full text-sm"
                wire:model.live="status"
              >
                <option value="">Any</option>
                <option value="enabled">Enabled</option>
                <option value="disabled">Disabled</option>
              </select>
            </div>

            <div class="flex-1 min-w-[200px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Sort by</label>
              <select
                wire:key="status-sort-{{ $statusSort }}"
                class="form-select w-full text-sm"
                wire:model.live="statusSort"
              >
                <option value="name">Name</option>
                <option value="email">Email</option>
                <option value="created_at">Created</option>
              </select>
            </div>

            <div class="w-[160px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Direction</label>
              <select
                wire:key="status-dir-{{ $statusDir }}"
                class="form-select w-full text-sm"
                wire:model.live="statusDir"
              >
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
              </select>
            </div>

            <div class="w-[140px]">
              <button
                type="button"
                wire:click="resetStatusFilters"
                wire:loading.attr="disabled"
                class="h-9 w-full px-4 rounded-lg border border-slate-300 text-slate-700 text-xs font-medium bg-slate-50 hover:bg-slate-100"
              >
                Reset
              </button>
              <noscript>
                <button
                  type="submit"
                  formaction="{{ route('admin.users.index') }}"
                  formmethod="GET"
                  class="h-9 w-full mt-2 px-4 rounded-lg border border-blue-600 text-blue-600 text-xs font-medium"
                >
                  Apply
                </button>
              </noscript>
            </div>
          </form>
        </div>

        @php
          $pageSize = $users->perPage();
          $rowCount = $users->count();
          $pad = max(0, $pageSize - $rowCount);
        @endphp

        <div>
          <div wire:loading.remove wire:target="q,status,statusSort,statusDir,page,perPage,resetStatusFilters">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                  <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Enable</th>
                    <th class="px-4 py-2 text-left">Disable</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  @forelse($users as $u)
                    @php
                      $uid = $u->firebase_uid;
                      $isDisabled = $uid ? (bool) ($fbDisabled[$uid] ?? false) : false;
                    @endphp

                    <livewire:admin.rows.user-status-row
                      :user="$u"
                      :disabled="$isDisabled"
                      :wire:key="'status-row-'.$u->id"
                    />
                  @empty
                    <tr class="h-12">
                      <td colspan="5" class="px-4 py-6 text-center text-slate-500 text-xs">
                        No users match your filters.
                      </td>
                    </tr>
                  @endforelse

                  @for ($i = 0; $i < $pad; $i++)
                    <tr class="h-12">
                      <td class="px-4 py-2">&nbsp;</td>
                      <td class="px-4 py-2"></td>
                      <td class="px-4 py-2"></td>
                      <td class="px-4 py-2"></td>
                      <td class="px-4 py-2"></td>
                    </tr>
                  @endfor
                </tbody>
              </table>
            </div>

            @php
              $first = $users->firstItem() ?? 0;
              $last  = $users->lastItem() ?? 0;
              $total = $users->total();
            @endphp

            <div class="flex items-center justify-end gap-6 p-3 border-t border-slate-100 bg-slate-50/60 rounded-b-xl">
              <div class="flex items-center gap-2 text-xs sm:text-sm">
                <span class="text-slate-600">Rows per page:</span>
                <select class="form-select h-8 w-[80px] text-xs" wire:model.live="perPage">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>

              <div class="text-xs sm:text-sm text-slate-600 min-w-[120px] text-right">
                {{ $first }}–{{ $last }} of {{ $total }}
              </div>

              <div class="flex items-center gap-1">
                <button
                  type="button"
                  wire:click="previousPage('page')"
                  wire:loading.attr="disabled"
                  class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                  @disabled($users->onFirstPage())
                  aria-label="Previous page"
                >&lsaquo;</button>

                <button
                  type="button"
                  wire:click="nextPage('page')"
                  wire:loading.attr="disabled"
                  class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                  @disabled($users->onLastPage())
                  aria-label="Next page"
                >&rsaquo;</button>
              </div>
            </div>
          </div>

          {{-- Loading skeleton --}}
          <div
            wire:loading.flex
            wire:target="q,status,statusSort,statusDir,page,perPage,resetStatusFilters"
            class="mt-2 flex flex-col gap-2 px-4 pb-4"
          >
            <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
            <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
            <div class="h-40 bg-slate-100 rounded-lg animate-pulse"></div>
          </div>
        </div>
      </div>
    @endif

    {{-- TAB 3: Roles --}}
    @if($tab === 'roles')
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
        <div class="px-4 sm:px-5 pt-4 pb-2 border-b border-slate-100">
          {{-- Filters (flex full-width; q from the global bar still applies) --}}
          <form class="flex flex-wrap gap-3 mb-2 items-end" @submit.prevent>
            <div class="flex-1 min-w-[200px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Sort by</label>
              <select class="form-select w-full text-sm" wire:model.live="rolesSort">
                <option value="name">Name</option>
                <option value="email">Email</option>
                <option value="created_at">Created</option>
              </select>
            </div>

            <div class="w-[160px]">
              <label class="block text-[11px] font-medium text-slate-600 mb-1">Direction</label>
              <select class="form-select w-full text-sm" wire:model.live="rolesDir">
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
              </select>
            </div>

            <div class="w-[140px]">
              <button
                type="button"
                wire:click="resetRolesFilters"
                wire:loading.attr="disabled"
                class="h-9 w-full px-4 rounded-lg border border-slate-300 text-slate-700 text-xs font-medium bg-slate-50 hover:bg-slate-100"
              >
                Reset
              </button>
              <noscript>
                <button
                  type="submit"
                  formaction="{{ route('admin.users.index') }}"
                  formmethod="GET"
                  class="h-9 w-full mt-2 px-4 rounded-lg border border-blue-600 text-blue-600 text-xs font-medium"
                >
                  Apply
                </button>
              </noscript>
            </div>
          </form>
        </div>

        @php
          $pageSize = $users->perPage();
          $rowCount = $users->count();
          $pad = max(0, $pageSize - $rowCount);
        @endphp

        <div wire:loading.remove wire:target="q,rolesSort,rolesDir,page,perPage,resetRolesFilters">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 border-b border-slate-200">
                <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                  <th class="px-4 py-2 text-left">Name</th>
                  <th class="px-4 py-2 text-left">Email</th>
                  <th class="px-4 py-2 text-left">Guest</th>
                  <th class="px-4 py-2 text-left">Student</th>
                  <th class="px-4 py-2 text-left">Faculty</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @forelse($users as $u)
                  <livewire:admin.rows.user-role-row
                    :user="$u"
                    :key="'role-row-'.$u->id"
                  />

                @empty
                  <tr class="h-12">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                      No users match your filters.
                    </td>
                  </tr>
                @endforelse

                {{-- Filler rows to keep body ~15 rows tall --}}
                @for ($i = 0; $i < $pad; $i++)
                  <tr class="h-12">
                    <td class="px-4 py-2">&nbsp;</td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2"></td>
                  </tr>
                @endfor
              </tbody>
            </table>
          </div>

          @php
            $first = $users->firstItem() ?? 0;
            $last  = $users->lastItem() ?? 0;
            $total = $users->total();
          @endphp

          <div class="flex items-center justify-end gap-6 p-3 border-t border-slate-100 bg-slate-50/60 rounded-b-xl">
            <div class="flex items-center gap-2 text-xs sm:text-sm">
              <span class="text-slate-600">Rows per page:</span>
              <select
                class="form-select h-8 w-[80px] text-xs"
                wire:model.live="perPage"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <div class="text-xs sm:text-sm text-slate-600 min-w-[120px] text-right">
              {{ $first }}–{{ $last }} of {{ $total }}
            </div>

            <div class="flex items-center gap-1">
              <button
                type="button"
                wire:click="previousPage('page')"
                wire:loading.attr="disabled"
                class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                @disabled($users->onFirstPage())
                aria-label="Previous page"
              >&lsaquo;</button>

              <button
                type="button"
                wire:click="nextPage('page')"
                wire:loading.attr="disabled"
                class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                @disabled($users->onLastPage())
                aria-label="Next page"
              >&rsaquo;</button>
            </div>
          </div>
        </div>

        {{-- Skeleton --}}
        <div
          wire:loading.flex
          wire:target="q,rolesSort,rolesDir,page,perPage,resetRolesFilters"
          class="mt-2 flex flex-col gap-2 px-4 pb-4"
        >
          <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-9 bg-slate-100 rounded-lg animate-pulse"></div>
          <div class="h-40 bg-slate-100 rounded-lg animate-pulse"></div>
        </div>
      </div>
    @endif

    {{-- TAB 4: Info --}}
    @if($tab === 'info')
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
        @if(!$inspectUserId)
          {{-- Filters (unchanged placeholder comment) --}}
          <div class="px-4 sm:px-5 pt-4 pb-2 border-b border-slate-100">
            <form class="flex flex-wrap gap-3 mb-2 items-end" @submit.prevent>
              {{-- ... keep your filters here exactly as-is ... --}}
            </form>
          </div>

          @php
            $pageSize = $users->perPage();
            $rowCount = $users->count();
            $pad = max(0, $pageSize - $rowCount);

            // Targets that should flip the table into skeleton
            $tableTargets = 'q,infoSort,infoDir,page,perPage,resetInfoFilters,inspect';
          @endphp

          {{-- TABLE (hidden while loading any of the table targets incl. inspect) --}}
          <div wire:loading.remove wire:target="{{ $tableTargets }}">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                  <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">CP No.</th>
                    <th class="px-4 py-2 text-left">Address</th>
                    <th class="px-4 py-2 text-left">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  @forelse($users as $u)
                    <tr class="h-12 hover:bg-slate-50/70 transition-colors">
                      <td class="px-4 py-2 font-medium text-slate-900">{{ $u->display_name }}</td>
                      <td class="px-4 py-2 text-slate-700">{{ $u->email }}</td>
                      <td class="px-4 py-2 text-slate-700">{{ $u->cp_no ?? '—' }}</td>
                      <td class="px-4 py-2 text-slate-700">{{ $u->address ?? '—' }}</td>
                      <td class="px-4 py-2">
                        <button
                          type="button"
                          wire:click="inspect({{ $u->id }})"
                          class="inline-flex items-center gap-2 h-8 px-3 rounded-full border border-blue-600 text-blue-700 text-[11px] font-medium hover:bg-blue-50"
                        >
                          View profile
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr class="h-12">
                      <td colspan="5" class="px-4 py-6 text-center text-slate-500 text-xs">
                        No users match your filters.
                      </td>
                    </tr>
                  @endforelse

                  {{-- Filler rows to keep body ~15 rows tall --}}
                  @for ($i = 0; $i < $pad; $i++)
                    <tr class="h-12">
                      <td class="px-4 py-2">&nbsp;</td>
                      <td class="px-4 py-2"></td>
                      <td class="px-4 py-2"></td>
                      <td class="px-4 py-2"></td>
                      <td class="px-4 py-2"></td>
                    </tr>
                  @endfor
                </tbody>
              </table>
            </div>

            {{-- Pagination (unchanged logic; restyled) --}}
            @php
              $first = $users->firstItem() ?? 0;
              $last  = $users->lastItem() ?? 0;
              $total = $users->total();
            @endphp

            <div class="flex items-center justify-end gap-6 p-3 border-t border-slate-100 bg-slate-50/60 rounded-b-xl">
              <div class="flex items-center gap-2 text-xs sm:text-sm">
                <span class="text-slate-600">Rows per page:</span>
                <select class="form-select h-8 w-[80px] text-xs" wire:model.live="perPage">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>

              <div class="text-xs sm:text-sm text-slate-600 min-w-[120px] text-right">
                {{ $first }}–{{ $last }} of {{ $total }}
              </div>

              <div class="flex items-center gap-1">
                <button
                  type="button"
                  wire:click="previousPage('page')"
                  wire:loading.attr="disabled"
                  class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                  @disabled($users->onFirstPage())
                  aria-label="Previous page"
                >&lsaquo;</button>

                <button
                  type="button"
                  wire:click="nextPage('page')"
                  wire:loading.attr="disabled"
                  class="h-8 w-8 rounded-md border border-slate-200 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                  @disabled($users->onLastPage())
                  aria-label="Next page"
                >&rsaquo;</button>
              </div>
            </div>
          </div>

          {{-- TABLE SKELETON (shows while filters/paging OR inspect are running) --}}
          <div
            wire:loading.flex
            wire:target="{{ $tableTargets }}"
            class="mt-3 flex flex-col gap-2 px-4 pb-4"
          >
            <div class="h-10 bg-slate-100 rounded-lg animate-pulse"></div>
            <div class="h-36 bg-slate-100 rounded-lg animate-pulse"></div>
          </div>
        @else
          {{-- INSPECT PROFILE (no route change, keeps filters alive) --}}
          <div class="px-4 sm:px-5 pt-4 pb-2 border-b border-slate-100">
            <button
              type="button"
              wire:click="backToList"
              wire:target="backToList"
              wire:loading.attr="disabled"
              class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-300 text-slate-700 text-xs font-medium bg-white hover:bg-slate-50 disabled:opacity-60"
            >
              &larr; Back to users
            </button>
          </div>

          {{-- Mini skeleton ONLY while returning to list --}}
          <div
            wire:loading.flex
            wire:target="backToList"
            class="mt-3 flex flex-col gap-2 px-4 pb-4"
          >
            <div class="h-10 bg-slate-100 rounded-lg animate-pulse"></div>
            <div class="h-36 bg-slate-100 rounded-lg animate-pulse"></div>
          </div>

          {{-- Hide profile content while backToList runs --}}
          <div wire:loading.remove wire:target="backToList" class="px-4 sm:px-5 py-4">
            @php
              $inspected = \App\Models\User::with('roles')->find($inspectUserId);
            @endphp

            @if($inspected)
              <livewire:profile.profile-page :user="$inspected" :key="'inspect-'.$inspected->id" />
            @else
              <div class="text-slate-500 text-sm">User not found.</div>
            @endif
          </div>
        @endif
      </div>
    @endif
  </div>
</div>
