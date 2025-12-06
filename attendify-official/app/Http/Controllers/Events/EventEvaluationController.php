<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventEvaluationController extends Controller
{
    /**
     * GET /events/{event:slug}/evaluation
     * For now, redirect back to the event page.
     */
    public function show(Event $event)
    {
        // Later: show a dedicated evaluation form view.
        return redirect()->route('events.show', $event)
            ->with('status', 'Evaluation page not implemented yet.');
    }

    /**
     * POST /events/{event:slug}/evaluation
     * Handle evaluation submission.
     */
    public function submit(Request $request, Event $event)
    {
        // TODO: validate and store evaluation responses.
        return redirect()->route('events.show', $event)
            ->with('status', 'Evaluation submission not implemented yet.');
    }
}
