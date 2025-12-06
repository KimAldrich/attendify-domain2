{{-- events/manage/certificates.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'certificates'])

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)]">
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">Certificate settings</h2>
                <p class="text-xs text-slate-600">
                    Controls for certificate template (title, body text, background, sponsor logos) will be added here.
                </p>
            </div>

            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">Preview & generation</h2>
                <p class="text-xs text-slate-600">
                    Certificate preview and buttons to generate/reissue certificates will be added here.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
