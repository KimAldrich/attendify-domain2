{{-- resources/views/components/layout-toggle.blade.php --}}
<button
    type="button"
    {{ $attributes->class(
        'inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-300
         bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition'
    ) }}
    @click="layout = (layout === 'grid') ? 'list' : 'grid'"
>
    {{-- Grid icon (shown when layout = grid) --}}
    <span x-show="layout === 'grid'">
        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
    </span>

    {{-- List icon (shown when layout = list) --}}
    <span x-show="layout === 'list'">
        <x-heroicon-o-queue-list class="w-4 h-4" />
    </span>
</button>
