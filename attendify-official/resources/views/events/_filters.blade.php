{{-- events/_filters.blade.php --}}
<form method="GET" class="mb-5 space-y-3">
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm px-4 py-3 space-y-3">
        {{-- Row 1 --}}
        <div class="grid gap-3 md:grid-cols-4 items-end">
            <div class="md:col-span-2 space-y-1">
                <label for="q" class="text-xs font-semibold text-slate-600">Search</label>
                <input
                    type="text"
                    name="q"
                    id="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search title, subtitle, or owner..."
                    list="owner-options"
                    class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                />
                <datalist id="owner-options">
                    @foreach (($owners ?? collect()) as $owner)
                        <option value="{{ $owner }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="space-y-1">
                <label for="event_type" class="text-xs font-semibold text-slate-600">Event type</label>
                <select
                    name="event_type"
                    id="event_type"
                    class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                >
                    <option value="">All types</option>
                    @foreach (($eventTypes ?? collect()) as $type)
                        <option value="{{ $type }}" @selected(($filters['event_type'] ?? '') === $type)>
                            {{ str($type)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label for="status" class="text-xs font-semibold text-slate-600">Status</label>
                <select
                    name="status"
                    id="status"
                    class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                >
                    <option value="">All</option>
                    <option value="published" @selected(($filters['status'] ?? '') === 'published')>Oncoming (Published)</option>
                    <option value="ongoing" @selected(($filters['status'] ?? '') === 'ongoing')>Ongoing</option>
                    <option value="finished" @selected(($filters['status'] ?? '') === 'finished')>Finished</option>
                </select>
            </div>
        </div>

        {{-- Row 2 --}}
        <div class="grid gap-3 md:grid-cols-4 items-end">
            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-600">Start date (from)</label>
                <input
                    type="date"
                    name="start_from"
                    value="{{ $filters['start_from'] ?? '' }}"
                    class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                />
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-600">Start date (to)</label>
                <input
                    type="date"
                    name="start_to"
                    value="{{ $filters['start_to'] ?? '' }}"
                    class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                />
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700 justify-end md:justify-end md:self-end">
                <input
                    type="checkbox"
                    name="joinable"
                    value="1"
                    @checked($filters['joinable'] ?? false)
                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                />
                Show only events I can join
            </label>

            <div class="flex flex-wrap items-center justify-end gap-2 md:self-end">
                <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                    <x-heroicon-o-arrow-path class="w-4 h-4" />
                    Reset
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-md bg-sky-600 text-white text-sm font-medium shadow-sm hover:bg-sky-700 focus:ring-2 focus:ring-offset-1 focus:ring-sky-500"
                >
                    <x-heroicon-o-funnel class="w-4 h-4" />
                    Apply
                </button>
            </div>
        </div>
    </div>
</form>
