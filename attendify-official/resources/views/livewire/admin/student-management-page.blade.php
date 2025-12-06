{{-- resources/views/livewire/admin/student-management-page.blade.php --}}
<div>
    <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/80 dark:border-slate-700">

        {{-- Header: title + search --}}
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                    Students
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Search by name, email, or student number to manage their records.
                </p>
            </div>
            <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-2 sm:items-center">
                <div class="w-full sm:w-72">
                    <label class="sr-only" for="student-search">Search students</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-2 flex items-center text-slate-400">
                            <i class="bi bi-search text-sm"></i>
                        </span>
                        <input
                            id="student-search"
                            type="text"
                            wire:model.defer="search"
                            class="form-input w-full pl-8 text-sm"
                            placeholder="Search by name, email, or student no."
                        >
                    </div>
                </div>

                {{-- New Department filter --}}
                <div class="w-full sm:w-48">
                    <label class="sr-only" for="department-filter">Filter by department</label>
                    <select
                        id="department-filter"
                        wire:model.defer="departmentId"
                        class="form-select w-full text-sm"
                    >
                        <option value="">All departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">
                                {{ $dept->name ?: $dept->abbrev }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 justify-end">
                    <button
                        type="button"
                        wire:click="applyFilters"
                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 text-white text-xs font-medium hover:bg-blue-700"
                    >
                        <i class="bi bi-funnel me-1 text-[11px]"></i>
                        Filter
                    </button>
                    @if($search !== '' || $departmentId)
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="inline-flex items-center px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="px-4 sm:px-6 py-4 bg-slate-50 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-700">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Total students --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-700 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">
                        Total Students
                    </p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">
                        {{ number_format($totalStudents) }}
                    </p>
                </div>

                {{-- Not yet verified (info_status = 0) --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-700 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">
                        Not Yet Verified
                    </p>
                    <p class="mt-1 text-xl font-semibold text-amber-700 dark:text-amber-400">
                        {{ number_format($totalUnverified) }}
                    </p>
                </div>

                {{-- No face-recognition photo --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-700 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">
                        No Face Image
                    </p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">
                        {{ number_format($totalNoFace) }}
                    </p>
                </div>

                {{-- Verified + has face image --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-700 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold">
                        Verified &amp; With Face
                    </p>
                    <p class="mt-1 text-xl font-semibold text-emerald-700 dark:text-emerald-400">
                        {{ number_format($totalVerifiedWithFace) }}
                        <span class="text-xs font-normal text-slate-500 dark:text-slate-400">
                            ({{ $verifiedWithFacePercentage }}%)
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="px-4 sm:px-6 py-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                            Name
                        </th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                            Student No.
                        </th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                            Department
                        </th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                            Info Status
                        </th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                            Face Image
                        </th>
                        <th class="px-3 py-2 text-right text-[11px] font-semibold tracking-wide text-slate-500 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($students as $student)
                        @php
                            $displayName = $student->display_name
                                ?? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
                                ?: ($student->name ?? $student->email);

                            $hasNumber = !empty($student->student_number);
                            $infoOk    = (int)($student->info_status ?? 0) === 1;
                            $hasFace   = !empty($student->face_recognition_path);
                            
                            $deptLabel = $student->studentDepartment?->name
                                ?: $student->studentDepartment?->abbrev;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-3 py-2 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-medium text-slate-900 dark:text-slate-100">
                                        {{ $displayName }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $student->email }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-3 py-2 align-middle text-slate-900 dark:text-slate-100">
                                {{ $hasNumber ? $student->student_number : '—' }}
                            </td>

                            <td class="px-3 py-2 align-middle text-slate-900 dark:text-slate-100">
                                {{ $deptLabel ?: '—' }}
                            </td>

                            <td class="px-3 py-2 align-middle">
                                @if($infoOk)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Incomplete
                                    </span>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-middle">
                                @if($hasFace)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <i class="bi bi-person-bounding-box mr-1.5 text-[12px]"></i>
                                        On file
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-50 text-slate-500 border border-slate-100">
                                        <i class="bi bi-dash-circle mr-1.5 text-[12px]"></i>
                                        None
                                    </span>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-middle text-right">
                                <div class="inline-flex flex-wrap justify-end gap-1.5">
                                    {{-- View image --}}
                                    <button
                                        type="button"
                                        class="px-2.5 py-1 rounded-md border border-slate-200 text-[11px] font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                        wire:click="openViewImage({{ $student->id }})"
                                        @if(! $hasFace) disabled @endif
                                    >
                                        <i class="bi bi-eye text-[12px] mr-1"></i>
                                        View
                                    </button>

                                    {{-- Delete face image --}}
                                    <button
                                        type="button"
                                        class="px-2.5 py-1 rounded-md border border-rose-200 text-[11px] font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 disabled:opacity-40 disabled:cursor-not-allowed"
                                        wire:click="deleteFaceImage({{ $student->id }})"
                                        @if(! $hasFace) disabled @endif
                                    >
                                        <i class="bi bi-trash text-[12px] mr-1"></i>
                                        Delete image
                                    </button>

                                    {{-- Unlink student no + info_status + face image --}}
                                    <button
                                        type="button"
                                        class="px-2.5 py-1 rounded-md border border-amber-300 text-[11px] font-medium text-amber-800 bg-amber-50 hover:bg-amber-100 disabled:opacity-40 disabled:cursor-not-allowed"
                                        wire:click="unlinkStudent({{ $student->id }})"
                                        @if(! $hasNumber && ! $hasFace && ! $infoOk) disabled @endif
                                    >
                                        <i class="bi bi-link-45deg text-[12px] mr-1"></i>
                                        Unlink
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">
                                No students found. Try adjusting your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <span>Rows per page:</span>
                    <select
                        wire:model.live="perPage"
                        class="form-select w-20 text-xs"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                    <span>
                        @if($students->total() > 0)
                            {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
                        @else
                            0 of 0
                        @endif
                    </span>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="previousPage"
                            class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                            @if(! $students->previousPageUrl()) disabled @endif
                        >
                            ‹
                        </button>
                        <button
                            type="button"
                            wire:click="nextPage"
                            class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                            @if(! $students->nextPageUrl()) disabled @endif
                        >
                            ›
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Face Image Modal, still teleported --}}
    <div wire:teleport="#modal-root">
        <div
            x-data="{ open: false }"
            x-cloak
            x-on:open-modal.window="
                if ($event.detail.name === 'view-face-image') {
                    open = true;
                }
            "
            x-on:close-modal.window="
                if ($event.detail.name === 'view-face-image') {
                    open = false;
                }
            "
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-[2100] bg-black/70 flex items-center justify-center px-4"
        >
            @php
                $faceUrl = null;
                if ($currentViewUser && $currentViewUser->face_recognition_path) {
                    $faceUrl = Storage::disk('r2')->url($currentViewUser->face_recognition_path);
                }
            @endphp

            <div
                class="bg-slate-900/90 backdrop-blur rounded-xl shadow-2xl max-w-[90vw] max-h-[85vh] w-full sm:w-auto p-3 flex flex-col gap-3"
                x-on:click.stop
            >
                <div class="flex items-center justify-between text-slate-100 text-sm">
                    <div>
                        <p class="font-semibold">
                            Facial-recognition image
                        </p>
                        @if($currentViewUser)
                            <p class="text-[11px] text-slate-300">
                                {{ $currentViewUser->display_name ?? $currentViewUser->name ?? $currentViewUser->email }}
                            </p>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-800"
                        x-on:click="open = false"
                    >
                        <i class="bi bi-x-lg text-xs"></i>
                        <span class="sr-only">Close</span>
                    </button>
                </div>

                @if($faceUrl)
                    <img
                        src="{{ $faceUrl }}"
                        alt="Facial recognition image"
                        class="max-w-[82vw] max-h-[70vh] rounded-lg object-contain bg-black/40"
                    />
                @else
                    <div class="py-8 px-6 text-center text-sm text-slate-300">
                        No facial-recognition image available for this student.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
