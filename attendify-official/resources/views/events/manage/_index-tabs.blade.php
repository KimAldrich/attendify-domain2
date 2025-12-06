{{-- resources/views/events/manage/_index-tabs.blade.php --}}
@props([
    'activeStatus',
    'statusLabels'   => [],
    'countsByStatus' => [],
])

@php
    $tabs = $statusLabels;
@endphp

<div class="border-b border-slate-200 mb-4">
    <nav class="-mb-px flex flex-wrap gap-2" aria-label="Event status tabs">
        @foreach ($tabs as $status => $label)
            @php
                $isActive = $activeStatus === $status;
                $count    = $countsByStatus[$status] ?? 0;

                // Preserve other filters in the query string
                $query = array_merge(request()->except('status', 'page'), [
                    'status' => $status,
                ]);
            @endphp

            <a
                href="{{ route('events.manage.index', $query) }}"
                class="inline-flex items-center px-3 py-2 text-sm font-medium border-b-2 rounded-t-md
                       {{ $isActive
                          ? 'border-[#0052CC] text-[#0052CC] bg-white'
                          : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300 bg-slate-50'
                       }}"
            >
                <span>{{ $label }}</span>
                <span class="ml-2 inline-flex items-center justify-center rounded-full text-xs px-2 py-0.5
                            {{ $isActive ? 'bg-[#0052CC]/10 text-[#0052CC]' : 'bg-slate-200 text-slate-700' }}">
                    {{ $count }}
                </span>
            </a>
        @endforeach
    </nav>
</div>
