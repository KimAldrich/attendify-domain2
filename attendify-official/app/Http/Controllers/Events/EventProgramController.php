<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDay;
use App\Models\EventTrack;
use App\Models\EventActivity;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventProgramController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/program
     */
    public function show(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        // days + activities + track for each activity
        $event->load([
            'days.activities.track',
        ]);

        // Tracks + activity counts for the venues sidebar
        $tracks = $event->tracks()
            ->withCount('activities')
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();

        // Existing days as Y-m-d strings (for calendar pre-selection)
        $selectedDayDates = $event->days
            ->sortBy('order_index')
            ->map(function (EventDay $day) {
                return optional($day->date)->format('Y-m-d');
            })
            ->filter()
            ->values()
            ->all();

        return view('events.manage.program', [
            'event'            => $event,
            'tracks'           => $tracks,
            'selectedDayDates' => $selectedDayDates,
        ]);
    }

    /**
     * POST /events/manage/{event}/program/days
     * Sync days with the list of marked dates.
     */
    public function storeDay(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        // Selected dates from the calendar: "YYYY-MM-DD"
        $selectedDates = collect($request->input('days', []))
            ->filter()
            ->unique()
            ->values(); // collection of strings

        // Existing days for this event (with activity counts)
        $existingDays = $event->days()->withCount('activities')->get();

        // Decide which existing days are being removed:
        // present before, missing now (compare by Y-m-d string)
        $selectedDateStrings = $selectedDates->all(); // plain array of strings

        $datesToDelete = $existingDays->filter(function (EventDay $day) use ($selectedDateStrings) {
            if (! $day->date) {
                // If somehow there's a day with null date, allow deletion through the normal path
                return true;
            }

            $dateString = $day->date->format('Y-m-d');

            return ! in_array($dateString, $selectedDateStrings, true);
        })->values();

        // Any of those have activities?
        $blocked = $datesToDelete->filter(fn ($day) => $day->activities_count > 0);

        if ($blocked->isNotEmpty()) {
            session()->flash('days_flash', [
                'level'   => 'error',
                'message' => 'Some days have activities. Please remove all activities on those days first.',
            ]);

            return back()->withFragment('days');
        }

        // 1) Delete days that are safe to delete
        $safeToDelete = $datesToDelete->pluck('id');
        if ($safeToDelete->isNotEmpty()) {
            EventDay::whereIn('id', $safeToDelete)->delete();
        }

        // 2) Create new days that don’t exist yet
        $existingDates = $existingDays
            ->pluck('date')
            ->filter()
            ->map->format('Y-m-d')
            ->all();

        foreach ($selectedDates as $date) {
            if (! in_array($date, $existingDates, true)) {
                $event->days()->create(['date' => $date]);
            }
        }

        session()->flash('days_flash', [
            'level'   => 'success',
            'message' => 'Event days updated.',
        ]);

        return back()->withFragment('days');
    }

    /**
     * POST /events/manage/{event}/program/tracks
     */
    public function storeTrack(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $data = $request->validateWithBag('tracks', [
            'name'     => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        $nextOrder = ((int) $event->tracks()->max('order_index')) + 1;

        $track = new EventTrack();
        $track->event_id    = $event->id;
        $track->name        = $data['name'];
        $track->location    = $data['location'] ?? null;
        $track->order_index = $nextOrder;
        $track->save();

        session()->flash('tracks_flash', [
            'level'   => 'success',
            'message' => 'Track added.',
        ]);

        return back()->withFragment('tracks');
    }

    /**
     * PUT /events/manage/{event}/program/tracks/{track}
     */
    public function updateTrack(Request $request, Event $event, EventTrack $track)
    {
        $this->authorize('manage', $event);

        // Safety: ensure track belongs to this event
        if ($track->event_id !== $event->id) {
            abort(404);
        }

        $data = $request->validateWithBag('tracks', [
            'name'     => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        $track->update([
            'name'     => $data['name'],
            'location' => $data['location'] ?? null,
        ]);

        session()->flash('tracks_flash', [
            'level'   => 'success',
            'message' => 'Track updated.',
        ]);

        return back()->withFragment('tracks');
    }

    /**
     * DELETE /events/manage/{event}/program/tracks/{track}
     */
    public function destroyTrack(Event $event, EventTrack $track)
    {
        $this->authorize('manage', $event);

        // ensure the track belongs to this event
        if ($track->event_id !== $event->id) {
            abort(404);
        }

        $hasActivities = $track->activities()->exists();

        if ($hasActivities) {
            session()->flash('tracks_flash', [
                'level'   => 'error',
                'message' => 'This track has activities scheduled. Move or remove those activities first.',
            ]);

            return back()->withFragment('tracks');
        }

        $track->delete();

        session()->flash('tracks_flash', [
            'level'   => 'success',
            'message' => 'Track deleted.',
        ]);

        return back()->withFragment('tracks');
    }

    /**
     * POST /events/manage/{event}/program/activities
     * Create a new activity for a given day.
     */
    public function storeActivity(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $data = $request->validate([
            'day_id' => [
                'required',
                'integer',
                Rule::exists('event_days', 'id')->where('event_id', $event->id),
            ],
        ]);

        /** @var \App\Models\EventDay $day */
        $day = EventDay::where('event_id', $event->id)->findOrFail($data['day_id']);

        // Existing activities on that day
        $existing = $day->activities()->get();

        // Determine default start & end times
        if ($existing->isNotEmpty()) {
            // Use the latest end_time among all activities that day
            $latestEnd = $existing->max('end_time');

            if ($latestEnd) {
                $start = Carbon::parse($latestEnd);
            } else {
                // Fallback: no end_time set anywhere, use latest start_time
                $latestStart = $existing->max('start_time') ?: now();
                $start = Carbon::parse($latestStart);
            }
        } else {
            // Default 08:00 on that day (or event start_at)
            $baseDate = $day->date ?? $event->start_at ?? now();
            $start = Carbon::parse($baseDate)->setTime(8, 0);
        }

        $end = (clone $start)->addHour(); // +60 mins

        // Determine default track: last track used on this day, or first event track
        $trackId = null;
        if ($existing->isNotEmpty() && $existing->last()->track_id) {
            $trackId = $existing->last()->track_id;
        } else {
            $trackId = $event->tracks()->orderBy('order_index')->value('id');
        }

        $orderIndex = ((int) $day->activities()->max('order_index')) + 1;

        $activity = new EventActivity();
        $activity->event_id        = $event->id;
        $activity->day_id          = $day->id;
        $activity->track_id        = $trackId;
        $activity->title           = 'New activity';
        $activity->description     = null;
        $activity->start_time      = $start;
        $activity->end_time        = $end;
        $activity->type            = null;
        $activity->needs_analytics = false;
        $activity->order_index     = $orderIndex;
        $activity->save();

        // Recompute event start/end based on all activities
        $this->syncEventProgramTimeBounds($event);

        session()->flash('activity_flash', [
            'mode'        => 'activity',
            'activity_id' => $activity->id,
            'level'       => 'success',
            'message'     => 'Activity added.',
        ]);

        return back()->withFragment('activity-'.$activity->id);
    }

    /**
     * PUT /events/manage/{event}/program/activities/{activity}
     * Update an existing activity.
     */
    public function updateActivity(Request $request, Event $event, EventActivity $activity)
    {
        $this->authorize('manage', $event);

        // Safety: make sure this activity belongs to the event
        if ($activity->event_id !== $event->id) {
            abort(404);
        }

        $data = $request->validate([
            'day_id' => [
                'required',
                'integer',
                Rule::exists('event_days', 'id')->where('event_id', $event->id),
            ],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'track_id'    => [
                'nullable',
                'integer',
                Rule::exists('event_tracks', 'id')->where('event_id', $event->id),
            ],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'type'        => ['nullable', 'string', 'max:50'],
            'needs_analytics' => ['nullable', 'boolean'],
            'activity_id' => ['nullable', 'integer'], // used by Blade to scope errors
        ]);

        // Day association – allow moving activity between days
        $activity->day_id = $data['day_id'];

        if (array_key_exists('title', $data)) {
            $activity->title = $data['title'] ?? $activity->title;
        }

        if (array_key_exists('description', $data)) {
            $activity->description = $data['description'] ?? null;
        }

        if (array_key_exists('track_id', $data)) {
            $activity->track_id = $data['track_id'] ?? null;
        }

        if (array_key_exists('type', $data)) {
            $activity->type = $data['type'] ?? null;
        }

        // Times: attach to the same calendar date as the (possibly new) day
        $day = EventDay::where('event_id', $event->id)->findOrFail($activity->day_id);
        $dayDate = $day->date ?? $event->start_at ?? now();

        if (!empty($data['start_time'])) {
            $activity->start_time = Carbon::parse(
                Carbon::parse($dayDate)->format('Y-m-d') . ' ' . $data['start_time']
            );
        }

        if (!empty($data['end_time'])) {
            $activity->end_time = Carbon::parse(
                Carbon::parse($dayDate)->format('Y-m-d') . ' ' . $data['end_time']
            );
        }

        $activity->needs_analytics = !empty($data['needs_analytics']);

        $activity->save();

        // Recompute event start/end based on all activities
        $this->syncEventProgramTimeBounds($event);

        session()->flash('activity_flash', [
            'mode'        => 'activity',
            'activity_id' => $activity->id,
            'level'       => 'success',
            'message'     => 'Activity updated.',
        ]);

        return back()->withFragment('activity-'.$activity->id);
    }

    /**
     * DELETE /events/manage/{event}/program/activities/{activity}
     */
    public function destroyActivity(Event $event, EventActivity $activity)
    {
        $this->authorize('manage', $event);

        if ($activity->event_id !== $event->id) {
            abort(404);
        }

        $dayId = $activity->day_id;

        $activity->delete();

        // Recompute event start/end based on all remaining activities
        $this->syncEventProgramTimeBounds($event);

        // Flash a day-level message
        session()->flash('activity_flash', [
            'mode'    => 'day',
            'day_id'  => $dayId,
            'level'   => 'success',
            'message' => 'Activity removed.',
        ]);

        return back()->withFragment('day-'.$dayId);
    }

    /**
     * POST /events/manage/{event}/program/activities/{activity}/duplicate
     */
    public function duplicateActivity(Event $event, EventActivity $activity)
    {
        $this->authorize('manage', $event);

        if ($activity->event_id !== $event->id) {
            abort(404);
        }

        // Next order within the SAME day
        $nextOrder = (int) EventActivity::where('event_id', $event->id)
            ->where('day_id', $activity->day_id)
            ->max('order_index') + 1;

        // Make a full copy of the activity (new row)
        $copy = $activity->replicate();

        $copy->event_id    = $activity->event_id;
        $copy->day_id      = $activity->day_id;
        $copy->order_index = $nextOrder;
        $copy->title       = 'New activity'; // fresh default title

        $copy->save();

        // Recompute event start/end based on all activities
        $this->syncEventProgramTimeBounds($event);

        session()->flash('activity_flash', [
            'mode'        => 'activity',
            'activity_id' => $copy->id,
            'level'       => 'success',
            'message'     => 'Activity duplicated.',
        ]);

        return back()->withFragment('activity-'.$copy->id);
    }

    /**
     * Helper: recompute the event's global start_at and end_at
     * from the earliest start_time and latest end_time of its activities.
     */
protected function syncEventProgramTimeBounds(Event $event): void
{
    // Make sure we have fresh relationships
    $event->load(['days.activities']);

    // Keep only days that have a date and at least one activity
    $daysWithActivities = $event->days
        ->filter(function (EventDay $day) {
            return $day->date && $day->activities->isNotEmpty();
        })
        ->sortBy('date')
        ->values();

    // No dated days with activities => clear bounds
    if ($daysWithActivities->isEmpty()) {
        $event->start_at = null;
        $event->end_at   = null;
        $event->save();
        return;
    }

    // Earliest and latest day (by date) that have activities
    /** @var \App\Models\EventDay $firstDay */
    $firstDay = $daysWithActivities->first();

    /** @var \App\Models\EventDay $lastDay */
    $lastDay = $daysWithActivities->last();

    // On the first day: earliest start_time
    $firstStartTime = $firstDay->activities
        ->pluck('start_time')
        ->filter()      // drop nulls
        ->sort()
        ->first();

    // On the last day: latest end_time (or latest start_time as fallback)
    $lastEndTime = $lastDay->activities
        ->pluck('end_time')
        ->filter()
        ->sort()
        ->last();

    if (! $lastEndTime) {
        $lastEndTime = $lastDay->activities
            ->pluck('start_time')
            ->filter()
            ->sort()
            ->last();
    }

    // If we still don't have both ends, clear bounds and bail
    if (! $firstStartTime || ! $lastEndTime) {
        $event->start_at = null;
        $event->end_at   = null;
        $event->save();
        return;
    }

    $firstStart = Carbon::parse($firstStartTime);
    $timeString = $firstStart->format('H:i:s');

    $startAt = Carbon::parse(
        $firstDay->date->format('Y-m-d') . ' ' . $timeString
    );

    $lastEnd = Carbon::parse($lastEndTime);
    $timeStringEnd = $lastEnd->format('H:i:s');

    $endAt = Carbon::parse(
        $lastDay->date->format('Y-m-d') . ' ' . $timeStringEnd
    );

    $event->start_at = $startAt;
    $event->end_at   = $endAt;
    $event->save();
}


 public function exportProgramPdf(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    // Load days + activities + tracks
    $event->load([
        'days.activities.track',
    ]);

    // Sort days by date (fallback to order_index)
    $days = $event->days
        ->sortBy(function (EventDay $day) {
            if ($day->date) {
                return $day->date->format('Y-m-d');
            }

            return sprintf('9999-12-31-%05d', $day->order_index ?? 0);
        });

    // Keep event start/end in sync with activities (used elsewhere)
    $this->syncEventProgramTimeBounds($event);

    // Hero URL for banner (uses accessor with fallback image)
    $heroUrl = $event->hero_image_url;

    // Date range: strictly from event days (not start_at/end_at)
    $firstDay = optional($days->first()?->date);
    $lastDay  = optional($days->last()?->date);

    $dateRangeLabel = null;
    if ($firstDay && $lastDay) {
        $startLabel = $firstDay->format('M d, Y');
        $endLabel   = $lastDay->format('M d, Y');

        $dateRangeLabel = $startLabel === $endLabel
            ? $startLabel
            : $startLabel . ' – ' . $endLabel;
    } elseif ($firstDay) {
        $dateRangeLabel = $firstDay->format('M d, Y');
    }

    $pdf = Pdf::loadView('events.manage.program-pdf', [
            'event'          => $event,
            'days'           => $days,
            'heroUrl'        => $heroUrl,
            'dateRangeLabel' => $dateRangeLabel,
        ])
        ->setPaper('a4', 'portrait');

    $filename = 'program-' . ($event->slug ?: 'event') . '.pdf';

    return $pdf->download($filename);
}

public function exportProgramCsv(Request $request, Event $event): StreamedResponse
{
    $this->authorize('manage', $event);

    // Eager-load days, activities, tracks
    $event->load('days.activities.track');

    // Sort days by date
    $days = $event->days->sortBy(function ($day) {
        return optional($day->date)->format('Y-m-d') ?? '9999-12-31';
    });

    // Build date range label from days
    $firstDay = optional($days->first()?->date);
    $lastDay  = optional($days->last()?->date);

    $dateRangeLabel = null;
    if ($firstDay && $lastDay) {
        $startLabel = $firstDay->format('M d, Y');
        $endLabel   = $lastDay->format('M d, Y');
        $dateRangeLabel = $startLabel === $endLabel
            ? $startLabel
            : $startLabel . ' – ' . $endLabel;
    } elseif ($firstDay) {
        $dateRangeLabel = $firstDay->format('M d, Y');
    }

    $filename = 'program-' . ($event->slug ?: 'event') . '.csv';

    $headers = [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ];

    return response()->stream(function () use ($event, $days, $dateRangeLabel) {
        $handle = fopen('php://output', 'w');

        // UTF-8 BOM so Excel behaves
        fwrite($handle, "\xEF\xBB\xBF");

        // Top "formatted" header (shows nicely in Excel)
        fputcsv($handle, [$event->title]);
        if ($event->subtitle || $dateRangeLabel) {
            $line = [];
            if ($event->subtitle) {
                $line[] = $event->subtitle;
            }
            if ($dateRangeLabel) {
                $line[] = $dateRangeLabel;
            }
            fputcsv($handle, [implode(' • ', $line)]);
        }

        // Blank row for spacing
        fputcsv($handle, []);

        // Column headings
        fputcsv($handle, [
            'Day',
            'Start Time',
            'End Time',
            'Title',
            'Description',
            'Venue',
            'Location',
        ]);

        $firstDayBlock = true;

        foreach ($days as $day) {
            $dayLabel = $day->date
                ? $day->date->format('F d, Y')
                : 'Unscheduled';

            // spacer row between days (but not before the first one)
            if (! $firstDayBlock) {
                fputcsv($handle, []); // visual gap between day groups
            }
            $firstDayBlock = false;

            // activities sorted by time
            $activities = $day->activities
                ->sortBy(fn ($a) => optional($a->start_time)->format('H:i') ?? '00:00');

            foreach ($activities as $a) {
                $start = optional($a->start_time)->format('H:i') ?: '';
                $end   = optional($a->end_time)->format('H:i') ?: '';

                $track = $a->track;
                $venue = $track?->name ?? '';
                $loc   = $track?->location ?? '';

                fputcsv($handle, [
                    $dayLabel,
                    $start,
                    $end,
                    $a->title ?: 'Untitled activity',
                    $a->description ?? '',
                    $venue,
                    $loc,
                ]);
            }
        }

        fclose($handle);
    }, 200, $headers);
}

public function transferDayActivities(Request $request, Event $event, EventDay $day)
{
    $this->authorize('manage', $event);

    // Ensure this day belongs to the event
    if ($day->event_id !== $event->id) {
        abort(404);
    }

    $data = $request->validate([
        'target_day_id' => [
            'required',
            'integer',
            Rule::exists('event_days', 'id')->where('event_id', $event->id),
        ],
    ]);

    $targetDayId = (int) $data['target_day_id'];

    if ($targetDayId === (int) $day->id) {
        session()->flash('activity_flash', [
            'mode'    => 'day',
            'day_id'  => $day->id,
            'level'   => 'error',
            'message' => 'Please choose a different day to transfer activities to.',
        ]);

        return back()->withFragment('day-' . $day->id);
    }

    /** @var \App\Models\EventDay $targetDay */
    $targetDay = EventDay::where('event_id', $event->id)->findOrFail($targetDayId);

    // Activities currently on the source day
    $activities = EventActivity::where('event_id', $event->id)
        ->where('day_id', $day->id)
        ->orderBy('start_time')
        ->orderBy('id')
        ->get();

    if ($activities->isEmpty()) {
        session()->flash('activity_flash', [
            'mode'    => 'day',
            'day_id'  => $day->id,
            'level'   => 'error',
            'message' => 'This day has no activities to transfer.',
        ]);

        return back()->withFragment('day-' . $day->id);
    }

    // Determine where to append in the target day
    $nextOrderBase = (int) EventActivity::where('event_id', $event->id)
        ->where('day_id', $targetDay->id)
        ->max('order_index') + 1;

    // We'll rebase the times onto the target day's date (keep H:i, change Y-m-d)
    $targetDate = $targetDay->date ?? $event->start_at;

    foreach ($activities as $offset => $activity) {
        $activity->day_id      = $targetDay->id;
        $activity->order_index = $nextOrderBase + $offset;

        if ($targetDate) {
            $dateStr = Carbon::parse($targetDate)->format('Y-m-d');

            if ($activity->start_time) {
                $activity->start_time = Carbon::parse(
                    $dateStr . ' ' . $activity->start_time->format('H:i')
                );
            }

            if ($activity->end_time) {
                $activity->end_time = Carbon::parse(
                    $dateStr . ' ' . $activity->end_time->format('H:i')
                );
            }
        }

        $activity->save();
    }

    // Keep event start/end in sync
    $this->syncEventProgramTimeBounds($event);

    session()->flash('activity_flash', [
        'mode'    => 'day',
        'day_id'  => $targetDay->id,
        'level'   => 'success',
        'message' => 'All activities were transferred to the selected day.',
    ]);

    return back()->withFragment('day-' . $targetDay->id);
}


}
