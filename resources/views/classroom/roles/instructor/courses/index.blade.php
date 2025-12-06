{{-- resources/views/classroom/roles/instructor/courses/index.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Manage Classrooms</h1>
                <p class="text-sm text-slate-500">
                    Manage all data about your courses and sections, classroom schedules, and class rosters.
                </p>
            </div>
            {{-- Breadcrumbs --}}
            <x-breadcrumbs :items="[
                ['label' => 'Courses'],
            ]" />

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        My Courses
                    </h2>
                    <p class="text-sm text-slate-500">
                        All course sections assigned to you under the selected academic period.
                    </p>
                </div>

                {{-- Add New Section --}}
                <button type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', { name: 'create-section' })"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0052CC] text-white text-sm font-medium px-4 py-2 shadow-sm hover:bg-[#003fa3]">
                    <span class="bi bi-plus-lg"></span>
                    Add New Section
                </button>
            </div>

            {{-- Filters --}}
            <form method="GET"
                  class="bg-white rounded-xl shadow border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Period selector --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide">
                            Academic Year &amp; Term
                        </label>
                        <select name="period_id"
                                class="mt-1 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]">
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}"
                                    @selected($selectedPeriod && $selectedPeriod->id === $period->id)>
                                    {{ $period->display_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-xs text-slate-500 mt-1">
                        <div class="font-semibold text-slate-600 uppercase tracking-wide">
                            Instructor
                        </div>
                        <div class="mt-0.5 text-[13px] text-slate-700">
                            {{ $instructor->full_name }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Filter by course code, name, or section…"
                        class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                    >
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-slate-900 text-white text-xs font-semibold px-3 py-2 hover:bg-slate-800">
                        Search
                    </button>
                </div>
            </form>

            {{-- Sections table --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Course Code</th>
                            <th class="px-4 py-3 text-left">Course Name</th>
                            <th class="px-4 py-3 text-left">Section</th>
                            <th class="px-4 py-3 text-center">No. of Students</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sections as $section)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $section->course_code }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $section->course_name }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $section->section_label }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-900">
                                    {{ $section->students_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-2">
                                        <a href="{{ route('classroom.instructor.sections.show', $section) }}"
                                            class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50">
                                            Manage Section
                                        </a>

                                        <button type="button"
                                            x-data
                                            x-on:click="$dispatch('open-modal', { name: 'edit-section-{{ $section->id }}' })"
                                            class="inline-flex items-center rounded-md border border-amber-200 text-xs px-2 py-1.5 text-amber-700 hover:bg-amber-50">
                                            Edit
                                        </button>

                                        <form action="{{ route('classroom.instructor.courses.destroy', $section) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this section?');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center rounded-md border border-red-200 text-xs px-2 py-1.5 text-red-700 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400 text-sm">
                                    No sections found{{ $search ? ' for this search.' : ' with the current context.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <x-table-footer :paginator="$sections" />
            </div>

            {{-- WEEKLY TIMETABLE --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Weekly Schedule (6:00 AM – 6:00 PM)
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Each blue strip represents one of your scheduled classes. Overlapping classes stack vertically.
                        </p>
                    </div>
                </div>

                @php
                    $hours = range(6, 18);
                    $hasAnySchedule = collect($timetable ?? [])->flatMap(fn($d) => $d['blocks'])->isNotEmpty();
                @endphp

                @if (! $hasAnySchedule)
                    <div class="px-4 py-6 text-center text-slate-400 text-sm">
                        No schedules found for the selected period.
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
                                        $laneCount   = $day['lane_count'] ?? 1;
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
    </div>

    @php
        $courseMap = $coursePresets->pluck('course_name', 'course_code')->toArray();

        $createSectionState = [
            'map'        => $courseMap,
            'courseCode' => old('course_code', ''),
            'courseName' => old('course_name', ''),
            'open'       => ($errors->has('course_code') || $errors->has('course_name') || $errors->has('section_label')),
        ];
    @endphp

    {{-- Create Section Modal --}}
    <div
        x-data='@json($createSectionState)'
        x-on:open-modal.window="if ($event.detail.name === 'create-section') open = true"
        x-on:close-modal.window="if ($event.detail.name === 'create-section') open = false"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
    >
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Add New Section</h2>
                    <p class="mt-1 text-xs text-slate-500 leading-snug">
                        Create a new section under the selected academic period.
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

            <form action="{{ route('classroom.instructor.courses.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Context --}}
                <input type="hidden" name="academic_period_id" value="{{ optional($selectedPeriod)->id }}">

                {{-- Course Code --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Course Code
                    </label>
                    <input
                        type="text"
                        name="course_code"
                        x-model="courseCode"
                        x-on:change="if (map[courseCode]) courseName = map[courseCode]"
                        list="course-code-list"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="e.g. IT 321"
                        required
                    />
                    <datalist id="course-code-list">
                        @foreach ($coursePresets as $preset)
                            <option value="{{ $preset->course_code }}">
                                {{ $preset->course_code }} — {{ $preset->course_name }}
                            </option>
                        @endforeach
                    </datalist>
                    @error('course_code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-[11px] text-slate-500">
                        Pick an existing code from the list or type a new one.
                    </p>
                </div>

                {{-- Course Name --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Course Name
                    </label>
                    <input
                        type="text"
                        name="course_name"
                        x-model="courseName"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="e.g. Web Systems & Technologies"
                        required
                    />
                    @error('course_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Section label --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Section Label
                    </label>
                    <input
                        type="text"
                        name="section_label"
                        value="{{ old('section_label') }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="e.g. BSIT-3A"
                        required
                    />
                    @error('section_label')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
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
                        class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]">
                        Save Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Section Modals --}}
    @foreach ($sections as $section)
        @php
            $editState = [
                'map'        => $courseMap,
                'courseCode' => $section->course_code,
                'courseName' => $section->course_name,
                'open'       => false,
            ];
        @endphp

        <div
            x-data='@json($editState)'
            x-on:open-modal.window="if ($event.detail.name === 'edit-section-{{ $section->id }}') open = true"
            x-on:close-modal.window="if ($event.detail.name === 'edit-section-{{ $section->id }}') open = false"
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Edit Section</h2>
                        <p class="mt-1 text-xs text-slate-500 leading-snug">
                            Update the course code, name, or section label.
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

                <form action="{{ route('classroom.instructor.courses.update', $section) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Course Code
                        </label>
                        <input
                            type="text"
                            name="course_code"
                            x-model="courseCode"
                            x-on:change="if (map[courseCode]) courseName = map[courseCode]"
                            list="course-code-list"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Course Name
                        </label>
                        <input
                            type="text"
                            name="course_name"
                            x-model="courseName"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Section Label
                        </label>
                        <input
                            type="text"
                            name="section_label"
                            value="{{ $section->section_label }}"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            required
                        />
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
                            class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</x-app-layout>
