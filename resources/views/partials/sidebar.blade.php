@php
    // Load, permission-filter, route-filter
    $navItems = collect(config('nav'))
        ->filter(fn($i) => blank($i['can'] ?? null) || auth()->user()?->can($i['can']))
        ->filter(fn($i) => Route::has($i['route'] ?? ''))
        ->values();

    // Partition into primary vs management (default to primary if mgmt not set)
    [$primaryItems, $managementItems] = $navItems->partition(fn($i) => !($i['mgmt'] ?? false));

    // Helper to compute active + icon
    $mapView = function ($items) {
        return $items->map(function ($item) {
            $isActive = request()->routeIs(($item['route'] ?? '').'*');
            $iconName = $isActive ? ($item['icon_fill'] ?? $item['icon'] ?? null) : ($item['icon'] ?? null);
            return [
                'label' => $item['label'] ?? '',
                'route' => $item['route'] ?? '',
                'icon'  => $iconName,
                'active'=> $isActive,
            ];
        });
    };

    $primaryView    = $mapView($primaryItems);
    $managementView = $mapView($managementItems);
@endphp


{{-- ✅ One Alpine scope controls both the rail and the backdrop --}}
<div x-data="{ expanded:false }">
    <!-- <div class="fixed bottom-2 left-2 z-[3000] text-xs px-2 py-1 bg-black/60 text-white rounded" x-text="'expanded: ' + expanded"></div> -->
    <nav id="attendify-rail"
        x-on:toggle-rail.window="expanded = !expanded"
        x-init="
        $el.addEventListener('mouseenter',()=>{ if (window.matchMedia('(min-width: 1024px)').matches) expanded = true; });
        $el.addEventListener('mouseleave',()=>{ if (window.matchMedia('(min-width: 1024px)').matches) expanded = false; });
        "
        class="rl-nav lg:translate-x-0 transition-transform"
        :class="expanded ? 'expanded translate-x-0' : '-translate-x-full'"
        aria-label="Primary">

        {{-- ✅ Mobile-only brand/header (hidden on desktop) --}}
        <div class="rl-brand lg:hidden px-2"
            x-show="expanded"
            x-transition.opacity.duration.150ms>
        <div class="flex items-center gap-2">
            <img src="{{ asset('images/branding/attendify-brand.png') }}" alt="Attendify" class="app-logo-mobile ml-1">
        </div>
        </div>

<div class="rl-section">
    {{-- Primary (non-management) --}}
    @if($primaryView->isNotEmpty())
        <ul class="rl-list">
            @foreach ($primaryView as $item)
                <li class="rl-item {{ $item['active'] ? 'active' : '' }}">
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}" @if($item['active']) aria-current="page" @endif>
                        <span class="rl-icon"><i class="bi bi-{{ $item['icon'] }}"></i></span>
                        <span class="rl-label">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Divider (render only if there are visible management items) --}}
    @if($managementView->isNotEmpty())
        <div class="rl-divider" aria-hidden="true">
            <span class="rl-divider__text">Management</span>
            <span class="rl-divider__rule"></span>
        </div>

        <ul class="rl-list mt-1">
            @foreach ($managementView as $item)
                <li class="rl-item {{ $item['active'] ? 'active' : '' }}">
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}" @if($item['active']) aria-current="page" @endif>
                        <span class="rl-icon"><i class="bi bi-{{ $item['icon'] }}"></i></span>
                        <span class="rl-label">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

        {{-- ✅ Utility-style footer (GitHub-ish) --}}
        <div class="rl-footer">
            <div class="px-3 py-3 text-[11px] leading-5 text-white/80">
                <div class="flex flex-wrap gap-x-3 gap-y-1">
                    <a class="hover:underline" href="{{ route('landing-page') }}#how-it-works" target="_blank" rel="noopener">About</a>
                    <a class="hover:underline" href="https://www.linkedin.com/in/attendify-support-1297a4387/">Support</a>
                    <a class="hover:underline" href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a>
                    <a class="hover:underline" href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy</a>
                </div>
                <div class="mt-2 opacity-75">© {{ date('Y') }} Attendify</div>
                <!-- <div class="opacity-60">v{{ config('app.version', '0.1.0') }}</div> -->
            </div>
        </div>
    </nav>

    {{-- ✅ Backdrop now follows the same Alpine state --}}
    <div class="fixed inset-0 bg-black/40 z-[1150] lg:hidden"
         x-show="expanded"
         x-transition.opacity
         x-on:click="expanded=false">
    </div>
</div>

