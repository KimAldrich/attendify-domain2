<div> {{-- single root (no x-app-layout here) --}}
  {{-- STICKY: Tabs + description --}}
  <div class="bg-[#f7f9fc]">
    <div class="overflow-x-auto whitespace-nowrap no-scrollbar">
      <div class="inline-flex gap-2 px-6 pt-4">
        @php
          $tabs = [
            'campuses'    => 'Campuses',
            'departments' => 'Departments',
            'offices'     => 'Offices',
          ];
        @endphp

        @foreach($tabs as $key => $label)
          <button type="button"
            wire:click="setTab('{{ $key }}')"
            @class([
              'px-3 h-9 rounded-full border text-sm transition',
              'bg-white border-blue-600 text-blue-700 font-medium' => $tab===$key,
              'bg-white border-gray-200 text-gray-700 hover:bg-gray-50' => $tab!==$key,
            ])
            @if($tab===$key) aria-current="page" @endif
          >{{ $label }}</button>
        @endforeach
      </div>
    </div>

    <div class="px-6 mt-2 pb-3 text-sm text-gray-600">
      @switch($tab)
        @case('campuses')
          Manage list of campuses for the university. Toggle active/inactive statuses for each campus.
          @break
        @case('departments')
          Manage department fields for the student and teaching staff users. Toggle active/inactive statuses for each department.
          @break
        @case('offices')
          Manage office fields for the non-teaching staff users. Toggle active/inactive statuses for each office.
          @break
      @endswitch
    </div>

    <div class="h-4 -mt-2 bg-gradient-to-b from-[#f7f9fc] to-transparent pointer-events-none"></div>
  </div>

  {{-- TOP-LEVEL SKELETON when switching tabs --}}
  <div wire:loading.flex wire:target="tab,setTab" class="px-6 mt-4 flex flex-col gap-2">
    <div class="h-9 bg-gray-100 rounded animate-pulse"></div>
    <div class="h-9 bg-gray-100 rounded animate-pulse"></div>
    <div class="h-40 bg-gray-100 rounded animate-pulse"></div>
  </div>

  {{-- TAB CONTENT (only current tab re-renders) --}}
  <div wire:loading.remove wire:target="tab,setTab" wire:key="tab-{{ $tab }}" class="px-6 py-4">
    @if($tab === 'campuses')
      @livewire('admin.system-management.campuses-table', key('campuses'))
    @elseif($tab === 'departments')
      @livewire('admin.system-management.departments-table', key('departments'))
    @else
      @livewire('admin.system-management.offices-table', key('offices'))
    @endif
  </div>
</div>
