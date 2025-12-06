{{-- resources/views/events/manage/_index-event-card.blade.php --}}
@php
    /** @var \App\Models\Event $event */
    $layout   = $layout ?? 'list';
    $starts   = $event->start_at?->format('M j, Y g:ia');
    $ends     = $event->end_at?->format('M j, Y g:ia');
    $sameDay  = $event->start_at && $event->end_at
                ? $event->start_at->isSameDay($event->end_at)
                : false;

    $statusClasses = match ($event->status) {
        'draft'    => 'bg-slate-200 text-slate-800',
        'published'=> 'bg-blue-100 text-blue-800',
        'ongoing'  => 'bg-emerald-100 text-emerald-800',
        'finished' => 'bg-slate-100 text-slate-800',
        'archived' => 'bg-zinc-100 text-zinc-800',
        default    => 'bg-slate-200 text-slate-700',
    };
    
    $heroUrl = $event->hero_image_url;
@endphp

@if ($layout === 'grid')
    {{-- GRID-STYLE CARD FOR MANAGE VIEW --}}
    <div
        class="relative flex flex-col border border-slate-200 overflow-hidden bg-slate-50 group"
    >
        {{-- Watermark background --}}
        @if ($heroUrl)
            <div class="pointer-events-none absolute inset-0 opacity-[0.24] group-hover:opacity-[0.48] transition">
                <div
                    class="absolute inset-0 bg-center bg-cover blur-sm scale-110"
                    style="background-image: url('{{ $heroUrl }}');"
                ></div>
                {{-- slight white veil so text always readable --}}
                <div class="absolute inset-0 bg-white/70"></div>
            </div>
        @endif

        {{-- Foreground content --}}
        <div class="relative flex-1 px-3 py-3 flex flex-col gap-2">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <a href="{{ route('events.manage.preview', $event) }}"
                       class="font-semibold text-sm text-slate-900 hover:text-[#0052CC] line-clamp-2">
                        {{ $event->title ?? 'Untitled event' }}
                    </a>

                    <p class="mt-1 text-[11px] text-slate-600">
                        @if ($event->start_at)
                            @php
                                $startDate = $event->start_at->format('M j, Y');
                                $endDate   = $event->end_at?->format('M j, Y');
                            @endphp
                            {{ $startDate }}
                            @if ($event->end_at && $endDate !== $startDate)
                                – {{ $endDate }}
                            @endif
                        @else
                            Schedule TBA
                        @endif

                        @if ($event->event_type)
                            • {{ str($event->event_type)->replace('_', ' ')->title() }}
                        @endif
                    </p>
                </div>

                {{-- STATUS CHIP --}}
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses }}">
                    {{ ucfirst($event->status) }}
                </span>
            </div>

            @if ($event->short_description)
                <p class="text-[11px] text-slate-700 line-clamp-1">
                    {{ $event->short_description }}
                </p>
            @endif
        </div>

        <div class="relative px-3 pb-3 pt-2 border-t border-slate-100/80 flex items-center justify-between text-[11px] text-slate-600">
            <div class="flex items-center gap-3">
                <a href="{{ route('events.manage.registration', $event) }}"
                   class="hover:text-[#0052CC]">
                    Registrations
                </a>
                <a href="{{ route('events.manage.analytics', $event) }}"
                   class="hover:text-[#0052CC]">
                    Analytics
                </a>
            </div>
            <a href="{{ route('events.manage.preview', $event) }}"
               class="inline-flex items-center px-2 py-1 rounded-md border border-slate-200 bg-white/80 hover:bg-white text-[11px] font-medium text-slate-700">
                Manage
            </a>
        </div>
    </div>
@else
    {{-- LIST-STYLE ROW FOR MANAGE VIEW --}}
    <div class="relative overflow-hidden">
        {{-- Watermark background behind row --}}
        @if ($heroUrl)
            <div class="pointer-events-none absolute inset-0 opacity-[0.05] md:opacity-[0.08]">
                <div
                    class="absolute inset-0 bg-center bg-cover blur-sm scale-110"
                    style="background-image: url('{{ $heroUrl }}');"
                ></div>
                <div class="absolute inset-0 bg-white/80"></div>
            </div>
        @endif

        <div class="relative flex items-center justify-between px-4 py-4 hover:bg-white/70 transition">
            <div class="flex items-center gap-4 min-w-0">
                {{-- Hero thumbnail --}}
                @if ($heroUrl)
                    <div class="hidden sm:block">
                        <img
                            src="{{ $heroUrl }}"
                            alt="{{ $event->title }}"
                            class="w-32 aspect-video rounded-md object-cover shadow-sm"
                        >
                    </div>
                @endif

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('events.manage.preview', $event) }}"
                           class="text-sm font-semibold text-slate-900 hover:text-[#0052CC] truncate">
                            {{ $event->title ?? 'Untitled event' }}
                        </a>

                        {{-- STATUS CHIP --}}
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses }}">
                            {{ ucfirst($event->status) }}
                        </span>
                    </div>

                    <div class="mt-1 text-xs text-slate-600 flex flex-wrap gap-x-3 gap-y-1">
                        @if ($event->start_at)
                            <span>
                                {{ $starts }}
                                @if ($event->end_at)
                                    – {{ $ends }}
                                @endif
                            </span>
                        @endif

                        @if ($event->event_type)
                            <span class="inline-flex items-center">
                                <span class="w-1 h-1 rounded-full bg-slate-400 mx-1"></span>
                                {{ str($event->event_type)->replace('_', ' ')->title() }}
                            </span>
                        @endif

                        @if ($event->capacity)
                            <span class="inline-flex items-center">
                                <span class="w-1 h-1 rounded-full bg-slate-400 mx-1"></span>
                                Capacity: {{ $event->capacity }}
                            </span>
                        @endif
                    </div>

                    @if ($event->short_description)
                        <p class="mt-2 text-xs text-slate-700 line-clamp-1">
                            {{ $event->short_description }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="ml-4 flex items-center gap-2 shrink-0">
                <a href="{{ route('events.manage.registration', $event) }}"
                   class="text-xs text-slate-600 hover:text-[#0052CC]">
                    Registrations
                </a>
                <span class="w-px h-4 bg-slate-200"></span>
                <a href="{{ route('events.manage.analytics', $event) }}"
                   class="text-xs text-slate-600 hover:text-[#0052CC]">
                    Analytics
                </a>

                <a href="{{ route('events.manage.preview', $event) }}"
                   class="ml-3 inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg
                          border border-slate-200 bg-white/80 text-slate-700 hover:bg-white">
                    Manage
                </a>
            </div>
        </div>
    </div>
@endif


