{{-- resources/views/events/manage/preview.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        {{-- Shared header + tabs --}}
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'preview'])

        {{-- Preview header (outside the preview frame) --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Event Preview
                </h2>
                <p class="text-sm text-slate-600">
                    This is how your event will appear to attendees on the public page.
                </p>
            </div>

            <a
                href="{{ route('events.show', $event->slug) }}"
                target="_blank"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg
                       border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
            >
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 mr-1.5" />
                Open page
            </a>
        </div>

        {{-- Actual preview frame --}}
        @include('events.manage._event-preview-actual', ['event' => $event])
    </div>

@push('styles')
    <style>
        /* Marquee for long titles & subtitles with 3s delay */
        @keyframes marquee-slide {
            0%   { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        .marquee {
            display: inline-block;
            animation: marquee-slide 18s linear infinite;
            animation-delay: 3s;
        }

        /* Hide scrollbar in preview (but keep scrolling) */
        .no-scrollbar {
            scrollbar-width: none; /* Firefox */
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none; /* Chrome/Safari/Edge */
        }
    </style>
@endpush

</x-app-layout>
