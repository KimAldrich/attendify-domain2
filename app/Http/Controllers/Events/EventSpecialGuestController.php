<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SpecialGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventSpecialGuestController extends Controller
{
    use AuthorizesRequests;

    public function index(Event $event)
    {
        $this->authorize('manage', $event);

        $event->load(['specialGuests']);

        return view('events.manage.guests', [
            'event'  => $event,
            'guests' => $event->specialGuests,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $this->authorize('manage', $event);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo'       => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store("special-guests/{$event->id}", 'r2');
        }

        $nextOrder = ($event->specialGuests()->max('order_index') ?? 0) + 1;

        $event->specialGuests()->create([
            'name'        => $data['name'],
            'title'       => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'photo_path'  => $photoPath,
            'order_index' => $nextOrder,
        ]);

        return back()->with('status', 'Special guest added.');
    }

    public function destroy(Event $event, SpecialGuest $guest)
    {
        $this->authorize('manage', $event);

        // Ensure guest belongs to the event
        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        // Optional: delete photo from R2
        if ($guest->photo_path) {
            Storage::disk('r2')->delete($guest->photo_path);
        }

        $guest->delete();

        return back()->with('status', 'Special guest removed.');
    }

public function update(Request $request, Event $event, SpecialGuest $guest)
{
    $this->authorize('manage', $event);

    $data = $request->validate([
        'name'        => ['required', 'string', 'max:255'],
        'title'       => ['nullable', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:2000'],
        'photo'       => ['nullable', 'image', 'max:5120'], // 5MB
    ]);

    if ($request->hasFile('photo')) {
        if ($guest->photo_path) {
            Storage::disk('r2')->delete($guest->photo_path);
        }

        $data['photo_path'] = $request->file('photo')->store("special-guests/{$event->id}", 'r2');
    }

    $guest->update($data);

    return redirect()
        ->route('events.manage.guests', $event)
        ->with('status', 'Special guest updated.');
}

}
