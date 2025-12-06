<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCertificate;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventCertificateController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/certificates
     */
    public function index(Event $event)
    {
        $this->authorize('manage', $event);

        $event->load(['certificateSettings', 'certificates']);

        return view('events.manage.certificates', compact('event'));
    }
    public function updateSettings(Request $request, Event $event)
    {
        // TODO: update EventCertificateSetting for this event.
        return back()->with('status', 'Certificate settings update not implemented yet.');
    }

    public function generateForEvent(Event $event)
    {
        // TODO: generate certificates for eligible registrations.
        return back()->with('status', 'Certificate generation not implemented yet.');
    }

    public function reissue(Event $event, EventCertificate $certificate)
    {
        // TODO: reissue a specific certificate.
        return back()->with('status', 'Certificate reissue not implemented yet.');
    }
}
