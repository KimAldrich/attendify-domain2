{{-- events/_event-card.blade.php --}}
@php
    /** @var \App\Models\Event $event */
    $layout    = $layout ?? 'grid';
    $starts    = $event->start_at?->format('M d, Y');
    $ends      = $event->end_at?->format('M d, Y');
    $heroUrl   = $event->hero_image_url;
    $ownerName = optional($event->owner)->display_name ?: optional($event->owner)->name ?: 'Organizer';
    $regCount  = $event->registrations_count ?? ($event->registrations?->count() ?? 0);

    $statusClasses = match ($event->status) {
        'draft'    => 'bg-slate-200 text-slate-800',
        'published'=> 'bg-blue-100 text-blue-800',
        'ongoing'  => 'bg-emerald-100 text-emerald-800',
        'finished' => 'bg-slate-100 text-slate-800',
        'archived' => 'bg-zinc-100 text-zinc-800',
        default    => 'bg-slate-200 text-slate-700',
    };
@endphp

@if ($layout === 'list')
    {{-- Descriptive list tile --}}
    <a
        href="{{ route('events.show', $event) }}"
        class="group relative flex flex-col sm:flex-row items-stretch gap-4 px-4 py-3 bg-white hover:bg-slate-50 transition"
    >
        <div class="sm:w-56 flex-shrink-0">
            <div class="h-full min-h-[130px] rounded-lg overflow-hidden shadow-sm">
                <img
                    src="{{ $heroUrl }}"
                    alt="{{ $event->title }}"
                    class="w-full h-full object-cover"
                >
            </div>
        </div>

        <div class="flex-1 min-w-0 flex flex-col gap-2">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 space-y-1">
                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-sky-700 line-clamp-2">
                        {{ $event->title ?? 'Untitled event' }}
                    </h3>
                    @if ($event->subtitle)
                        <p class="text-sm text-slate-600 line-clamp-1">{{ $event->subtitle }}</p>
                    @endif
                    <p class="text-[12px] text-slate-500">{{ $ownerName }}</p>
                </div>

                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold bg-slate-100 text-slate-700">
                    {{ $regCount }}{{ $event->capacity ? ' / '.$event->capacity : '' }} registered
                </span>
            </div>

            <div class="text-[13px] text-slate-600 flex flex-wrap gap-3">
                <span class="inline-flex items-center gap-1">
                    <x-heroicon-o-calendar class="w-4 h-4 text-slate-500" />
                    @if ($starts && $ends && $starts !== $ends)
                        {{ $starts }} - {{ $ends }}
                    @elseif ($starts)
                        {{ $starts }}
                    @else
                        Schedule TBA
                    @endif
                </span>

                @if ($event->event_type)
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-tag class="w-4 h-4 text-slate-500" />
                        {{ str($event->event_type)->replace('_', ' ')->title() }}
                    </span>
                @endif

            </div>

        </div>
    </a>
@else
    {{-- Modern grid card --}}
    <a
        href="{{ route('events.show', $event) }}"
        class="group relative flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition"
    >
        <div class="relative w-full aspect-[2/1] bg-slate-100">
            <img
                src="{{ $heroUrl }}"
                alt="{{ $event->title }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/15 to-transparent"></div>

            <div class="absolute top-3 left-3 flex flex-wrap items-center gap-2">
                @if ($event->event_type)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-sky-600 text-white shadow-sm">
                        {{ str($event->event_type)->replace('_', ' ')->title() }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex-1 flex flex-col gap-1.5 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900 group-hover:text-sky-700 line-clamp-2">
                {{ $event->title ?? 'Untitled event' }}
            </h3>
            @if ($event->subtitle)
                <p class="text-sm text-slate-600 line-clamp-1">{{ $event->subtitle }}</p>
            @endif
            <p class="text-[12px] text-slate-500">{{ $ownerName }}</p>

            <div class="text-[13px] text-slate-600 flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center gap-1">
                    <x-heroicon-o-calendar class="w-4 h-4 text-slate-500" />
                    @if ($starts && $ends && $starts !== $ends)
                        {{ $starts }} - {{ $ends }}
                    @elseif ($starts)
                        {{ $starts }}
                    @else
                        Schedule TBA
                    @endif
                </span>
            </div>
        </div>
    </a>
@endif
