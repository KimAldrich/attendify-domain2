{{-- resources/views/events/manage/people.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        {{-- Header + tabs --}}
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'people'])

        {{-- Flash status --}}
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                <p class="font-semibold mb-1">There were problems with your input:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $ownerRole = $event->userRoles->firstWhere('role', 'owner');
            $ownerUser = $event->owner ?? optional($ownerRole)->user;

            $coOrganizerRoles = $event->userRoles->where('role', 'co_organizer');
            $staffRoles       = $event->userRoles->where('role', 'staff');
        @endphp

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- LEFT: Hybrid picker form --}}
            <form
                method="POST"
                action="{{ route('events.manage.people.roles.store', $event) }}"
                class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-4 pb-24"
                x-data='coStaffPicker(@json($roleCandidateOptions))'
                x-cloak
                @submit.prevent="
                    if (entries.length === 0) {
                        alert('Please add at least one person before saving.');
                        return;
                    }
                    $el.submit();
                "
            >
                @csrf

                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Add co-organizers & staff
                    </h2>
                    <x-heroicon-o-user-plus class="w-4 h-4 text-slate-400" />
                </div>

                <p class="text-xs text-slate-600">
                    Use this panel to assign students and faculty as co-organizers or staff.
                    You can add multiple people and then save them in one go.
                </p>

                {{-- Selected entries --}}
                <div class="space-y-2">
                    <template x-if="entries.length === 0">
                        <p class="text-[11px] text-slate-500">
                            No people selected yet. Use the search below to add co-organizers or staff.
                        </p>
                    </template>

                    <template x-for="entry in entries" :key="entry.user_id">
                        <div
                            class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs"
                        >
                            <div>
                                <div class="font-medium text-slate-800" x-text="entry.name"></div>
                                <div class="text-[11px] text-slate-500" x-text="entry.email"></div>
                                <div class="text-[11px] text-slate-600 mt-0.5">
                                    Role:
                                    <span class="font-medium" x-text="entry.role_label"></span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="text-[11px] text-slate-500 hover:text-red-600"
                                @click="remove(entry.user_id)"
                            >
                                Remove
                            </button>

                            {{-- Hidden inputs for backend --}}
                            <template x-if="entry.role === 'co_organizer'">
                                <input type="hidden" name="co_organizers[]" :value="entry.user_id">
                            </template>
                            <template x-if="entry.role === 'staff'">
                                <input type="hidden" name="staff[]" :value="entry.user_id">
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Role for next added user --}}
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <p class="text-[11px] text-slate-500 leading-relaxed mt-1">
                        <span class="font-medium text-slate-700">Co-organizers</span> can help manage the event:
                        they can edit event details, manage the program, oversee registration, view analytics, and assist in
                        post-event workflows. They act as secondary managers after the event owner.<br><br>
                        <span class="font-medium text-slate-700">Staff</span> assist with on-site operations such as
                        handling registration booths, scanning QR codes, and verifying attendance. Their access is
                        limited to operational tools only and they cannot modify event details.
                    </p>
                    <label class="text-[11px] font-medium text-slate-700">
                        Role for next added user:
                    </label>
                    <select
                        x-model="currentRole"
                        class="rounded-md border border-slate-300 px-2 pr-6 py-1 text-xs
                            focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                    >
                        <option value="co_organizer">Co-organizer</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>

                {{-- Hybrid search + dropdown --}}
                <div class="space-y-1" @click.away="isOpen = false">
                    <label class="block text-[11px] font-medium text-slate-700">
                        Search users
                    </label>

                    <div class="relative">
                        <div
                            class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs"
                        >
                            <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5 text-slate-400" />
                            <input
                                type="text"
                                x-model="query"
                                @focus="isOpen = true"
                                placeholder="Type a name or email..."
                                class="flex-1 border-none focus:outline-none focus:ring-0 text-xs text-slate-700"
                            >
                            <button
                                type="button"
                                class="text-[11px] text-slate-500 hover:text-slate-700"
                                @click="clearSearch()"
                                x-show="query"
                            >
                                Clear
                            </button>
                        </div>

                        {{-- Dropdown options --}}
                        <div
                            class="absolute z-10 mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200
                                bg-white shadow-sm text-xs"
                            x-show="isOpen && filtered.length"
                            x-transition
                        >
                            <template x-for="user in filtered" :key="user.id">
                                <button
                                    type="button"
                                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center justify-between"
                                    @click="add(user)"
                                >
                                    <div>
                                        <div class="font-medium text-slate-800" x-text="user.name"></div>
                                        <div class="text-[11px] text-slate-500" x-text="user.email"></div>
                                    </div>
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600"
                                        x-text="currentRole === 'co_organizer' ? 'Co-organizer' : 'Staff'"
                                    ></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500">
                        Only student and faculty accounts are listed here.
                    </p>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                            text-white hover:bg-[#0042a3]"
                    >
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                        Save roles
                    </button>
                </div>
            </form>


            {{-- RIGHT: Owner + lists (co-organizers first, then staff) --}}
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-5">
                {{-- Owner --}}
                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-medium text-slate-600">
                            Event owner
                        </p>
                        <x-heroicon-o-user class="w-4 h-4 text-slate-400" />
                    </div>

                        @if ($ownerUser)
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    {{-- Avatar --}}
                                    <img
                                        src="{{ $ownerUser->photo_url ?? asset('images/ui/userdefault.jpg') }}"
                                        alt="{{ $ownerUser->full_name }}"
                                        class="w-9 h-9 rounded-full object-cover flex-shrink-0"
                                    >

                                    {{-- Text --}}
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $ownerUser->full_name }}
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ $ownerUser->email }}
                                        </div>
                                    </div>
                                </div>

                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[11px] text-sky-700 font-medium">
                                    Owner
                                </span>
                            </div>
                        @else
                            <p class="mt-1 text-xs text-slate-500">
                                No explicit owner record set. The event creator is treated as the owner.
                            </p>
                        @endif
                    </div>


                {{-- Co-organizers list (top) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold text-slate-800">
                            Co-organizers
                        </h3>
                        <x-heroicon-o-user-group class="w-4 h-4 text-slate-400" />
                    </div>

                    @if ($coOrganizerRoles->isEmpty())
                        <p class="text-xs text-slate-500">
                            No co-organizers assigned yet.
                        </p>
                    @else
                        <ul class="space-y-1.5">
                            @foreach ($coOrganizerRoles as $role)
                                <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-1.5">
                                    <div class="flex items-center gap-3">
                                        {{-- Avatar --}}
                                        <img
                                            src="{{ $role->user?->photo_url ?? asset('images/ui/userdefault.jpg') }}"
                                            alt="{{ $role->user?->full_name ?? 'User #'.$role->user_id }}"
                                            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                                        >

                                        {{-- Text --}}
                                        <div>
                                            <div class="text-sm font-medium text-slate-900">
                                                {{ $role->user?->full_name ?? 'User #'.$role->user_id }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                {{ $role->user?->email }}
                                            </div>
                                        </div>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('events.manage.people.roles.destroy', [$event, $role]) }}"
                                        onsubmit="return confirm('Remove this co-organizer from the event?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-[11px] text-red-600 hover:text-red-700"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Staff list (bottom) --}}
                <div class="space-y-2 border-t border-slate-100 pt-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold text-slate-800">
                            Staff
                        </h3>
                        <x-heroicon-o-qr-code class="w-4 h-4 text-slate-400" />
                    </div>

                    @if ($staffRoles->isEmpty())
                        <p class="text-xs text-slate-500">
                            No staff members assigned yet.
                        </p>
                    @else
                        <ul class="space-y-1.5">
                            @foreach ($staffRoles as $role)
                                <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-1.5">
                                    <div class="flex items-center gap-3">
                                        {{-- Avatar --}}
                                        <img
                                            src="{{ $role->user?->photo_url ?? asset('images/ui/userdefault.jpg') }}"
                                            alt="{{ $role->user?->full_name ?? 'User #'.$role->user_id }}"
                                            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                                        >

                                        {{-- Text --}}
                                        <div>
                                            <div class="text-sm font-medium text-slate-900">
                                                {{ $role->user?->full_name ?? 'User #'.$role->user_id }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                {{ $role->user?->email }}
                                            </div>
                                        </div>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('events.manage.people.roles.destroy', [$event, $role]) }}"
                                        onsubmit="return confirm('Remove this staff member from the event?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-[11px] text-red-600 hover:text-red-700"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
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
