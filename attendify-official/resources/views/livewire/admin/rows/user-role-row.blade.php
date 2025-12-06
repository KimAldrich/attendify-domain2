<tr
  {{-- Dim + block clicks while this row is updating --}}
  wire:loading.class="opacity-60 pointer-events-none"
  wire:target="setRole"
  class="h-12 hover:bg-slate-50/70 transition-colors"
>
  {{-- Name --}}
  <td class="px-4 py-2 whitespace-nowrap font-medium text-slate-900">
    {{ $user->display_name }}
  </td>

  {{-- Email --}}
  <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600">
    {{ $user->email }}
  </td>

  @php
    $is  = fn($r) => $currentRole === $r;

    // GitHub-ish segmented pills (one per column)
    $btn = fn($active) => $active
      ? 'inline-flex items-center gap-1.5 h-8 px-3 rounded-full border text-[11px] font-medium bg-blue-600 text-white border-blue-600 shadow-sm cursor-default'
      : 'inline-flex items-center gap-1.5 h-8 px-3 rounded-full border text-[11px] font-medium bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300';
  @endphp

  {{-- Guest --}}
  <td class="px-4 py-2 whitespace-nowrap">
    <button
      wire:click="setRole('guest')"
      wire:loading.attr="disabled"
      wire:target="setRole('guest')"
      class="{{ $btn($is('guest')) }} @if($is('guest')) opacity-80 @endif"
      @disabled($is('guest'))
    >
      {{-- per-button spinner --}}
      <svg
        wire:loading
        wire:target="setRole('guest')"
        class="w-3.5 h-3.5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      Guest
    </button>
  </td>

  {{-- Student --}}
  <td class="px-4 py-2 whitespace-nowrap">
    <button
      wire:click="setRole('student')"
      wire:loading.attr="disabled"
      wire:target="setRole('student')"
      class="{{ $btn($is('student')) }} @if($is('student')) opacity-80 @endif"
      @disabled($is('student'))
    >
      <svg
        wire:loading
        wire:target="setRole('student')"
        class="w-3.5 h-3.5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      Student
    </button>
  </td>

  {{-- Faculty --}}
  <td class="px-4 py-2 whitespace-nowrap">
    <button
      wire:click="setRole('faculty')"
      wire:loading.attr="disabled"
      wire:target="setRole('faculty')"
      class="{{ $btn($is('faculty')) }} @if($is('faculty')) opacity-80 @endif"
      @disabled($is('faculty'))
    >
      <svg
        wire:loading
        wire:target="setRole('faculty')"
        class="w-3.5 h-3.5 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
      </svg>
      Faculty
    </button>
  </td>
</tr>
