@php
    /** @var \App\Models\User $profile */
    $photoUrl = $profile->photo_url ?? asset('images/ui/userdefault.jpg');
@endphp

<div
    x-data="{ menu:false, view:false }"
    class="relative flex flex-col items-center gap-4 px-4 py-5"
>
    {{-- Avatar + label --}}
    <div class="flex flex-col items-center gap-3">
        {{-- Avatar with overlay menu button --}}
        <div class="relative">
            <div class="w-32 h-32 md:w-40 md:h-40 rounded-full overflow-hidden ring-2 ring-slate-200 bg-slate-100">
                <img
                    src="{{ $photoUrl }}"
                    alt="Profile photo"
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
            </div>

            {{-- Corner action button --}}
            <div class="absolute -bottom-1 -right-1">
                <div class="relative">
                    <button
                        type="button"
                        x-on:click="menu=!menu"
                        class="h-9 w-9 rounded-full bg-white shadow-sm ring-1 ring-slate-200 flex items-center justify-center hover:bg-slate-50"
                        aria-haspopup="menu"
                        :aria-expanded="menu"
                        title="Photo options"
                    >
                        @if($viewerIsOwner)
                            <i class="bi bi-pencil text-slate-700 text-base"></i>
                        @else
                            <i class="bi bi-eye text-slate-700 text-base"></i>
                        @endif
                        <span class="sr-only">Photo options</span>
                    </button>

                    {{-- Dropdown --}}
                    <div
                        x-cloak
                        x-show="menu"
                        x-transition
                        x-on:click.outside="menu=false"
                        class="absolute right-0 mt-2 w-44 rounded-lg bg-white shadow-lg ring-1 ring-slate-200 z-[1] overflow-hidden text-sm"
                        role="menu"
                    >
                        <div class="px-3 py-2 text-[11px] uppercase tracking-wide text-slate-500 bg-slate-50 border-b border-slate-100">
                            Photo options
                        </div>

                        <button
                            type="button"
                            class="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center gap-2"
                            x-on:click="view=true; menu=false"
                            role="menuitem"
                        >
                            <i class="bi bi-arrows-fullscreen text-slate-500 text-sm"></i>
                            <span>View photo</span>
                        </button>

                        @if($viewerIsOwner)
                            <button
                                type="button"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center gap-2"
                                wire:click="changePhoto"
                                role="menuitem"
                            >
                                <i class="bi bi-upload text-slate-500 text-sm"></i>
                                <span>Change photo</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Caption --}}
        <div class="text-center space-y-1">
            <p class="text-lg font-bold text-slate-900">
                {{ $profile->display_name ?? $profile->name ?? 'User' }}
            </p>
            <p class="text-xs text-slate-500">
                {{ $viewerIsOwner
                    ? $profile->slug
                    : 'Profile photo'
                }}
            </p>
        </div>
    </div>

    {{-- View photo modal --}}
    <div wire:teleport="#modal-root">
        <div
            x-cloak
            x-show="view"
            x-transition.opacity
            x-on:keydown.escape.window="view=false"
            x-on:click="view=false"
            class="fixed inset-0 bg-black/80 z-[2000] grid place-items-center px-4"
        >
            <div
                class="bg-slate-900/70 p-3 rounded-xl max-w-[96vw] max-h-[90vh] flex flex-col gap-3"
                x-on:click.stop
            >
                <div class="flex items-center justify-between text-slate-100 text-sm">
                    <span>Profile photo</span>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-800"
                        x-on:click="view=false"
                    >
                        <i class="bi bi-x-lg text-xs"></i>
                        <span class="sr-only">Close</span>
                    </button>
                </div>
                <img
                    src="{{ $photoUrl }}"
                    alt="Profile photo enlarged"
                    class="max-w-[88vw] max-h-[75vh] rounded-lg shadow-2xl object-contain bg-slate-900"
                />
            </div>
        </div>
    </div>

    {{-- Upload photo modal --}}
    <div wire:teleport="#modal-root">
        <div
            x-data="{ open: false }"
            x-on:open-modal.window="
                if ($event.detail.name === 'upload-avatar') open = true
            "
            x-on:close-modal.window="
                if ($event.detail.name === 'upload-avatar') open = false
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
                            Update profile photo
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 leading-snug">
                            Use a clear, recent photo where your face is visible. Maximum size is 2&nbsp;MB.
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

                <form wire:submit.prevent="savePhotoUpload" class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-slate-700">
                            Choose image file
                        </label>
                        <input
                            type="file"
                            wire:model="photoUpload"
                            accept="image/*"
                            class="block w-full text-sm text-slate-700 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border file:border-slate-200 file:text-xs file:font-medium file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100"
                        />

                        @error('photoUpload')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        @if ($photoUpload)
                            <div class="mt-3 flex items-center gap-3">
                                <div class="w-14 h-14 rounded-full overflow-hidden ring-1 ring-slate-200 bg-slate-100">
                                    <img
                                        src="{{ $photoUpload->temporaryUrl() }}"
                                        alt="New profile photo preview"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <p class="text-xs text-slate-500">
                                    Preview of your new photo. It will replace your current avatar.
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <p class="text-[11px] text-slate-500">
                            Supported formats: JPG, PNG, GIF, WebP &middot; Max 2&nbsp;MB
                        </p>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                x-on:click="open = false"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="px-3 py-1.5 rounded-md bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                                wire:loading.attr="disabled"
                                wire:target="photoUpload,savePhotoUpload"
                            >
                                <span wire:loading.remove wire:target="savePhotoUpload">
                                    Upload photo
                                </span>
                                <span wire:loading wire:target="savePhotoUpload">
                                    Uploading…
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
