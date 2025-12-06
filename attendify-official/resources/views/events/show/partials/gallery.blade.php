{{-- resources/views/events/show/partials/gallery.blade.php --}}
@if ($hasGallery)
    @php
        // Randomized carousel photos (include album label for overlay)
        $carouselPhotos = $galleryPhotos
            ->shuffle()
            ->take(12)
            ->map(function ($p) {
                /** @var \App\Models\EventGallery $p */
                $albumLabel = $p->album_name ?: 'Uncategorized';
                return [
                    'id'          => $p->id,
                    'url'         => $p->image_url,
                    'caption'     => $p->caption,
                    'album_label' => $albumLabel,
                ];
            })
            ->values();

        // Group by album_name (''/null => Uncategorized)
        $albumsGrouped = $galleryPhotos
            ->groupBy(function ($photo) {
                return $photo->album_name ?? '';
            });

        $albumsForPreview = collect();

        // 1) Pseudo-album: All photos
        $albumsForPreview->push([
            'key'    => '__all__',
            'name'   => '__all__',
            'label'  => 'All photos',
            'count'  => $galleryPhotos->count(),
            'cover'  => $galleryPhotos->first()?->image_url,
            'photos' => $galleryPhotos->map(fn ($p) => [
                'id'      => $p->id,
                'url'     => $p->image_url,
                'caption' => $p->caption,
            ])->values(),
        ]);

        // 2) Real albums
        foreach ($albumsGrouped as $rawName => $photos) {
            $name  = $rawName === '' ? null : $rawName;
            $label = $name ?: 'Uncategorized';

            $albumsForPreview->push([
                'key'    => $name ?? 'uncategorized',
                'name'   => $name,
                'label'  => $label,
                'count'  => $photos->count(),
                'cover'  => $photos->first()?->image_url,
                'photos' => $photos->map(fn ($p) => [
                    'id'      => $p->id,
                    'url'     => $p->image_url,
                    'caption' => $p->caption,
                ])->values(),
            ]);
        }
    @endphp

    {{-- RIBBON HEADER: EVENT PHOTOS --}}
    <div id="event-photos-section" class="-mx-4 sm:-mx-6 mt-8 mb-4">
        <div class="relative flex">
            <div class="relative w-full sm:w-3/4 lg:w-1/2">
                <div class="absolute inset-0 bg-gradient-to-r from-[#021530] via-[#032963] to-[#053b88]"></div>

                <div class="relative flex items-center px-4 sm:px-6 h-12">
                    <h2 class="text-[1.1rem] sm:text-lg font-semibold text-white tracking-tight flex items-center">
                        <x-heroicon-o-photo class="w-5 h-5 mr-2 text-white/90" />
                        Event Gallery
                    </h2>
                </div>

                <div
                    class="absolute top-0 right-[-24px]
                        w-0 h-0
                        border-t-[24px] border-b-[24px] border-l-[24px]
                        border-t-transparent border-b-transparent border-l-[#053b88]"
                ></div>
            </div>

            <div class="hidden lg:block flex-1"></div>
        </div>
    </div>

    <section
        class="mb-10"
        x-data="{
            albums: @js($albumsForPreview),
            carouselPhotos: @js($carouselPhotos),

            // null => View A (albums list)
            // non-null => View B (photos in that album)
            activeAlbumKey: null,

            // Lightbox state
            lightboxOpen: false,
            lightboxAlbumKey: null,
            lightboxIndex: 0,

            // Carousel state
            carouselIndex: 0,

            get currentAlbum() {
                if (!this.activeAlbumKey) return null;
                return this.albums.find(a => a.key === this.activeAlbumKey) || null;
            },

            openAlbum(key) {
                this.activeAlbumKey = key;
                this.lightboxAlbumKey = key;
                this.lightboxIndex = 0;
            },

            openLightbox(albumKey, idx) {
                this.lightboxAlbumKey = albumKey;
                this.lightboxIndex = idx;
                this.lightboxOpen = true;
            },

            closeLightbox() {
                this.lightboxOpen = false;
            },

            nextPhoto() {
                const album = this.albums.find(a => a.key === this.lightboxAlbumKey);
                if (!album || !album.photos.length) return;
                this.lightboxIndex = (this.lightboxIndex + 1 + album.photos.length) % album.photos.length;
            },

            prevPhoto() {
                const album = this.albums.find(a => a.key === this.lightboxAlbumKey);
                if (!album || !album.photos.length) return;
                this.lightboxIndex = (this.lightboxIndex - 1 + album.photos.length) % album.photos.length;
            },

            nextCarousel() {
                if (!this.carouselPhotos.length) return;
                this.carouselIndex = (this.carouselIndex + 1) % this.carouselPhotos.length;
            },

            prevCarousel() {
                if (!this.carouselPhotos.length) return;
                this.carouselIndex = (this.carouselIndex - 1 + this.carouselPhotos.length) % this.carouselPhotos.length;
            },

            init() {
                if (this.carouselPhotos.length > 1) {
                    setInterval(() => {
                        this.nextCarousel();
                    }, 8000); // longer interval (8s)
                }
            },
        }"
    >
        {{-- CAROUSEL: all photos, random order, with controls + album label --}}
        <div class="px-4 sm:px-6 mb-3">
            <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-900/95">
                <div class="aspect-[16/9] sm:aspect-[21/9] w-full overflow-hidden relative">
                    <template x-if="carouselPhotos.length">
                        <div
                            class="h-full w-full flex transition-transform duration-500"
                            :style="`transform: translateX(-${carouselIndex * 100}%);`"
                        >
                            <template x-for="(photo, idx) in carouselPhotos" :key="photo.id">
                                <div class="w-full h-full flex-shrink-0 relative">
                                    <img
                                        :src="photo.url"
                                        class="w-full h-full object-cover"
                                        :alt="photo.caption || 'Event photo'"
                                    >
                                    {{-- Bottom-left overlay: album name + optional caption --}}
                                    <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/70 via-black/40 to-transparent text-white">
                                        <p class="text-[11px] font-semibold tracking-[0.16em] uppercase text-white/80">
                                            <span x-text="photo.album_label"></span>
                                        </p>
                                        <p
                                            class="text-xs text-white line-clamp-2"
                                            x-show="photo.caption"
                                            x-text="photo.caption"
                                        ></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!carouselPhotos.length">
                        <div class="w-full h-full flex items-center justify-center text-xs text-slate-400 bg-slate-900/80">
                            <x-heroicon-o-photo class="w-5 h-5 mr-1" />
                            <span>No photos available yet</span>
                        </div>
                    </template>

                    {{-- Carousel controls --}}
                    <button
                        type="button"
                        class="absolute inset-y-0 left-2 my-auto w-9 h-9 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg:black/80 hover:bg-black/80"
                        @click="prevCarousel()"
                        x-show="carouselPhotos.length > 1"
                    >
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                    </button>

                    <button
                        type="button"
                        class="absolute inset-y-0 right-2 my-auto w-9 h-9 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80"
                        @click="nextCarousel()"
                        x-show="carouselPhotos.length > 1"
                    >
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        {{-- BREADCRUMB (below carousel) --}}
        <div class="flex items-center justify-between px-4 sm:px-6 mb-2">
            <nav class="text-[12px] text-slate-600 flex items-center gap-1">
                {{-- View A: just label --}}
                <template x-if="!activeAlbumKey">
                    <span class="font-semibold text-slate-800">
                        Event Albums
                    </span>
                </template>

                {{-- View B: Event photos / Album Name --}}
                <template x-if="activeAlbumKey">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="text-slate-600 hover:text-sky-600 font-medium"
                            @click="activeAlbumKey = null"
                        >
                            Event Albums
                        </button>
                        <span class="text-slate-400">/</span>
                        <span
                            class="font-semibold text-slate-900"
                            x-text="currentAlbum?.label || ''"
                        ></span>
                    </div>
                </template>
            </nav>
        </div>

        {{-- VIEW A: ALBUM STRIP (full-width bg, 2 rows, horizontal scroll, thin scrollbar) --}}
        <div class="-mx-4 sm:-mx-6" x-show="!activeAlbumKey" x-transition>
            <div class="bg-[#021530] border-y border-slate-800 px-4 sm:px-6 py-3">
                <div class="overflow-x-auto thin-scrollbar pb-2 -mx-2 px-2">
                    <div
                        class="grid grid-rows-2 grid-flow-col auto-cols-[11rem] gap-3"
                    >
                        <template x-for="album in albums" :key="album.key">
                            <button
                                type="button"
                                class="group relative h-full w-full text-left border border-slate-500/60 rounded-lg bg-slate-900/40 hover:border-sky-400 hover:bg-slate-900/70 hover:shadow-sm overflow-hidden flex flex-col"
                                @click="openAlbum(album.key)"
                            >
                                <div class="relative">
                                    <template x-if="album.cover">
                                        <div>
                                            <template x-if="album.count > 1">
                                                <div>
                                                    <div class="absolute inset-0 translate-x-1 translate-y-1 rounded-md border border-slate-600 bg-slate-900/60"></div>
                                                    <div class="absolute inset-0 translate-x-0.5 -translate-y-0.5 rounded-md border border-slate-600 bg-slate-900/40"></div>
                                                </div>
                                            </template>
                                            <div class="relative">
                                                <img
                                                    :src="album.cover"
                                                    alt=""
                                                    class="w-full aspect-[4/3] object-cover rounded-md"
                                                >
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!album.cover">
                                        <div class="w-full aspect-[4/3] flex items-center justify-center text-xs text-slate-300 bg-slate-900/60">
                                            <x-heroicon-o-photo class="w-5 h-5 mr-1" />
                                            <span>No photos</span>
                                        </div>
                                    </template>
                                </div>

                                <div class="px-3 py-2 border-t border-slate-600/80 mt-auto">
                                    <div class="flex items-center justify-between gap-2">
                                        <p
                                            class="text-xs font-semibold text-white truncate group-hover:text-sky-300"
                                            x-text="album.label"
                                        ></p>
                                        <p
                                            class="text-[11px] text-slate-200 whitespace-nowrap"
                                            x-text="album.count + (album.count === 1 ? ' photo' : ' photos')"
                                        ></p>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- subtle scrollbar underline --}}
                <div class="mt-1 h-px bg-slate-700/80"></div>
            </div>
        </div>

        {{-- VIEW B: PHOTOS STRIP (full-width, taller, 2 rows, horizontal scroll, thin scrollbar) --}}
        <div class="-mx-4 sm:-mx-6 mt-3" x-show="activeAlbumKey" x-transition>
            <template x-if="currentAlbum && currentAlbum.photos.length">
                <div class="bg-slate-50 border-y border-slate-200 px-4 sm:px-6 py-4">
                    <div class="overflow-x-auto thin-scrollbar pb-2 -mx-2 px-2">
                        <div
                            class="grid grid-rows-2 grid-flow-col auto-cols-[13rem] gap-3 h-72"
                        >
                            <template x-for="(photo, idx) in currentAlbum.photos" :key="photo.id">
                                <div class="w-full h-full flex flex-col">
                                    <img
                                        :src="photo.url"
                                        :alt="photo.caption || 'Event photo'"
                                        class="w-full h-40 rounded-md border border-slate-200 bg-slate-50 cursor-pointer object-cover"
                                        @click="openLightbox(currentAlbum.key, idx)"
                                    >

                                    <template x-if="photo.caption">
                                        <p
                                            class="mt-1 text-[11px] text-slate-700 line-clamp-2"
                                            x-text="photo.caption"
                                        ></p>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- scrollbar underline --}}
                        <div class="mt-1 h-px bg-slate-200"></div>
                    </div>
                </div>
            </template>

            <template x-if="currentAlbum && !currentAlbum.photos.length">
                <p class="text-[13px] text-slate-500 italic py-4 px-4 sm:px-6">
                    This album has no photos yet.
                </p>
            </template>
        </div>

        {{-- LIGHTBOX MODAL --}}
        <div
            x-show="lightboxOpen"
            x-cloak
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/70"
        >
            <div
                class="relative bg-slate-950/95 border border-slate-700 rounded-xl w-full max-w-3xl mx-4 shadow-2xl"
                @click.away="closeLightbox()"
            >
                <button
                    type="button"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90"
                    @click="closeLightbox()"
                >
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>

                {{-- Top bar: album label + index --}}
                <div class="flex items-center justify-between px-4 pt-4 pb-2 text-[12px] text-slate-100">
                    <div>
                        <p
                            class="font-semibold"
                            x-text="(albums.find(a => a.key === lightboxAlbumKey)?.label) || 'Event photos'"
                        ></p>
                    </div>
                    <div x-show="albums.find(a => a.key === lightboxAlbumKey)">
                        <span
                            x-text="(() => {
                                const album = albums.find(a => a.key === lightboxAlbumKey);
                                if (!album) return '';
                                return (lightboxIndex + 1) + ' / ' + (album.photos?.length || 0);
                            })()"
                        ></span>
                    </div>
                </div>

                {{-- Body: arrows + image --}}
                <div class="px-4 pb-4 flex items-center gap-3">
                    <button
                        type="button"
                        class="w-9 h-9 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90"
                        @click.stop="prevPhoto()"
                    >
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                    </button>

                    <div class="flex-1 max-h-[70vh]">
                        <template x-if="albums.find(a => a.key === lightboxAlbumKey)">
                            <img
                                :src="(() => {
                                    const album = albums.find(a => a.key === lightboxAlbumKey);
                                    return album?.photos?.[lightboxIndex]?.url || '';
                                })()"
                                :alt="(() => {
                                    const album = albums.find(a => a.key === lightboxAlbumKey);
                                    return album?.photos?.[lightboxIndex]?.caption || 'Event photo';
                                })()"
                                class="w-full max-h-[70vh] object-contain rounded-lg bg-black"
                            >
                        </template>

                        <template x-if="(() => {
                            const album = albums.find(a => a.key === lightboxAlbumKey);
                            return !!album?.photos?.[lightboxIndex]?.caption;
                        })()">
                            <p
                                class="mt-2 text-[12px] text-slate-100"
                                x-text="(() => {
                                    const album = albums.find(a => a.key === lightboxAlbumKey);
                                    return album?.photos?.[lightboxIndex]?.caption || '';
                                })()"
                            ></p>
                        </template>
                    </div>

                    <button
                        type="button"
                        class="w-9 h-9 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90"
                        @click.stop="nextPhoto()"
                    >
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    </section>
@endif
