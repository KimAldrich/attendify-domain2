{{-- resources/views/events/manage/index.blade.php --}}
<x-app-layout>
    <div
        class="min-h-screen bg-slate-50 px-6 py-8"
        x-data="{
            createEventOpen: {{ $errors->createEvent->any() ? 'true' : 'false' }},
            createStep: 1,
            createErrors: {},

            validateStep(step) {
                this.createErrors = {};

                if (step === 1) {
                    const title = document.getElementById('title')?.value.trim() || '';
                    const subtitle = document.getElementById('subtitle')?.value.trim() || '';
                    const eventType = document.getElementById('event_type')?.value || '';

                    if (!title) {
                        this.createErrors.title = 'Please enter an event title.';
                    }
                    if (!subtitle) {
                        this.createErrors.subtitle = 'Please enter a subtitle/tagline.';
                    }
                    if (!eventType) {
                        this.createErrors.event_type = 'Please select an event type.';
                    }
                }

                if (step === 3) {
                    const visChecked = document.querySelector('input[name=visibility]:checked');
                    if (!visChecked) {
                        this.createErrors.visibility = 'Please choose a visibility option.';
                    }
                }

                if (step === 4) {
                    const start = document.getElementById('start_at')?.value.trim() || '';
                    if (!start) {
                        this.createErrors.start_at = 'Please set a start date and time.';
                    }
                }

                return Object.keys(this.createErrors).length === 0;
            }
        }"
        x-cloak
    >
        <div class="max-w-6xl mx-auto space-y-6">

            {{-- BREADCRUMBS --}}
            <x-breadcrumbs :items="[
                [
                    'label' => 'Events',
                    'url'   => route('events.index'),
                ],
                [
                    'label' => 'Manage Events',
                ],
            ]" />

            {{-- PAGE HEADER --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">
                        Manage Events
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Events you can manage, organized by status.
                    </p>
                </div>

                @can('manage events')
                    <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg
                            bg-[#0052CC] text-white shadow-sm hover:bg-[#0042a3] transition"
                        @click="
                            createEventOpen = true;
                            createStep = 1;
                        "
                    >
                        <x-heroicon-o-plus class="w-4 h-4 mr-1.5" />
                        Create Event
                    </button>
                @endcan
            </div>

            {{-- STATUS TABS --}}
            @include('events.manage._index-tabs', [
                'activeStatus'   => $activeStatus,
                'statusLabels'   => $statusLabels,
                'countsByStatus' => $countsByStatus,
            ])

            {{-- MAIN CARD (filters + list/grid) --}}
            <div
                class="bg-white shadow border border-slate-200"
                x-data="{ layout: 'grid' }"
                x-cloak
            >
                {{-- FILTER BAR: search + layout toggle --}}
                <div class="px-4 pt-4 pb-3 border-b border-slate-100">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        {{-- Search --}}
                        <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
                            {{-- Preserve active status when filtering --}}
                            <input type="hidden" name="status" value="{{ $activeStatus }}">

                            <label for="q" class="text-xs font-medium text-slate-600">
                                Search events:
                            </label>
                            <input
                                type="text"
                                name="q"
                                id="q"
                                value="{{ $search }}"
                                placeholder="Search by title or description..."
                                class="w-full md:w-64 rounded-lg border border-slate-200 px-3 py-1.5 text-sm
                                       focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                            />
                        </form>

                        {{-- Layout toggle: grid vs list --}}
                        <x-layout-toggle />
                    </div>
                </div>

                {{-- CURRENT TAB CONTENT --}}
                @if ($events->isEmpty())
                    <div class="p-6 text-sm text-slate-600">
                        @switch($activeStatus)
                            @case('draft')
                                You don't have any event drafts yet.
                                @can('manage events')
                                    <span class="block mt-1">
                                        Use the <span class="font-bold">Create Event</span> button to start a new event.
                                    </span>
                                @endcan
                                @break

                            @case('published')
                                There are no published events found under your account.
                                @break

                            @case('ongoing')
                                You don't have any ongoing events right now.
                                @break

                            @case('finished')
                                You don't have any finished events yet.
                                @break

                            @case('archived')
                                There are no archived events found under your account.
                                @break

                            @default
                                No events found for this status.
                        @endswitch
                    </div>
                @else
                    {{-- LIST LAYOUT --}}
                    <div x-show="layout === 'list'" class="divide-y divide-slate-100">
                        @foreach ($events as $event)
                            @include('events.manage._index-event-card', [
                                'event'  => $event,
                                'layout' => 'list',
                            ])
                        @endforeach
                    </div>

                    {{-- GRID LAYOUT --}}
                    <div
                        x-show="layout === 'grid'"
                        class="p-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        @foreach ($events as $event)
                            @include('events.manage._index-event-card', [
                                'event'  => $event,
                                'layout' => 'grid',
                            ])
                        @endforeach
                    </div>

                    {{-- Single table footer / paginator --}}
                    <x-table-footer :paginator="$events" />
                @endif
            </div>
        </div>
        @include('events.manage._create-event-modal')
    </div>

@push('scripts')
<script>
    // Plain JS factories that Alpine can call from x-data

    function audiencePicker(options = [], selectedValues = []) {
        return {
            query: '',
            isOpen: false,
            options: options,
            selected: options.filter(opt => (selectedValues || []).includes(opt.value)),

            get filtered() {
                const q = this.query.toLowerCase().trim();

                let base = this.options.filter(opt =>
                    !this.selected.some(s => s.value === opt.value)
                );

                if (q) {
                    base = base.filter(opt =>
                        (opt.label || '').toLowerCase().includes(q) ||
                        (opt.group || '').toLowerCase().includes(q)
                    );
                }

                // show maximum of 4 nearest items
                return base.slice(0, 4);
            },

            add(option) {
                if (!this.selected.some(s => s.value === option.value)) {
                    this.selected.push(option);
                }
                this.query = '';
                this.isOpen = true;

                this.$nextTick(() => {
                    if (this.$refs.audienceInput) {
                        this.$refs.audienceInput.focus();
                    }
                });
            },

            remove(value) {
                this.selected = this.selected.filter(s => s.value !== value);
            }
        };
    }

    function coStaffPicker(options = []) {
        return {
            query: '',
            isOpen: false,
            currentRole: 'co_organizer',
            options: options,
            entries: [],

            get filtered() {
                const q = this.query.toLowerCase().trim();

                let base = this.options.filter(user =>
                    !this.entries.some(e => e.user_id === user.id)
                );

                if (q) {
                    base = base.filter(user =>
                        (user.name || '').toLowerCase().includes(q) ||
                        (user.email || '').toLowerCase().includes(q)
                    );
                }

                // show maximum of 4 nearest items
                return base.slice(0, 4);
            },

            add(user) {
                if (this.entries.some(e => e.user_id === user.id)) {
                    return;
                }
                this.entries.push({
                    user_id: user.id,
                    name: user.name,
                    email: user.email,
                    role: this.currentRole,
                    role_label: this.currentRole === 'co_organizer'
                        ? 'Co-organizer'
                        : 'Staff',
                });
                this.query = '';
                this.isOpen = true;
            },

            remove(userId) {
                this.entries = this.entries.filter(e => e.user_id !== userId);
            },

            clearSearch() {
                this.query = '';
            }
        };
    }
</script>
@endpush


</x-app-layout>
