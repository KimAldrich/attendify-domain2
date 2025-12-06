{{-- resources/views/classroom/roles/admin/_tabs.blade.php --}}
@php
    $isRoomsTab       = request()->routeIs('classroom.admin.rooms.*');
    $isInstructorsTab = request()->routeIs('classroom.admin.instructors.*')
                            || request()->routeIs('classroom.admin.courses.*')
                            || request()->routeIs('classroom.admin.sections.*');
    $isPeriodsTab     = request()->routeIs('classroom.admin.periods.*');
@endphp
<div>
    <h1 class="text-2xl font-semibold text-slate-900">Manage Classrooms</h1>
    <p class="text-sm text-slate-500">
        Manage all data about academic periods, classrooms, and instructor rosters.
    </p>
</div>
<nav class="mt-1 border-b border-slate-200 mb-4">
    <ul class="flex flex-wrap gap-1">
        <li>
            <a href="{{ route('classroom.admin.periods.index') }}"
               class="inline-flex items-center h-10 px-4 text-sm font-medium border-b-2 -mb-px
                   {{ $isPeriodsTab ? 'border-[#0052CC] text-[#0052CC]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-200' }}">
                Academic Periods
            </a>
        </li>

        <li>
            <a href="{{ route('classroom.admin.rooms.index') }}"
               class="inline-flex items-center h-10 px-4 text-sm font-medium border-b-2 -mb-px
                   {{ $isRoomsTab ? 'border-[#0052CC] text-[#0052CC]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-200' }}">
                Rooms
            </a>
        </li>

        <li>
            <a href="{{ route('classroom.admin.instructors.index') }}"
               class="inline-flex items-center h-10 px-4 text-sm font-medium border-b-2 -mb-px
                   {{ $isInstructorsTab ? 'border-[#0052CC] text-[#0052CC]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-200' }}">
                Instructors
            </a>
        </li>
    </ul>
</nav>
