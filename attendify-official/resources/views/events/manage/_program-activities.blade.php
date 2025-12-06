{{-- resources/views/events/manage/_program-activities.blade.php --}}

@php
    /** @var \App\Models\Event $event */
    /** @var \Illuminate\Support\Collection|\App\Models\EventTrack[] $tracks */

    // Days, sorted by date
    $days = $event->days
        ? $event->days->sortBy('date')
        : collect();

    $tracksById = $tracks->keyBy('id');

    $flash = session('activity_flash');
@endphp

<div
    class="border border-slate-200 rounded-lg bg-white p-4"
    x-data="programActivities({
        initialDayId: {{ $days->isNotEmpty() ? $days->first()->id : 'null' }},
    })"
    x-init="initFromHash()"
    x-cloak
>
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-slate-900">Program activities</h2>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('events.manage.program.csv', $event) }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md border border-slate-300
                       text-[11px] text-slate-700 bg-white hover:bg-slate-50"
            >
                Export CSV
            </a>
            <a
                href="{{ route('events.manage.program.export-pdf', $event) }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md border border-slate-300
                       text-xs font-medium text-slate-700 bg-white hover:bg-slate-50"
                target="_blank"
            >
                <i class="bi bi-file-earmark-pdf text-xs mr-1"></i>
                Export as PDF
            </a>
        </div>
    </div>

    @if ($days->isEmpty())
        <p class="text-xs text-slate-600">
            No event days yet. Use the <span class="font-medium">Event Day Selector</span> above to add days,
            then you’ll be able to schedule activities here.
        </p>
    @else
        {{-- Day tabs (modern style + horizontal scroll) --}}
        @if ($days->count() > 1)
            <div class="flex flex-wrap items-center justify-between mb-3 gap-3">
                {{-- Scrollable day tabs --}}
                <div class="flex-1 min-w-0 border-b border-slate-200">
                    <div class="overflow-x-auto overflow-y-hidden  thin-scrollbar">
                        <nav
                            class="-mb-px flex flex-nowrap gap-1 min-w-max"
                            aria-label="Program day tabs"
                        >
                            @foreach ($days as $navDay)
                                @php
                                    /** @var \App\Models\EventDay $navDay */
                                    $navDate  = optional($navDay->date);
                                    $navLabel = $navDate
                                        ? $navDate->format('M d')
                                        : 'Day ' . $navDay->order_index;
                                @endphp

                                <button
                                    type="button"
                                    @click="activeDayId = {{ $navDay->id }}; showAll = false"
                                    class="inline-flex items-center px-3 py-2 text-xs font-medium border-b-2 rounded-t-md
                                           transition whitespace-nowrap"
                                    :class="(activeDayId === {{ $navDay->id }} && !showAll)
                                        ? 'border-[#0052CC] text-[#0052CC] bg-white'
                                        : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 bg-transparent'"
                                >
                                    <span>{{ $navLabel }}</span>
                                </button>
                            @endforeach
                        </nav>
                    </div>
                </div>

                {{-- Show all toggle --}}
                <div class="flex items-center">
                    <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 whitespace-nowrap">
                        <input
                            type="checkbox"
                            x-model="showAll"
                            class="rounded border-slate-300 text-[#0052CC] focus:ring-[#0052CC]"
                        >
                        <span>Show all days</span>
                    </label>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @foreach ($days as $day)
                @php
                    /** @var \App\Models\EventDay $day */

                    $dayActivities = ($day->activities ?? collect())->sortBy(function ($activity) {
                        return optional($activity->start_time)->format('H:i') ?? '00:00';
                    });

                    $date = optional($day->date);
                    $dayLabel = $date
                        ? $date->format('D, M d, Y')
                        : 'Unscheduled day';
                @endphp

                <div
                    id="day-{{ $day->id }}"
                    data-day-id="{{ $day->id }}"
                    data-day-wrapper
                    class="border-2 border-slate-300 rounded-lg mb-4 scroll-mt-24"
                    x-show="showAll || activeDayId === {{ $day->id }}"
                    x-transition
                >
                    {{-- Day header --}}
                    <div class="flex items-center justify-between px-3 py-2 bg-slate-50 border-b border-slate-200">
                        <div class="flex flex-col">
                            <span class="text-xs font-semibold text-slate-800">
                                {{ $dayLabel }}
                            </span>
                            <span class="text-[11px] text-slate-500">
                                {{ $dayActivities->count() }} activit{{ $dayActivities->count() <= 1 ? 'y' : 'ies' }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            {{-- Add activity --}}
                            <form
                                method="POST"
                                action="{{ route('events.manage.program.activities.store', $event) }}"
                            >
                                @csrf
                                <input type="hidden" name="day_id" value="{{ $day->id }}">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-slate-300
                                           text-[11px] text-slate-700 bg-white hover:bg-slate-50"
                                >
                                    + Add activity
                                </button>
                            </form>

                            {{-- Transfer all activities to another day --}}
                            @if ($days->count() > 1)
                                <form
                                    method="POST"
                                    action="{{ route('events.manage.program.days.transfer-activities', [$event, $day]) }}"
                                    class="flex items-center gap-1"
                                >
                                    @csrf

                                    <select
                                        name="target_day_id"
                                        class="rounded-md border border-slate-300 px-2 py-1 text-[11px]
                                               bg-white text-slate-700 focus:outline-none focus:ring-1
                                               focus:ring-[#0052CC] focus:border-[#0052CC]"
                                        required
                                    >
                                        <option value="">Move activities to…</option>
                                        @foreach ($days as $otherDay)
                                            @continue($otherDay->id === $day->id)
                                            @php
                                                $otherDate = optional($otherDay->date);
                                                $otherLabel = $otherDate
                                                    ? $otherDate->format('D, M d, Y')
                                                    : 'Unscheduled day #' . $otherDay->order_index;
                                            @endphp
                                            <option value="{{ $otherDay->id }}">
                                                {{ $otherLabel }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button
                                        type="submit"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-slate-300
                                               text-[11px] text-slate-700 bg-white hover:bg-slate-50"
                                    >
                                        Transfer
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Day-level flash (for delete/transfer) --}}
                    @if ($flash && $flash['mode'] === 'day' && (int) $flash['day_id'] === $day->id)
                        <div class="my-2 mx-2 rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-[11px] text-emerald-800">
                            {{ $flash['message'] }}
                        </div>
                    @endif

                    {{-- Day activities --}}
                    <div class="px-3 py-2">
                        @if ($dayActivities->isEmpty())
                            <p class="text-[11px] text-slate-500">
                                No activities for this day yet. Use <span class="font-medium">Add activity</span> to create one.
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-500 text-xs">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium">Activity List</th>
                                            <th class="px-3 py-2 text-center font-medium w-32">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    @foreach ($dayActivities as $activity)
                                        @php
                                            /** @var \App\Models\EventActivity $activity */

                                            $isErroredActivity = old('activity_id') == $activity->id;

                                            $startTimeValue = $isErroredActivity
                                                ? old('start_time')
                                                : optional($activity->start_time)->format('H:i');

                                            $endTimeValue = $isErroredActivity
                                                ? old('end_time')
                                                : optional($activity->end_time)->format('H:i');

                                            $titleValue = $isErroredActivity
                                                ? old('title')
                                                : ($activity->title ?? 'New activity');

                                            $descriptionValue = $isErroredActivity
                                                ? old('description')
                                                : $activity->description;

                                            $selectedTrackId = $isErroredActivity
                                                ? old('track_id')
                                                : $activity->track_id;

                                            $needsAnalytics = $isErroredActivity
                                                ? (bool) old('needs_analytics')
                                                : (bool) $activity->needs_analytics;
                                        @endphp

                                        <tr id="activity-{{ $activity->id }}" class="align-top scroll-mt-24">
                                            {{-- Activity card --}}
                                            <td class="px-3 py-3">
                                                <div class="rounded-md border border-slate-200 bg-slate-50/60 px-3 py-3 space-y-3">
                                                    {{-- small header for separation --}}
                                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                                        <span class="inline-flex items-center gap-1">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                                            Activity
                                                        </span>
                                                    </div>

                                                    <form
                                                        id="activity-form-{{ $activity->id }}"
                                                        method="POST"
                                                        action="{{ route('events.manage.program.activities.update', [$event, $activity]) }}"
                                                        class="space-y-3"
                                                    >
                                                        @csrf
                                                        @method('PUT')

                                                        @if ($flash && $flash['mode'] === 'activity' && (int) $flash['activity_id'] === $activity->id)
                                                            <div class="mb-2 rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-[11px] text-emerald-800">
                                                                {{ $flash['message'] }}
                                                            </div>
                                                        @endif

                                                        {{-- Title + description --}}
                                                        <div class="space-y-2">
                                                            <div>
                                                                <label
                                                                    for="title-{{ $activity->id }}"
                                                                    class="block text-[11px] font-medium text-slate-700 mb-1"
                                                                >
                                                                    Activity title
                                                                </label>
                                                                <input
                                                                    id="title-{{ $activity->id }}"
                                                                    type="text"
                                                                    name="title"
                                                                    value="{{ $titleValue }}"
                                                                    placeholder="Activity title"
                                                                    class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs
                                                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                                                >
                                                                @if ($isErroredActivity)
                                                                    @error('title')
                                                                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                                                    @enderror
                                                                @endif
                                                            </div>

                                                            <div>
                                                                <label
                                                                    for="description-{{ $activity->id }}"
                                                                    class="block text-[11px] font-medium text-slate-700 mb-1"
                                                                >
                                                                    Description
                                                                    <span class="font-normal text-slate-400">(optional)</span>
                                                                </label>
                                                                <textarea
                                                                    id="description-{{ $activity->id }}"
                                                                    name="description"
                                                                    rows="2"
                                                                    placeholder="Short description (optional)"
                                                                    class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs
                                                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                                                >{{ $descriptionValue }}</textarea>
                                                                @if ($isErroredActivity)
                                                                    @error('description')
                                                                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Time row + venue + analytics --}}
                                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                            <div class="flex flex-col gap-1">
                                                                <div class="flex items-center gap-2">
                                                                    <input
                                                                        type="time"
                                                                        name="start_time"
                                                                        value="{{ $startTimeValue }}"
                                                                        class="w-28 rounded-md border border-slate-300 px-2 py-1 text-xs
                                                                            focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                                                    >
                                                                    <span class="text-[11px] text-slate-400">–</span>
                                                                    <input
                                                                        type="time"
                                                                        name="end_time"
                                                                        value="{{ $endTimeValue }}"
                                                                        class="w-28 rounded-md border border-slate-300 px-2 py-1 text-xs
                                                                            focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                                                    >
                                                                </div>

                                                                @if ($isErroredActivity)
                                                                    @error('start_time')
                                                                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                                                    @enderror
                                                                    @error('end_time')
                                                                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                                                                    @enderror
                                                                @endif
                                                            </div>

                                                            {{-- Venue select + analytics --}}
                                                            <div class="flex-1 flex flex-wrap gap-3 items-center">
                                                                <select
                                                                    name="track_id"
                                                                    class="min-w-[360px] rounded-md border border-slate-300 px-2 py-1 text-xs
                                                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                                                >
                                                                    <option value="">Unassigned venue</option>
                                                                    @foreach ($tracks as $track)
                                                                        <option
                                                                            value="{{ $track->id }}"
                                                                            @selected($selectedTrackId == $track->id)
                                                                        >
                                                                            {{ $track->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                                <label class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                                                                    <input
                                                                        type="checkbox"
                                                                        name="needs_analytics"
                                                                        value="1"
                                                                        @checked($needsAnalytics)
                                                                        class="rounded border-slate-300 text-[#0052CC] focus:ring-[#0052CC]"
                                                                    >
                                                                    <span>Enable analytics</span>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        {{-- Hidden fields --}}
                                                        <input type="hidden" name="day_id" value="{{ $day->id }}">
                                                        <input type="hidden" name="activity_id" value="{{ $activity->id }}">

                                                        {{-- Per-activity error banner --}}
                                                        @if ($isErroredActivity && $errors->any())
                                                            <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-2 py-1.5 text-[11px] text-red-700">
                                                                <p class="font-medium">Please fix the highlighted fields for this activity.</p>
                                                            </div>
                                                        @endif
                                                    </form>
                                                </div>
                                            </td>

                                            {{-- Actions --}}
                                            <td class="px-3 py-3 align-middle text-center">
                                                <div class="flex flex-col gap-1 items-center justify-center">
                                                    <button
                                                        type="submit"
                                                        form="activity-form-{{ $activity->id }}"
                                                        class="inline-flex items-center px-3 py-1 rounded-md border border-slate-300
                                                            text-[11px] text-slate-700 bg-white hover:bg-slate-50"
                                                    >
                                                        Save
                                                    </button>

                                                    {{-- Duplicate --}}
                                                    <form
                                                        method="POST"
                                                        action="{{ route('events.manage.program.activities.duplicate', [$event, $activity]) }}"
                                                    >
                                                        @csrf
                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center px-3 py-1 rounded-md border border-slate-300
                                                                text-[11px] text-slate-700 bg-white hover:bg-slate-50"
                                                        >
                                                            Duplicate
                                                        </button>
                                                    </form>

                                                    {{-- Delete --}}
                                                    <form
                                                        method="POST"
                                                        action="{{ route('events.manage.program.activities.destroy', [$event, $activity]) }}"
                                                        onsubmit="return confirm('Delete this activity?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center px-3 py-1 rounded-md border border-red-200
                                                                text-[11px] text-red-600 bg-white hover:bg-red-50"
                                                        >
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                window.programActivities = function (config = {}) {
                    return {
                        activeDayId: config.initialDayId || null,
                        showAll: false,

                        initFromHash() {
                            const raw = window.location.hash ? window.location.hash.substring(1) : '';
                            if (!raw) return;

                            // We support #day-123 and #activity-456
                            if (!raw.startsWith('day-') && !raw.startsWith('activity-')) {
                                return;
                            }

                            const targetEl = document.getElementById(raw);
                            if (!targetEl) return;

                            const dayWrapper = targetEl.closest('[data-day-id]');
                            if (dayWrapper) {
                                const dayId = parseInt(dayWrapper.getAttribute('data-day-id'));
                                if (!Number.isNaN(dayId)) {
                                    this.activeDayId = dayId;
                                    this.showAll = false;
                                }
                            }

                            // Scroll into view after DOM/layout settles
                            requestAnimationFrame(() => {
                                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        },
                    };
                };
            });
        </script>
    @endpush
@endonce
