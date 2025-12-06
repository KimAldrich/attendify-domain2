{{-- resources/views/classroom/roles/instructor/sections/show.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div
            class="max-w-6xl mx-auto space-y-6"
            x-data="sectionPage()"
            x-cloak
        >
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Manage Classrooms</h1>
                <p class="text-sm text-slate-500">
                    Manage all data about your courses and sections, classroom schedules, and class rosters.
                </p>
            </div>

            {{-- Breadcrumbs: Courses / CODE • NAME (SECTION) --}}
            <x-breadcrumbs :items="[
                [
                    'label' => 'Courses',
                    'url'   => route('classroom.instructor.courses.index', [
                        'period_id' => $section->academic_period_id,
                    ]),
                ],
                [
                    'label' => $section->course_code . ' • ' . $section->course_name . ' (' . $section->section_label . ')',
                ],
            ]" />

            @php
                $daysOfWeek = [
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ];

                $studentChoices = $studentsForSelect->map(function ($u) {
                    return [
                        'id'             => $u->id,
                        'student_number' => $u->student_number,
                        'name'           => $u->full_name,
                    ];
                })->values();
            @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        {{ $section->course_code }} • {{ $section->course_name }}
                    </h2>
                    <p class="text-sm text-slate-500">
                        Section {{ $section->section_label }} · Instructor: {{ $section->instructor->full_name }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    {{-- View attendance photos --}}
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', { name: 'attendance-photos' })"
                        class="inline-flex items-center gap-1 rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    >
                        <i class="bi bi-images text-sm"></i>
                        View Attendance Photos
                    </button>
                </div>
            </div>

            {{-- SCHEDULES CARD --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Schedules</h2>

                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', { name: 'add-schedule' })"
                        class="inline-flex items-center gap-1 rounded-md bg-slate-900 text-white text-xs font-medium px-3 py-1.5 hover:bg-slate-800"
                    >
                        <span class="bi bi-plus-lg"></span>
                        Add Schedule
                    </button>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-left">Day</th>
                            <th class="px-4 py-2 text-left">Start Time</th>
                            <th class="px-4 py-2 text-left">End Time</th>
                            <th class="px-4 py-2 text-left">Room</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($section->schedules as $schedule)
                            <tr>
                                <td class="px-4 py-2 text-slate-700">
                                    {{ $daysOfWeek[$schedule->day_of_week] ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 text-slate-700">
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}
                                </td>
                                <td class="px-4 py-2 text-slate-700">
                                    {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                </td>
                                <td class="px-4 py-2 text-slate-700">
                                    @if ($schedule->room)
                                        <div class="inline-flex items-center gap-2">
                                            <span>{{ $schedule->room->room_number }}</span>

                                            @if ($schedule->room->is_face_recognition_enabled)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[11px] font-medium">
                                                    Face-Recog: Enabled
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px] font-medium">
                                                    Face-Recog: Disabled
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-2">
                                        {{-- Edit schedule --}}
                                        <button
                                            type="button"
                                            x-data
                                            x-on:click="$dispatch('open-modal', { name: 'edit-schedule-{{ $schedule->id }}' })"
                                            class="inline-flex items-center rounded-md border border-amber-200 text-xs px-2 py-1.5 text-amber-700 hover:bg-amber-50"
                                        >
                                            Edit
                                        </button>

                                        {{-- Delete schedule --}}
                                        <form
                                            action="{{ route('classroom.instructor.sections.schedules.destroy', [$section, $schedule]) }}"
                                            method="POST"
                                            class="inline"
                                            onsubmit="return confirm('Remove this schedule?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-md border border-red-200 text-xs px-2 py-1.5 text-red-700 hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-400 text-sm">
                                    No schedules yet. Use <span class="font-medium">“Add Schedule”</span> to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- SUMMARY CARDS --}}
            <div class="grid gap-4 md:grid-cols-3">
                {{-- Total students --}}
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Total Enrolled
                    </div>
                    <div class="mt-1 flex items-baseline gap-1">
                        <span class="text-2xl font-semibold text-slate-900">
                            {{ $summaryTotalStudents }}
                        </span>
                        <span class="text-xs text-slate-500">students</span>
                    </div>
                </div>

                {{-- Face-recog inactive --}}
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Face Recognition Photo Inactive
                    </div>
                    <div class="mt-1 flex items-baseline gap-1">
                        <span class="text-2xl font-semibold text-amber-600">
                            {{ $summaryInactiveFace }}
                        </span>
                        <span class="text-xs text-slate-500">students</span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Students without an active face-recognition portrait.
                    </p>
                </div>

                {{-- Overall present percentage --}}
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        Overall Present Attendance
                    </div>
                    <div class="mt-1 flex items-baseline gap-1">
                        <span class="text-2xl font-semibold text-emerald-600">
                            {{ number_format($summaryPresentPct, 1) }}%
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Based on all attendance records marked present or tardy.
                    </p>
                </div>
            </div>

            {{-- IMPORT FAILURES (from last CSV import) --}}
            @if (!empty($importFailures))
                <div class="bg-white rounded-xl shadow border border-red-100 px-4 py-3 border mt-2">
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-50">
                                <i class="bi bi-exclamation-circle text-red-500 text-sm"></i>
                            </span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-red-700">
                                Some student numbers could not be added
                            </h3>
                            <p class="mt-0.5 text-xs text-red-600">
                                The following student numbers do not match any existing student accounts.
                            </p>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($importFailures as $num)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-[11px] font-medium">
                                        {{ $num }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- STUDENTS CARD --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        {{-- Left: title + filter --}}
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <h2 class="text-sm font-semibold text-slate-900">
                                Class Roster
                            </h2>

                            {{-- Smart filter --}}
                            <form method="GET" class="flex items-center gap-2">
                                <input
                                    type="text"
                                    name="q"
                                    value="{{ $search }}"
                                    placeholder="Search name, number, email…"
                                    class="w-56 md:w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                >
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-slate-900 text-white text-xs font-semibold px-3 py-1.5 hover:bg-slate-800"
                                >
                                    Filter
                                </button>
                            </form>
                        </div>

                        {{-- Right: roster actions --}}
                        <div class="flex flex-wrap gap-2 justify-start sm:justify-end">
                            {{-- Add Student --}}
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', { name: 'add-student' })"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#0052CC] text-white text-xs font-medium px-3 py-2 shadow-sm hover:bg-[#003fa3]"
                            >
                                <span class="bi bi-plus-lg"></span>
                                Add Student
                            </button>

                            {{-- Import CSV --}}
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', { name: 'import-students' })"
                                class="inline-flex items-center rounded-lg border border-slate-200 text-xs font-medium px-3 py-2 text-slate-700 hover:bg-slate-50"
                            >
                                Import Roster CSV
                            </button>

                            {{-- Export CSV --}}
                            <a
                                href="{{ route('classroom.instructor.sections.students.export', $section) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 text-xs font-medium px-3 py-2 text-slate-700 hover:bg-slate-50"
                            >
                                Download Roster CSV
                            </a>
                        </div>
                    </div>
                </div>

                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-600 uppercase border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-left">Student #</th>
                            <th class="px-4 py-2 text-left">Name</th>
                            {{-- <th class="px-4 py-2 text-left">Email</th> --}}
                            <th class="px-4 py-2 text-left">Face-Recog</th>
                            <th class="px-4 py-2 text-center">Present</th>
                            <th class="px-4 py-2 text-center">Tardy</th>
                            <th class="px-4 py-2 text-center">Excused</th>
                            <th class="px-4 py-2 text-center">Absent</th>
                            <th class="px-4 py-2 text-center">Attendance %</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($enrollments as $enrollment)
                            @php
                                $student = $enrollment->student;

                                // $perStudentStats is a Collection keyed by student_id
                                $stats   = $perStudentStats->get($student->id);
                                $present = $stats->present_count ?? 0;
                                $tardy   = $stats->tardy_count ?? 0;
                                $excused = $stats->excused_count ?? 0;
                                $absent  = $stats->absent_count ?? 0;

                                $total   = $present + $tardy + $excused + $absent;
                                $pct     = $total > 0
                                    ? round((($present + $tardy) / $total) * 100, 2)
                                    : 0;
                            @endphp

                            <tr>
                                <td class="px-4 py-2 text-slate-700">
                                    {{ $student->student_number ?? '—' }}
                                </td>
                                <td class="px-4 py-2 font-medium text-slate-900">
                                    {{ $student->full_name }}
                                </td>
                                {{-- <td class="px-4 py-2 text-slate-700">
                                    {{ $student->email }}
                                </td> --}}
                                <td class="px-4 py-2">
                                    @if ($enrollment->face_recognition_status === 'active')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            Active
                                        </span>
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route('classroom.instructor.sections.students.notify-face', [$section, $student]) }}"
                                            class="inline"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-medium hover:bg-amber-200"
                                            >
                                                Inactive · Notify
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center text-slate-900">
                                    {{ $present }}
                                </td>
                                <td class="px-4 py-2 text-center text-slate-900">
                                    {{ $tardy }}
                                </td>
                                <td class="px-4 py-2 text-center text-slate-900">
                                    {{ $excused }}
                                </td>
                                <td class="px-4 py-2 text-center text-slate-900">
                                    {{ $absent }}
                                </td>
                                <td class="px-4 py-2 text-center text-slate-900">
                                    {{ $pct }}%
                                </td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    {{-- View Attendance --}}
                                    <button
                                        type="button"
                                        x-on:click="loadAttendance({{ $student->id }})"
                                        class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50"
                                    >
                                        View Attendance
                                    </button>

                                    {{-- Remove from section --}}
                                    <form
                                        action="{{ route('classroom.instructor.sections.students.destroy', [$section, $enrollment]) }}"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Remove this student from the section?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-md border border-red-200 text-xs px-2 py-1.5 text-red-700 hover:bg-red-50"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-4 text-center text-slate-400 text-sm">
                                    No students enrolled yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="border-t border-slate-200 px-4 py-3">
                    <x-table-footer :paginator="$enrollments" />
                </div>
            </div>

            {{-- ========================= --}}
            {{-- MODALS: ADD/EDIT SCHEDULE --}}
            {{-- ========================= --}}

            {{-- Add Schedule Modal --}}
            <div
                x-data="{ open: false }"
                x-on:open-modal.window="if ($event.detail.name === 'add-schedule') open = true"
                x-on:close-modal.window="if ($event.detail.name === 'add-schedule') open = false"
                x-show="open"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
            >
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Add Schedule</h2>
                            <p class="mt-1 text-xs text-slate-500 leading-snug">
                                Create a new schedule entry for this section.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                            x-on:click="open = false"
                        >
                            <i class="bi bi-x-lg text-xs text-slate-500"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <form
                        action="{{ route('classroom.instructor.sections.schedules.store', $section) }}"
                        method="POST"
                        class="space-y-4"
                    >
                        @csrf

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-slate-700">
                                Day of Week
                            </label>
                            <select
                                name="day_of_week"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                required
                            >
                                <option value="">Select day…</option>
                                @foreach ($daysOfWeek as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-3">
                            <div class="flex-1 space-y-1.5">
                                <label class="block text-xs font-medium text-slate-700">
                                    Start Time
                                </label>
                                <input
                                    type="time"
                                    name="start_time"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                    required
                                >
                                @error('start_time')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex-1 space-y-1.5">
                                <label class="block text-xs font-medium text-slate-700">
                                    End Time
                                </label>
                                <input
                                    type="time"
                                    name="end_time"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                    required
                                >
                                @error('end_time')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-slate-700">
                                Room
                            </label>
                            <select
                                name="room_id"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                required
                            >
                                <option value="">Select room…</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                x-on:click="open = false"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                            >
                                Save Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Edit Schedule Modals --}}
            @foreach ($section->schedules as $schedule)
                <div
                    x-data="{ open: false }"
                    x-on:open-modal.window="if ($event.detail.name === 'edit-schedule-{{ $schedule->id }}') open = true"
                    x-on:close-modal.window="if ($event.detail.name === 'edit-schedule-{{ $schedule->id }}') open = false"
                    x-show="open"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
                >
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">Edit Schedule</h2>
                                <p class="mt-1 text-xs text-slate-500 leading-snug">
                                    Update this schedule entry.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                                x-on:click="open = false"
                            >
                                <i class="bi bi-x-lg text-xs text-slate-500"></i>
                                <span class="sr-only">Close</span>
                            </button>
                        </div>

                        <form
                            action="{{ route('classroom.instructor.sections.schedules.update', [$section, $schedule]) }}"
                            method="POST"
                            class="space-y-4"
                        >
                            @csrf
                            @method('PUT')

                            <div class="space-y-1.5">
                                <label class="block text-xs font-medium text-slate-700">
                                    Day of Week
                                </label>
                                <select
                                    name="day_of_week"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                    required
                                >
                                    <option value="">Select day…</option>
                                    @foreach ($daysOfWeek as $value => $label)
                                        <option value="{{ $value }}" @selected($schedule->day_of_week == $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex gap-3">
                                <div class="flex-1 space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-700">
                                        Start Time
                                    </label>
                                    <input
                                        type="time"
                                        name="start_time"
                                        value="{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}"
                                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                        required
                                    >
                                    @error('start_time')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex-1 space-y-1.5">
                                    <label class="block text-xs font-medium text-slate-700">
                                        End Time
                                    </label>
                                    <input
                                        type="time"
                                        name="end_time"
                                        value="{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}"
                                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                        required
                                    >
                                    @error('end_time')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-medium text-slate-700">
                                    Room
                                </label>
                                <select
                                    name="room_id"
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                    required
                                >
                                    <option value="">Select room…</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" @selected($schedule->room_id == $room->id)>
                                            {{ $room->room_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-3">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    x-on:click="open = false"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach

            {{-- ========================= --}}
            {{-- MODALS: ADD STUDENT, IMPORT CSV --}}
            {{-- ========================= --}}

            {{-- Add Student Modal --}}
            <div
                x-data="{
                    modalOpen: false,
                    allStudents: window.attendifySectionStudents || [],
                    query: '',
                    suggestions: [],
                    showList: false,
                    focused: false,

                    updateSuggestions() {
                        const q = this.query.toLowerCase().trim();

                        if (!q) {
                            this.suggestions = this.allStudents.slice(0, 5);
                            this.showList = this.suggestions.length > 0;
                            return;
                        }

                        this.suggestions = this.allStudents
                            .filter(s =>
                                s.student_number.toLowerCase().includes(q) ||
                                s.name.toLowerCase().includes(q)
                            )
                            .slice(0, 5);

                        this.showList = this.suggestions.length > 0;
                    },

                    choose(student) {
                        this.query = student.student_number;
                        this.showList = false;
                        this.focused = false;
                        if (this.$refs.studentInput) {
                            this.$refs.studentInput.blur();
                        }
                    },

                    init() {
                        this.updateSuggestions();
                    },
                }"
                x-on:open-modal.window="if ($event.detail.name === 'add-student') modalOpen = true"
                x-on:close-modal.window="if ($event.detail.name === 'add-student') modalOpen = false"
                x-show="modalOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
            >
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Add Student</h2>
                            <p class="mt-1 text-xs text-slate-500 leading-snug">
                                Enroll a student by their student number. You can type or pick from the suggestions.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                            x-on:click="modalOpen = false"
                        >
                            <i class="bi bi-x-lg text-xs text-slate-500"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <form
                        action="{{ route('classroom.instructor.sections.students.store', $section) }}"
                        method="POST"
                        class="space-y-4"
                    >
                        @csrf

                        <div class="space-y-1.5 relative">
                            <label class="block text-xs font-medium text-slate-700">
                                Student Number
                            </label>
                            <input
                                type="text"
                                name="student_number"
                                x-ref="studentInput"
                                x-model="query"
                                x-on:input="updateSuggestions()"
                                x-on:focus="focused = true; updateSuggestions()"
                                x-on:blur="setTimeout(() => { focused = false; showList = false }, 100)"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                                placeholder="Search by number or name…"
                                autocomplete="off"
                                required
                            />
                            <p class="mt-1 text-[11px] text-slate-500">
                                Start typing a student number or name, then click a suggestion or press Enter.
                            </p>

                            {{-- Suggestions dropdown --}}
                            <div
                                x-show="focused && showList && suggestions.length"
                                x-transition
                                class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-52 overflow-auto text-sm"
                            >
                                <template x-for="student in suggestions" :key="student.id">
                                    <button
                                        type="button"
                                        class="w-full text-left px-3 py-1.5 hover:bg-slate-50 flex flex-col gap-0.5"
                                        x-on:click="choose(student)"
                                    >
                                        <span
                                            class="font-medium text-slate-900"
                                            x-text="student.student_number"
                                        ></span>
                                        <span
                                            class="text-xs text-slate-600"
                                            x-text="student.name"
                                        ></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                x-on:click="modalOpen = false"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                            >
                                Add Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Import Students Modal --}}
            <div
                x-data="{ open: false }"
                x-on:open-modal.window="if ($event.detail.name === 'import-students') open = true"
                x-on:close-modal.window="if ($event.detail.name === 'import-students') open = false"
                x-show="open"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
            >
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Import Roster from CSV</h2>
                            <p class="mt-1 text-xs text-slate-500 leading-snug">
                                Upload the CSV exported for this section. Only the
                                <strong>Student Number</strong> column should be edited. New numbers will be added;
                                removed numbers will be unenrolled.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                            x-on:click="open = false"
                        >
                            <i class="bi bi-x-lg text-xs text-slate-500"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <form
                        action="{{ route('classroom.instructor.sections.students.import', $section) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf

                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-slate-700">
                                CSV File
                            </label>
                            <input
                                type="file"
                                name="csv_file"
                                accept=".csv,text/csv"
                                class="block w-full text-sm text-slate-700"
                                required
                            />
                            <p class="mt-1 text-[11px] text-slate-500">
                                Use the CSV generated by the <strong>Export CSV</strong> button for this same section.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                x-on:click="open = false"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                            >
                                Import Roster
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ========================= --}}
            {{-- MODAL: VIEW ATTENDANCE --}}
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
                                    <th class="px-3 py-2 text-left">Time In</th>
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
                                        <td class="px-3 py-2 text-slate-700" x-text="record.time_in ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Attendance Photos Gallery Modal --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail.name === 'attendance-photos') open = true"
        x-on:close-modal.window="if ($event.detail.name === 'attendance-photos') open = false"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
    >
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[80vh] overflow-hidden flex flex-col"
            x-on:click.stop
        >
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Attendance Photos</h2>
                    <p class="text-xs text-slate-500">
                        Photos uploaded from the Attendance Today page, grouped by date.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                    x-on:click="open = false"
                >
                    <i class="bi bi-x-lg text-xs text-slate-500"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <div class="px-5 py-4 overflow-y-auto space-y-4">
                @forelse ($photoGroups as $date => $photos)
                    <div>
                        <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
                            {{ \Carbon\Carbon::parse($date)->format('F j, Y (l)') }}
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            @foreach ($photos as $photo)
                                <div class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                                    <img
                                        src="{{ Storage::disk('r2')->url($photo->photo_path) }}"
                                        alt="Attendance photo"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-400">
                        No attendance photos have been uploaded yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        window.attendifySectionStudents = @json($studentChoices);
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sectionPage', () => ({
                attendanceOpen: false,
                attendanceStudent: '',
                attendanceRecords: [],

                // Template URL with a placeholder we will replace in JS
                baseAttendanceUrl: "{{ route('classroom.instructor.sections.students.attendance', [
                    'section' => $section->id,
                    'student' => '__STUDENT_ID__',
                ]) }}",

                loadAttendance(studentId) {
                    const url = this.baseAttendanceUrl.replace('__STUDENT_ID__', studentId);
                    console.log('[Attendify] Fetching attendance from:', url);

                    fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('HTTP ' + res.status);
                            }
                            return res.json();
                        })
                        .then(data => {
                            this.attendanceStudent = data.student;
                            this.attendanceRecords = data.records || [];
                            this.attendanceOpen = true;
                        })
                        .catch(err => {
                            console.error('Attendance fetch error:', err);
                            this.attendanceStudent = 'Error loading records';
                            this.attendanceRecords = [];
                            this.attendanceOpen = true;
                        });
                }
            }));
        });
    </script>
</x-app-layout>
