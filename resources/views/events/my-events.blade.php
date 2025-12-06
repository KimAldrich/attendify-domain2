{{-- events/my-events.blade.php --}}
<x-app-layout>
    <div
        class="max-w-5xl mx-auto px-4 py-6"
        x-data="{ layout: 'list' }"
    >
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">My Events</h1>
                <p class="text-sm text-slate-500">
                    Events you’re registered for, have attended, or have certificates for.
                </p>
            </div>
        </div>

        @include('events._filters')

        @php
            $myEvents = $myEvents ?? collect();
        @endphp

        <div x-show="layout === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($myEvents as $event)
                @include('events._event-card', ['event' => $event, 'layout' => 'grid'])
            @empty
                <div class="col-span-full text-sm text-slate-500 border border-dashed border-slate-300 rounded-lg p-6 text-center">
                    You’re not registered for any events yet.
                </div>
            @endforelse
        </div>

        <div x-show="layout === 'list'" class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            @forelse ($myEvents as $event)
                @include('events._event-card', ['event' => $event, 'layout' => 'list'])
            @empty
                <div class="px-4 py-6 text-sm text-slate-500 text-center">
                    You’re not registered for any events yet.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
