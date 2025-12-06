{{-- resources/views/errors/403.blade.php --}}
<x-app-layout>
    <div class="min-h-[70vh] flex items-center justify-center px-8 py-10">
        <div class="w-full max-w-3xl mt-6 mx-auto text-center space-y-2">
            <p class="font-black leading-none" style="font-size: 120px; color: #0b4dbb;">403</p>
            <h1 class="text-2xl sm:text-3xl font-semibold" style="color: #0b4dbb;">Access denied</h1>
            <p class="text-sm sm:text-base text-slate-600 max-w-xl mx-auto">
                You don't have permission to view this page or event. If you believe this is a mistake,
                please contact the organizer or your administrator.
            </p>
            <div class="flex items-center justify-center gap-3 pt-2">
                <a
                    href="{{ url()->previous() !== url()->current() ? url()->previous() : route('landing-page') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Back
                </a>
                <a
                    href="{{ route('landing-page') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-sky-600 text-white text-sm font-semibold shadow-sm hover:bg-sky-700"
                >
                    <x-heroicon-o-home class="w-4 h-4" />
                    Home
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
