{{-- resources/views/events/manage/program-pdf.blade.php --}}
@php
    /** @var \App\Models\Event $event */
    /** @var \Illuminate\Support\Collection|\App\Models\EventDay[] $days */

    $title     = $event->title;
    $subtitle  = $event->subtitle;

    // Remote URL (R2 / asset) for background blur
    $heroBgSrc = $heroUrl ?? $event->hero_image_url;

    // For the inline <img>, prefer a local file path when using the fallback logo
    if ($event->hero_image_path) {
        $heroImgSrc = $heroBgSrc; // custom hero from R2/asset
    } else {
        $heroImgSrc = public_path('images/branding/attendify-brand.png');
    }

    $dateRange = $dateRangeLabel ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} – Program</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 11px;
            color: #0f172a; /* slate-900 */
            line-height: 1.4;
        }

        /* Full-page hero background + very light white veil */
        .bg-layer {
            position: absolute;
            inset: 0;
            z-index: -2;
            background-image: url('{{ $heroBgSrc }}');
            background-size: cover;
            background-position: center;
            opacity: 0.35; /* hero a bit more visible */
        }

        .bg-overlay {
            position: absolute;
            inset: 0;
            z-index: -1;
            background: rgba(255, 255, 255, 0.25); /* soft white veil, lighter than before */
        }

        .page {
            padding: 18mm 14mm 16mm 14mm;
            min-height: 100vh;
        }

        .card {
            background: #ffffff;
            border-radius: 12px 12px 0 0; /* only top corners rounded */
            border: 1px solid #dbe3f0;
            padding: 14px 16px 18px 16px;
            max-width: 520pt;
            margin: 0 auto;
        }

        /* Hero banner at top */
        .hero-banner {
            width: 100%;
            height: 70mm; /* fixed visual height */
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }

        .hero-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .header {
            margin-bottom: 14px;
            text-align: center;
            page-break-inside: avoid;
        }

        .event-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 4px 0;
            color: #0f172a;
        }

        .event-subtitle {
            font-size: 12px;
            font-weight: 500;
            margin: 0 0 4px 0;
            color: #475569; /* slate-600 */
        }

        .event-dates {
            font-size: 11px;
            font-weight: 500;
            color: #0f172a;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b; /* slate-500 */
            margin: 4px 0 6px 0;
            text-align: center;
        }

        .program-wrapper {
            margin-top: 4px;
        }

        .day-block {
            margin-bottom: 14px;
            page-break-inside: auto; /* let days flow across pages */
        }

        .day-header {
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.04);
            display: inline-block;
            margin-bottom: 6px;
        }

        /* Two-column layout for activities */
        .activity-grid {
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }

        .activity-list {
            margin: 0;
            padding: 0;
            list-style: none;
            /* removed timeline spine */
            border-left: none;
        }

        .activity-item {
            display: flex;
            flex-direction: row;
            padding: 7px 0;
            position: relative;
            page-break-inside: avoid; /* keep a single activity together */
        }

        /* removed the blue timeline dot */

        .activity-time {
            width: 68px;
            padding-right: 8px;
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
            text-align: right;
            flex-shrink: 0;
        }

        .activity-body {
            padding-left: 10px;
            border-radius: 8px;
        }

        .activity-title {
            font-size: 11px;
            font-weight: 600;
            margin: 0 0 2px 0;
            color: #0f172a;
        }

        .activity-description {
            font-size: 10px;
            font-style: italic;
            margin: 0 0 2px 0;
            color: #4b5563; /* gray-700 */
        }

        .activity-meta {
            font-size: 9px;
            color: #64748b; /* slate-500 */
        }

        .activity-meta strong {
            font-weight: 600;
        }

        .footer-note {
            margin-top: 18px;
            font-size: 10px;
            text-align: center;
            color: #0f172a;
            font-weight: 500;
            page-break-inside: avoid;
        }

        .footer-note span {
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="bg-layer"></div>
<div class="bg-overlay"></div>

<div class="page">
    <div class="card">

        {{-- Hero visual --}}
        <div class="hero-banner">
            <img src="{{ $heroImgSrc }}" alt="Event hero image">
        </div>

        {{-- Header: Event info --}}
        <div class="header">
            <h1 class="event-title">
                {{ $title }}
            </h1>

            @if ($subtitle)
                <p class="event-subtitle">
                    {{ $subtitle }}
                </p>
            @endif

            @if ($dateRange)
                <p class="event-dates">
                    {{ $dateRange }}
                </p>
            @endif
        </div>

        {{-- Program section --}}
        <div class="program-wrapper">
            <div class="section-title">Program Schedule</div>

            @forelse ($days as $day)
                @php
                    /** @var \App\Models\EventDay $day */
                    $date = optional($day->date);
                    $dayLabel = $date
                        ? $date->format('l, F d, Y')
                        : 'Unscheduled Day';

                    $activities = ($day->activities ?? collect())
                        ->sortBy(function ($activity) {
                            return optional($activity->start_time)->format('Y-m-d H:i') ?? '9999-12-31 23:59';
                        })
                        ->values();

                    $total = $activities->count();
                    $half  = (int) ceil($total / 2);

                    $col1 = $activities->slice(0, $half);
                    $col2 = $activities->slice($half);
                @endphp

                <div class="day-block">
                    <div class="day-header">
                        {{ $dayLabel }}
                    </div>

                    @if ($activities->isEmpty())
                        <p style="font-size:9px; color:#9ca3af; margin: 4px 2px 0 2px;">
                            No activities scheduled for this day yet.
                        </p>
                    @else
                        <div class="activity-grid">
                            {{-- Column 1 --}}
                            <ul class="activity-list">
                                @foreach ($col1 as $activity)
                                    @php
                                        /** @var \App\Models\EventActivity $activity */
                                        $start = optional($activity->start_time);
                                        $end   = optional($activity->end_time);

                                        $timeLabel = 'TBA';
                                        if ($start && $end) {
                                            $timeLabel = $start->format('H:i') . ' – ' . $end->format('H:i');
                                        } elseif ($start) {
                                            $timeLabel = $start->format('H:i');
                                        }

                                        $track  = $activity->track;
                                        $venue  = $track?->name;
                                        $loc    = $track?->location;
                                        $venueLine = null;

                                        if ($venue && $loc) {
                                            $venueLine = '@ ' . $venue . ' — ' . $loc;
                                        } elseif ($venue) {
                                            $venueLine = '@ ' . $venue;
                                        } elseif ($loc) {
                                            $venueLine = '@ ' . $loc;
                                        }
                                    @endphp

                                    <li class="activity-item">
                                        <div class="activity-time">
                                            {{ $timeLabel }}
                                        </div>
                                        <div class="activity-body">
                                            <p class="activity-title">
                                                {{ $activity->title ?: 'Untitled activity' }}
                                            </p>

                                            @if ($activity->description)
                                                <p class="activity-description">
                                                    {{ $activity->description }}
                                                </p>
                                            @endif

                                            @if ($venueLine)
                                                <p class="activity-meta">
                                                    <strong>{{ $venueLine }}</strong>
                                                </p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Column 2 --}}
                            @if ($col2->isNotEmpty())
                                <ul class="activity-list">
                                    @foreach ($col2 as $activity)
                                        @php
                                            /** @var \App\Models\EventActivity $activity */
                                            $start = optional($activity->start_time);
                                            $end   = optional($activity->end_time);

                                            $timeLabel = 'TBA';
                                            if ($start && $end) {
                                                $timeLabel = $start->format('H:i') . ' – ' . $end->format('H:i');
                                            } elseif ($start) {
                                                $timeLabel = $start->format('H:i');
                                            }

                                            $track  = $activity->track;
                                            $venue  = $track?->name;
                                            $loc    = $track?->location;
                                            $venueLine = null;

                                            if ($venue && $loc) {
                                                $venueLine = '@ ' . $venue . ' — ' . $loc;
                                            } elseif ($venue) {
                                                $venueLine = '@ ' . $venue;
                                            } elseif ($loc) {
                                                $venueLine = '@ ' . $loc;
                                            }
                                        @endphp

                                        <li class="activity-item">
                                            <div class="activity-time">
                                                {{ $timeLabel }}
                                            </div>
                                            <div class="activity-body">
                                                <p class="activity-title">
                                                    {{ $activity->title ?: 'Untitled activity' }}
                                                </p>

                                                @if ($activity->description)
                                                    <p class="activity-description">
                                                        {{ $activity->description }}
                                                    </p>
                                                @endif

                                                @if ($venueLine)
                                                    <p class="activity-meta">
                                                        <strong>{{ $venueLine }}</strong>
                                                    </p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p style="font-size:10px; color:#9ca3af; margin-top:8px;">
                    No event days have been defined yet. Once days and activities are added, they will appear here.
                </p>
            @endforelse
        </div>

        {{-- Footer note --}}
        <div class="footer-note">
            We, the <span>Attendify Team</span>, expect you there!
        </div>
    </div>
</div>
</body>
</html>
