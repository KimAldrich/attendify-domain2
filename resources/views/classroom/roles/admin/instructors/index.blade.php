{{-- resources/views/classroom/roles/admin/instructors/index.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div class="max-w-6xl mx-auto space-y-6">
            @include('classroom.roles.admin._tabs')

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Instructor List</h2>
                    <p class="text-sm text-slate-500">
                        View instructors teaching in a specific academic year and term.
                    </p>
                </div>

                {{-- Go to Academic Periods management --}}
                <a href="{{ route('classroom.admin.periods.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-[#0052CC] text-white text-sm font-medium px-4 py-2 shadow-sm hover:bg-[#003fa3]">
                    <span class="bi bi-calendar3"></span>
                    Manage Academic Periods
                </a>
            </div>

{{-- AY-Term + filters --}}
<form method="GET"
      class="bg-white rounded-xl shadow border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-slate-700">
            Academic Year & Term
        </label>
        <select name="period_id"
                class="min-w-[260px] rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]">
            @foreach ($periods as $period)
                <option value="{{ $period->id }}"
                        @selected($selectedPeriod && $selectedPeriod->id === $period->id)>
                    {{ $period->display_label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-wrap items-end gap-3">
        {{-- Search by name/email --}}
        <div class="flex flex-col">
            <label class="text-xs font-medium text-slate-600">
                Search (name or email)
            </label>
            <input
                type="text"
                name="q"
                value="{{ $search ?? '' }}"
                placeholder="e.g. Juan Dela Cruz"
                class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
            >
        </div>

        {{-- Min courses --}}
        <div class="flex flex-col">
            <label class="text-xs font-medium text-slate-600">
                Min courses
            </label>
            <input
                type="number"
                name="min_courses"
                min="0"
                value="{{ $minCourses ?? '' }}"
                class="w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
            >
        </div>

        {{-- Max courses --}}
        <div class="flex flex-col">
            <label class="text-xs font-medium text-slate-600">
                Max courses
            </label>
            <input
                type="number"
                name="max_courses"
                min="0"
                value="{{ $maxCourses ?? '' }}"
                class="w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
            >
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg bg-slate-900 text-white text-xs font-semibold px-3 py-2 hover:bg-slate-800">
            Apply
        </button>
    </div>
</form>

            {{-- Instructors table --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Number of Courses</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($instructors as $instructor)
                            <tr>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $instructor->email }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $instructor->full_name }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $instructor->courses_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('classroom.admin.courses.index', [
                                            'period_id'     => optional($selectedPeriod)->id,
                                            'instructor_id' => $instructor->id,
                                        ]) }}"
                                       class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50">
                                        View Courses
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400 text-sm">
                                    No instructors found based on the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <x-table-footer :paginator="$instructors" />
            </div>

        </div>
    </div>
</x-app-layout>
