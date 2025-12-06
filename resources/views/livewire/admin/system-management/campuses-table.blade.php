<div>
    <div class="flex items-center justify-between mb-3">
        <div class="flex gap-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search campuses..."
                   class="border rounded px-2 py-1 text-sm" />
        </div>
        <button class="bg-blue-600 text-white px-3 py-1.5 rounded" wire:click="create">Add Campus</button>
    </div>

    {{-- SHOW TABLE when idle (filters/paging only) --}}
    <div wire:loading.remove wire:target="{{ $this->tableTargets }}">
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('abbrev')">Abbrev</th>
                        <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('name')">Name</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left w-40">Actions</th>
                    </tr>
                </thead>
<tbody>
@foreach ($rows as $row)
<tr
  wire:key="campus-{{ $row->id }}"
  x-data="{ busy: false }"
  x-on:row-done.window="if ($event.detail.id === {{ $row->id }}) busy = false"
  :class="{ 'opacity-60 pointer-events-none': busy || @js($busyId) === {{ $row->id }} }"
  class="border-t transition"
>
  <td class="px-4 py-2 font-mono">{{ $row->abbrev }}</td>
  <td class="px-4 py-2">{{ $row->name }}</td>

  <td class="px-4 py-2">
    <button
      x-on:click="busy = true"
      wire:click="toggleActive({{ $row->id }})"
      wire:target="toggleActive"
      wire:loading.attr="disabled"
      :disabled="busy || @js($busyId) === {{ $row->id }}"
      class="h-8 px-3 rounded border text-xs inline-flex items-center gap-2
             {{ $row->is_active ? 'border-green-600 text-green-700 hover:bg-green-50'
                                 : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}"
    >
      <svg x-show="busy || @js($busyId) === {{ $row->id }}" x-cloak
           class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      {{ $row->is_active ? 'Active' : 'Inactive' }}
    </button>
  </td>

  <td class="px-4 py-2">
    <button
      x-on:click="busy = true"
      wire:click="edit({{ $row->id }})"
      wire:target="edit"
      wire:loading.attr="disabled"
      :disabled="busy || @js($busyId) === {{ $row->id }}"
      class="h-8 px-3 rounded border border-blue-600 text-blue-700 text-xs hover:bg-blue-50 inline-flex items-center gap-2"
    >
      <svg x-show="busy || @js($busyId) === {{ $row->id }}" x-cloak
           class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      Edit
    </button>
  </td>
</tr>
@endforeach
</tbody>

            </table>
        </div>

        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>

    {{-- SKELETON (only for filters/paging) --}}
    <div class="mt-3 space-y-2"
         wire:loading.flex
         wire:target="{{ $this->tableTargets }}"
         style="display:none">
        <div class="h-9 bg-gray-100 rounded animate-pulse"></div>
        @for ($i=0;$i<5;$i++)
            <div class="h-10 bg-gray-100 rounded animate-pulse"></div>
        @endfor
    </div>

    {{-- Modal --}}
    <div wire:teleport="#modal-root">
    <div x-data="{ open:false }"
        x-cloak
        x-on:open-modal.window="if($event.detail.name==='campus-modal') open=true"
        x-on:close-modal.window="if($event.detail.name==='campus-modal') open=false"
        x-trap.noscroll="open"
        class="relative z-[2001]">

        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/30"></div>

        <div x-show="open" x-transition class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded p-4 space-y-3 shadow-xl">
            <h3 class="text-lg font-semibold">Campus</h3>

            <div class="space-y-2">
            <label class="block text-sm">Abbrev</label>
            <input type="text" wire:model.live="abbrev" class="w-full border rounded px-2 py-1 text-sm">
            @error('abbrev') <div class="text-red-600 text-xs">{{ $message }}</div> @enderror

            <label class="block text-sm mt-2">Name</label>
            <input type="text" wire:model.live="name" class="w-full border rounded px-2 py-1 text-sm">
            @error('name') <div class="text-red-600 text-xs">{{ $message }}</div> @enderror

            <label class="inline-flex items-center gap-2 mt-2 text-sm">
                <input type="checkbox" wire:model.live="is_active" class="rounded">
                <span>Active</span>
            </label>
            </div>

            <div class="flex justify-end gap-2 pt-2">
            <button class="px-3 py-1.5" x-on:click="open=false">Cancel</button>
            <button class="bg-blue-600 text-white px-3 py-1.5 rounded"
                    wire:click="save" wire:target="save" wire:loading.attr="disabled">
                <svg wire:loading wire:target="save"
                    class="w-3.5 h-3.5 animate-spin inline-block mr-1" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                </svg>
                Save
            </button>
            </div>
        </div>
        </div>
    </div>
    </div>
</div>
