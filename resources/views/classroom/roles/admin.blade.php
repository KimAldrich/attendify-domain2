

<section class="bg-white rounded-[14px] shadow-[0_10px_20px_rgba(0,0,0,.06)] p-4 md:p-5 space-y-4">
    <h2 class="text-[1.1rem] font-semibold tracking-wide">Latest Attendance</h2>

    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-[#fbfbfd] p-3">
    <div class="grid gap-1 min-w-[180px] sm:min-w-[200px]">
        <label for="course1" class="text-xs text-slate-500">Course</label>
        <div class="relative">
        <select id="course1" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none ring-0 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-200/60">
            <option>Technopreneurship</option>
            <option>Websys2</option>
            <option>Prog2</option>
        </select>
        <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
    <div class="grid gap-1 min-w-[180px] sm:min-w-[200px]">
        <label for="year1" class="text-xs text-slate-500">Academic Year</label>
        <div class="relative">
        <select id="year1" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-200/60">
            <option>2024 - 2025</option>
            <option>2023 - 2024</option>
            <option>2022 - 2023</option>
        </select>
        <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
    <div class="grid gap-1 min-w-[180px] sm:min-w-[200px]">
        <label for="status1" class="text-xs text-slate-500">Status</label>
        <div class="relative">
        <select id="status1" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-200/60">
            <option>Active</option>
            <option>Inactive</option>
            <option>Archived</option>
        </select>
        <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
    <div class="min-w-0 grow"></div>
    </div>

    <div class="w-full overflow-x-auto">
    <table class="w-full border-separate [border-spacing:0_8px] min-w-[720px]" aria-label="Latest attendance table">
        <thead class="bg-[#0a2ea6] text-white font-semibold text-sm rounded-lg overflow-hidden">
        <tr>
            <th class="text-left px-4 py-3 w-16">No.</th>
            <th class="text-left px-4 py-3">Name of Employee</th>
            <th class="text-left px-4 py-3 hidden md:table-cell">Class Size</th>
            <th class="text-left px-4 py-3">Course</th>
            <th class="text-left px-4 py-3">Status</th>
            <th class="text-left px-4 py-3 hidden md:table-cell">Academic Year</th>
        </tr>
        </thead>
        <tbody>
        <tr class="bg-slate-50 even:bg-slate-100 border border-slate-200 rounded-lg shadow-[0_4px_10px_rgba(0,0,0,.03)]">
            <td class="px-4 py-3">1</td>
            <td class="px-4 py-3">Websys2-3A</td>
            <td class="px-4 py-3 hidden md:table-cell">28</td>
            <td class="px-4 py-3">Websys2</td>
            <td class="px-4 py-3"><span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Active</span></td>
            <td class="px-4 py-3 hidden md:table-cell">2024-2025</td>
        </tr>
        <tr class="bg-slate-50 even:bg-slate-100 border border-slate-200 rounded-lg shadow-[0_4px_10px_rgba(0,0,0,.03)]">
            <td class="px-4 py-3">2</td>
            <td class="px-4 py-3">Websys2-3A</td>
            <td class="px-4 py-3 hidden md:table-cell">28</td>
            <td class="px-4 py-3">Websys2</td>
            <td class="px-4 py-3"><span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Active</span></td>
            <td class="px-4 py-3 hidden md:table-cell">2024-2025</td>
        </tr>
        <tr class="bg-slate-50 even:bg-slate-100 border border-slate-200 rounded-lg shadow-[0_4px_10px_rgba(0,0,0,.03)]">
            <td class="px-4 py-3">3</td>
            <td class="px-4 py-3">Websys2-3A</td>
            <td class="px-4 py-3 hidden md:table-cell">28</td>
            <td class="px-4 py-3">Websys2</td>
            <td class="px-4 py-3"><span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Active</span></td>
            <td class="px-4 py-3 hidden md:table-cell">2024-2025</td>
        </tr>
        </tbody>
    </table>
    </div>
</section>

<section class="bg-white rounded-[14px] shadow-[0_10px_20px_rgba(0,0,0,.06)] p-4 md:p-5 space-y-4">
    <h2 class="text-[1.1rem] font-semibold tracking-wide">All Attendance</h2>

    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-[#fbfbfd] p-3">
    <div class="grid gap-1 min-w-[180px] sm:min-w-[200px]">
        <label for="course2" class="text-xs text-slate-500">Course</label>
        <div class="relative">
        <select id="course2" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-200/60">
            <option>Technopreneurship</option>
            <option>Websys2</option>
            <option>Prog2</option>
        </select>
        <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
    <div class="grid gap-1 min-w-[180px] sm:min-w-[200px]">
        <label for="year2" class="text-xs text-slate-500">Academic Year</label>
        <div class="relative">
        <select id="year2" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-200/60">
            <option>2024 - 2025</option>
            <option>2023 - 2024</option>
            <option>2022 - 2023</option>
        </select>
        <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
    <div class="grid gap-1 min-w-[180px] sm:min-w-[200px]">
        <label for="status2" class="text-xs text-slate-500">Status</label>
        <div class="relative">
        <select id="status2" class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-200/60">
            <option>Active</option>
            <option>Inactive</option>
            <option>Archived</option>
        </select>
        <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
    <div class="min-w-0 grow"></div>
    </div>

    <div class="w-full overflow-x-auto">
    <table class="w-full border-separate [border-spacing:0_8px] min-w-[720px]" aria-label="All attendance table">
        <thead class="bg-[#0a2ea6] text-white font-semibold text-sm rounded-lg overflow-hidden">
        <tr>
            <th class="text-left px-4 py-3 w-16">No.</th>
            <th class="text-left px-4 py-3">Name of Employee</th>
            <th class="text-left px-4 py-3 hidden md:table-cell">Class Size</th>
            <th class="text-left px-4 py-3">Course</th>
            <th class="text-left px-4 py-3">Status</th>
            <th class="text-left px-4 py-3 hidden md:table-cell">Academic Year</th>
        </tr>
        </thead>
        <tbody>
        <tr class="bg-slate-50 even:bg-slate-100 border border-slate-200 rounded-lg shadow-[0_4px_10px_rgba(0,0,0,.03)]">
            <td class="px-4 py-3">1</td>
            <td class="px-4 py-3">Websys2-3A</td>
            <td class="px-4 py-3 hidden md:table-cell">28</td>
            <td class="px-4 py-3">Websys2</td>
            <td class="px-4 py-3"><span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Active</span></td>
            <td class="px-4 py-3 hidden md:table-cell">2024-2025</td>
        </tr>
        <tr class="bg-slate-50 even:bg-slate-100 border border-slate-200 rounded-lg shadow-[0_4px_10px_rgba(0,0,0,.03)]">
            <td class="px-4 py-3">2</td>
            <td class="px-4 py-3">Websys2-3A</td>
            <td class="px-4 py-3 hidden md:table-cell">28</td>
            <td class="px-4 py-3">Websys2</td>
            <td class="px-4 py-3"><span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Active</span></td>
            <td class="px-4 py-3 hidden md:table-cell">2024-2025</td>
        </tr>
        <tr class="bg-slate-50 even:bg-slate-100 border border-slate-200 rounded-lg shadow-[0_4px_10px_rgba(0,0,0,.03)]">
            <td class="px-4 py-3">3</td>
            <td class="px-4 py-3">Websys2-3A</td>
            <td class="px-4 py-3 hidden md:table-cell">28</td>
            <td class="px-4 py-3">Websys2</td>
            <td class="px-4 py-3"><span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Active</span></td>
            <td class="px-4 py-3 hidden md:table-cell">2024-2025</td>
        </tr>
        </tbody>
    </table>
    </div>
</section>