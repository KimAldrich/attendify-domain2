@php
    /** @var \App\Models\User|null $user */
    $user     = auth()->user();
    $isGuest  = ! auth()->check();
    $fullName = $user?->display_name ?? $user?->name ?? 'Guest';
    $roleName = $user?->getRoleNames()->first() ?? 'guest';
    $version  = $user?->updated_at->timestamp;
    $avatar   = $user?->photo_url ?? asset('images/ui/userdefault.jpg');
@endphp

<header class="app-header" x-data="{ open:false }">
    {{-- Left: brand / menu --}}
    <div class="app-header__brand flex items-center gap-2">
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

        <img
            src="{{ asset('images/branding/attendify-brand.png') }}"
            alt="Attendify"
            class="app-logo"
        />
    </div>

<div class="app-header__actions flex items-center gap-3">
    {{-- Notification bell (only when logged in) --}}
    @unless($isGuest)
        @php
            $unreadNotifications = $user
                ? \App\Models\Notification::where('user_id', $user->id)
                    ->where('status', 'unread')
                    ->orderByDesc('created_at')
                    ->take(5)
                    ->get()
                : collect();

            $hasUnread = $unreadNotifications->isNotEmpty();
        @endphp

        <div class="relative" x-data="{ openNotif:false }">
            <button
                class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200 bg-white text-[var(--c-main)] hover:bg-slate-50 relative"
                title="Notifications"
                x-on:click="openNotif = !openNotif"
            >
                <i class="bi bi-bell text-base"></i>
                <span class="sr-only">Notifications</span>

                @if($hasUnread)
                    <span class="absolute top-1 right-1 inline-block w-2 h-2 rounded-full bg-red-500"></span>
                @endif
            </button>

            {{-- Dropdown panel --}}
            <div
                x-cloak
                x-show="openNotif"
                x-transition
                x-on:click.outside="openNotif = false"
                class="absolute right-0 mt-2
                       w-[320px] sm:w-[360px] max-w-[calc(100vw-1rem)]
                       rounded-2xl bg-white text-slate-800 shadow-lg
                       border border-slate-200 overflow-hidden z-[1300]"
            >
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Notifications
                        </p>
                        <p class="text-xs text-slate-500">
                            Latest unread activity for your account.
                        </p>
                    </div>
                </div>

                <div class="max-h-80 overflow-y-auto">
                    @if($hasUnread)
                        <ul class="divide-y divide-slate-100">
                            @foreach($unreadNotifications as $notification)
                                <li class="px-4 py-3">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 inline-block w-2 h-2 rounded-full bg-blue-500"></span>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-slate-900 truncate">
                                                {{ $notification->title }}
                                            </p>
                                            @if($notification->message)
                                                <p class="mt-0.5 text-[11px] text-slate-600 line-clamp-2">
                                                    {{ $notification->message }}
                                                </p>
                                            @endif
                                            <p class="mt-0.5 text-[11px] text-slate-400">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </div>

                                        @if($notification->link)
                                            <a href="{{ route('notifications.open', $notification) }}"
                                               class="mt-1 inline-flex items-center justify-center h-7 px-2 text-[11px] font-medium rounded-full
                                                      bg-slate-50 text-slate-700 border border-slate-200
                                                      hover:bg-slate-100 hover:border-slate-300">
                                                View
                                            </a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="px-4 py-6 text-center text-xs text-slate-500">
                            <i class="bi bi-bell-slash text-lg mb-1 block text-slate-400"></i>
                            <p>No new notifications.</p>
                        </div>
                    @endif
                </div>

                <div class="border-t border-slate-200 bg-slate-50">
                    <a
                        href="{{ route('notifications.index') }}"
                        class="flex items-center justify-between px-4 py-2.5 text-xs font-medium text-[var(--c-main)] hover:bg-slate-100"
                    >
                        <span>View all notifications</span>
                        <i class="bi bi-chevron-right text-[11px]"></i>
                    </a>
                </div>
            </div>
        </div>
    @endunless

        {{-- Avatar dropdown --}}
        <div class="relative">
            <button
                class="inline-flex items-center justify-center w-10 h-10 rounded-full ring-1 ring-slate-200 overflow-hidden bg-white hover:ring-slate-300"
                x-on:click="open = !open"
                aria-label="Account menu"
            >
                <img
                    src="{{ $avatar }}?v={{ $version }}"
                    alt="{{ $fullName }} avatar"
                    class="w-full h-full object-cover"
                >
            </button>

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
                @if (! $isGuest)
                    {{-- Logged-in dropdown --}}
                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full overflow-hidden ring-1 ring-slate-200 bg-slate-100">
                            <img
                                src="{{ $avatar }}?v={{ $version }}"
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
                @else
                    {{-- Guest dropdown --}}
                    <div class="px-5 py-4 flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full overflow-hidden ring-1 ring-slate-200 bg-slate-100">
                            <img
                                src="{{ $avatar }}"
                                alt="Guest avatar"
                                class="w-full h-full object-cover"
                            >
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-slate-900 truncate">
                                Guest
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                You’re browsing without an Attendify account.
                            </div>
                        </div>
                    </div>

                    <div class="h-px bg-slate-200"></div>

                    <a
                        href="{{ route('register') }}"
                        class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-[var(--c-main)] hover:bg-slate-50"
                    >
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-[var(--c-main-soft)] text-[var(--c-main)]">
                            <i class="bi bi-person-plus"></i>
                        </span>
                        <span>Register Now</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
