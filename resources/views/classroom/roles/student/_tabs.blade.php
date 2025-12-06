{{-- resources/views/classroom/roles/student/_tabs.blade.php --}}
@php
    $tabs = [
        [
            'label' => 'Attendance Today',
            'route' => 'classroom.student.attendance-today',
        ],
        [
            'label' => 'Overall Attendance',
            'route' => 'classroom.student.overall-attendance',
        ],
    ];
@endphp
<div>
    <h1 class="text-2xl font-semibold text-slate-900">Classes Today</h1>
    <p class="text-sm text-slate-500">
        Access your classes for today, or see your overall class schedule. You may also submit your excuse letters for today here.
    </p>
</div>
<div class="border-b border-slate-200 mb-4">
    <nav class="flex gap-6 text-sm font-medium" aria-label="Attendance tabs">
        @foreach ($tabs as $tab)
            @php
                $active = request()->routeIs($tab['route']);
            @endphp

            <a href="{{ route($tab['route']) }}"
               class="pb-2 border-b-2 transition-colors
                      {{ $active
                            ? 'border-[#0052CC] text-[#0052CC]'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
