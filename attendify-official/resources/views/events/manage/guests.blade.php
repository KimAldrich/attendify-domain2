{{-- resources/views/events/manage/guests.blade.php --}}
<x-app-layout>
    <div
        class="max-w-6xl mx-auto px-4 py-6 space-y-4"
        x-data="specialGuestEditor()"
        x-cloak
    >
        {{-- Header + tabs --}}
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'guests'])

        {{-- Flash --}}
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- LEFT: Create form --}}
            <form
                method="POST"
                action="{{ route('events.manage.guests.store', $event) }}"
                enctype="multipart/form-data"
                class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-4"
            >
                @csrf

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Add special guest
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Highlight VIPs, guests of honor, or invited personalities.
                        </p>
                    </div>
                    <x-heroicon-o-star class="w-4 h-4 text-amber-400" />
                </div>

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
                        value="{{ old('name') }}"
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
                        value="{{ old('title') }}"
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
                    >{{ old('description') }}</textarea>
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
                    <input
                        type="file"
                        name="photo"
                        accept="image/png,image/jpeg,image/webp"
                        class="mt-1 block w-full text-xs text-slate-600
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

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                               text-white hover:bg-[#0042a3]"
                    >
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                        Add guest
                    </button>
                </div>
            </form>

            {{-- RIGHT: List --}}
            <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Special guests for this event
                    </h2>
                </div>

                @if ($guests->isEmpty())
                    <p class="text-xs text-slate-500">
                        No special guests have been added yet. Use the form on the left to feature VIPs or guests of honor.
                    </p>
                @else
                    <ul class="space-y-2">
                        @foreach ($guests as $guest)
<li class="flex items-center gap-4 rounded-md border border-slate-200 px-3 py-3">
    @if ($guest->photo_url)
        <img
            src="{{ $guest->photo_url }}"
            alt="{{ $guest->name }}"
            class="w-12 h-12 rounded-full object-cover flex-shrink-0"
        >
    @else
        <div
            class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-sm text-slate-500 flex-shrink-0"
        >
            <i class="bi bi-person text-lg"></i>
        </div>
    @endif

    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
            <div>
                <div class="text-sm font-semibold text-slate-900">
                    {{ $guest->name }}
                </div>

                @if ($guest->title)
                    <div class="text-[11px] text-slate-600">
                        {{ $guest->title }}
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3">
                {{-- EDIT --}}
                <button
                    type="button"
                    class="text-[11px] text-slate-600 hover:text-[#0052CC]"
                    @click="openEdit(@js([
                        'id'          => $guest->id,
                        'name'        => $guest->name,
                        'title'       => $guest->title,
                        'description' => $guest->description,
                        'photoUrl'    => $guest->photo_url,
                        'updateUrl'   => route('events.manage.guests.update', [$event, $guest]),
                    ]))"
                >
                    Edit
                </button>

                {{-- DELETE --}}
                <form
                    method="POST"
                    action="{{ route('events.manage.guests.destroy', [$event, $guest]) }}"
                    onsubmit="return confirm('Remove this special guest from the event?');"
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
            </div>
        </div>

        @if ($guest->description)
            <p class="mt-1 text-[11px] text-slate-600">
                {{ $guest->description }}
            </p>
        @endif
    </div>
</li>

                        @endforeach
                    </ul>
                @endif

                <p class="text-[11px] text-slate-500 mt-1">
                    These guests can be shown on the public event page in a “Special Guests” section.
                </p>
            </div>
        </div>

        {{-- EDIT MODAL --}}
        <div
            class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 overflow-y-auto"
            x-show="editOpen"
            x-transition.opacity
        >
            <div
                class="bg-white rounded-2xl shadow-xl w-full max-w-lg my-8 overflow-hidden flex flex-col"
                @click.away="closeEdit()"
            >
                {{-- Header --}}
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Edit special guest
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Update this guest’s details for the event page.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="p-1 rounded-full hover:bg-slate-100 text-slate-500"
                        @click="closeEdit()"
                    >
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Form --}}
                <form
                    method="POST"
                    :action="editing.updateUrl"
                    enctype="multipart/form-data"
                    class="flex-1 min-h-0 flex flex-col"
                >
                    @csrf
                    @method('PUT')

                    <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4 space-y-4 text-sm">
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
                                x-model="editing.name"
                                required
                            >
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
                                x-model="editing.title"
                            >
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
                                x-text="editing.description ?? ''"
                                x-on:input="editing.description = $event.target.value"
                            ></textarea>
                            <p class="mt-1 text-[11px] text-slate-500">
                                Shown on the event page to give context to attendees.
                            </p>
                        </div>

                        {{-- Photo --}}
                        <div>
                            <label class="block text-xs font-medium text-slate-700">
                                Profile photo (optional)
                            </label>

                            <template x-if="editing.photoUrl">
                                <div class="flex items-center gap-3 mt-1">
                                    <img
                                        :src="editing.photoUrl"
                                        alt=""
                                        class="w-12 h-12 rounded-full object-cover"
                                    >
                                    <p class="text-[11px] text-slate-500">
                                        Upload a new file below to replace this photo.
                                    </p>
                                </div>
                            </template>

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
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <button
                            type="button"
                            class="text-[11px] text-slate-600 hover:text-slate-800"
                            @click="closeEdit()"
                        >
                            Cancel
                        </button>

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
    </div>

@push('scripts')
<script>
    function specialGuestEditor() {
        return {
            editOpen: false,
            editing: {
                id: null,
                name: '',
                title: '',
                description: '',
                photoUrl: '',
                updateUrl: '',
            },

            openEdit(payload) {
                this.editing = Object.assign({}, this.editing, payload || {});
                this.editOpen = true;
            },

            closeEdit() {
                this.editOpen = false;
            },
        };
    }
</script>
@endpush

</x-app-layout>
