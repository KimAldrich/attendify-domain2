@php
    /** @var \App\Models\User|null $user */
    $user     = auth()->user();
    $fullName = $user?->display_name ?? $user?->name ?? 'User';
    $roleName = $user?->getRoleNames()->first() ?? 'guest';
    $avatar   = $user?->photo_url ?? asset('images/ui/userdefault.jpg');
@endphp

<header class="app-header" x-data="{ open:false }">
    {{-- Left: brand / menu --}}
    <div class="app-header__brand flex items-center gap-2">
        {{-- Drawer button: visible on mobile, hidden on lg+ --}}
        <button
            class="inline-flex lg:hidden items-center h-9 px-3 rounded-md border border-slate-200 text-[var(--c-main)] hover:bg-slate-50"
            x-on:click="$dispatch('toggle-rail')"
            aria-controls="attendify-rail"
            aria-expanded="false"
            title="Menu"
        >
            <i class="bi bi-list text-lg"></i>
            <span class="sr-only">Open menu</span>
        </button>

        {{-- Logo (keep your existing styling) --}}
        <img
            src="{{ asset('images/branding/attendify-brand.png') }}"
            alt="Attendify"
            class="app-logo"
        />
    </div>

    {{-- Right: actions --}}
    <div class="app-header__actions flex items-center gap-3">
        {{-- Notification bell (rounded card) --}}
        <button
            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200 bg-white text-[var(--c-main)] hover:bg-slate-50"
            title="Notifications"
        >
            <i class="bi bi-bell text-base"></i>
            <span class="sr-only">Notifications</span>
        </button>

        {{-- Avatar dropdown --}}
        <div class="relative">
            <button
                class="inline-flex items-center justify-center w-10 h-10 rounded-full ring-1 ring-slate-200 overflow-hidden bg-white hover:ring-slate-300"
                x-on:click="open = !open"
                aria-label="Account menu"
            >
                <img
                    src="{{ $avatar }}"
                    alt="{{ $fullName }} avatar"
                    class="w-full h-full object-cover"
                >
            </button>

            {{-- Dropdown card --}}
            <div
                x-cloak
                x-show="open"
                x-transition
                x-on:click.outside="open = false"
                class="absolute right-0 mt-2
                       w-[360px] sm:w-[420px] max-w-[calc(100vw-1rem)]
                       rounded-3xl bg-white text-slate-800 shadow-lg
                       border border-slate-200 border-t-4 border-t-[var(--c-main)]
                       overflow-hidden z-[1300]"
            >
                {{-- Top: avatar + name + role pill --}}
                <div class="px-5 py-4 flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full overflow-hidden ring-1 ring-slate-200 bg-slate-100">
                        <img
                            src="{{ $avatar }}"
                            alt="{{ $fullName }} avatar"
                            class="w-full h-full object-cover"
                        >
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-slate-900 truncate">
                            {{ $fullName }}
                        </div>
                        @if ($user?->slug)
                            <div class="text-xs text-slate-500 truncate">
                                {{ $user->slug }}
                            </div>
                        @endif
                        <div class="mt-1">
                            <span class="inline-flex items-center px-3 py-0.5 text-xs font-medium rounded-full bg-blue-50 text-[var(--c-main)] border border-blue-100">
                                {{ Str::title($roleName) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-slate-200"></div>

                {{-- My Account --}}
                <a
                    href="{{ route('profile.me') }}"
                    class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-slate-800 hover:bg-slate-50"
                >
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-[var(--c-main-soft)] text-[var(--c-main)]">
                        <i class="bi bi-gear"></i>
                    </span>
                    <span>My Account</span>
                </a>

                <div class="h-px bg-slate-200"></div>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full text-left flex items-center gap-3 px-5 py-3 text-sm font-medium text-slate-800 hover:bg-slate-50"
                    >
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-[var(--c-main-soft)] text-[var(--c-main)]">
                            <i class="bi bi-box-arrow-right"></i>
                        </span>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
