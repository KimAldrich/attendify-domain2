{{-- resources/views/events/manage/_event-tabs.blade.php --}}
@php
    /** @var \App\Models\Event $event */
    $active = $active ?? 'details';

    $tabs = [
        'preview'      => 'Preview',
        'details'      => 'Details',
        'program'      => 'Program',
        'guests'       => 'Special Guests', 
        'people'       => 'Event Administrators',
        'registration' => 'Registration',
        'gallery'      => 'Gallery',
        'certificates' => 'Certificates',
        'analytics'    => 'Analytics',
    ];
@endphp

<div class="border-b border-slate-200 mb-5">
    <nav class="-mb-px flex flex-wrap gap-2" aria-label="Event manage tabs">
        @foreach ($tabs as $key => $label)
            @php
                $isActive = $active === $key;
            @endphp

            <a
                href="{{ route('events.manage.' . $key, $event) }}"
                class="inline-flex items-center px-3 py-2 text-sm font-medium border-b-2 rounded-t-md
                       {{ $isActive
                            ? 'border-[#0052CC] text-[#0052CC] bg-white'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 bg-slate-50'
                       }}"
            >
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
</div>
