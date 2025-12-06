@props(['paginator'])

@if ($paginator && $paginator->total() > 0)
    <div class="px-4 py-3 bg-white border-t border-slate-200 flex items-center justify-end gap-6 text-sm text-slate-600">

        {{-- Rows info --}}
        <div class="flex items-center gap-3">
            <span class="text-slate-500">Rows per page:</span>
            <span class="inline-flex items-center h-8 px-3 rounded-md border border-slate-200 bg-slate-50 text-slate-700">
                {{ $paginator->perPage() }}
            </span>

            <span class="ml-2">
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
                <span class="text-slate-400">of</span>
                {{ $paginator->total() }}
            </span>
        </div>

        {{-- Prev / Next controls --}}
        <div class="flex items-center gap-2">
            {{-- Prev --}}
            @php
                $prevDisabled = $paginator->onFirstPage();
            @endphp
            @if ($prevDisabled)
                <button
                    type="button"
                    disabled
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200
                           bg-slate-50 text-slate-300 cursor-not-allowed"
                >
                    <i class="bi bi-chevron-left text-sm"></i>
                </button>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200
                           bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-800"
                >
                    <i class="bi bi-chevron-left text-sm"></i>
                </a>
            @endif

            {{-- Next --}}
            @php
                $nextDisabled = ! $paginator->hasMorePages();
            @endphp
            @if ($nextDisabled)
                <button
                    type="button"
                    disabled
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200
                           bg-slate-50 text-slate-300 cursor-not-allowed"
                >
                    <i class="bi bi-chevron-right text-sm"></i>
                </button>
            @else
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200
                           bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-800"
                >
                    <i class="bi bi-chevron-right text-sm"></i>
                </a>
            @endif
        </div>
    </div>
@endif
