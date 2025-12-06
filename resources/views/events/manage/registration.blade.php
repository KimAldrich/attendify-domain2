{{-- resources/views/events/manage/registration.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'registration'])

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-2">
            {{-- Capacity (active registrations vs limit) --}}
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide">
                        Capacity
                    </span>
                    <x-heroicon-o-user-group class="w-4 h-4 text-slate-400" />
                </div>
                <div class="mt-1 flex items-baseline gap-1">
                    <span class="text-lg font-semibold text-slate-900">
                        {{ $totalRegistrations }}
                    </span>
                    @if ($event->capacity)
                        <span class="text-xs text-slate-500">
                            / {{ $event->capacity }}
                        </span>
                    @endif
                </div>
                @php
                    if ($event->enable_waitlist) {
                        $capacityDesc = 'Open with Whitelisting';
                    } elseif (! $event->capacity) {
                        $capacityDesc = 'Open Attendance';
                    } else {
                        $slotsLeft = max($event->capacity - $totalRegistrations, 0);
                        $capacityDesc = $slotsLeft === 0
                            ? 'Limit Reached'
                            : "{$slotsLeft} Slots Left";
                    }
                @endphp
                <p class="mt-0.5 text-[0.7rem] text-slate-500">
                    {{ $capacityDesc }}
                </p>
            </div>

            {{-- Pending --}}
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide">
                        Pending
                    </span>
                    <x-heroicon-o-clock class="w-4 h-4 text-amber-500" />
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ $pendingCount }}
                </div>
                <p class="mt-0.5 text-[0.7rem] text-slate-500">
                    Awaiting approval.
                </p>
            </div>

            {{-- Waitlisted --}}
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide">
                        Waitlisted
                    </span>
                    <x-heroicon-o-arrow-path class="w-4 h-4 text-indigo-500" />
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ $waitlistedCount }}
                </div>
                <p class="mt-0.5 text-[0.7rem] text-slate-500">
                    On hold when capacity is full.
                </p>
            </div>

            {{-- Accepted --}}
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide">
                        Accepted
                    </span>
                    <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500" />
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ $approvedCount }}
                </div>
                <p class="mt-0.5 text-[0.7rem] text-slate-500">
                    Approved attendees.
                </p>
            </div>

            {{-- Rejected --}}
            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[0.7rem] font-semibold text-slate-500 uppercase tracking-wide">
                        Rejected
                    </span>
                    <x-heroicon-o-x-circle class="w-4 h-4 text-rose-500" />
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ $rejectedCount }}
                </div>
                <p class="mt-0.5 text-[0.7rem] text-slate-500">
                    Registrations that were declined.
                </p>
            </div>
        </div>

        @if ($settingsLocked)
            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Registration settings are read-only because the event is {{ $event->status }}.
            </div>
        @endif

        <div
            x-data="{
                showSettings: @js(session('open_settings', false) || ($errors->updateRegistrationSettings->any() ?? false)),
                paymentModalOpen: false,
                paymentImageUrl: '',
                paymentDisplayName: '',
                paymentEmail: '',
                paymentFileType: '',
                paymentIsPdf: false,
                paymentIsImage: false,
                setPayment(url, name, email) {
                    this.paymentImageUrl = url;
                    this.paymentDisplayName = name;
                    this.paymentEmail = email;
                    const ext = (url || '').split('.').pop()?.toLowerCase() || '';
                    this.paymentFileType = ext;
                    this.paymentIsPdf = ext === 'pdf';
                    this.paymentIsImage = ['jpg','jpeg','png','webp','gif','bmp'].includes(ext);
                    this.paymentModalOpen = true;
                },
                closePayment() {
                    this.paymentModalOpen = false;
                },
            }"
            class="grid gap-4"
            :class="showSettings ? 'lg:grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)]' : 'lg:grid-cols-1'"
        >

        {{-- Left: registrations list --}}
            <div
                id="registrations"
                class="border border-slate-200 rounded-lg bg-white overflow-hidden scroll-mt-24 text-smscroll-mt-24 min-h-[960px] flex flex-col"
            >
                {{-- Success flash for table actions --}}
                @if (session('registration_table_status'))
                    <div class="mx-4 mt-3 mb-1 rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                        {{ session('registration_table_status') }}
                    </div>
                @endif

                {{-- Header --}}
                <div class="px-4 py-2 border-b border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-users class="w-4 h-4 text-slate-500" />
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                            Registrations
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="showSettings = !showSettings"
                        class="inline-flex items-center gap-1 rounded-md border border-sky-400 bg-sky-50 text-sky-700 hover:bg-sky-100 px-3 py-1 text-[0.7rem]"
                        :aria-pressed="showSettings"
                        :class="showSettings ? 'border-sky-500 text-sky-800 bg-sky-100' : ''"
                    >
                        <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                        <span x-text="showSettings ? 'Hide Settings' : 'Open Settings'"></span>
                    </button>
                </div>

