<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleUpgradeRequest;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleApplicationsController extends Controller
{
    /**
     * List + filter role upgrade requests.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');   // all|pending|accepted|declined
        $type   = $request->input('type', 'all');         // all|student|faculty
        $search = trim($request->input('search', ''));
        $from   = $request->input('from');                // YYYY-MM-DD
        $to     = $request->input('to');    
        
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [10, 20, 25, 50, 100], true)) {
            $perPage = 20;
        }

        $query = RoleUpgradeRequest::query()
            ->with('user')
            ->latest();

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Request type filter
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Search by name or user email
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Date range
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $requests = $query->paginate($perPage)->withQueryString();

        return view('admin.role-applications.index', compact(
            'requests', 'status', 'type', 'search', 'from', 'to', 'perPage'
        ));
    }

    /**
     * Approve a request:
     * - mark request accepted
     * - sync Spatie role
     * - set users.info_status = 0 (incomplete)
     */
    public function approve(RoleUpgradeRequest $roleRequest)
    {
        if ($roleRequest->status !== 'pending') {
            return back()->with('status', 'This request has already been processed.');
        }

        /** @var User|null $user */
        $user = $roleRequest->user;

        if (! $user) {
            return back()->with('status', 'No user is linked to this request.');
        }

        $role = $roleRequest->type; // student|faculty

        if (! in_array($role, ['student', 'faculty'], true)) {
            return back()->with('status', 'Invalid requested role type.');
        }

        try {
            DB::transaction(function () use ($roleRequest, $user, $role) {
                // 1) mark request as accepted
                $roleRequest->update([
                    'status'      => 'accepted',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

                // 2) apply role change locally (MySQL only)
                $this->applyRoleChange($user, $role);
            });

            Notification::create([
                'user_id' => $user->id,
                'title'   => 'Role Upgrade Approved',
                'message' => sprintf(
                    'Your request to become a %s has been approved.',
                    ucfirst($role)
                ),
                // link user directly to their profile page or account settings
                'link'    => route('profile.me'),
                'status'  => 'unread',
            ]);

            return back()->with('status', 'Request accepted and user role updated.');
        } catch (\Throwable $e) {
            Log::error('[RoleApplicationsController] approve failed', [
                'request_id' => $roleRequest->id,
                'user_id'    => $user->id ?? null,
                'role'       => $role,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('status', 'Something went wrong while updating the role.');
        }
    }

    /**
     * Reject a request (no role changes).
     */
    public function reject(RoleUpgradeRequest $roleRequest)
    {
        if ($roleRequest->status !== 'pending') {
            return back()->with('status', 'This request has already been processed.');
        }

        $roleRequest->update([
            'status'      => 'declined',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($roleRequest->user) {
            Notification::create([
                'user_id' => $roleRequest->user->id,
                'title'   => 'Role Upgrade Request Declined',
                'message' => sprintf(
                    'Your request to upgrade to %s was declined.',
                    ucfirst($roleRequest->type)
                ),
                'link'    => route('profile.me'),
                'status'  => 'unread',
            ]);
        }

        return back()->with('status', 'Request has been declined.');
    }

    /**
     * Pure-MySQL copy of your role update logic:
     * - Spatie role sync
     * - users.info_status = 0
     */
    private function applyRoleChange(User $user, string $role): void
    {
        Log::info('[RoleApplicationsController] applyRoleChange', [
            'user_id' => $user->id,
            'role'    => $role,
        ]);

        if (! in_array($role, ['guest', 'student', 'faculty'], true)) {
            Log::warning('[RoleApplicationsController] invalid role', ['role' => $role]);
            return;
        }

        // Ensure role exists (throws if not)
        SpatieRole::findByName($role, 'web');

        // Sync Spatie roles
        $user->syncRoles([$role]);

        // Mark info as incomplete so they’re forced to re-fill profile
        $user->info_status = 0;    // or false; depends on cast
        $user->save();
    }
}
