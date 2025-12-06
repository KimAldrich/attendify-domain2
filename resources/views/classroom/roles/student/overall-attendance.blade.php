{{-- resources/views/classroom/roles/student/overall-attendance.blade.php --}}
<x-app-layout>
    <div class="min-h-screen bg-slate-50 px-6 py-8"
         x-data="{
            attendanceOpen: false,
            attendanceStudent: '',
            attendanceRecords: [],
            openAttendance(student, records) {
                this.attendanceStudent = student;
                this.attendanceRecords = records;
                this.attendanceOpen = true;
            }
         }">

        <div class="max-w-6xl mx-auto space-y-6">

            {{-- Tabs --}}
            @include('classroom.roles.student._tabs')

            {{-- Header + Period selector --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        Overall Attendance
                    </h2>
                    <p class="text-sm text-slate-500">
                        Summary of your attendance per class for the selected academic period.
                    </p>
                </div>

                <form method="GET" class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                        Academic Year &amp; Term
                    </label>
                    <select name="period_id"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            onchange="this.form.submit()">
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}"
                                @selected($selectedPeriod && $selectedPeriod->id === $period->id)>
                                {{ $period->display_label }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase">Sections</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ $overallTotals['sections'] }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-green-100 px-4 py-3">
                    <div class="text-xs font-semibold text-green-700 uppercase">Present</div>
                    <div class="mt-1 text-xl font-semibold text-green-700">
                        {{ $overallTotals['present'] }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-amber-100 px-4 py-3">
                    <div class="text-xs font-semibold text-amber-700 uppercase">Tardy</div>
                    <div class="mt-1 text-xl font-semibold text-amber-700">
                        {{ $overallTotals['tardy'] }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-sky-100 px-4 py-3">
                    <div class="text-xs font-semibold text-sky-700 uppercase">Excused</div>
                    <div class="mt-1 text-xl font-semibold text-sky-700">
                        {{ $overallTotals['excused'] }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-red-100 px-4 py-3">
                    <div class="text-xs font-semibold text-red-700 uppercase">Absent</div>
                    <div class="mt-1 text-xl font-semibold text-red-700">
                        {{ $overallTotals['absent'] }}
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-indigo-100 px-4 py-3">
                    <div class="text-xs font-semibold text-indigo-700 uppercase">Overall %</div>
                    <div class="mt-1 text-xl font-semibold text-indigo-700">
                        {{ $overallTotals['pct'] }}%
                    </div>
                </div>
            </div>

            {{-- Sections table --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Course</th>
                            <th class="px-4 py-3 text-left">Section</th>
                            <th class="px-4 py-3 text-left">Instructor</th>
                            <th class="px-4 py-3 text-center">Present</th>
                            <th class="px-4 py-3 text-center">Tardy</th>
                            <th class="px-4 py-3 text-center">Excused</th>
                            <th class="px-4 py-3 text-center">Absent</th>
                            <th class="px-4 py-3 text-center">% for Section</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sections as $section)
                            @php
                                $agg     = $perSectionAgg->get($section->id);
                                $present = $agg->present_count ?? 0;
                                $tardy   = $agg->tardy_count ?? 0;
                                $excused = $agg->excused_count ?? 0;
                                $absent  = $agg->absent_count ?? 0;
                                $total   = $agg->total ?? 0;
                                $pct     = $total > 0 ? round((($present + $tardy) / $total) * 100, 2) : 0;

                                // Build a plain PHP array; will be JSON-encoded safely with @js()
                                $recordsPayload = ($sectionRecords[$section->id] ?? collect())
                                    ->map(function ($rec) {
                                        return [
                                            'id'           => $rec->id,
                                            'meeting_date' => $rec->meeting_date?->format('M d, Y'),
                                            'time_in'      => ($rec->time_in && in_array($rec->status, ['present','tardy']))
                                                ? $rec->time_in->format('h:i A')
                                                : '—',
                                            'status'       => $rec->status,
                                        ];
                                    })
                                    ->values()
                                    ->all();
                            @endphp

                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ $section->course_name }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $section->course_code }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $section->section_label }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $section->instructor->full_name ?? 'TBA' }}
                                </td>
                                <td class="px-4 py-3 text-center text-green-700 font-semibold">
                                    {{ $present }}
                                </td>
                                <td class="px-4 py-3 text-center text-amber-700 font-semibold">
                                    {{ $tardy }}
                                </td>
                                <td class="px-4 py-3 text-center text-sky-700 font-semibold">
                                    {{ $excused }}
                                </td>
                                <td class="px-4 py-3 text-center text-red-700 font-semibold">
                                    {{ $absent }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-900 font-semibold">
                                    {{ $pct }}%
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50"
                                        x-on:click="openAttendance(
                                            @js($section->course_code . ' • ' . $section->course_name . ' (' . $section->section_label . ')'),
                                            @js($recordsPayload)
                                        )"
                                    >
                                        View Records
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-slate-400 text-sm">
                                    You are not enrolled in any sections for this academic period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Weekly timetable (like instructor) --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Weekly Schedule (6:00 AM – 6:00 PM)
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Each blue strip represents one of your scheduled classes in this period.
                        </p>
                    </div>
                </div>

                @php
                    $hasAnySchedule = collect($timetable ?? [])->flatMap(fn($d) => $d['blocks'])->isNotEmpty();
                @endphp

                @if (! $hasAnySchedule)
                    <div class="px-4 py-6 text-center text-slate-400 text-sm">
                        No schedules found for your classes in this period.
                    </div>
                @else
                    <div class="px-4 pb-4 pt-2">
                        <div class="grid grid-cols-[80px,1fr] text-xs">
                            <div></div>
                            <div class="pl-1">
                                <div class="grid grid-cols-12 text-[11px] text-slate-500 mb-1">
                                    @for ($i = 0; $i < 12; $i++)
                                        @php
                                            $hour = 6 + $i;
                                        @endphp
                                        <div class="border-l border-slate-100 pl-1">
                                            {{ \Carbon\Carbon::createFromTime($hour, 0)->format('g A') }}
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            @foreach ($timetable as $dow => $day)
                                <div class="py-2 pr-3 text-xs font-medium text-slate-700">
                                    {{ $day['label'] }}
                                </div>

                                <div class="py-2 border-t border-slate-100 overflow-hidden">
                                    @php
                                        $laneCount    = $day['lane_count'] ?? 1;
                                        $rowHeightRem = max(2.25 * $laneCount, 4.5);
                                    @endphp

                                    <div class="relative" style="height: {{ $rowHeightRem }}rem;">
                                        <div class="absolute inset-0 grid grid-cols-12">
                                            @for ($i = 0; $i < 12; $i++)
                                                <div class="border-l border-slate-200"></div>
                                            @endfor
                                        </div>

                                        @foreach ($day['blocks'] as $block)
                                            <div
                                                class="absolute flex flex-col justify-center rounded-md bg-[#0052CC] text-white text-[11px] px-2 py-1 shadow-sm"
                                                style="
                                                    left: {{ $block['left'] }}%;
                                                    width: {{ $block['width'] }}%;
                                                    top: {{ $block['lane'] * 1.75 }}rem;
                                                "
                                            >
                                                <div class="font-semibold truncate">
                                                    {{ $block['section']->course_code }}
                                                    • {{ $block['section']->section_label }}
                                                </div>
                                                <div class="truncate opacity-95">
                                                    {{ $block['section']->course_name }}
                                                </div>
                                                <div class="text-[10px] opacity-80">
                                                    {{ $block['room_label'] }}
                                                    • {{ $block['time_label'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ========================= --}}
        {{-- MODAL: VIEW ATTENDANCE   --}}
        {{-- ========================= --}}
        <div
            x-show="attendanceOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
            x-on:click.self="attendanceOpen = false"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6" x-on:click.stop>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Attendance Records
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 leading-snug">
                            <span class="font-medium" x-text="attendanceStudent"></span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                        x-on:click="attendanceOpen = false"
                    >
                        <i class="bi bi-x-lg text-xs text-slate-500"></i>
                        <span class="sr-only">Close</span>
                    </button>
                </div>

                <div class="border border-slate-200 rounded-lg max-h-80 overflow-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-600 uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="attendanceRecords.length === 0">
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-slate-400">
                                        No attendance records yet.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="record in attendanceRecords" :key="record.id">
                                <tr>
                                    <td class="px-3 py-2 text-slate-700" x-text="record.meeting_date"></td>
                                    <td class="px-3 py-2 text-slate-700 capitalize" x-text="record.status"></td>
                                    <td class="px-3 py-2 text-slate-700" x-text="record.time_in"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
