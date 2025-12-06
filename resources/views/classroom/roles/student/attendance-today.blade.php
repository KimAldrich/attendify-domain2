{{-- resources/views/classroom/roles/student/attendance-today.blade.php --}}
<x-app-layout>
    <div
        class="min-h-screen bg-slate-50 px-6 py-8"
        x-data="{ openExcuseModal:false, selectedSectionId:null, notes:'' }"
        x-cloak
    >
        <div class="max-w-6xl mx-auto space-y-6">

            {{-- Tab switcher: Attendance Today / Overall Attendance --}}
            @include('classroom.roles.student._tabs')

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        Attendance Today
                    </h2>
                    <p class="text-sm text-slate-500">
                        {{ $today->format('l, F j, Y') }} · Classes scheduled for today.
                    </p>
                </div>

                {{-- Upload excuse button (opens modal, section chosen in row) --}}
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0052CC] text-white text-sm font-medium px-4 py-2 shadow-sm hover:bg-[#003fa3]"
                    x-on:click="
                        selectedSectionId = null;
                        notes = '';
                        openExcuseModal = true;
                    "
                >
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    Upload Excuse for Today
                </button>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase">Classes Today</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['total'] }}</div>
                </div>

                <div class="bg-white rounded-xl border border-green-100 px-4 py-3">
                    <div class="text-xs font-semibold text-green-700 uppercase">Present</div>
                    <div class="mt-1 text-xl font-semibold text-green-700">{{ $summary['present'] }}</div>
                </div>

                <div class="bg-white rounded-xl border border-amber-100 px-4 py-3">
                    <div class="text-xs font-semibold text-amber-700 uppercase">Tardy</div>
                    <div class="mt-1 text-xl font-semibold text-amber-700">{{ $summary['tardy'] }}</div>
                </div>

                <div class="bg-white rounded-xl border border-sky-100 px-4 py-3">
                    <div class="text-xs font-semibold text-sky-700 uppercase">Excused</div>
                    <div class="mt-1 text-xl font-semibold text-sky-700">{{ $summary['excused'] }}</div>
                </div>

                <div class="bg-white rounded-xl border border-red-100 px-4 py-3">
                    <div class="text-xs font-semibold text-red-700 uppercase">Absent</div>
                    <div class="mt-1 text-xl font-semibold text-red-700">{{ $summary['absent'] }}</div>
                </div>
            </div>

            {{-- Today’s classes table --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Course</th>
                            <th class="px-4 py-3 text-left">Instructor</th>
                            <th class="px-4 py-3 text-left">Room</th>
                            <th class="px-4 py-3 text-left">Schedule</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Time Arrived</th>
                            <th class="px-4 py-3 text-right">Excuse</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($classesToday as $cls)
                            @php
                                $status = $cls->status;
                                $statusLabel = ucfirst($status);
                                $statusClasses = match ($status) {
                                    'present' => 'bg-green-100 text-green-800',
                                    'tardy'   => 'bg-amber-100 text-amber-800',
                                    'excused' => 'bg-sky-100 text-sky-800',
                                    'absent'  => 'bg-red-100 text-red-800',
                                    default   => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ $cls->section->course_name }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $cls->section->course_code }} · Section {{ $cls->section->section_label }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $cls->section->instructor->full_name ?? 'TBA' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $cls->roomLabel }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $cls->timeLabel }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $statusClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $cls->timeArrive }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($excuseLettersToday->has($cls->section->id))
                                        {{-- Letter Sent Badge --}}
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-sky-100 text-sky-800 text-xs font-medium">
                                            Letter Sent
                                        </span>
                                    @else
                                        {{-- Upload button --}}
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50"
                                            x-on:click="
                                                selectedSectionId = {{ $cls->section->id }};
                                                notes = '';
                                                openExcuseModal = true;
                                            "
                                        >
                                            Upload Letter
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-400 text-sm">
                                    No classes scheduled for today.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Excuse upload modal --}}
        <div
            x-show="openExcuseModal"
            x-transition.opacity
            class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
            x-on:click.self="openExcuseModal = false"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" x-on:click.stop>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Upload Excuse Letter (Today)
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 leading-snug">
                            Attach a clear photo or document for your excuse letter for today’s class.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                        x-on:click="openExcuseModal = false"
                    >
                        <i class="bi bi-x-lg text-xs text-slate-500"></i>
                        <span class="sr-only">Close</span>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('classroom.student.attendance-today.excuse.store') }}"
                    enctype="multipart/form-data"
                    class="space-y-4"
                >
                    @csrf

                    {{-- Section selector --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Class / Section
                        </label>
                        <select
                            name="course_section_id"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            x-model="selectedSectionId"
                        >
                            <option value="">Select class…</option>
                            @foreach ($classesToday as $cls)
                                <option value="{{ $cls->section->id }}">
                                    {{ $cls->section->course_code }} • {{ $cls->section->course_name }} ({{ $cls->section->section_label }})
                                </option>
                            @endforeach
                        </select>
                        @error('course_section_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Excuse File (max 8 MB)
                        </label>
                        <input
                            type="file"
                            name="file"
                            accept="image/*,.pdf"
                            class="block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                        />
                        @error('file')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Notes (optional)
                        </label>
                        <textarea
                            name="notes"
                            x-model="notes"
                            rows="3"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            placeholder="Briefly explain your reason…"
                        ></textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                            x-on:click="openExcuseModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                        >
                            Upload Excuse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
