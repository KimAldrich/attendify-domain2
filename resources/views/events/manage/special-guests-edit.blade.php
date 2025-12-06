{{-- resources/views/events/manage/special-guests-edit.blade.php --}}
<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 py-6 space-y-4">
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'guests'])

        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Edit special guest
                    </h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Update this guest’s details for the event page.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('events.manage.guests.update', [$event, $guest]) }}"
                enctype="multipart/form-data"
                class="space-y-4"
            >
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Full name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                               focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                        value="{{ old('name', $guest->name) }}"
                        required
                    >
                    @error('name')
                        <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Title
                    </label>
                    <input
                        type="text"
                        name="title"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                               focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="e.g. Bachelor in Information Technology, IT Department Chair"
                        value="{{ old('title', $guest->title) }}"
                    >
                    @error('title')
                        <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Description (optional)
                    </label>
                    <textarea
                        name="description"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                               focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="Briefly describe who they are and why they are special to this event."
                    >{{ old('description', $guest->description) }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Shown on the event page to give context to attendees.
                    </p>
                    @error('description')
                        <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Photo --}}
                <div>
                    <label class="block text-xs font-medium text-slate-700">
                        Profile photo (optional)
                    </label>

                    @if ($guest->photo_url)
                        <div class="flex items-center gap-3 mt-1">
                            <img
                                src="{{ $guest->photo_url }}"
                                alt="{{ $guest->name }}"
                                class="w-12 h-12 rounded-full object-cover"
                            >
                            <p class="text-[11px] text-slate-500">
                                Upload a new file below to replace this photo.
                            </p>
                        </div>
                    @endif

                    <input
                        type="file"
                        name="photo"
                        accept="image/png,image/jpeg,image/webp"
                        class="mt-2 block w-full text-xs text-slate-600
                               file:mr-3 file:py-1.5 file:px-3 file:rounded-md
                               file:border-0 file:text-xs file:font-medium
                               file:bg-slate-100 file:text-slate-700
                               hover:file:bg-slate-200"
                    >
                    <p class="mt-1 text-[11px] text-slate-500">
                        Square or 4:5 portrait recommended. JPG/PNG/WebP up to 5MB.
                    </p>
                    @error('photo')
                        <p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <a
                        href="{{ route('events.manage.guests', $event) }}"
                        class="text-[11px] text-slate-600 hover:text-slate-800"
                    >
                        Cancel and go back
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                               text-white hover:bg-[#0042a3]"
                    >
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
