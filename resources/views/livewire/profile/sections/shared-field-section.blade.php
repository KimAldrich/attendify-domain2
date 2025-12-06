<div class="relative border border-[#001f99] p-4 h-full overflow-hidden"
     style="background: linear-gradient(to bottom, #001f99 0%, #0a2ea6 100%);">

  <!-- Soft side fade overlay -->
  <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(0,31,153,0.55),rgba(0,31,153,0),rgba(0,31,153,0.55))]"></div>

  <h3 class="font-medium mb-3 text-white relative">Contact Information</h3>

  <dl class="text-sm divide-y divide-white/10 relative">
    {{-- Mobile --}}
    <div class="py-3">
      <div class="flex items-baseline justify-between gap-3">
        <dt class="text-white font-medium">Mobile Number</dt>
      </div>
      <dd class="mt-1 text-blue-200">
        {{ $profile->cp_no ?? '—' }}
      </dd>
    </div>

    {{-- Address --}}
    <div class="py-3">
      <div class="flex items-baseline justify-between gap-3">
        <dt class="text-white font-medium">Address</dt>
      </div>
      <dd class="mt-1 text-blue-200 space-y-0.5">
        @if($address_house || $address_brgy || $address_city || $address_province)
          @if($address_house)
            <div>{{ $address_house }}</div>
          @endif

          @if($address_brgy || $address_city)
            <div>
              @if($address_brgy){{ $address_brgy }}@endif
              @if($address_brgy && $address_city), @endif
              @if($address_city){{ $address_city }}@endif
            </div>
          @endif

          @if($address_province)
            <div>{{ $address_province }}</div>
          @endif
        @else
          <span class="text-blue-300">—</span>
        @endif
      </dd>
    </div>

    {{-- Facebook --}}
    <div class="py-3">
      <div class="flex items-baseline justify-between gap-3">
        <dt class="text-white font-medium">Facebook</dt>
      </div>
      <dd class="mt-1">
        @if($facebook)
          <a
            class="inline-flex items-center gap-1 text-blue-200 hover:underline"
            href="{{ $facebook }}" target="_blank" rel="noopener"
          >
            Open <i class="bi bi-box-arrow-up-right text-[12px]"></i>
          </a>
        @else
          <span class="text-blue-300">—</span>
        @endif
      </dd>
    </div>

    {{-- LinkedIn --}}
    <div class="py-3">
      <div class="flex items-baseline justify-between gap-3">
        <dt class="text-white font-medium">LinkedIn</dt>
      </div>
      <dd class="mt-1">
        @if($linkedin)
          <a
            class="inline-flex items-center gap-1 text-blue-200 hover:underline"
            href="{{ $linkedin }}" target="_blank" rel="noopener"
          >
            Open <i class="bi bi-box-arrow-up-right text-[12px]"></i>
          </a>
        @else
          <span class="text-blue-300">—</span>
        @endif
      </dd>
    </div>
  </dl>
</div>
