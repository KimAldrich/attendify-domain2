{{-- views/dashboard/partials/hero-event.blade.php --}}
@php
  /** @var array $nextEvent */
  /** @var string $downloadPngUrl */
@endphp

<div class="mb-5 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm">
  <div class="relative">
    <img src="{{ $nextEvent['img'] }}" alt="Event banner"
         class="h-44 w-full object-cover" loading="lazy">
    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-black/0"></div>

    <div class="absolute bottom-3 left-4 right-28">
      <h2 class="text-white text-lg sm:text-xl font-semibold drop-shadow">
        {{ $nextEvent['title'] }}
      </h2>
      <p class="text-white/90 text-xs sm:text-sm">{{ $nextEvent['when'] }}</p>
      <p class="text-white/90 text-xs sm:text-sm">{{ $nextEvent['where'] }}</p>
    </div>

    {{-- Small QR (click -> open teleported modal) --}}
    <button
      type="button"
      class="absolute bottom-3 right-3 group rounded-xl bg-white/95 p-2 ring-1 ring-slate-200 hover:bg-white hover:shadow-md transition"
      title="Show QR code"
      onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'event-qr' } }))"
    >
      <div class="h-20 w-20 overflow-hidden rounded-md">
        {!! QrCode::format('svg')->size(80)->margin(0)->generate($nextEvent['reg_uid']) !!}
      </div>
      <span class="mt-1 block text-[10px] text-slate-600 group-hover:text-slate-800 text-center">
        Tap to enlarge
      </span>
    </button>
  </div>

  <div class="p-4 flex flex-wrap items-center gap-2">
    <span class="inline-flex items-center rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2.5 py-1 text-xs font-semibold">
      Your upcoming event
    </span>
    <a href="#"
       class="ml-auto inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
      View details
    </a>
  </div>
</div>
