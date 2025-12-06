@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();

    // Load nav config and filter by permissions, teaching_only, and existing routes
    $navItems = collect(config('nav'))
        ->filter(function ($i) use ($user) {
            // Allow certain items for guests (non-authenticated users)
            if (! $user) {

                // Hide all management items
                if ($i['mgmt'] ?? false) {
                    return false;
                }

                // Hide any item that requires permissions
                if (! empty($i['can']) || ! empty($i['can_any'])) {
                    return false;
                }

                // Allow ONLY the Events item, based on route
                if (($i['route'] ?? '') === 'events.index') {
                    return true;
                }

                // Everything else is hidden for guests
                return false;
            }

            // teaching_only => must be teaching OR admin
            if (($i['teaching_only'] ?? false) && ! ($user->is_teaching || $user->hasRole('admin'))) {
                return false;
            }

            // Single permission gate
            if (! empty($i['can']) && ! $user->can($i['can'])) {
                return false;
            }

            // Multi-permission gate (can_any OR logic)
            if (! empty($i['can_any']) && is_array($i['can_any'])) {
                $hasAny = collect($i['can_any'])->some(fn ($perm) => $user->can($perm));
                if (! $hasAny) {
                    return false;
                }
            }

            return true;
        })
        ->filter(fn ($i) => Route::has($i['route'] ?? ''))
        ->values();

    // Partition into primary vs management
    [$primaryItems, $managementItems] = $navItems->partition(fn ($i) => ! ($i['mgmt'] ?? false));

    // Helper: compute active + icon per raw item
    $prepareItems = function ($items) {
        return $items->map(function ($item) {
            // If active_prefix is provided, use that; otherwise default to `route*`
            $prefixes = $item['active_prefix'] ?? [($item['route'] ?? '') . '*'];
            $activePatterns = is_array($prefixes) ? $prefixes : [$prefixes];

            $isActive = request()->routeIs($activePatterns);

            $iconName = $isActive
                ? ($item['icon_fill'] ?? $item['icon'] ?? null)
                : ($item['icon'] ?? null);

            return [
                'label'     => $item['label'] ?? '',
                'route'     => $item['route'] ?? '',
                'icon'      => $iconName,
                'is_active' => $isActive,
            ];
        });
    };

    $primaryView    = $prepareItems($primaryItems);
    $managementView = $prepareItems($managementItems);
@endphp

{{-- ✅ One Alpine scope controls both the rail and the backdrop --}}
<div x-data="{ expanded:false }">
    <nav id="attendify-rail"
        x-on:toggle-rail.window="expanded = !expanded"
        x-init="
            $el.addEventListener('mouseenter', () => {
                if (window.matchMedia('(min-width: 1024px)').matches) expanded = true;
            });
            $el.addEventListener('mouseleave', () => {
                if (window.matchMedia('(min-width: 1024px)').matches) expanded = false;
            });
        "
        class="rl-nav lg:translate-x-0 transition-transform overflow-hidden flex flex-col"
        :class="expanded ? 'expanded translate-x-0' : '-translate-x-full'"
        aria-label="Primary">

        <div class="flex-1 min-h-0 rl-scroll">
            {{-- Mobile-only brand --}}
            <div class="rl-brand lg:hidden px-2"
                 x-show="expanded"
                 x-transition.opacity.duration.150ms>
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/branding/attendify-brand.png') }}" alt="Attendify" class="app-logo-mobile ml-1">
                </div>
            </div>

            <div class="rl-section">
                {{-- Primary --}}
                @if ($primaryView->isNotEmpty())
                    <ul class="rl-list">
                        @foreach ($primaryView as $item)
                            <li class="rl-item {{ $item['is_active'] ? 'active' : '' }}">
                                <a href="{{ route($item['route']) }}"
                                   title="{{ $item['label'] }}"
                                   @if($item['is_active']) aria-current="page" @endif>
                                    <span class="rl-icon">
                                        <i class="bi bi-{{ $item['icon'] }}"></i>
                                    </span>
                                    <span class="rl-label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Divider + Management --}}
                @if ($managementView->isNotEmpty())
                    <div class="rl-divider" aria-hidden="true">
                        <span class="rl-divider__text">Management</span>
                        <span class="rl-divider__rule"></span>
                    </div>

                    <ul class="rl-list mt-1">
                        @foreach ($managementView as $item)
                            <li class="rl-item {{ $item['is_active'] ? 'active' : '' }}">
                                <a href="{{ route($item['route']) }}"
                                   title="{{ $item['label'] }}"
                                   @if($item['is_active']) aria-current="page" @endif>
                                    <span class="rl-icon">
                                        <i class="bi bi-{{ $item['icon'] }}"></i>
                                    </span>
                                    <span class="rl-label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="rl-footer">
            <div class="px-3 py-3 text-[11px] leading-5 text-white/80">
                <div class="flex flex-wrap gap-x-3 gap-y-1">
                    <a class="hover:underline" href="{{ route('landing-page') }}#how-it-works" target="_blank" rel="noopener">About</a>
                    <a class="hover:underline" href="https://www.linkedin.com/in/attendify-support-1297a4387/">Support</a>
                    <a class="hover:underline" href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a>
                    <a class="hover:underline" href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy</a>
                </div>
                <div class="mt-2 opacity-75">© {{ date('Y') }} Attendify</div>
            </div>
        </div>
    </nav>

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/40 z-[1150] lg:hidden"
         x-show="expanded"
         x-transition.opacity
         x-on:click="expanded = false">
    </div>
</div>
