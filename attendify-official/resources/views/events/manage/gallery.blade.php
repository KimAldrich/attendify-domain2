{{-- resources/views/events/manage/gallery.blade.php --}}
@php
    use Illuminate\Support\Str;

    $inAlbum = ! is_null($currentAlbum);

    $breadcrumbItems = $inAlbum
        ? [
            [
                'label' => 'Event Albums',
                'url'   => route('events.manage.gallery', $event),
            ],
            [
                'label' => $currentAlbum['label'],
            ],
        ]
        : [
            ['label' => 'Event Albums'],
        ];

    $currentAlbumToken = $inAlbum ? ($currentAlbum['name'] ?? '') : '';

    // Options for transfer dropdown: Uncategorized + all existing albums
    $albumOptions = collect([
        [
            'token' => '__uncategorized__',
            'label' => 'Uncategorized',
        ],
    ]);

    foreach ($albums as $album) {
        if ($album['name'] === '__uncategorized__') {
            continue;
        }
        $albumOptions->push([
            'token' => $album['name'],
            'label' => $album['label'],
        ]);
    }
@endphp

<x-app-layout>
    <div
        class="max-w-6xl mx-auto px-4 py-6 space-y-4"
        x-data="{
            showUploadModal: false,
            showCaptionModal: false,
            showRenameAlbumModal: false,
            showTransferModal: false,

            editingPhotoId: null,
            editingCaption: '',
            editingImageSrc: null,

            currentAlbumParam: '{{ $currentAlbumToken }}',
            uploadPreviews: [],
            handleFileChange(event) {
                this.uploadPreviews = [];
                const files = event.target.files || [];
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    // Only preview images
                    if (!file.type.startsWith('image/')) continue;
                    this.uploadPreviews.push({
                        name: file.name,
                        size: file.size,
                        url: URL.createObjectURL(file),
                    });
                }
            },
            clearUploadPreviews() {
                this.uploadPreviews.forEach(f => URL.revokeObjectURL(f.url));
                this.uploadPreviews = [];
            },

            selectionMode: false,
            selectedIds: [],
            transferTarget: '',

            renameNewName: '{{ $inAlbum && !in_array($currentAlbumToken, ['__all__','__uncategorized__']) ? e($currentAlbum['label']) : '' }}',

            toggleSelection(id) {
                const idx = this.selectedIds.indexOf(id);
                if (idx === -1) {
                    this.selectedIds.push(id);
                } else {
                    this.selectedIds.splice(idx, 1);
                }
            },
            isSelected(id) {
                return this.selectedIds.includes(id);
            },
            resetSelection() {
                this.selectedIds = [];
            }
        }"
    >
        {{-- Header + tabs --}}
        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'gallery'])

        {{-- Breadcrumbs --}}
        <x-breadcrumbs :items="$breadcrumbItems" />

        {{-- Flash status --}}
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                <p class="font-semibold mb-1">There were problems with your input:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border border-slate-200 rounded-lg bg-white p-4 text-sm space-y-3">
            <div class="flex items-center justify-between mb-1">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-photo class="w-4 h-4 text-slate-500" />
                        @if ($inAlbum)
                            <h2 class="text-sm font-semibold text-slate-900">
                                Album:
                                <span class="font-bold">{{ $currentAlbum['label'] }}</span>
                            </h2>
                        @else
                            <h2 class="text-sm font-semibold text-slate-900">
                                Event gallery albums
                            </h2>
                        @endif
                    </div>

                    <p class="text-xs text-slate-600">
                        @if ($inAlbum)
                            {{ $currentAlbum['count'] }} {{ Str::plural('photo', $currentAlbum['count']) }} in this album.
                        @else
                            Manage albums and photos for this event. Click an album to view its photos.
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Rename album (only for named albums, not All/Uncategorized) --}}
                    @if ($inAlbum && !in_array($currentAlbumToken, ['__all__', '__uncategorized__']))
                        <button
                            type="button"
                            class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 bg-white hover:bg-slate-50"
                            @click="showRenameAlbumModal = true"
                        >
                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                            Rename album
                        </button>
                    @endif

                    {{-- Multi-select toggle (only when in album with photos) --}}
                    @if ($inAlbum && $currentAlbum['count'] > 0)
                        <button
                            type="button"
                            class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 bg-white hover:bg-slate-50"
                            @click="
                                selectionMode = !selectionMode;
                                if (!selectionMode) { resetSelection(); }
                            "
                        >
                            <x-heroicon-o-rectangle-group class="w-4 h-4 mr-1" />
                            <span x-show="!selectionMode">Multi-select</span>
                            <span x-show="selectionMode">Exit multi-select</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- View A: Albums grid --}}
            @unless ($inAlbum)
                <div class="mt-2">
                    @if ($allAlbum['count'] === 0 && $albums->isEmpty())
                        <p class="text-[11px] text-slate-500 mb-2">
                            No gallery photos yet. Create your first album and upload photos.
                        </p>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        {{-- All Photos pseudo-album --}}
                        <a
                            href="{{ route('events.manage.gallery', ['event' => $event, 'album' => '__all__']) }}"
                            class="group border border-slate-200 rounded-lg overflow-hidden bg-slate-50 hover:border-sky-300 hover:shadow-sm transition"
                        >
                            <div class="relative">
                                @if ($allAlbum['cover'] && $allAlbum['cover']->image_url)
                                    @if ($allAlbum['count'] > 1)
                                        <div class="absolute inset-0 translate-x-1 translate-y-1 rounded-md border border-slate-200 bg-slate-100"></div>
                                        <div class="absolute inset-0 translate-x-0.5 -translate-y-0.5 rounded-md border border-slate-200 bg-slate-50"></div>
                                    @endif
                                    <div class="relative">
                                        <img
                                            src="{{ $allAlbum['cover']->image_url }}"
                                            alt="All photos"
                                            class="w-full aspect-[4/3] object-cover rounded-md"
                                        >
                                    </div>
                                @else
                                    <div class="w-full aspect-[4/3] flex items-center justify-center text-xs text-slate-400 bg-slate-100 rounded-md">
                                        <x-heroicon-o-photo class="w-5 h-5 mr-1" />
                                        No photos yet
                                    </div>
                                @endif
                            </div>

                            <div class="px-3 py-2 border-t border-slate-200">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-slate-900 truncate group-hover:text-sky-700">
                                        All photos
                                    </p>
                                    <p class="text-[11px] text-slate-500 whitespace-nowrap">
                                        {{ $allAlbum['count'] }} {{ Str::plural('photo', $allAlbum['count']) }}
                                    </p>
                                </div>
                            </div>
                        </a>

                        {{-- Real albums --}}
                        @foreach ($albums as $album)
                            <a
                                href="{{ route('events.manage.gallery', ['event' => $event, 'album' => $album['name']]) }}"
                                class="group border border-slate-200 rounded-lg overflow-hidden bg-slate-50 hover:border-sky-300 hover:shadow-sm transition"
                            >
                                <div class="relative">
                                    @if ($album['cover'] && $album['cover']->image_url)
                                        @if ($album['count'] > 1)
                                            <div class="absolute inset-0 translate-x-1 translate-y-1 rounded-md border border-slate-200 bg-slate-100"></div>
                                            <div class="absolute inset-0 translate-x-0.5 -translate-y-0.5 rounded-md border border-slate-200 bg-slate-50"></div>
                                        @endif
                                        <div class="relative">
                                            <img
                                                src="{{ $album['cover']->image_url }}"
                                                alt="{{ $album['label'] }}"
                                                class="w-full aspect-[4/3] object-cover rounded-md"
                                            >
                                        </div>
                                    @else
                                        <div class="w-full aspect-[4/3] flex items-center justify-center text-xs text-slate-400 bg-slate-100 rounded-md">
                                            <x-heroicon-o-photo class="w-5 h-5 mr-1" />
                                            No cover photo
                                        </div>
                                    @endif
                                </div>

                                <div class="px-3 py-2 border-t border-slate-200">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-semibold text-slate-900 truncate group-hover:text-sky-700">
                                            {{ $album['label'] }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 whitespace-nowrap">
                                            {{ $album['count'] }} {{ Str::plural('photo', $album['count']) }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        @endforeach

                        {{-- Add New Album tile --}}
                        <button
                            type="button"
                            class="border border-dashed border-slate-300 rounded-lg bg-slate-50 hover:border-sky-400 hover:bg-sky-50/40 transition flex flex-col items-center justify-center text-xs text-slate-500 py-6"
                            @click="showUploadModal = true"
                        >
                            <x-heroicon-o-plus-circle class="w-6 h-6 mb-2 text-slate-400" />
                            <span class="font-medium text-slate-700">Add new album</span>
                            <span class="text-[11px] text-slate-500 mt-1">
                                Create an album and upload photos
                            </span>
                        </button>
                    </div>
                </div>
            @endunless

            {{-- View B: Inside album (masonry) --}}
            @if ($inAlbum)
                <div class="mt-3 space-y-2">
                    @if ($currentAlbum['count'] === 0)
                        <p class="text-[11px] text-slate-500">
                            This album has no photos yet. Use the “Add more photos” card below to upload images.
                        </p>
                    @endif

                    <div class="columns-2 md:columns-3 gap-3">
                        {{-- Add more photos card (first item) --}}
                        <div class="mb-3 break-inside-avoid" x-show="!selectionMode">
                            <button
                                type="button"
                                class="w-full aspect-[4/3] flex flex-col items-center justify-center rounded-md border border-dashed border-slate-300 bg-slate-50 hover:border-sky-400 hover:bg-sky-50/40 text-xs text-slate-500 transition"
                                @click="showUploadModal = true"
                            >
                                <x-heroicon-o-arrow-up-tray class="w-6 h-6 mb-2 text-slate-400" />
                                <span class="font-medium text-slate-700">Add more photos</span>
                                <span class="text-[11px] text-slate-500 mt-1">
                                    Upload additional images to this album
                                </span>
                            </button>
                        </div>

{{-- Photos --}}
@foreach ($currentAlbum['photos'] as $photo)
    <div
        class="mb-3 break-inside-avoid relative group cursor-pointer"
        @click="
            if (selectionMode) {
                toggleSelection({{ $photo->id }});
            } else {
                showCaptionModal = true;
                editingPhotoId   = {{ $photo->id }};
                editingCaption   = @js($photo->caption ?? '');
                editingImageSrc  = @js($photo->image_url ?? asset('images/branding/attendify-brand.png'));
            }
        "
    >
        {{-- Selection highlight --}}
        <div
            class="absolute inset-0 rounded-md ring-2 ring-sky-500 pointer-events-none"
            x-show="selectionMode && isSelected({{ $photo->id }})"
        ></div>

        <img
            src="{{ $photo->image_url ?? asset('images/branding/attendify-brand.png') }}"
            alt="{{ $photo->caption ?: 'Event photo' }}"
            class="w-full rounded-md border border-slate-200 bg-slate-50"
            loading="lazy"
        >

        {{-- Selection check badge --}}
        <div
            class="absolute top-1 left-1"
            x-show="selectionMode"
        >
            <div
                class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs"
                :class="isSelected({{ $photo->id }})
                    ? 'bg-sky-600'
                    : 'bg-black/60'"
            >
                <x-heroicon-o-check class="w-3.5 h-3.5" />
            </div>
        </div>

        {{-- Top-right edit + delete (hidden in selectionMode) --}}
        <div
            class="absolute top-1 right-1 flex gap-1 opacity-0 group-hover:opacity-100 transition"
            x-show="!selectionMode"
        >
            {{-- Edit --}}
            <button
                type="button"
                class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-black/55 text-white hover:bg-black/70"
                @click.stop="
                    showCaptionModal = true;
                    editingPhotoId   = {{ $photo->id }};
                    editingCaption   = @js($photo->caption ?? '');
                    editingImageSrc  = @js($photo->image_url ?? asset('images/branding/attendify-brand.png'));
                "
            >
                <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
            </button>

            {{-- Delete --}}
            <form
                action="{{ route('events.manage.gallery.destroy', [$event, $photo]) }}"
                method="POST"
                onsubmit="return confirm('Remove this photo from the album?')"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-black/55 text-white hover:bg-black/70"
                    @click.stop
                >
                    <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                </button>
            </form>
        </div>

        {{-- Static caption preview --}}
        @if ($photo->caption)
            <div class="mt-1 px-0.5">
                <p class="text-[11px] text-slate-700 line-clamp-2">
                    {{ $photo->caption }}
                </p>
            </div>
        @endif
    </div>
@endforeach

                    </div>
                </div>

                {{-- Mass actions footer --}}
                <div
                    class="pt-3 mt-3 border-t border-slate-200 flex items-center justify-between text-xs"
                    x-show="selectionMode"
                >
                    <p class="text-slate-600">
                        <span x-text="selectedIds.length"></span>
                        <span x-text="selectedIds.length === 1 ? ' photo selected' : ' photos selected'"></span>
                    </p>

                    <div class="flex items-center gap-2">
                        {{-- Transfer selected --}}
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                   border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed"
                            :disabled="selectedIds.length === 0"
                            @click="if (selectedIds.length > 0) { showTransferModal = true; transferTarget = ''; }"
                        >
                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 mr-1" />
                            Transfer to
                        </button>

                        {{-- Delete selected --}}
                        <form
                            action="{{ route('events.manage.gallery.bulk-destroy', $event) }}"
                            method="POST"
                            @submit="if (!confirm('Delete selected photos? This cannot be undone.')) { $event.preventDefault(); }"
                        >
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="album" :value="currentAlbumParam">

                            <template x-for="id in selectedIds" :key="id">
                                <input type="hidden" name="ids[]" :value="id">
                            </template>

                            <button
                                type="submit"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                       bg-red-600 text-white hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed"
                                :disabled="selectedIds.length === 0"
                            >
                                <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                Delete selected
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Upload modal --}}
        <div
            x-show="showUploadModal"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/50"
        >
            <div
                class="bg-white rounded-xl shadow-lg w-full max-w-lg mx-4 border border-slate-200"
                @click.away="showUploadModal = false; clearUploadPreviews();"
            >
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4 text-slate-500" />
                        @if ($inAlbum && $currentAlbumToken !== '__all__' && $currentAlbumToken !== '__uncategorized__')
                            Add photos to album
                        @else
                            Upload gallery photos
                        @endif
                    </h3>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600"
                        @click="showUploadModal = false; clearUploadPreviews();"
                    >
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </div>

                <form
                    action="{{ route('events.manage.gallery.store', $event) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="px-4 py-3 space-y-3 text-sm"
                >
                    @csrf

                    {{-- Album name --}}
                    @if ($inAlbum && $currentAlbumToken !== '__all__' && $currentAlbumToken !== '__uncategorized__')
                        <input type="hidden" name="album_name" value="{{ $currentAlbum['name'] ?? '' }}">
                        <div>
                            <p class="text-xs text-slate-600">
                                Uploading to album:
                                <span class="font-semibold text-slate-900">
                                    {{ $currentAlbum['label'] }}
                                </span>
                            </p>
                        </div>
                    @else
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Album name
                            </label>
                            <input
                                type="text"
                                name="album_name"
                                class="block w-full rounded-md border-slate-300 text-sm
                                       focus:border-sky-500 focus:ring-sky-500"
                                placeholder="e.g. Day 1 – Morning, Closing Ceremony"
                                value="{{ old('album_name') }}"
                            >
                            <p class="mt-1 text-[11px] text-slate-500">
                                If left blank, photos will go to <span class="italic">Uncategorized</span>.
                                If an album with this name already exists, photos will be added to it.
                            </p>
                        </div>
                    @endif

                    {{-- Batch caption --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Caption for this batch (optional)
                        </label>
                        <input
                            type="text"
                            name="caption"
                            class="block w-full rounded-md border-slate-300 text-sm
                                   focus:border-sky-500 focus:ring-sky-500"
                            placeholder="Short caption applied to all uploaded photos"
                            value="{{ old('caption') }}"
                        >
                        <p class="mt-1 text-[11px] text-slate-500">
                            You can edit captions per photo later from the album view.
                        </p>
                    </div>

{{-- Photos --}}
<div>
    <label class="block text-xs font-medium text-slate-700 mb-1">
        Photos
    </label>
    <input
        type="file"
        name="photos[]"
        multiple
        accept="image/*"
        class="block w-full text-sm text-slate-700
               file:mr-3 file:py-1.5 file:px-3
               file:rounded-md file:border-0
               file:text-xs file:font-medium
               file:bg-slate-100 file:text-slate-700
               hover:file:bg-slate-200"
        @change="handleFileChange($event)"
    >
    <p class="mt-1 text-[11px] text-slate-500">
        Select one or more images. Max 5&nbsp;MB each.
    </p>

    {{-- Horizontal preview strip --}}
    <div
        class="mt-3 overflow-x-auto"
        x-show="uploadPreviews.length"
        x-cloak
    >
        <div class="flex gap-2 pb-1">
            <template x-for="(file, index) in uploadPreviews" :key="index">
                <div class="w-20 h-20 rounded-md border border-slate-200 bg-slate-50 overflow-hidden flex-shrink-0">
                    <img
                        :src="file.url"
                        :alt="file.name"
                        class="w-full h-full object-cover"
                    >
                </div>
            </template>
        </div>
        <p class="mt-1 text-[11px] text-slate-500" x-text="`${uploadPreviews.length} file(s) selected`"></p>
    </div>
</div>

                    <div class="pt-2 border-t border-slate-200 flex justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                   border border-slate-200 text-slate-600 bg-white hover:bg-slate-50"
                            @click="showUploadModal = false; clearUploadPreviews();"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                   bg-[#0052CC] text-white hover:bg-[#0041a3] shadow-sm"
                        >
                            <x-heroicon-o-arrow-up-tray class="w-4 h-4 mr-1" />
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>

{{-- Caption edit modal --}}
<div
    x-show="showCaptionModal"
    x-cloak
    class="fixed inset-0 z-40 flex items-center justify-center bg-black/50"
>
    <div
        class="bg-white rounded-xl shadow-lg w-full max-w-lg mx-4 border border-slate-200 overflow-hidden"
        @click.away="showCaptionModal = false"
    >
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <x-heroicon-o-pencil-square class="w-4 h-4 text-slate-500" />
                Edit photo caption
            </h3>
            <button
                type="button"
                class="text-slate-400 hover:text-slate-600"
                @click="showCaptionModal = false"
            >
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </button>
        </div>

        <form
            x-bind:action="`{{ route('events.manage.gallery.update', ['event' => $event, 'photo' => '__PHOTO__']) }}`.replace('__PHOTO__', editingPhotoId)"
            method="POST"
            class="space-y-0 text-sm"
        >
            @csrf
            @method('PUT')

            <input type="hidden" name="album" :value="currentAlbumParam">

            {{-- Image preview full width on top --}}
            <div class="w-full bg-slate-100 border-b border-slate-200 max-h-72 overflow-hidden flex items-center justify-center">
                <img
                    :src="editingImageSrc"
                    alt=""
                    class="w-full h-full object-contain"
                >
            </div>

            {{-- Form content below --}}
            <div class="px-4 py-3 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">
                        Caption
                    </label>
                    <textarea
                        name="caption"
                        rows="3"
                        class="block w-full rounded-md border-slate-300 text-sm
                               focus:border-sky-500 focus:ring-sky-500"
                        x-model="editingCaption"
                    ></textarea>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Leave blank to remove the caption.
                    </p>
                </div>

                <div class="pt-2 border-t border-slate-200 flex justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                               border border-slate-200 text-slate-600 bg-white hover:bg-slate-50"
                        @click="showCaptionModal = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                               bg-[#0052CC] text-white hover:bg-[#0041a3] shadow-sm"
                    >
                        <x-heroicon-o-check class="w-4 h-4 mr-1" />
                        Save caption
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


        {{-- Rename album modal --}}
        @if ($inAlbum && !in_array($currentAlbumToken, ['__all__', '__uncategorized__']))
            <div
                x-show="showRenameAlbumModal"
                x-cloak
                class="fixed inset-0 z-40 flex items-center justify-center bg-black/50"
            >
                <div
                    class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 border border-slate-200"
                    @click.away="showRenameAlbumModal = false"
                >
                    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <x-heroicon-o-pencil-square class="w-4 h-4 text-slate-500" />
                            Rename album
                        </h3>
                        <button
                            type="button"
                            class="text-slate-400 hover:text-slate-600"
                            @click="showRenameAlbumModal = false"
                        >
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    </div>

                    <form
                        action="{{ route('events.manage.gallery.rename-album', $event) }}"
                        method="POST"
                        class="px-4 py-3 space-y-3 text-sm"
                    >
                        @csrf

                        <input type="hidden" name="old_name" value="{{ $currentAlbum['name'] ?? '' }}">

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                Current name
                            </label>
                            <div class="px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-xs text-slate-700">
                                {{ $currentAlbum['label'] }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">
                                New album name
                            </label>
                            <input
                                type="text"
                                name="new_name"
                                class="block w-full rounded-md border-slate-300 text-sm
                                       focus:border-sky-500 focus:ring-sky-500"
                                x-model="renameNewName"
                            >
                            <p class="mt-1 text-[11px] text-slate-500">
                                All photos in this album will move under the new name.
                            </p>
                        </div>

                        <div class="pt-2 border-t border-slate-200 flex justify-end gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                       border border-slate-200 text-slate-600 bg-white hover:bg-slate-50"
                                @click="showRenameAlbumModal = false"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                       bg-[#0052CC] text-white hover:bg-[#0041a3] shadow-sm"
                            >
                                <x-heroicon-o-check class="w-4 h-4 mr-1" />
                                Save name
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Transfer selected photos modal --}}
        <div
            x-show="showTransferModal"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/50"
        >
            <div
                class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 border border-slate-200"
                @click.away="showTransferModal = false"
            >
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 text-slate-500" />
                        Transfer selected photos
                    </h3>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600"
                        @click="showTransferModal = false"
                    >
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </div>

                <form
                    action="{{ route('events.manage.gallery.bulk-transfer', $event) }}"
                    method="POST"
                    class="px-4 py-3 space-y-3 text-sm"
                >
                    @csrf

                    <input type="hidden" name="album" :value="currentAlbumParam">

                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Move to album
                        </label>
                        <select
                            name="target_album"
                            class="block w-full rounded-md border-slate-300 text-sm
                                   focus:border-sky-500 focus:ring-sky-500"
                            x-model="transferTarget"
                        >
                            <option value="" disabled>Select album...</option>
                            @foreach ($albumOptions as $option)
                                <option value="{{ $option['token'] }}">
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-500">
                            Photos will be moved to the selected album. Choose “Uncategorized” to remove album assignment.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-slate-200 flex justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                   border border-slate-200 text-slate-600 bg-white hover:bg-slate-50"
                            @click="showTransferModal = false"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium
                                   bg-[#0052CC] text-white hover:bg-[#0041a3] shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
                            :disabled="!transferTarget"
                        >
                            <x-heroicon-o-check class="w-4 h-4 mr-1" />
                            Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
