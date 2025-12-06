{{-- events/show.blade.php --}}
@php
    /** @var \App\Models\Event $event */
@endphp

<x-app-layout>
    @include('events.show.partials.shell', ['event' => $event])
</x-app-layout>
