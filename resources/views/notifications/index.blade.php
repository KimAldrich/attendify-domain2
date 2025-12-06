{{-- resources/views/notifications/index.blade.php --}}
<x-app-layout>
    <div class="px-4 pt-4 sm:px-6 lg:px-8">
        {{-- Page header row (visible even when list is empty) --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-slate-900">
                    Notifications
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Always be updated. View all activities happening related to your Attendify account.
                </p>
            </div>

            @php
                /** @var \Illuminate\Pagination\LengthAwarePaginator $notifications */
                $hasRead = $notifications->where('status', 'read')->isNotEmpty();

                // These come from controller or fall back to request()
                $isAdmin = $isAdmin ?? (auth()->user()?->hasRole('admin') ?? false);
                $q       = request('q', '');
                $kind    = request('kind', 'all'); // all|reports|non-reports
            @endphp

            <form method="POST" action="{{ route('notifications.delete-read') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full
                           border text-red-700 bg-red-50 border-red-200
                           hover:bg-red-100 hover:border-red-300
                           disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    @unless($hasRead) disabled @endunless
                >
                    <i class="bi bi-trash3 mr-1 text-[11px]"></i>
                    Delete All Read
                </button>
            </form>
        </div>

        {{-- GitHub-ish card --}}
        <div class="bg-white shadow-sm border border-slate-200 rounded-lg overflow-hidden">
<div class="px-4 py-3 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="text-sm font-semibold text-slate-900">
            Notification feed
        </h2>
        <p class="text-xs text-slate-500">
            Unread notifications are highlighted. Older read items appear below.
        </p>
    </div>

    @if($isAdmin)
        <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            {{-- Preserve other query params except q, kind, page --}}
            @foreach(request()->except('q', 'kind', 'page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            {{-- Search input --}}
            <div class="relative">
                <span class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                    <i class="bi bi-search text-[11px] text-slate-400"></i>
                </span>
                <input
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    class="pl-6 pr-2 py-1.5 rounded-md border border-slate-200 text-xs
                           focus:outline-none focus:ring-1 focus:ring-slate-300 focus:border-slate-300"
                    placeholder="Search title or message"
                >
            </div>

            {{-- Kind filter --}}
            <select
                name="kind"
                class="form-select text-xs h-8"
                onchange="this.form.submit()"
            >
                <option value="all" @selected($kind === 'all')>All types</option>
                <option value="reports" @selected($kind === 'reports')>Reports only</option>
                <option value="non-reports" @selected($kind === 'non-reports')>Other notifications</option>
            </select>

            <button
                type="submit"
                class="hidden sm:inline-flex items-center h-8 px-3 rounded-md border border-slate-200
                       text-slate-700 bg-white hover:bg-slate-50"
            >
                Apply
            </button>
        </form>
    @endif
</div>

            <div class="p-4 sm:p-5">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if($notifications->isEmpty())
                    {{-- Empty state --}}
                    <div class="py-10 flex flex-col items-center text-slate-500">
                        <i class="bi bi-bell-slash text-3xl mb-2"></i>
                        <p class="font-medium">You’re all caught up!</p>
                        <p class="text-sm">There are no notifications for your account right now.</p>
                    </div>
                @else
                    {{-- List of notifications --}}
                    <ul class="divide-y divide-slate-200">
                        @foreach ($notifications as $notification)
                            <li class="py-3 sm:py-4">
                                <div class="flex items-start gap-3 sm:gap-4">
                                    {{-- Status dot --}}
                                    <div class="mt-1.5">
                                        @if($notification->status === 'unread')
                                            <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                                        @else
                                            <span class="inline-block w-2 h-2 rounded-full bg-slate-300"></span>
                                        @endif
                                    </div>

                                    {{-- Text content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h3 class="text-sm text-slate-900
                                                @if($notification->status === 'unread') font-semibold @else font-normal @endif">
                                                {{ $notification->title }}
                                            </h3>

                                            <span class="text-[11px] text-slate-400 whitespace-nowrap">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        @if($notification->message)
                                            <p class="mt-1 text-sm text-slate-600">
                                                {{ $notification->message }}
                                            </p>
                                        @endif

                                        <p class="mt-1 text-[11px] text-slate-400">
                                            {{ $notification->created_at->format('M d, Y · h:i A') }}
                                        </p>
                                    </div>

                                    {{-- Action button (View) --}}
                                    <div class="mt-1 shrink-0">
                                        @if($notification->link)
                                            <a href="{{ route('notifications.open', $notification) }}"
                                               class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full
                                                      bg-slate-50 text-slate-700 border border-slate-200
                                                      hover:bg-slate-100 hover:border-slate-300 transition-colors">
                                                View
                                                <i class="bi bi-chevron-right ms-1 text-[11px]"></i>
                                            </a>
                                        @else
                                            <span class="text-[11px] text-slate-400 italic">
                                                No link
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Pagination footer (non-Livewire version of your snippet) --}}
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
                        {{-- Rows per page --}}
                        <div class="flex items-center gap-2">
                            <span>Rows per page:</span>
                            <form method="GET">
                                {{-- Preserve other query params except perPage & page --}}
                                @foreach(request()->except('perPage', 'page') as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach

                                <select
                                    name="perPage"
                                    class="form-select w-20 text-xs"
                                    onchange="this.form.submit()"
                                >
                                    @foreach([10,25,50,100] as $size)
                                        <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        {{-- Page info + controls --}}
                        <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                            <span>
                                @if($notifications->total() > 0)
                                    {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ $notifications->total() }}
                                @else
                                    0 of 0
                                @endif
                            </span>

                            <div class="flex items-center gap-1">
                                {{-- Previous --}}
                                <button
                                    type="button"
                                    class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200
                                           text-slate-600 hover:bg-slate-50
                                           disabled:opacity-40 disabled:cursor-not-allowed"
                                    @if($notifications->previousPageUrl())
                                        onclick="window.location='{{ $notifications->previousPageUrl() }}'"
                                    @else
                                        disabled
                                    @endif
                                >
                                    ‹
                                </button>

                                {{-- Next --}}
                                <button
                                    type="button"
                                    class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200
                                           text-slate-600 hover:bg-slate-50
                                           disabled:opacity-40 disabled:cursor-not-allowed"
                                    @if($notifications->nextPageUrl())
                                        onclick="window.location='{{ $notifications->nextPageUrl() }}'"
                                    @else
                                        disabled
                                    @endif
                                >
                                    ›
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
