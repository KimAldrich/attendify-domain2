{{-- resources/views/classroom/roles/admin/periods/index.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div class="max-w-6xl mx-auto space-y-6">

            @include('classroom.roles.admin._tabs')

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Academic Periods</h2>
                    <p class="text-sm text-slate-500">
                        Manage academic years and terms, and choose which period is currently active.
                        Periods that already have sections cannot be deleted.
                    </p>
                </div>

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', { name: 'create-period' })"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0052CC] text-white text-sm font-medium px-4 py-2 shadow-sm hover:bg-[#003fa3]"
                >
                    <span class="bi bi-plus-lg"></span>
                    Add New
                </button>
            </div>

            {{-- Periods table --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Label</th>
                            <th class="px-4 py-3 text-left">Academic Year</th>
                            <th class="px-4 py-3 text-left">Term</th>
                            <th class="px-4 py-3 text-center">Sections</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($periods as $period)
                            @php
                                $hasSections = $period->sections_count > 0;
                            @endphp

                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $period->display_label }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $period->year_start }} – {{ $period->year_end }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $period->term }}
                                </td>

                                {{-- Sections count --}}
                                <td class="px-4 py-3 text-center text-slate-900">
                                    {{ $period->sections_count }}
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @if ($period->is_current)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>
                                            Current
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 text-right space-x-2">
                                    {{-- Set current (only for inactive) --}}
                                    @if (! $period->is_current)
                                        <form action="{{ route('classroom.admin.periods.set-current', $period) }}"
                                            method="POST"
                                            class="inline-block">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-md border border-blue-200 text-xs px-2 py-1.5 text-blue-700 hover:bg-blue-50">
                                                Set Current
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Edit --}}
                                    <button
                                        type="button"
                                        x-data
                                        x-on:click="$dispatch('open-modal', { name: 'edit-period-{{ $period->id }}' })"
                                        class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </button>

                                    {{-- Delete (disabled if period has sections) --}}
                                    <form
                                        action="{{ route('classroom.admin.periods.destroy', $period) }}"
                                        method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Delete this academic period?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            @disabled($hasSections)
                                            @if($hasSections)
                                                title="Cannot delete a period that still has sections."
                                            @endif
                                            class="inline-flex items-center rounded-md text-xs px-2 py-1.5
                                                {{ $hasSections
                                                    ? 'border border-red-100 text-red-400 cursor-not-allowed opacity-60'
                                                    : 'border border-red-200 text-red-700 hover:bg-red-50' }}"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-400 text-sm">
                                    No academic periods yet. Click <span class="font-medium">“Add Academic Period”</span> to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{-- Pagination --}}
                <x-table-footer :paginator="$periods" />
            </div>

        </div>
    </div>

    {{-- Create Period Modal --}}
    <div
        x-data="{ open: {{ $errors->has('year_pair') || $errors->has('term') ? 'true' : 'false' }} }"
        x-on:open-modal.window="
            if ($event.detail.name === 'create-period') open = true
        "
        x-on:close-modal.window="
            if ($event.detail.name === 'create-period') open = false
        "
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
    >
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
            x-on:click.stop
        >
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">
                        Add Academic Period
                    </h2>
                    <p class="mt-1 text-xs text-slate-500 leading-snug">
                        Define a new academic year and term. You can also mark it as the current period.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                    x-on:click="open = false"
                >
                    <i class="bi bi-x-lg text-xs text-slate-500"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <form action="{{ route('classroom.admin.periods.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Year Pair Selector --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Academic Year
                    </label>

                    @php
                        $yearPairs = [];
                        for ($y = 2010; $y <= 2099; $y++) {
                            $yearPairs[] = [
                                'start' => $y,
                                'end'   => $y + 1,
                                'label' => "AY {$y}-".($y+1)
                            ];
                        }
                    @endphp

                    <select
                        name="year_pair"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        required
                    >
                        <option value="">Select AY</option>

                        @foreach ($yearPairs as $pair)
                            <option value="{{ $pair['start'] }}|{{ $pair['end'] }}">
                                {{ $pair['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Term
                    </label>
                    <input
                        type="text"
                        name="term"
                        value="{{ old('term') }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="1st Semester"
                        required
                    />
                    @error('term')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Custom Label (optional)
                    </label>
                    <input
                        type="text"
                        name="label"
                        value="{{ old('label') }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="AY 2024-2025 • 1st Semester"
                    />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input
                            type="checkbox"
                            name="is_current"
                            value="1"
                            class="rounded border-slate-300 text-[#0052CC] focus:ring-[#0052CC]"
                            {{ old('is_current') ? 'checked' : '' }}
                        >
                        <span>Set as current academic period</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        x-on:click="open = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                    >
                        Save Period
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Period modals --}}
    @foreach ($periods as $period)
        <div
            x-data="{ open: false }"
            x-on:open-modal.window="
                if ($event.detail.name === 'edit-period-{{ $period->id }}') open = true
            "
            x-on:close-modal.window="
                if ($event.detail.name === 'edit-period-{{ $period->id }}') open = false
            "
            x-show="open"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
        >
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
                x-on:click.stop
            >
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Edit Academic Period
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 leading-snug">
                            Update the academic year, term, or mark this period as current.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                        x-on:click="open = false"
                    >
                        <i class="bi bi-x-lg text-xs text-slate-500"></i>
                        <span class="sr-only">Close</span>
                    </button>
                </div>

                <form action="{{ route('classroom.admin.periods.update', $period) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Year Pair Selector --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Academic Year
                        </label>

                        @php
                            $yearPairs = [];
                            for ($y = 2010; $y <= 2099; $y++) {
                                $yearPairs[] = [
                                    'start' => $y,
                                    'end'   => $y + 1,
                                    'label' => "AY {$y}-".($y+1)
                                ];
                            }
                            $currentValue = $period->year_start . '|' . $period->year_end;
                        @endphp

                        <select
                            name="year_pair"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            required
                        >
                            @foreach ($yearPairs as $pair)
                                <option value="{{ $pair['start'] }}|{{ $pair['end'] }}"
                                    @selected($currentValue === ($pair['start'].'|'.$pair['end']))>
                                    {{ $pair['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Term
                        </label>
                        <input
                            type="text"
                            name="term"
                            value="{{ $period->term }}"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-slate-700">
                            Custom Label (optional)
                        </label>
                        <input
                            type="text"
                            name="label"
                            value="{{ $period->label }}"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input
                                type="checkbox"
                                name="is_current"
                                value="1"
                                class="rounded border-slate-300 text-[#0052CC] focus:ring-[#0052CC]"
                                {{ $period->is_current ? 'checked' : '' }}
                            >
                            <span>Set as current academic period</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                            x-on:click="open = false"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</x-app-layout>
