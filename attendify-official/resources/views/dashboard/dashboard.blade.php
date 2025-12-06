<x-app-layout>
@php
    /** @var \App\Models\User|null $user */
    $user     = auth()->user();
    $fullName = $user?->display_name ?? 'User';
    $roleName = $user?->getRoleNames()->first() ?? 'guest';
    $roleKey  = \Illuminate\Support\Str::of($roleName)->lower()->value();
    $roleKey  = in_array($roleKey, ['student','faculty','admin']) ? $roleKey : 'guest';

    $viewerIsOwner = $user?->id && \Illuminate\Support\Facades\Auth::id() === $user->id;

    $infoComplete = (int)($user->info_status ?? 0) === 1;

    $isTeaching = $user->is_teaching == 1;

    $showAux = ($roleKey === 'student') || ($roleKey === 'faculty' && $isTeaching);

    $nextEvent = [
        'title'   => 'Campus Sparkler Night',
        'when'    => 'Oct 30, 2025 • 5:00 PM - 12:00 PM',
        'where'   => 'Tech Hall, PSU-UCC',
        'img'     => asset('images/event3.jpg'),
        'reg_uid' => 'reg_123e4567-e89b-12d3-a456-426614174000',
    ];

    $eventCode = 'design-jam-2025'; 

    $firebaseUid = $user?->firebase_uid;
    $qrPayload = json_encode([
        'event' => $eventCode,
        'uid'   => $firebaseUid,
        'ver'   => 1, 
    ], JSON_UNESCAPED_SLASHES);

    $downloadPngUrl = route('registration.qr.download.png', [
        'eventCode' => $eventCode,
        'uid'       => $firebaseUid ?: 'guest',
    ]);
@endphp


    <div class="px-3 sm:px-6 py-5">
        {{-- Header --}}
        <header class="mb-4">
            <h1 class="text-xl text-[#0033cc] sm:text-2xl font-bold text-slate-900 tracking-tight">Hello, {{ $fullName }}</h1>
            <p class="text-slate-600 text-sm sm:text-base">Welcome back to Attendify.</p>
        </header>

        {{-- Incomplete profile banner (unchanged) --}}
@if ($viewerIsOwner && !$infoComplete)
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50">
        <div class="px-3 py-2.5 flex flex-col sm:flex-row sm:items-center sm:gap-3">
            {{-- Icon + text --}}
            <div class="flex-1 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-base"></i>
                <p class="text-xs sm:text-sm leading-normal text-amber-900">
                    Your account details are still incomplete. To experience the full features of the system,
                    please check and update your account.
                </p>
            </div>

            {{-- Action --}}
            <div class="mt-2 sm:mt-0 sm:ml-4">
                <a
                    href="{{ route('profile.me') }}"
                    class="inline-flex items-center justify-center rounded-md border border-amber-400 bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-200"
                >
                    Go now
                </a>
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  {{-- MAIN COLUMN --}}
  <div class="{{ $showAux ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-4">

    @unlessrole('admin')
      @include('dashboard.partials.hero-event', [
        'nextEvent'      => $nextEvent,
        'downloadPngUrl' => $downloadPngUrl,
      ])
    @endunlessrole

    @includeIf("dashboard.roles.$roleKey", ['user' => $user, 'showAux' => $showAux])

    @unless (View::exists("dashboard.roles.$roleKey"))
      <div class="rounded-lg bg-white ring-1 ring-slate-200 p-4">
        <p class="text-slate-700">
          No dashboard is defined for role: <strong>{{ $roleKey }}</strong>.
        </p>
      </div>
    @endunless
  </div>

  {{-- AUX COLUMN (Calendar) --}}
  @if ($showAux)
    <aside class="lg:col-span-1 space-y-4">
      @include('dashboard.partials.aux-calendar')
    </aside>
  @endif
</div>


<div wire:teleport="#modal-root">
  <div x-data="{ open:false }" x-cloak
       x-on:open-modal.window="if($event.detail.name==='event-qr') open=true"
       x-on:close-modal.window="if($event.detail.name==='event-qr') open=false"
       x-trap.noscroll="open"
       class="relative z-[2001]">

    {{-- Backdrop --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/30"></div>

    {{-- Dialog --}}
    <div x-show="open" x-transition class="fixed inset-0 flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-sm rounded-2xl p-4 sm:p-6 space-y-4 shadow-xl ring-1 ring-slate-200">
        <div class="flex items-start justify-between gap-3">
          <h3 class="text-lg font-semibold text-slate-900">
            {{ $nextEvent['title'] }}
          </h3>
        </div>

        <div class="flex justify-center">
          <div class="overflow-hidden rounded-lg ring-1 ring-slate-200 p-2 bg-white">
            {!! QrCode::format('svg')->size(280)->margin(0)->generate($qrPayload) !!}
          </div>
        </div>

        {{-- When & where under the QR --}}
        <div class="text-center text-sm text-slate-700">
          <div class="font-medium">{{ $nextEvent['when'] }}</div>
          <div class="text-slate-600">{{ $nextEvent['where'] }}</div>
        </div>

        <div class="flex justify-center sm:justify-end gap-2 pt-1">
            <a href="{{ $downloadPngUrl }}"
                class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                Download QR
            </a>
          <button class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                  @click="open=false">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

    </div>
</x-app-layout>
