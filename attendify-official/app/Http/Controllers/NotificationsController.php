<?php

// app/Http/Controllers/NotificationsController.php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    /**
     * Show all notifications for the current user.
     * - Only status in [unread, read]
     * - Unread first, then read
     * - Newest first within each group
     */

    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->query('perPage', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $q       = trim($request->query('q', ''));
        $kind    = $request->query('kind', 'all'); // all|reports|non-reports
        $isAdmin = $user && $user->hasRole('admin');

        $query = Notification::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'deleted');

        // 🔎 Text search (admin only)
        if ($isAdmin && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        // 🎯 Type filter: reports vs others (admin only)
        if ($isAdmin && in_array($kind, ['reports', 'non-reports'], true)) {
            $reportLike = '%report%';

            if ($kind === 'reports') {
                $query->where('title', 'like', $reportLike);
            } else {
                $query->where('title', 'not like', $reportLike);
            }
        }

        // Unread first, then newest
        $query->orderByRaw("FIELD(status, 'unread', 'read') ASC")
            ->orderByDesc('created_at');

        $notifications = $query->paginate($perPage)->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'perPage'       => $perPage,
            'isAdmin'       => $isAdmin,
        ]);
    }


    /**
     * "Delete All Read" — mark all read notifications as deleted,
     * so they no longer appear in the listing.
     */
    public function deleteRead(Request $request)
    {
        $userId = Auth::id();

        Notification::where('user_id', $userId)
            ->where('status', 'read')
            ->update(['status' => 'deleted']);

        return redirect()
            ->route('notifications.index')
            ->with('status', 'All read notifications have been cleared.');
    }

    public function open(Notification $notification)
    {
        // Only allow the owner to open it
        abort_unless($notification->user_id === auth()->id(), 403);

        // Mark as read if unread
        if ($notification->status === 'unread') {
            $notification->update(['status' => 'read']);
        }

        // If no link, just return to notification list
        if (!$notification->link) {
            return redirect()->route('notifications.index');
        }

        // Redirect to the actual stored link
        return redirect()->to($notification->link);
    }

}
