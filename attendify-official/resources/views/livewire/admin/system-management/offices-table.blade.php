{{-- resources/views/livewire/admin/offices-page.blade.php (example name) --}}
<div>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
        {{-- Header --}}
        <div class="px-4 sm:px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-0.5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                    Offices
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Manage faculty offices and their active status.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center w-full sm:w-auto">
                <div class="w-full sm:w-64">
                    <label class="sr-only" for="office-search">Search offices</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-2 flex items-center text-slate-400">
                            <i class="bi bi-search text-xs"></i>
                        </span>
                        <input
                            id="office-search"
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search offices..."
                            class="form-input w-full pl-7 pr-8 py-1.5 text-sm rounded-md border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        >
                        @if($search ?? false)
                            <button
                                type="button"
                                wire:click="$set('search','')"
                                class="absolute inset-y-0 right-1 flex items-center px-1 text-slate-400 hover:text-slate-600"
                            >
                                <i class="bi bi-x-lg text-[11px]"></i>
                                <span class="sr-only">Clear search</span>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md bg-[#238636] hover:bg-[#2ea043] text-white text-xs font-medium px-3 py-1.5 shadow-sm"
                        wire:click="create"
                    >
                        <i class="bi bi-plus-lg text-[11px]"></i>
                        Add Office
                    </button>
                </div>
            </div>
        </div>

        {{-- SHOW TABLE when idle --}}
        <div wire:loading.remove wire:target="{{ $this->tableTargets }}">
            <div class="px-4 sm:px-5 pt-3 pb-2 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <div>
                    @if($rows->total() > 0)
                        Showing <span class="font-semibold">{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</span>
                        of <span class="font-semibold">{{ $rows->total() }}</span> offices
                    @else
                        No offices found.
                    @endif
                    @if($search ?? false)
                        <span class="ml-1 text-slate-400">for “{{ $search }}”</span>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto border-t border-slate-200 dark:border-slate-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr class="text-xs font-semibold text-slate-500 dark:text-slate-300 uppercase tracking-wide">
                            <th
                                class="px-4 py-2 text-left align-middle cursor-pointer select-none"
                                wire:click="sortBy('name')"
                            >
                                <span class="inline-flex items-center gap-1">
                                    Name
                                    <i class="bi bi-arrow-down-up text-[10px] opacity-60"></i>
                                </span>
                            </th>
                            <th class="px-4 py-2 text-left align-middle">
                                Status
                            </th>
                            <th class="px-4 py-2 text-left align-middle w-40">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($rows as $row)
                            <tr
                                wire:key="office-{{ $row->id }}"
                                x-data="{ busy: false }"
                                x-on:row-done.window="if ($event.detail.id === {{ $row->id }}) busy = false"
                                :class="{ 'opacity-60 pointer-events-none': busy || @js($busyId) === {{ $row->id }} }"
                                class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                            >
                                <td class="px-4 py-2 align-middle text-slate-900 dark:text-slate-100">
                                    {{ $row->name }}
                                </td>

                                <td class="px-4 py-2 align-middle">
                                    <button
                                        x-on:click="busy = true"
                                        wire:click="toggleActive({{ $row->id }})"
                                        wire:target="toggleActive"
                                        wire:loading.attr="disabled"
                                        :disabled="busy || @js($busyId) === {{ $row->id }}"
                                        class="h-7 px-3 rounded-full border text-[11px] inline-flex items-center gap-1.5
                                               {{ $row->is_active
                                                    ? 'border-emerald-600 text-emerald-700 bg-emerald-50 hover:bg-emerald-100'
                                                    : 'border-slate-300 text-slate-700 bg-white hover:bg-slate-50' }}"
                                    >
                                        <svg
                                            x-show="busy || @js($busyId) === {{ $row->id }}"
                                            x-cloak
                                            class="w-3.5 h-3.5 animate-spin"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                                        </svg>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $row->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $row->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </button>
                                </td>

                                <td class="px-4 py-2 align-middle">
                                    <button
                                        x-on:click="busy = true"
                                        wire:click="edit({{ $row->id }})"
                                        wire:target="edit"
                                        wire:loading.attr="disabled"
                                        :disabled="busy || @js($busyId) === {{ $row->id }}"
                                        class="h-8 px-3 rounded-md border border-blue-600 text-blue-700 text-xs font-medium hover:bg-blue-50 inline-flex items-center gap-1.5"
                                    >
                                        <svg
                                            x-show="busy || @js($busyId) === {{ $row->id }}"
                                            x-cloak
                                            class="w-3.5 h-3.5 animate-spin"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                                        </svg>
                                        <i class="bi bi-pencil text-[11px]"></i>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 sm:px-5 py-3 border-t border-slate-200 dark:border-slate-700">
                {{ $rows->links() }}
            </div>
        </div>

        {{-- SKELETON --}}
        <div
            class="mt-3 space-y-2 px-4 sm:px-5 pb-4"
            wire:loading.flex
            wire:target="{{ $this->tableTargets }}"
            style="display:none"
        >
            <div class="h-9 bg-slate-100 dark:bg-slate-800 rounded-md animate-pulse"></div>
            @for ($i=0; $i<5; $i++)
                <div class="h-10 bg-slate-100 dark:bg-slate-800 rounded-md animate-pulse"></div>
            @endfor
        </div>
    </div>

    {{-- Modal --}}
    <div wire:teleport="#modal-root">
        <div
            x-data="{ open:false }"
            x-cloak
            x-on:open-modal.window="if($event.detail.name==='office-modal') open=true"
            x-on:close-modal.window="if($event.detail.name==='office-modal') open=false"
            x-trap.noscroll="open"
            class="relative z-[2001]"
        >
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div x-show="open" x-transition class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            Office
                        </h3>
                        <button
                            type="button"
                            class="h-7 w-7 inline-flex items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-800"
                            x-on:click="open=false"
                        >
                            <i class="bi bi-x-lg text-[11px] text-slate-500"></i>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <div class="px-4 py-3 space-y-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                                Name
                            </label>
                            <input
                                type="text"
                                wire:model.live="name"
                                class="w-full form-input border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm"
                            >
                            @error('name')
                                <div class="text-rose-600 text-xs mt-0.5">{{ $message }}</div>
                            @enderror
                        </div>

                        <label class="inline-flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 mt-1.5">
                            <input type="checkbox" wire:model.live="is_active" class="rounded border-slate-300 dark:border-slate-600">
                            <span>Active</span>
                        </label>
                    </div>

                    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2 bg-slate-50/70 dark:bg-slate-900/60 rounded-b-xl">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900"
                            x-on:click="open=false"
                        >
                            Cancel
                        </button>
                        <button
                            class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-medium"
                            wire:click="save"
                            wire:target="save"
                            wire:loading.attr="disabled"
                        >
                            <svg
                                wire:loading
                                wire:target="save"
                                class="w-3.5 h-3.5 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                            </svg>
                            <span>Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
