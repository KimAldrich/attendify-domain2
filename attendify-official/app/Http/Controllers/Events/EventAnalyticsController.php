<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class EventAnalyticsController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/analytics
     */
    public function show(Event $event)
    {
        $this->authorize('manage', $event);

        // Later: $event->load(['evaluationSummaryStats', 'aiSummaries', ...]);
        return view('events.manage.analytics', compact('event'));
    }
    public function recalculate(Event $event)
    {
        // TODO: recalculate EvaluationSummaryStat records for this event.
        return back()->with('status', 'Analytics recalculation not implemented yet.');
    }

    public function regenerateAiSummary(Event $event)
    {
        // TODO: regenerate AI summaries for this event.
        return back()->with('status', 'AI summary regeneration not implemented yet.');
    }
}
