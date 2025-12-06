<tr
  wire:loading.class="opacity-60 pointer-events-none"
  wire:target="enable,disable"
  class="hover:bg-slate-50/70 transition-colors"
>
  {{-- Name --}}
  <td class="px-4 py-2 whitespace-nowrap font-medium text-slate-900">
    {{ $user->display_name }}
  </td>

  {{-- Email --}}
  <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600">
    {{ $user->email }}
  </td>

  {{-- Status badge --}}
  <td class="px-4 py-2 whitespace-nowrap">
    <span @class([
      'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium border',
      'bg-green-50 text-green-700 border-green-200' => !$disabled,
      'bg-rose-50 text-rose-700 border-rose-200'    =>  $disabled,
    ])>
      <span @class([
        'w-1.5 h-1.5 rounded-full mr-1.5',
        !$disabled ? 'bg-green-500' : 'bg-rose-500',
      ])></span>
      {{ $disabled ? 'Disabled' : 'Enabled' }}
    </span>
  </td>

  {{-- Enable button --}}
  <td class="px-4 py-2 whitespace-nowrap">
    <button
      wire:click="enable"
      wire:loading.attr="disabled"
      wire:target="enable"
      @class([
        'inline-flex items-center gap-1.5 h-8 px-3 rounded-full border text-[11px] font-medium transition',
        $disabled
          ? 'border-emerald-600 text-emerald-700 bg-white hover:bg-emerald-50'
          : 'border-slate-200 text-slate-500 bg-slate-100 cursor-default opacity-70',
      ])
    >
      <svg
        wire:loading
        wire:target="enable"
        class="w-3.5 h-3.5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      Enable
    </button>
  </td>

  {{-- Disable button --}}
  <td class="px-4 py-2 whitespace-nowrap">
    <button
      wire:click="disable"
      wire:loading.attr="disabled"
      wire:target="disable"
      @class([
        'inline-flex items-center gap-1.5 h-8 px-3 rounded-full border text-[11px] font-medium transition',
        !$disabled
          ? 'border-rose-600 text-rose-700 bg-white hover:bg-rose-50'
          : 'border-slate-200 text-slate-500 bg-slate-100 cursor-default opacity-70',
      ])
    >
      <svg
        wire:loading
        wire:target="disable"
        class="w-3.5 h-3.5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      Disable
    </button>
  </td>
</tr>