{{-- Filters --}}
<div class="px-4 py-3 border-b border-slate-100">
    <form method="GET"
          action="{{ route('events.manage.registration', $event) }}#registrations"
          class="space-y-3 text-xs">

        {{-- Row 1: search only --}}
        <div class="space-y-1">
            <label class="block text-[0.7rem] font-medium text-slate-700">
                Search by attendee name or email
            </label>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="w-full rounded-md border-slate-300 text-xs px-2 py-1.5
                       focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                placeholder="e.g. Juan Dela Cruz or juan@example.com"
            >
        </div>

        {{-- Row 2: status + type + sort + Filter + Reset --}}
        <div class="grid gap-2 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1.4fr)_minmax(0,1.4fr)_auto_auto] items-end">
            {{-- Status --}}
            <div class="space-y-1">
                <label class="block text-[0.7rem] font-medium text-slate-700">
                    Status
                </label>
                <select
                    name="status"
                    class="w-full rounded-md border-slate-300 text-xs px-2 py-1.5
                           bg-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                >
                    @php
                        $statuses = [
                            'all'        => 'All registrations',
                            'pending'    => 'Pending',
                            'approved'   => 'Approved',
                            'rejected'   => 'Rejected',
                            'waitlisted' => 'Waitlisted',
                            'cancelled' => 'Cancelled',
                        ];
                    @endphp

                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div class="space-y-1">
                <label class="block text-[0.7rem] font-medium text-slate-700">
                    Attendee type
                </label>
                <select
                    name="type"
                    class="w-full rounded-md border-slate-300 text-xs px-2 py-1.5
                           bg-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                >
                    @php
                        $types = [
                            'all'     => 'All types',
                            'visitor' => 'Visitor',
                            'guest'   => 'Guest',
                            'student' => 'Student',
                            'faculty' => 'Faculty',
                        ];
                    @endphp
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($typeFilter ?? 'all') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Sort --}}
            <div class="space-y-1">
                <label class="block text-[0.7rem] font-medium text-slate-700">
                    Order by registration time
                </label>
                <select
                    name="sort"
                    class="w-full rounded-md border-slate-300 text-xs px-2 py-1.5
                           bg-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                >
                    <option value="desc" @selected($sortDirection === 'desc')>
                        Newest first
                    </option>
                    <option value="asc" @selected($sortDirection === 'asc')>
                        Oldest first
                    </option>
                </select>
            </div>

            {{-- Filter button (between sort and reset) --}}
            <div class="flex justify-start md:justify-center">
                <button
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 rounded-md border border-sky-600
                           bg-sky-600 text-white font-semibold text-[0.7rem] hover:bg-sky-700
                           focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-sky-500"
                >
                    Filter
                </button>
            </div>

            {{-- Reset button --}}
            <div class="flex justify-start md:justify-end">
                <a href="{{ route('events.manage.registration', $event) }}#registrations"
                   class="inline-flex items-center px-3 py-1.5 rounded-md border border-slate-200
                          bg-white text-[0.7rem] text-slate-700 hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

                <div class="flex-1 flex flex-col">
                    {{-- Table --}}
                    @if ($registrations->count() === 0)
                        <div class="px-4 py-6 text-xs text-slate-500 text-center flex-1 flex items-center justify-center">
                            No registrations found for this event yet.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-[0.7rem] text-slate-500 uppercase tracking-wide">
                                        <th class="px-4 py-2">Attendee</th>
                                        <th class="py-2 text-center">Type</th>
                                        <th class="py-2 text-center">Status</th>
                                        <th class="py-2 text-center">Payment</th>
                                        <th class="px-2 py-2 text-center">Registered</th>
                                        <th class="px-4 py-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($registrations as $registration)
                                        <tr>
                                            {{-- Attendee --}}
                                            <td class="px-4 py-2">
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-slate-900">
                                                        {{ $registration->display_name }}
                                                    </span>
                                                    @if ($registration->email)
                                                        <span class="text-[0.7rem] text-slate-500">
                                                            {{ $registration->email }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Type --}}
                                            <td class="px-2 py-2 text-slate-700 text-center">
                                                @php
                                                    $typeLabel = match ($registration->attendee_type) {
                                                        'no_account' => 'Visitor',
                                                        default => ucfirst($registration->attendee_type ?? 'Unknown'),
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-50 border border-slate-200 text-[0.7rem]">
                                                    {{ $typeLabel }}
                                                </span>
                                            </td>

                                            {{-- Status --}}
                                            <td class="px-2 py-2 text-center">
                                                @php
                                                    $status = $registration->status;
                                                    $statusClasses = match ($status) {
                                                        'approved'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
                                                        'rejected'   => 'bg-rose-50 text-rose-700 border-rose-200',
                                                        'waitlisted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                        default      => 'bg-slate-50 text-slate-600 border-slate-200',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[0.7rem] {{ $statusClasses }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </td>

                                            {{-- Payment --}}
                                            <td class="px-2 py-2 text-center">
                                                @if ($registration->has_payment_proof)
                                                    @php
                                                        $paymentUrl = \Illuminate\Support\Facades\Storage::disk('r2')->url($registration->proof_of_payment_path);
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-1 text-[0.7rem] text-emerald-700 underline decoration-emerald-300"
                                                        @click="
                                                            setPayment(
                                                                @js($paymentUrl),
                                                                @js($registration->user?->display_name ?? 'No-account registration'),
                                                                @js($registration->user?->email ?? $registration->email ?? '-')
                                                            );
                                                        "
                                                    >
                                                        <x-heroicon-o-document-check class="w-3.5 h-3.5" />
                                                        View proof
                                                    </button>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[0.7rem] text-slate-400">
                                                        <x-heroicon-o-document class="w-3.5 h-3.5" />
                                                        None
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Registered at --}}
                                            <td class="px-2 py-2 text-[0.7rem] text-slate-600 text-center">
                                                {{ $registration->created_at?->format('M d, Y H:i') ?? '-' }}
                                            </td>

                                            {{-- Actions --}}
                                            <td class="px-4 py-2 text-right">
                                                @php
                                                    $canApprove = in_array($registration->status, ['pending', 'waitlisted']);
                                                    $canReject  = in_array($registration->status, ['pending', 'waitlisted', 'approved']);
                                                    $isLocked   = in_array($registration->status, ['rejected', 'cancelled']);
                                                @endphp
                                                <div class="inline-flex items-center gap-1">
                                                    {{-- Approve --}}
                                                    <form method="POST"
                                                          action="{{ route('events.manage.registration.approve', [$event, $registration]) }}">
                                                        @csrf
                                                        <button type="submit"
                                                                @disabled(! $canApprove)
                                                                class="inline-flex items-center px-2 py-0.5 rounded-md border text-[0.7rem]
                                                                       {{ $canApprove
                                                                            ? 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100'
                                                                            : 'border-slate-200 text-slate-400 bg-slate-100 cursor-not-allowed' }}">
                                                            <x-heroicon-o-check class="w-3.5 h-3.5 mr-1" />
                                                            Approve
                                                        </button>
                                                    </form>

                                                    {{-- Reject --}}
                                                    <form method="POST"
                                                          action="{{ route('events.manage.registration.reject', [$event, $registration]) }}">
                                                        @csrf
                                                        <button type="submit"
                                                                @disabled(! $canReject)
                                                                class="inline-flex items-center px-2 py-0.5 rounded-md border text-[0.7rem]
                                                                       {{ $canReject
                                                                            ? 'border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100'
                                                                            : 'border-slate-200 text-slate-400 bg-slate-100 cursor-not-allowed' }}">
                                                            <x-heroicon-o-x-mark class="w-3.5 h-3.5 mr-1" />
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    {{-- Pagination --}}
                    <div class="border-t border-slate-200 mt-auto">
                        <x-table-footer :paginator="$registrations" />
                    </div>
                </div>
                {{-- Payment proof modal --}}
                <div
                    x-show="paymentModalOpen"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                >
                    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Proof of Payment</p>
                                <p class="text-xs text-slate-500" x-text="paymentDisplayName + ' • ' + paymentEmail"></p>
                            </div>
                            <button
                                type="button"
                                class="rounded-md border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 px-2 py-1 text-xs"
                                @click="closePayment()"
                            >
                                Close
                            </button>
                        </div>
                        <div class="bg-slate-50">
                            <template x-if="paymentIsImage">
                                <img
                                    :src="paymentImageUrl"
                                    alt="Proof of payment"
                                    class="w-full max-h-[70vh] object-contain bg-slate-900/5"
                                >
                            </template>

                            <template x-if="paymentIsPdf">
                                <object
                                    :data="paymentImageUrl"
                                    type="application/pdf"
                                    class="w-full h-[70vh] bg-white"
                                >
                                    <p class="p-4 text-sm text-slate-600">
                                        Unable to display PDF. <a :href="paymentImageUrl" target="_blank" class="text-sky-600 underline">Download</a>
                                    </p>
                                </object>
                            </template>

                            <template x-if="!paymentIsImage && !paymentIsPdf">
                                <div class="p-4 text-sm text-slate-700 flex flex-col gap-2">
                                    <p>Preview unavailable for this file type.</p>
                                    <a
                                        :href="paymentImageUrl"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-sky-600 underline"
                                    >
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                        Download file
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Right: settings --}}
            <div
                x-show="showSettings"
                x-cloak
                class="border border-slate-200 rounded-lg bg-white p-4 space-y-4 text-sm"
            >
                {{-- Success flash for settings --}}
                @if (session('registration_settings_status'))
                    <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                        {{ session('registration_settings_status') }}
                    </div>
                @endif

                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Registration settings</h2>
                        <p class="mt-1 text-xs text-slate-600">
                            Configure when registration is open, capacity, instructions, and
                            rules for payment proof, auto-approval, and waitlisting.
                        </p>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('events.manage.registration.settings.update', $event) }}"
                      class="space-y-4">
                    @csrf
                    @method('PUT')

                    @php
                        $capacityMode = old(
                            'capacity_mode',
                            $event->capacity ? 'limited' : 'unlimited'
                        );
                    @endphp

                    {{-- Capacity & registration window --}}
                    <div class="border border-slate-200 rounded-md p-3 space-y-3 bg-slate-50/60">
                        <h3 class="text-[0.7rem] font-semibold text-slate-700 uppercase tracking-wide">
                            Capacity & registration window
                        </h3>

                        {{-- Capacity radio + number --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-medium text-slate-700">
                                    Capacity
                                    <span class="text-slate-400 font-normal">
                                        – choose open attendance or a fixed limit.
                                    </span>
                                </label>
                            </div>

                            <div class="space-y-2 text-xs text-slate-700">
                                {{-- No limit --}}
                                <label class="flex items-center gap-2">
                                    <input
                                        type="radio"
                                        name="capacity_mode"
                                        value="unlimited"
                                        @checked($capacityMode === 'unlimited')
                                        class="text-sky-600 border-slate-300 focus:ring-sky-500"
                                        @disabled($settingsLocked)
                                    >
                                    <span>No limit (open attendance)</span>
                                </label>

                                {{-- Limited --}}
                                <label class="flex items-center gap-2">
                                    <input
                                        type="radio"
                                        name="capacity_mode"
                                        value="limited"
                                        @checked($capacityMode === 'limited')
                                        class="text-sky-600 border-slate-300 focus:ring-sky-500"
                                        @disabled($settingsLocked)
                                    >
                                    <span class="flex items-center gap-2 flex-wrap">
                                        <span>Limit to</span>
                                        <input
                                            type="number"
                                            name="capacity"
                                            min="1"
                                            value="{{ old('capacity', $event->capacity) }}"
                                            class="w-24 rounded-md border-slate-300 text-xs px-2 py-1.5
                                                   focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                            placeholder="100"
                                            @disabled($settingsLocked)
                                        >
                                        <span>attendees</span>
                                    </span>
                                </label>

                                @error('capacity_mode', 'updateRegistrationSettings')
                                    <p class="text-[0.7rem] text-rose-600 mt-0.5">{{ $message }}</p>
                                @enderror

                                @error('capacity', 'updateRegistrationSettings')
                                    <p class="text-[0.7rem] text-rose-600 mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Registration opens --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-medium text-slate-700">
                                    Registration opens
                                </label>
                                <span class="text-[0.7rem] text-slate-400">
                                    Leave blank to set to <b>now until</b>.
                                </span>
                            </div>
                            <input
                                type="datetime-local"
                                name="reg_open_at"
                                value="{{ old('reg_open_at', $event->reg_open_at ? $event->reg_open_at->format('Y-m-d\TH:i') : '') }}"
                                class="w-full rounded-md border-slate-300 text-sm px-2 py-1.5
                                       focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                @disabled($settingsLocked)
                            >
                            @error('reg_open_at', 'updateRegistrationSettings')
                                <p class="text-[0.7rem] text-rose-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Registration closes --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-medium text-slate-700">
                                    Registration closes
                                </label>
                                <span class="text-[0.7rem] text-slate-400">
                                    Must be on or after the open time.
                                </span>
                            </div>
                            <input
                                type="datetime-local"
                                name="reg_close_at"
                                value="{{ old('reg_close_at', ($event->reg_close_at && ! $closeWhenEventStarts)
                                    ? $event->reg_close_at->format('Y-m-d\TH:i')
                                    : '') }}"
                                class="w-full rounded-md border-slate-300 text-sm px-2 py-1.5
                                       focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                @disabled($settingsLocked)
                            >
                            @error('reg_close_at', 'updateRegistrationSettings')
                                <p class="text-[0.7rem] text-rose-600 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Close when event starts --}}
                        <div class="flex items-start gap-2 pt-1">
                            <input
                                id="close_when_event_starts"
                                type="checkbox"
                                name="close_when_event_starts"
                                value="1"
                                @checked(old('close_when_event_starts', $closeWhenEventStarts))
                                @disabled(! $event->start_at)
                                class="mt-0.5 rounded border-slate-300 text-sky-600
                                       focus:ring-sky-500"
                                @disabled($settingsLocked)
                            >
                            <div class="text-xs text-slate-600">
                                <label for="close_when_event_starts" class="font-medium text-slate-800">
                                    Keep registration open until the event starts
                                </label>
                                <p class="text-[0.7rem] text-slate-500 mt-0.5">
                                    When enabled, the registration end time will match the event’s
                                    start time.
                                    @if (! $event->start_at)
                                        <span class="text-amber-700">
                                            (Set the event start date/time in the Details tab first.)
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Instructions --}}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-medium text-slate-700">
                                Registration instructions
                            </label>
                            <span class="text-[0.7rem] text-slate-400">
                                Up to 2000 characters.
                            </span>
                        </div>
                        <textarea
                            name="registration_instructions"
                            rows="7"
                            class="w-full rounded-md border-slate-300 text-sm px-2 py-1.5
                                   focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                            placeholder="Explain requirements or payment details, or any special notes for registrants if any..."
                            @disabled($settingsLocked)
                        >{{ old('registration_instructions', $event->registration_instructions) }}</textarea>
                        @error('registration_instructions', 'updateRegistrationSettings')
                            <p class="text-[0.7rem] text-rose-600 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Boolean toggles --}}
                    <div class="border border-slate-200 rounded-md p-3 space-y-2 bg-slate-50/60">
                        <h3 class="text-[0.7rem] font-semibold text-slate-700 uppercase tracking-wide">
                            Rules & automation
                        </h3>

                        <div class="space-y-2">
                            {{-- Payment proof --}}
                            <label class="flex items-start gap-2 text-xs text-slate-700">
                                <input
                                    type="checkbox"
                                    name="requires_payment_proof"
                                    value="1"
                                    @checked(old('requires_payment_proof', $event->requires_payment_proof))
                                    class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                    @disabled($settingsLocked || $paymentProofLocked)
                                >
                                <span>
                                    <span class="font-medium text-slate-900">
                                        Require proof of payment
                                    </span>
                                    <span class="block text-[0.7rem] text-slate-500">
                                        Registration form will show an upload field.
                                        @if ($paymentProofLocked)
                                            <span class="text-amber-700 font-semibold">Cannot change while registrations exist.</span>
                                        @endif
                                    </span>
                                </span>
                            </label>

                            {{-- Allow no-account --}}
                            <label class="flex items-start gap-2 text-xs text-slate-700">
                                <input
                                    type="checkbox"
                                    name="allow_no_account"
                                    value="1"
                                    @checked(old('allow_no_account', data_get($event->target_audience_json, 'allow_no_account', false)))
                                    class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                    @disabled($settingsLocked)
                                >
                                <span>
                                    <span class="font-medium text-slate-900">
                                        Allow no-account visitors to register
                                    </span>
                                    <span class="block text-[0.7rem] text-slate-500">
                                        Visitors without an Attendify account can submit registrations.
                                    </span>
                                </span>
                            </label>

                            {{-- Auto approve --}}
                            <label class="flex items-start gap-2 text-xs text-slate-700">
                                <input
                                    type="checkbox"
                                    name="auto_approve_registrations"
                                        value="1"
                                        @checked(old('auto_approve_registrations', $event->auto_approve_registrations))
                                        class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                        @disabled($settingsLocked)
                                    >
                                <span>
                                    <span class="font-medium text-slate-900">
                                        Auto-approve registrations
                                    </span>
                                    <span class="block text-[0.7rem] text-slate-500">
                                        New registrations skip the pending state and become approved immediately.
                                    </span>
                                </span>
                            </label>

                            {{-- Waitlist --}}
                            <label class="flex items-start gap-2 text-xs text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="enable_waitlist"
                                        value="1"
                                        @checked(old('enable_waitlist', $event->enable_waitlist))
                                        class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                        @disabled($settingsLocked)
                                    >
                                <span>
                                    <span class="font-medium text-slate-900">
                                        Enable waitlist when capacity is full
                                    </span>
                                    <span class="block text-[0.7rem] text-slate-500">
                                        Additional registrants will be marked as waitlisted instead of rejected.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold
                                   bg-sky-600 text-white hover:bg-sky-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:border-slate-200
                                   focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-sky-500"
                            @disabled($settingsLocked)
                        >
                            <x-heroicon-o-check-circle class="w-4 h-4 mr-1.5" />
                            Save registration settings
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</x-app-layout>
