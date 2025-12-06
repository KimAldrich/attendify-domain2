{{-- resources/views/admin/role-applications/index.blade.php --}}
<x-app-layout>
<div class="px-4 md:px-6 py-4 space-y-4">
    <h1 class="text-lg font-semibold text-slate-900 mb-1">
        Role upgrade requests
    </h1>
    <p class="text-xs text-slate-500 mb-4">
        Review pending upgrade requests and update user roles.
    </p>

    @if (session('status'))
        <div class="mb-3 rounded-md border border-emerald-300 bg-emerald-50 text-emerald-900 px-3 py-2 text-xs">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET"
          class="mb-4 bg-white border rounded-xl p-3 sm:p-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <input type="hidden" name="per_page" value="{{ $perPage ?? 20 }}">
        <div class="flex flex-wrap gap-3">
            {{-- Status --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Status</label>
                @php $status = $status ?? 'pending'; @endphp
                <select name="status" class="form-select text-sm">
                    <option value="all"      {{ $status === 'all' ? 'selected' : '' }}>All</option>
                    <option value="pending"  {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="accepted" {{ $status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="declined" {{ $status === 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>

            {{-- Request type --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Request type</label>
                @php $type = $type ?? 'all'; @endphp
                <select name="type" class="form-select text-sm">
                    <option value="all"     {{ $type === 'all' ? 'selected' : '' }}>All</option>
                    <option value="student" {{ $type === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="faculty" {{ $type === 'faculty' ? 'selected' : '' }}>Faculty</option>
                </select>
            </div>

            {{-- Date range --}}
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-input text-sm">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-input text-sm">
            </div>
        </div>

        {{-- Search --}}
        <div class="flex gap-2 sm:w-64">
            <div class="flex-1">
                <label class="block text-[11px] font-medium text-slate-500 mb-1">Search</label>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Name or email…"
                       class="form-input text-sm w-full">
            </div>
            <div class="self-end pb-0.5">
                <button type="submit"
                        class="inline-flex items-center h-9 px-3 rounded-md bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                    <i class="bi bi-search me-1.5"></i> Filter
                </button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white border rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b">
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-3 py-2.5">User</th>
                    <th class="px-3 py-2.5">Requested role</th>
                    <th class="px-3 py-2.5">Submitted at</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">Credential</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requests as $req)
                    <tr>
                        <td class="px-3 py-2.5 align-top">
                            <div class="text-[13px] text-slate-900">
                                {{ $req->name ?? $req->user->name ?? '—' }}
                            </div>
                            <div class="text-[11px] text-slate-500">
                                {{ $req->user->email ?? 'No email' }}
                            </div>
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium
                                         {{ $req->type === 'student' ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-indigo-50 border-indigo-200 text-indigo-700' }}">
                                {{ ucfirst($req->type) }}
                            </span>
                        </td>

                        <td class="px-3 py-2.5 align-top text-[12px] text-slate-700">
                            {{ $req->created_at?->format('M j, Y H:i') ?? '—' }}
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            @php
                                $statusClass = match ($req->status) {
                                    'pending'  => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'accepted' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'declined' => 'bg-rose-50 text-rose-800 border-rose-200',
                                    default    => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $statusClass }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>

                        <td class="px-3 py-2.5 align-top text-[12px]">
                            @if ($req->creds_path)
                                {{-- Small “View” button opens modal via Alpine; URL depends on your disk --}}
                                @php
                                    $url = Storage::disk('r2')->url($req->creds_path);
                                @endphp
                                <button type="button"
                                        x-data
                                        x-on:click="$dispatch('open-credential', { url: '{{ $url }}' })"
                                        class="inline-flex items-center px-2 py-1 text-[11px] rounded-md border border-slate-200 hover:bg-slate-50">
                                    <i class="bi bi-image me-1"></i> View
                                </button>
                            @else
                                <span class="text-slate-400 text-[11px]">No file</span>
                            @endif
                        </td>

                        <td class="px-3 py-2.5 align-top">
                            <div class="flex justify-end gap-2 text-[11px]">
                                @if ($req->status === 'pending')
                                    <form method="POST"
                                          action="{{ route('admin.role-applications.approve', $req) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                                            <i class="bi bi-check2 me-1"></i> Accept
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('admin.role-applications.reject', $req) }}"
                                          onsubmit="return confirm('Reject this request?');">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1 rounded-md border border-rose-300 text-rose-700 hover:bg-rose-50">
                                            <i class="bi bi-x-lg me-1"></i> Reject
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">
                                        Already {{ $req->status }}
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-xs text-slate-500">
                            No role upgrade requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

<div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
    {{-- Left: rows per page --}}
    <div class="flex items-center gap-2">
        <span>Rows per page:</span>
        <form method="GET">
            {{-- keep existing filters when changing per_page --}}
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">

            <select
                name="per_page"
                class="form-select w-20 text-xs"
                onchange="this.form.submit()"
            >
                @foreach([10, 20, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Right: 1–4 of 4 + prev/next --}}
    <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
        <span>
            @if($requests->total() > 0)
                {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }}
            @else
                0 of 0
            @endif
        </span>

        <div class="flex items-center gap-1">
            <a
                href="{{ $requests->previousPageUrl() ?? '#' }}"
                class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 {{ $requests->onFirstPage() ? 'opacity-40 pointer-events-none' : '' }}"
            >
                
            </a>
            <a
                href="{{ $requests->nextPageUrl() ?? '#' }}"
                class="h-7 w-7 flex items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50 {{ $requests->hasMorePages() ? '' : 'opacity-40 pointer-events-none' }}"
            >
                
            </a>
        </div>
    </div>
</div>

</div>

{{-- Simple image modal, GitHub-ish --}}
<div x-data="{ open:false, url:null }"
     x-on:open-credential.window="open=true; url=$event.detail.url"
     x-cloak
     x-show="open"
     x-transition.opacity
     class="fixed inset-0 z-[2100] bg-black/70 flex items-center justify-center px-4">
    <div class="bg-slate-900/90 rounded-xl p-3 max-w-[96vw] max-h-[90vh] flex flex-col gap-2"
         x-on:click.stop>
        <div class="flex items-center justify-between text-slate-100 text-xs">
            <span>Credential preview</span>
            <button type="button"
                    class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-800"
                    x-on:click="open=false">
                <i class="bi bi-x-lg text-[11px]"></i>
            </button>
        </div>
        <img :src="url"
             alt="Credential"
             class="max-w-[88vw] max-h-[78vh] object-contain rounded-lg bg-slate-900">
    </div>
</div>
</x-app-layout>
