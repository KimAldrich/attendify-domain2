<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGallery;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EventGalleryController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/gallery
     * - View A: albums grid (no ?album)
     * - View B: inside album (?album=...)
     */
    public function index(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $albumName = $request->query('album'); // null, '__all__', '__uncategorized__', or real name

        if ($albumName === '') {
            $albumName = '__uncategorized__';
        }

        $photos = $event->gallery()
            ->orderBy('album_name')
            ->orderBy('order_index')
            ->orderBy('id')
            ->get();

        // Group into albums (null/'' => "__uncategorized__")
        $albums = $photos
            ->groupBy(function (EventGallery $photo) {
                return $photo->album_name ?? '__uncategorized__';
            })
            ->map(function ($photos, $key) {
                $name  = $key; // "__uncategorized__" or real name
                $label = $key === '__uncategorized__' ? 'Uncategorized' : $key;

                return [
                    'name'   => $name,
                    'label'  => $label,
                    'count'  => $photos->count(),
                    'cover'  => $photos->first(),
                    'photos' => $photos,
                ];
            });

        // Pseudo-album: All photos
        $allAlbum = [
            'name'   => '__all__',
            'label'  => 'All photos',
            'count'  => $photos->count(),
            'cover'  => $photos->first(),
        ];

        $currentAlbum = null;

        if ($albumName !== null) {
            if ($albumName === '__all__') {
                $currentAlbum = [
                    'name'   => '__all__',
                    'label'  => 'All photos',
                    'count'  => $photos->count(),
                    'photos' => $photos,
                ];
            } else {
                $currentAlbum = $albums->firstWhere('name', $albumName);

                if (! $currentAlbum) {
                    $label = $albumName === '__uncategorized__'
                        ? 'Uncategorized'
                        : $albumName;

                    $currentAlbum = [
                        'name'   => $albumName,
                        'label'  => $label,
                        'count'  => 0,
                        'photos' => collect(),
                    ];
                }
            }
        }

        return view('events.manage.gallery', [
            'event'        => $event,
            'albums'       => $albums,
            'allAlbum'     => $allAlbum,
            'currentAlbum' => $currentAlbum,
        ]);
    }

    /**
     * POST /events/manage/{event}/gallery
     * Upload photos to an album (new or existing).
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $validated = $request->validate([
            'album_name' => ['nullable', 'string', 'max:100'],
            'caption'    => ['nullable', 'string', 'max:255'],
            'photos'     => ['required', 'array'],
            'photos.*'   => ['image', 'max:5120'], // 5MB
        ]);

        $albumName = trim($validated['album_name'] ?? '');
        $albumName = $albumName === '' ? null : $albumName;

        $caption   = $validated['caption'] ?? null;
        $disk      = 'r2';
        $dir       = "events/{$event->id}/gallery";
        $nextIndex = (int) $event->gallery()->max('order_index') + 1;

        $uploadedCount = 0;

        foreach ($validated['photos'] as $file) {
            $extension = $file->getClientOriginalExtension();
            $filename  = now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.'.$extension;
            $path      = $dir.'/'.$filename;

            try {
                Storage::disk($disk)->putFileAs($dir, $file, $filename);

                EventGallery::create([
                    'event_id'   => $event->id,
                    'album_name' => $albumName,
                    'image_path' => $path,
                    'caption'    => $caption,
                    'order_index'=> $nextIndex++,
                ]);

                $uploadedCount++;
            } catch (\Throwable $e) {
                Log::error('[EventGallery] Failed to upload photo to R2', [
                    'event_id' => $event->id,
                    'path'     => $path,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $redirectParams = ['event' => $event];
        if ($albumName !== null) {
            $redirectParams['album'] = $albumName;
        }

        $statusMessage = $uploadedCount > 0
            ? "Uploaded {$uploadedCount} photo".($uploadedCount === 1 ? '' : 's')." to the gallery."
            : 'No photos were uploaded. Please try again.';

        return redirect()
            ->route('events.manage.gallery', $redirectParams)
            ->with('status', $statusMessage);
    }

    /**
     * PUT /events/manage/{event}/gallery/{photo}
     * Update caption for a single photo.
     */
    public function update(Request $request, Event $event, EventGallery $photo)
    {
        $this->authorize('manage', $event);

        if ($photo->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'album'   => ['nullable', 'string'],
        ]);

        $photo->update([
            'caption' => $validated['caption'] ?? null,
        ]);

        $redirectParams = ['event' => $event];
        if (! empty($validated['album'])) {
            $redirectParams['album'] = $validated['album'];
        }

        return redirect()
            ->route('events.manage.gallery', $redirectParams)
            ->with('status', 'Caption updated.');
    }

    /**
     * DELETE /events/manage/{event}/gallery/{photo}
     * Single delete.
     */
    public function destroy(Event $event, EventGallery $photo)
    {
        $this->authorize('manage', $event);

        if ($photo->event_id !== $event->id) {
            abort(404);
        }

        $albumName = $photo->album_name;

        if ($photo->image_path) {
            try {
                Storage::disk('r2')->delete($photo->image_path);
                Log::info('[EventGallery] Deleted gallery photo from R2', [
                    'event_id' => $event->id,
                    'path'     => $photo->image_path,
                ]);
            } catch (\Throwable $e) {
                Log::error('[EventGallery] Failed to delete gallery photo from R2', [
                    'event_id' => $event->id,
                    'path'     => $photo->image_path,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $photo->delete();

        $redirectParams = ['event' => $event];
        if ($albumName !== null) {
            $redirectParams['album'] = $albumName;
        }

        return redirect()
            ->route('events.manage.gallery', $redirectParams)
            ->with('status', 'Photo removed from gallery.');
    }

    /**
     * DELETE /events/manage/{event}/gallery/bulk-delete
     * Mass delete selected photos.
     */
    public function bulkDestroy(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
            'album' => ['nullable', 'string'],
        ]);

        $ids   = $validated['ids'];
        $album = $validated['album'] ?? null;

        $photos = EventGallery::where('event_id', $event->id)
            ->whereIn('id', $ids)
            ->get();

        $deletedCount = 0;

        foreach ($photos as $photo) {
            if ($photo->image_path) {
                try {
                    Storage::disk('r2')->delete($photo->image_path);
                    Log::info('[EventGallery] Deleted gallery photo from R2 (bulk)', [
                        'event_id' => $event->id,
                        'path'     => $photo->image_path,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('[EventGallery] Failed to delete gallery photo from R2 (bulk)', [
                        'event_id' => $event->id,
                        'path'     => $photo->image_path,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            $photo->delete();
            $deletedCount++;
        }

        $redirectParams = ['event' => $event];
        if (! empty($album)) {
            $redirectParams['album'] = $album;
        }

        $status = $deletedCount > 0
            ? "Deleted {$deletedCount} selected photo".($deletedCount === 1 ? '' : 's')."."
            : 'No photos were deleted.';

        return redirect()
            ->route('events.manage.gallery', $redirectParams)
            ->with('status', $status);
    }

    /**
     * POST /events/manage/{event}/gallery/rename-album
     * Rename a named album (updates all photos' album_name).
     */
    public function renameAlbum(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $validated = $request->validate([
            'old_name' => ['required', 'string', 'max:100'],
            'new_name' => ['required', 'string', 'max:100'],
        ]);

        $old = $validated['old_name'];
        $new = trim($validated['new_name']);

        // Guard against reserved tokens
        if (in_array($new, ['__all__', '__uncategorized__'], true)) {
            return back()->withErrors([
                'new_name' => 'This album name is reserved. Please choose another name.',
            ]);
        }

        // Update all photos in this album
        EventGallery::where('event_id', $event->id)
            ->where('album_name', $old)
            ->update(['album_name' => $new]);

        return redirect()
            ->route('events.manage.gallery', ['event' => $event, 'album' => $new])
            ->with('status', 'Album renamed successfully.');
    }

    /**
     * POST /events/manage/{event}/gallery/bulk-transfer
     * Move selected photos to another album.
     */
    public function bulkTransfer(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $validated = $request->validate([
            'ids'          => ['required', 'array'],
            'ids.*'        => ['integer'],
            'target_album' => ['required', 'string'], // token or real name
            'album'        => ['nullable', 'string'], // current album token (for redirect fallback)
        ]);

        $ids          = $validated['ids'];
        $targetToken  = $validated['target_album'];
        $currentAlbum = $validated['album'] ?? null;

        // Map token -> stored album_name
        if ($targetToken === '__uncategorized__') {
            $newName = null;
        } else {
            $newName = $targetToken;
        }

        $photos = EventGallery::where('event_id', $event->id)
            ->whereIn('id', $ids)
            ->get();

        $movedCount = 0;

        foreach ($photos as $photo) {
            $photo->update(['album_name' => $newName]);
            $movedCount++;
        }

        // Decide which album to redirect to: the target album
        $redirectParams = ['event' => $event];

        if ($targetToken === '__uncategorized__') {
            $redirectParams['album'] = '__uncategorized__';
        } elseif ($targetToken === '__all__') {
            $redirectParams['album'] = '__all__';
        } else {
            $redirectParams['album'] = $targetToken;
        }

        $status = $movedCount > 0
            ? "Moved {$movedCount} photo".($movedCount === 1 ? '' : 's')." to the selected album."
            : 'No photos were moved.';

        return redirect()
            ->route('events.manage.gallery', $redirectParams)
            ->with('status', $status);
    }
}
