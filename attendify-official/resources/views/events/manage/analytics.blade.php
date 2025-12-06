{{-- events/manage/analytics.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'analytics'])

        {{-- Summary cards placeholder --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm">
                <p class="text-xs text-slate-500 mb-1">Responses</p>
                <p class="text-2xl font-semibold text-slate-900">—</p>
            </div>
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm">
                <p class="text-xs text-slate-500 mb-1">Average rating</p>
                <p class="text-2xl font-semibold text-slate-900">—</p>
            </div>
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm">
                <p class="text-xs text-slate-500 mb-1">Attendance rate</p>
                <p class="text-2xl font-semibold text-slate-900">—</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1.3fr)]">
            {{-- Charts placeholder --}}
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm">
                <h2 class="text-sm font-semibold text-slate-900 mb-2">Scores by question</h2>
                <div class="border border-dashed border-slate-300 rounded-md px-4 py-6 text-xs text-slate-500 text-center">
                    Charts and numeric breakdowns will be rendered here using evaluation_summary_stats.
                </div>
            </div>

            {{-- AI summary placeholder --}}
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">AI-generated summary</h2>
                    <button
                        type="button"
                        class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 bg-white hover:bg-slate-50"
                    >
                        Regenerate
                    </button>
                </div>

                <p class="text-xs text-slate-600">
                    The AI summary (overall, strengths, weaknesses, recommendations) will appear here once generated.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
