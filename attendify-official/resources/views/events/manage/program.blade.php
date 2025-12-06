{{-- resources/views/events/manage/program.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'program'])

        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @php
            $daysFlash   = session('days_flash');
            $tracksFlash = session('tracks_flash');
        @endphp

        <div class="space-y-4">
    {{-- Top row: Days + Tracks side by side --}}
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,2.3fr)]">

        {{-- DAYS CARD --}}
        @php
            $initialMonth  = $initialMonth ?? now()->format('Y-m');
            $selectedDates = $selectedDates ?? $event->days->pluck('date')->map->format('Y-m-d')->all();
            $lockedDates = $event->days()
                ->whereHas('activities')
                ->pluck('date')
                ->map->format('Y-m-d')
                ->all();
        @endphp

        @php
            $calendarConfig = [
                'initialMonth'    => $initialMonth,
                'initialSelected' => $selectedDates,
                'lockedDates'     => $lockedDates,
            ];
        @endphp

        <form
            id="days"
            method="POST"
            action="{{ route('events.manage.program.days.store', $event) }}"
            class="border border-slate-200 rounded-lg bg-white p-4 space-y-3 flex flex-col scroll-mt-24"
            x-data='daysCalendar({!! json_encode($calendarConfig) !!})'
        >
            @csrf

            @if ($daysFlash)
                @php
                    $isSuccess = ($daysFlash['level'] ?? 'success') === 'success';
                    $daysFlashClasses = $isSuccess
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : 'border-red-200 bg-red-50 text-red-700';
                @endphp
                <div class="mb-2 rounded-md px-3 py-2 text-[11px] {{ $daysFlashClasses }}">
                    {{ $daysFlash['message'] ?? '' }}
                </div>
            @endif

            {{-- header with month label + arrows --}}
            <div class="flex items-center justify-between mb-2">
                <div class="space-y-0.5">
                    <h2 class="text-sm font-semibold text-slate-900">Event Day Selector</h2>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="prevMonth()"
                        class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-300
                               text-slate-600 hover:bg-slate-50"
                    >
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>

                    <span
                        class="inline-flex items-center justify-center px-3 h-8 rounded-md border border-slate-300
                               bg-white text-xs font-medium text-slate-700 min-w-[120px] text-center"
                        x-text="monthLabel"
                    ></span>

                    <button
                        type="button"
                        @click="nextMonth()"
                        class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-300
                               text-slate-600 hover:bg-slate-50"
                    >
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- calendar body (fills available height) --}}
            <div class="flex-1 flex flex-col space-y-2">
                <div class="grid grid-cols-7 text-[11px] font-medium text-slate-400 text-center mb-1">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span>
                    <span>Thu</span><span>Fri</span><span>Sat</span>
                </div>

                <div class="grid grid-cols-7 gap-1 text-xs">
                    <template x-for="cell in cells" :key="cell.key">
                        <div>
                            <button
                                type="button"
                                @click="toggleDate(cell.date)"
                                :disabled="cell.isPast"
                                class="w-full min-h-[40px] py-1.5 rounded-md text-xs flex flex-col items-center justify-center
                                    transition border"
                                :class="{
                                    // other months
                                    'bg-slate-50 text-slate-300 border-slate-200 cursor-default': !cell.inMonth,

                                    // today (on top of other rules)
                                    'ring-2 ring-sky-400 font-semibold text-sky-700': cell.isToday && cell.inMonth,

                                    // selected future day
                                    'bg-sky-600 text-white hover:bg-sky-700 border-sky-600':
                                        cell.inMonth && !cell.isPast && isSelected(cell.date),

                                    // selected past day
                                    'bg-slate-500 text-white border-slate-500':
                                        cell.inMonth && cell.isPast && isSelected(cell.date),

                                    // unselected past day
                                    'bg-slate-100 text-slate-400 border-slate-200':
                                        cell.inMonth && cell.isPast && !isSelected(cell.date),

                                    // unselected future day
                                    'bg-white text-slate-700 hover:bg-slate-100 border-slate-200':
                                        cell.inMonth && !cell.isPast && !isSelected(cell.date),
                                }"
                            >
                                <span x-text="cell.day"></span>

                                {{-- white dot indicator for locked days (has activities) --}}
                                <span
                                    x-show="isLocked(cell.date)"
                                    class="mt-0.5 w-1.5 h-1.5 rounded-full bg-white/90"
                                ></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            <div
                x-show="snackbar"
                x-transition
                class="mt-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1"
                x-text="snackbar"
            ></div>
            {{-- footer --}}
            <div class="flex items-center justify-between mt-1 text-xs text-slate-600">
                <span x-text="selectedSummary"></span>

                {{-- hidden inputs for selected dates --}}
                <template x-for="d in selectedDates" :key="d">
                    <input type="hidden" name="days[]" :value="d">
                </template>

                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                           text-white hover:bg-[#0042a3]"
                >
                    Save days
                </button>
            </div>
        </form>

        {{-- TRACKS CARD --}}
        <div id="tracks" class="border border-slate-200 rounded-lg bg-white p-4 space-y-3 text-sm flex flex-col lg:h-[480px] scroll-mt-24">
            <div class="flex items-center justify-between gap-2 mb-1">
                <h2 class="text-sm font-semibold text-slate-900">Tracks/Venues</h2>
            </div>

            {{-- Tracks flash (success/error) --}}
            @if ($tracksFlash)
                @php
                    $tracksSuccess = ($tracksFlash['level'] ?? 'success') === 'success';
                    $tracksFlashClasses = $tracksSuccess
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : 'border-red-200 bg-red-50 text-red-700';
                @endphp
                <div class="mb-2 rounded-md px-3 py-2 text-[11px] {{ $tracksFlashClasses }}">
                    {{ $tracksFlash['message'] ?? '' }}
                </div>
            @endif

            {{-- Validation errors for tracks --}}
            @if ($errors->tracks->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-[11px] text-red-700 mb-1">
                    <p class="font-semibold mb-1">There were problems with the track form:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->tracks->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Add track form --}}
            <form
                method="POST"
                action="{{ route('events.manage.program.tracks.store', $event) }}"
                class="space-y-2"
            >
                @csrf

                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Venue name (e.g., Multimedia Room)"
                        class="flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-xs
                               focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                    >
                    <input
                        type="text"
                        name="location"
                        value="{{ old('location') }}"
                        placeholder="Location (e.g., Academic Building 1, Room 201)"
                        class="flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-xs
                               focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                    >
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center px-3 py-1.5 rounded-md bg-[#0052CC]
                               text-xs font-medium text-white hover:bg-[#0042a3]"
                    >
                        Add track
                    </button>
                </div>

                <p class="text-[11px] text-slate-500">
                    <b>Use tracks for parallel venues. Update your event locations here.</b>
                </p>
            </form>

            {{-- Tracks list (scrollable, fills remaining height) --}}
            @if ($tracks->count())
                <div class="mt-3 flex-1 overflow-y-auto">
                    <div class="border border-slate-200 rounded-md overflow-hidden">
                        <table class="min-w-full text-xs">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Name</th>
                                    <th class="px-3 py-2 text-left font-medium">Location</th>
                                    <th class="px-3 py-2 text-center font-medium w-20">Activities</th>
                                    <th class="px-3 py-2 text-center font-medium w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tracks as $track)
<tr class="align-middle">
    {{-- Name --}}
    <td class="px-3 py-1.5">
        <form
            method="POST"
            action="{{ route('events.manage.program.tracks.update', [$event, $track]) }}"
            class="flex flex-col"
        >
            @csrf
            @method('PUT')

            <input
                type="text"
                name="name"
                value="{{ old('name', $track->name) }}"
                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs
                       focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]
                       "
            >
    </td>

    {{-- Location --}}
    <td class="px-3 py-1.5">
            <input
                type="text"
                name="location"
                value="{{ old('location', $track->location) }}"
                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs
                       focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]
                       "
            >
    </td>

    {{-- Activities --}}
    <td class="px-3 py-1.5 text-center text-xs text-slate-600">
        {{ $track->activities_count }}
    </td>

    {{-- Actions --}}
    <td class="px-3 py-1.5 text-center">
        <div class="flex items-center justify-center gap-1">
            <button
                type="submit"
                class="inline-flex items-center px-2 py-1 rounded-md border border-slate-300
                       text-[11px] text-slate-700 bg-white hover:bg-slate-50"
            >
                Save
            </button>
        </form>

            <form
                method="POST"
                action="{{ route('events.manage.program.tracks.destroy', [$event, $track]) }}"
                onsubmit="return confirm('Delete this track?');"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex items-center px-2 py-1 rounded-md border border-red-200
                           text-[11px] text-red-600 bg-white hover:bg-red-50"
                >
                    Delete
                </button>
            </form>
        </div>
    </td>
</tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p class="mt-2 text-[11px] text-slate-500">
                    No tracks yet. Use the form above to add your first venue.
                </p>
            @endif
        </div>
    </div>

    {{-- Bottom row: Program activities, full width --}}
    <div id="activities">
        @include('events.manage._program-activities', [
            'event'  => $event,
            'tracks' => $tracks,
        ])
    </div>
        </div>

    </div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            function formatDateLocal(dateObj) {
                const y = dateObj.getFullYear();
                const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                const d = String(dateObj.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`; // YYYY-MM-DD
            }

            function formatMonthLocal(dateObj) {
                const y = dateObj.getFullYear();
                const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                return `${y}-${m}`; // YYYY-MM
            }

            window.daysCalendar = function (config = {}) {
                // Use config.initialMonth if provided, otherwise current local month
                const defaultMonth = config.initialMonth || formatMonthLocal(new Date());
                const initialSelected = Array.isArray(config.initialSelected)
                    ? config.initialSelected
                    : [];
                const locked          = Array.isArray(config.lockedDates) ? config.lockedDates : [];

                const todayLocal = formatDateLocal(new Date());

                return {
                    // state
                    month: defaultMonth,          // "YYYY-MM"
                    today: todayLocal,            // "YYYY-MM-DD"
                    selected: new Set(initialSelected),
                    lockedDates: new Set(locked),
                    snackbar: null,

                    // ─────────────────────────────
                    // Computed props
                    // ─────────────────────────────
                    get monthLabel() {
                        const [year, month] = this.month.split('-').map(Number);
                        const d = new Date(year, month - 1, 1);

                        return d.toLocaleDateString('en-US', {
                            month: 'long',
                            year: 'numeric',
                        });
                    },

                    get cells() {
                        const [year, month] = this.month.split('-').map(Number);
                        const firstOfMonth = new Date(year, month - 1, 1);
                        const monthIndex = firstOfMonth.getMonth();

                        // Start from the Sunday of the first week shown
                        const start = new Date(firstOfMonth);
                        const weekday = start.getDay(); // 0 = Sun, 6 = Sat
                        start.setDate(start.getDate() - weekday);

                        const cells = [];

                        for (let i = 0; i < 42; i++) { // 6 weeks grid
                            const d = new Date(start);
                            d.setDate(start.getDate() + i);

                            const iso = formatDateLocal(d); // "YYYY-MM-DD"
                            const isPast = iso < this.today;
                            const isToday = iso === this.today;

                            cells.push({
                                key: iso,
                                date: iso,
                                day: d.getDate(),
                                inMonth: d.getMonth() === monthIndex,
                                isPast,
                                isToday,
                            });
                        }

                        return cells;
                    },

                    get selectedDates() {
                        return Array.from(this.selected).sort();
                    },

                    get selectedSummary() {
                        const count = this.selected.size;

                        if (count === 0) return 'No days selected';
                        if (count === 1) return '1 day selected';
                        return `${count} days selected`;
                    },

                    // ─────────────────────────────
                    // Methods
                    // ─────────────────────────────
                    isSelected(iso) {
                        return this.selected.has(iso);
                    },

                    isLocked(iso) {
                        return this.lockedDates.has(iso);
                    },

                    toggleDate(iso) {
                        // block past dates completely
                        if (this.lockedDates.has(iso)) {
                        this.snackbar = 'Please remove all activities on that day first.';
                        setTimeout(() => this.snackbar = null, 3000);
                        return;
                    }

                        if (iso < this.today) return;

                        if (this.selected.has(iso)) {
                            this.selected.delete(iso);
                        } else {
                            this.selected.add(iso);
                        }
                    },

                    prevMonth() {
                        const [year, month] = this.month.split('-').map(Number);
                        const d = new Date(year, month - 1, 1);
                        d.setMonth(d.getMonth() - 1);
                        this.month = formatMonthLocal(d); // "YYYY-MM"
                    },

                    nextMonth() {
                        const [year, month] = this.month.split('-').map(Number);
                        const d = new Date(year, month - 1, 1);
                        d.setMonth(d.getMonth() + 1);
                        this.month = formatMonthLocal(d); // "YYYY-MM"
                    },
                };
            };
        });
    </script>
@endpush


</x-app-layout>
