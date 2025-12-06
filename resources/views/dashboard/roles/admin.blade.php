<section class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
    <article class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-4">
        <h3 class="text-slate-900 font-semibold">Users & Roles</h3>
        <p class="mt-1 text-slate-600 text-sm">Manage accounts and permissions.</p>
        <div class="mt-3 flex gap-2">
            <a href="{{ route("admin.users.index") }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                Users
            </a>
        </div>
    </article>

    <article class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-4">
        <h3 class="text-slate-900 font-semibold">System Settings</h3>
        <p class="mt-1 text-slate-600 text-sm">Configure integrations and policies.</p>
        <div class="mt-3">
            <a href="{{ route("admin.system-management") }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                Open settings
            </a>
        </div>
    </article>
</section>
