<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventPreviewController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/preview
     * Show an owner/co-organizer preview of the public event page.
     */
    public function show(Event $event)
    {
        // Per-event policy: admin OR owner OR co_organizer
        $this->authorize('manage', $event);

        // Eager-load bits that are likely needed for preview
        $event->load([
            'owner',
            'days' => fn ($q) => $q->orderBy('order_index'),
            'tracks' => fn ($q) => $q->orderBy('order_index'),
            'activities',
            'specialGuests' => fn ($q) => $q->orderBy('order_index')->orderBy('name'),
            'gallery' => fn ($q) => $q->orderBy('order_index')->orderBy('id'),
        ]);

        return view('events.manage.preview', compact('event'));
    }
}
