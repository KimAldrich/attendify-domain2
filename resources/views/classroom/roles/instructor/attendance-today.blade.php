{{-- resources/views/classroom/roles/instructor/attendance-today.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div
            class="max-w-6xl mx-auto space-y-6"
            x-data="{ tab: 'pending' }"
        >
            
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Classes Today</h1>
                <p class="text-sm text-slate-500">
                    Access all sections and class rosters for the classes you're teaching today. Manage the attendance records per selected section.
                </p>
            </div>
            {{-- Breadcrumbs --}}
            <x-breadcrumbs :items="[
                ['label' => 'Today\'s Classes', 'url' => route('classroom.instructor.sections-today')],
                ['label' => $section->course_code . ' • ' . $section->course_name . ' (' . $section->section_label . ')'],
            ]" />

            {{-- Header --}}
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        Attendance Today
                    </h2>
                    <p class="text-sm text-slate-500">
                        {{ $section->course_code }} • {{ $section->course_name }} — Section {{ $section->section_label }}
                    </p>
                    <p class="text-xs text-slate-400">
                        {{ $today->format('l, F j, Y') }}
                    </p>
                </div>

                <div class="text-sm text-slate-500">
                    @php
                        $schedule = $section->schedules->first();
                        $room     = $schedule?->room;
                    @endphp

                    @if ($schedule)
                        <div class="text-right">
                            <div class="font-medium text-slate-700">
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                –
                                {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Room: {{ $room?->room_number ?? '—' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Face-recognition banner --}}
            <div class="rounded-xl border px-4 py-3 text-sm flex items-start gap-3
                {{ $room && $room->is_face_recognition_enabled ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                <div class="mt-0.5">
                    <i class="bi {{ $room && $room->is_face_recognition_enabled ? 'bi-camera-video' : 'bi-exclamation-triangle' }}"></i>
                </div>
                <div>
                    @if ($room && $room->is_face_recognition_enabled)
                        <div class="font-semibold">
                            Face-recognition is enabled for this room today.
                        </div>
                        <p class="text-xs mt-0.5">
                            Any face-recognition program can safely update the same attendance records used by this page
                            (keyed by section, student, and meeting date).
                        </p>
                    @else
                        <div class="font-semibold">
                            Face-recognition is currently not enabled for this room.
                        </div>
                        <p class="text-xs mt-0.5">
                            You can still mark attendance manually. If a Python face-recognition system is used later, it
                            should also upsert the same records, not create duplicates.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Summary cards --}}
            <div class="grid gap-4 md:grid-cols-5">
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">No Record Yet</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ $summaryNoRecord }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Present</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-600">
                        {{ $summaryPresent }}
                    </div>
                    <div class="mt-1 text-[11px] text-slate-500">
                        Overall present+tardy: <span class="font-semibold">{{ $summaryAttendancePct }}%</span>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Tardy</div>
                    <div class="mt-1 text-2xl font-semibold text-orange-500">
                        {{ $summaryTardy }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Excused</div>
                    <div class="mt-1 text-2xl font-semibold text-amber-500">
                        {{ $summaryExcused }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Absent</div>
                    <div class="mt-1 text-2xl font-semibold text-red-500">
                        {{ $summaryAbsent }}
                    </div>
                </div>
            </div>

            {{-- Class photo uploader --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-3 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-1">
                        <i class="bi bi-image text-slate-500"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-900">
                            Attendance Photo for Today
                        </div>
                        <p class="text-xs text-slate-500">
                            Upload a class photo (max 8 MB). Multiple photos per day are allowed for history.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    @if ($todayPhoto)
                        <div class="w-24 h-16 rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                            <img
                                src="{{ Storage::disk('r2')->url($todayPhoto->photo_path) }}"
                                alt="Class photo"
                                class="w-full h-full object-cover"
                            >
                        </div>
                    @endif

                    <form action="{{ route('classroom.instructor.sections.photos.store', $section) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="flex items-center gap-2 text-xs">
                        @csrf
                        <input type="file"
                               name="photo"
                               accept="image/*"
                               class="block w-44 text-xs text-slate-700" required>
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-slate-900 text-white px-3 py-1.5 font-semibold hover:bg-slate-800">
                            Upload
                        </button>
                    </form>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <div class="border-b border-slate-200">
                    <nav class="flex">
                        <button
                            type="button"
                            class="flex-1 px-4 py-2.5 text-sm font-medium border-b-2"
                            :class="tab === 'pending'
                                ? 'border-[#0052CC] text-[#0052CC] bg-slate-50'
                                : 'border-transparent text-slate-500 hover:text-slate-700'"
                            x-on:click="tab = 'pending'"
                        >
                            Pending Attendance
                            <span class="ml-1 text-xs text-slate-400">({{ $pending->total() }})</span>
                        </button>
                        <button
                            type="button"
                            class="flex-1 px-4 py-2.5 text-sm font-medium border-b-2"
                            :class="tab === 'recorded'
                                ? 'border-[#0052CC] text-[#0052CC] bg-slate-50'
                                : 'border-transparent text-slate-500 hover:text-slate-700'"
                            x-on:click="tab = 'recorded'"
                        >
                            Recorded Attendance
                            <span class="ml-1 text-xs text-slate-400">({{ $recorded->total() }})</span>
                        </button>
                    </nav>
                </div>

                {{-- Pending table --}}
                <div x-show="tab === 'pending'" x-cloak>
                    <div class="px-4 py-3 border-b border-slate-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">
                            Students without attendance record yet
                        </h3>

                        <form method="GET" class="flex items-center gap-2">
                            {{-- Keep recorded filters when switching --}}
                            <input type="hidden" name="recorded_q" value="{{ $recordedQuery }}">
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                            <input type="hidden" name="tab" value="pending">

                            <input type="text"
                                   name="pending_q"
                                   value="{{ $pendingQuery }}"
                                   placeholder="Search name or email…"
                                   class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]">
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-slate-900 text-white text-xs font-semibold px-3 py-1.5 hover:bg-slate-800">
                                Filter
                            </button>
                        </form>
                    </div>

                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-600 uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2 text-left">Student #</th>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                                <th class="px-4 py-2 text-left">Excuse Letter</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pending as $row)
                                @php
                                    $student = $row['student'];
                                    $excuse  = $excuseLetters->get($student->id);
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-slate-700">
                                        {{ $student->student_number ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 font-medium text-slate-900">
                                        {{ $student->full_name }}
                                        <div class="text-xs text-slate-500">
                                            {{ $student->email }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex flex-wrap gap-2">
                                            {{-- Present --}}
                                            <form method="POST"
                                                  action="{{ route('classroom.instructor.sections.attendance.mark', [$section, $student]) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="present">
                                                <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                                        {{ $isWithinGrace ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                                    Present
                                                </button>
                                            </form>

                                            {{-- Tardy (only highlighted after grace period, but still usable anytime) --}}
                                            <form method="POST"
                                                  action="{{ route('classroom.instructor.sections.attendance.mark', [$section, $student]) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="tardy">
                                                <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                                        {{ $isAfterGrace ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                                    Tardy
                                                </button>
                                            </form>

                                            {{-- Excused --}}
                                            <form method="POST"
                                                  action="{{ route('classroom.instructor.sections.attendance.mark', [$section, $student]) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="excused">
                                                <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 hover:bg-amber-200">
                                                    Excused
                                                </button>
                                            </form>

                                            {{-- Absent --}}
                                            <form method="POST"
                                                  action="{{ route('classroom.instructor.sections.attendance.mark', [$section, $student]) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="absent">
                                                <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200">
                                                    Absent
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-slate-700">
                                        @if ($excuse)
                                            {{-- You can later wire this to a modal; for now just a download link --}}
                                            <a href="{{ Storage::disk('r2')->url($excuse->file_path) }}"
                                               target="_blank"
                                               class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                                                Letter
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400">No letter</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-slate-400 text-sm">
                                        All students have an attendance record for today.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="border-t border-slate-200 px-4 py-3">
                        <x-table-footer :paginator="$pending" />
                    </div>
                </div>

                {{-- Recorded table --}}
                <div x-show="tab === 'recorded'" x-cloak>
                    <div class="px-4 py-3 border-b border-slate-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">
                            Students with attendance record today
                        </h3>

                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            {{-- Keep pending filters when switching --}}
                            <input type="hidden" name="pending_q" value="{{ $pendingQuery }}">
                            <input type="hidden" name="tab" value="recorded">

                            <input type="text"
                                   name="recorded_q"
                                   value="{{ $recordedQuery }}"
                                   placeholder="Search name or email…"
                                   class="w-48 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]">

                            <select name="status"
                                    class="rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]">
                                <option value="">All statuses</option>
                                <option value="present" @selected($statusFilter === 'present')>Present</option>
                                <option value="tardy" @selected($statusFilter === 'tardy')>Tardy</option>
                                <option value="excused" @selected($statusFilter === 'excused')>Excused</option>
                                <option value="absent" @selected($statusFilter === 'absent')>Absent</option>
                            </select>

                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-slate-900 text-white text-xs font-semibold px-3 py-1.5 hover:bg-slate-800">
                                Apply
                            </button>
                        </form>
                    </div>

                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-600 uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2 text-left">Student #</th>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Time Arrived</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recorded as $row)
                                @php
                                    $student = $row['student'];
                                    $record  = $row['record'];
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-slate-700">
                                        {{ $student->student_number ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 font-medium text-slate-900">
                                        {{ $student->full_name }}
                                        <div class="text-xs text-slate-500">
                                            {{ $student->email }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        @php
                                            $badgeClasses = match ($record->status) {
                                                'present' => 'bg-emerald-100 text-emerald-700',
                                                'tardy'   => 'bg-orange-100 text-orange-700',
                                                'excused' => 'bg-amber-100 text-amber-700',
                                                'absent'  => 'bg-red-100 text-red-700',
                                                default   => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $badgeClasses }}">
                                            {{ ucfirst($record->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-slate-700">
                                        @if (in_array($record->status, ['present', 'tardy'], true) && $record->time_in)
                                            {{ \Carbon\Carbon::parse($record->time_in)->format('g:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST"
                                              action="{{ route('classroom.instructor.sections.attendance.reset', [$section, $student]) }}"
                                              onsubmit="return confirm('Reset today\'s attendance for this student?');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border border-slate-200 text-slate-700 hover:bg-slate-50">
                                                Reset Record
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-slate-400 text-sm">
                                        No attendance records for today yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="border-t border-slate-200 px-4 py-3">
                        <x-table-footer :paginator="$recorded" />
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
