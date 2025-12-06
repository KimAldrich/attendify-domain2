{{-- events/index.blade.php --}}
<x-app-layout>
    @php
        /** @var \App\Models\Event[]|\Illuminate\Pagination\LengthAwarePaginator $events */
        /** @var \App\Models\Event[]|\Illuminate\Pagination\LengthAwarePaginator|null $myEvents */

        $events    = $events    ?? collect();
        $myEvents  = $myEvents  ?? collect();

        $eventsIsPaginator  = $events instanceof \Illuminate\Pagination\LengthAwarePaginator;
        $myEventsIsPaginator = $myEvents instanceof \Illuminate\Pagination\LengthAwarePaginator;

        $collectAll = collect();
        $collectAll = $collectAll->concat($eventsIsPaginator ? $events->items() : $events);
        $collectAll = $collectAll->concat($myEventsIsPaginator ? $myEvents->items() : $myEvents);

        $eventTypes = $collectAll->pluck('event_type')->filter()->unique()->sort()->values();
        $owners     = $collectAll->map(function ($event) {
            return optional($event->owner)->display_name ?: optional($event->owner)->name ?: null;
        })->filter()->unique()->sort()->values();

        $filters = [
            'q'          => request('q', ''),
            'subtitle'   => request('subtitle', ''),
            'owner'      => request('owner', ''),
            'event_type' => request('event_type', ''),
            'status'     => request('status', ''),
            'joinable'   => request()->boolean('joinable'),
            'start_from' => request('start_from', ''),
            'start_to'   => request('start_to', ''),
        ];
    @endphp

    <div
        class="max-w-6xl mx-auto px-2 py-6"
        x-data="{
            activeTab: '{{ request('tab', 'all') === 'mine' ? 'mine' : 'all' }}',
            layout: '{{ request('layout', 'grid') === 'list' ? 'list' : 'grid' }}',
        }"
        x-cloak
    >
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Events</h1>
                <p class="text-sm text-slate-500">
                    Browse upcoming and past events. Switch between All Events and your personal events.
                </p>
            </div>
        </div>

        @if (! auth()->check())
            <div class="mb-4 px-4 py-3 rounded-md bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start gap-3">
                <i class="bi bi-info-circle text-lg mt-[2px]"></i>

                <div>
                    <strong class="font-semibold">You are currently browsing anonymously!</strong><br>
                    To access the system proper, where your certificates are secured, along with personalized features,
                    <a href="{{ route('register') }}"
                    class="font-medium underline decoration-amber-600">
                        create an account
                    </a>
                    or
                    <a href="{{ route('login') }}"
                    class="font-medium underline decoration-amber-600">
                        log in
                    </a>.
                </div>
            </div>
        @endif

        {{-- Tabs (GitHub style) --}}
        <div class="border-b border-slate-200 mb-4 flex items-center gap-3 justify-between">
            <div class="flex gap-3 text-sm flex-1">
                <button
                    type="button"
                    class="pb-2 border-b-2 -mb-px px-2 sm:px-4 flex-1 text-center"
                    :class="activeTab === 'all'
                        ? 'border-sky-600 text-slate-900 font-medium'
                        : 'border-transparent text-slate-500 hover:text-slate-700'"
                    @click="activeTab = 'all'"
                >
                    All Events
                </button>

                @auth
                    <button
                        type="button"
                        class="pb-2 border-b-2 -mb-px px-2 sm:px-4 flex-1 text-center"
                        :class="activeTab === 'mine'
                            ? 'border-sky-600 text-slate-900 font-medium'
                            : 'border-transparent text-slate-500 hover:text-slate-700'"
                        @click="activeTab = 'mine'"
                    >
                        My Events
                    </button>
                @endauth
            </div>

            <x-layout-toggle class="ml-auto" />
        </div>

        {{-- Filters + layout toggle --}}
        @include('events._filters', [
            'filters'    => $filters,
            'eventTypes' => $eventTypes,
            'owners'     => $owners,
        ])

        {{-- Content area --}}
        <div x-show="activeTab === 'all'">
            @php
                $events = $events ?? collect();
            @endphp

            {{-- Grid layout --}}
            <div x-show="layout === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-cloak>
                @forelse ($events as $event)
                    @include('events._event-card', ['event' => $event, 'layout' => 'grid'])
                @empty
                    <div class="col-span-full text-sm text-slate-500 border border-dashed border-slate-300 rounded-lg p-6 text-center">
                        No events found.
                    </div>
                @endforelse
            </div>

            {{-- List layout --}}
            <div x-show="layout === 'list'" class="bg-white border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100" x-cloak>
                @forelse ($events as $event)
                    @include('events._event-card', ['event' => $event, 'layout' => 'list'])
                @empty
                    <div class="px-4 py-6 text-sm text-slate-500 text-center">
                        No events found.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                @if ($eventsIsPaginator)
                    <x-table-footer :paginator="$events" />
                @endif
            </div>
        </div>

        @auth
            <div x-show="activeTab === 'mine'">
                @php
                    $myEvents = $myEvents ?? collect();
                @endphp

                <div x-show="layout === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-cloak>
                    @forelse ($myEvents as $event)
                        @include('events._event-card', ['event' => $event, 'layout' => 'grid'])
                    @empty
                        <div class="col-span-full text-sm text-slate-500 border border-dashed border-slate-300 rounded-lg p-6 text-center">
                            You're not registered for any events yet.
                        </div>
                    @endforelse
                </div>

                <div x-show="layout === 'list'" class="bg-white border border-slate-200 rounded-lg overflow-hidden divide-y divide-slate-100" x-cloak>
                    @forelse ($myEvents as $event)
                        @include('events._event-card', ['event' => $event, 'layout' => 'list'])
                    @empty
                        <div class="px-4 py-6 text-sm text-slate-500 text-center">
                            You're not registered for any events yet.
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    @if ($myEventsIsPaginator)
                        <x-table-footer :paginator="$myEvents" />
                    @endif
                </div>
            </div>
        @endauth
    </div>
</x-app-layout>
