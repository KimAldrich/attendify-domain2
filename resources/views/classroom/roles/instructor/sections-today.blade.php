{{-- resources/views/classroom/roles/instructor/sections-today.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Classes Today</h1>
                <p class="text-sm text-slate-500">
                    Access all sections and class rosters for the classes you're teaching today. Manage the attendance records per selected section.
                </p>
            </div>
            {{-- Breadcrumbs --}}
            <x-breadcrumbs :items="[
                ['label' => 'Today\'s Classes',]
            ]" />

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">
                        Today’s Classes
                    </h2>
                    <p class="text-sm text-slate-500">
                        All class sections scheduled for today, ordered by start time.
                    </p>
                </div>
                <div class="text-sm text-slate-500">
                    {{ $today->format('l, F j, Y') }}
                </div>
            </div>

            @if ($sections->isEmpty())
                <div class="bg-white rounded-xl shadow border border-slate-200 px-4 py-6 text-center text-slate-400 text-sm">
                    You have no scheduled classes today.
                </div>
            @else
                <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-xs font-semibold text-slate-600 uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2 text-left">Course</th>
                                <th class="px-4 py-2 text-left">Section</th>
                                <th class="px-4 py-2 text-left">Room</th>
                                <th class="px-4 py-2 text-left">Time</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sections as $section)
                                @php
                                    $schedule = $section->schedules->first();
                                    $room     = $schedule?->room;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900">
                                            {{ $section->course_code }} • {{ $section->course_name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $section->section_label }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $room?->room_number ?? '—' }}
                                        @if ($room)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                                                {{ $room->is_face_recognition_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-600' }}">
                                                {{ $room->is_face_recognition_enabled ? 'Face-Recog: Enabled' : 'Face-Recog: Disabled' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        @if ($schedule)
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                            –
                                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('classroom.instructor.attendance-today', $section) }}"
                                           class="inline-flex items-center rounded-md border border-slate-200 text-xs px-3 py-1.5 text-slate-700 hover:bg-slate-50">
                                            Open Attendance
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
