<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventUserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventPeopleController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /events/manage/{event}/people
     */
public function index(Event $event)
{
    $this->authorize('manage', $event);

    $event->load(['userRoles.user']);

    $existingUserIds = $event->userRoles->pluck('user_id');

    $roleCandidates = User::role(['student', 'faculty'])
        ->whereNotIn('id', $existingUserIds)   // <- NEW
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $roleCandidateOptions = $roleCandidates->map(function ($user) {
        return [
            'id'    => $user->id,
            'name'  => $user->full_name,
            'email' => $user->email,
        ];
    });

    return view('events.manage.people', [
        'event'               => $event,
        'roleCandidateOptions'=> $roleCandidateOptions,
    ]);
}

    /**
     * POST /events/manage/{event}/people/roles
     * Batch-assign co-organizers and staff via arrays.
     */

public function storeRole(Request $request, Event $event)
{
    $this->authorize('manage', $event);

    $data = $request->validate([
        'co_organizers'   => ['array'],
        'co_organizers.*' => ['integer', 'exists:users,id'],
        'staff'           => ['array'],
        'staff.*'         => ['integer', 'exists:users,id'],
    ]);

    $coIds    = collect($data['co_organizers'] ?? [])->unique();
    $staffIds = collect($data['staff'] ?? [])->unique();

    // If user is in both, treat them as co-organizer (higher rank)
    $staffIds = $staffIds->reject(fn ($id) => $coIds->contains($id));

    $rank = [
        'staff'        => 1,
        'co_organizer' => 2,
        'owner'        => 3,
    ];

    // Handle co-organizers (promote if needed)
    foreach ($coIds as $userId) {
        $existing = EventUserRole::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->first();

        if (! $existing) {
            EventUserRole::create([
                'event_id' => $event->id,
                'user_id'  => $userId,
                'role'     => 'co_organizer',
            ]);
            continue;
        }

        if ($rank['co_organizer'] > ($rank[$existing->role] ?? 0)) {
            $existing->update(['role' => 'co_organizer']);
        }
    }

    // Handle staff (only if there is no existing row at all)
    foreach ($staffIds as $userId) {
        $existing = EventUserRole::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->first();

        if (! $existing) {
            EventUserRole::create([
                'event_id' => $event->id,
                'user_id'  => $userId,
                'role'     => 'staff',
            ]);
        }
    }

    return back()->with('status', 'Event roles updated.');
}



    /**
     * DELETE /events/manage/{event}/people/roles/{role}
     */
    public function destroyRole(Event $event, EventUserRole $role)
    {
        $this->authorize('manage', $event);

        // Ensure the role belongs to this event
        if ($role->event_id !== $event->id) {
            abort(404);
        }

        // Do not remove owner here
        if ($role->role === 'owner') {
            return back()->with('status', 'You cannot remove the event owner from here.');
        }

        $name = optional($role->user)->full_name ?? 'User';

        $role->delete();

        return back()->with('status', "{$name} has been removed from this event.");
    }
}
