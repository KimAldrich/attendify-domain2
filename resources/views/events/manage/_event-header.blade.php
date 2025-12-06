{{-- events/manage/_event-header.blade.php --}}
@php
    use Illuminate\Support\Str;
    /** @var \App\Models\Event $event */
    $starts = $event->start_at?->format('M d, Y');
    $ends   = $event->end_at?->format('M d, Y');
    $breadcrumbTitle = Str::limit($event->title ?? 'Untitled event', 40, '…');
@endphp

<div class="mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <x-breadcrumbs :items="[
                [
                    'label' => 'Manage Events',
                    'url'   => route('events.manage.index'),
                ],
                [
                    'label' => $breadcrumbTitle,
                ],
            ]" />
            <h1 class="text-xl font-semibold text-slate-900 truncate">
                {{ $event->title ?? 'Untitled event' }}
            </h1>

            <p class="text-xs text-slate-500 mt-1">
                @if ($starts && $ends && $starts !== $ends)
                    {{ $starts }} – {{ $ends }}
                @elseif ($starts)
                    {{ $starts }}
                @else
                    Schedule TBA
                @endif
                @if ($event->event_type)
                    - {{ $event->event_type ? Str::title(str_replace('_', ' ', $event->event_type)) : '' }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($event->status)
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                    @if($event->status === 'published') border-emerald-500 text-emerald-700 bg-emerald-50
                    @elseif($event->status === 'draft') border-slate-300 text-slate-600 bg-slate-50
                    @elseif($event->status === 'ongoing') border-sky-500 text-sky-700 bg-sky-50
                    @else border-slate-200 text-slate-500 bg-slate-50 @endif">
                    {{ ucfirst($event->status) }}
                </span>
            @endif

            {{-- Placeholder quick stats --}}
            <div class="text-xs text-slate-500">
                {{-- TODO: registrations count, attendance rate, etc. --}}
                Registrations: —
            </div>
        </div>
    </div>
</div>
