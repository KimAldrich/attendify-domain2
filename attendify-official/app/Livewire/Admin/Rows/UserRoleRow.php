<?php

namespace App\Livewire\Admin\Rows;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserRoleRow extends Component
{
    public User $user;
    public string $currentRole = 'guest'; // guest|student|faculty

    public function mount(User $user, ?string $currentRole = null): void
    {
        $this->user = $user;
        $initial = $currentRole ?? ($user->roles->first()->name ?? 'guest');
        $this->currentRole = $initial;

        Log::info('[UserRoleRow] mount', [
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'initial_role' => $initial,
            'from_parent'  => $currentRole !== null,
        ]);
    }

    public function setRole(string $role): void
    {
        Log::info('[UserRoleRow] setRole:request', [
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'currentRole'  => $this->currentRole,
            'requested'    => $role,
        ]);

        if (!in_array($role, ['guest','student','faculty'], true)) {
            Log::warning('[UserRoleRow] setRole aborted: invalid role', ['requested' => $role]);
            return;
        }

        if ($this->currentRole === $role) {
            Log::debug('[UserRoleRow] setRole no-op: role unchanged', ['role' => $role]);
            return;
        }

        // NOTE: since role changes are now DB-only (Spatie), we don't need to
        // block them when Firebase is offline. That flag is only relevant to
        // Firebase Auth operations (enable/disable).
        // If you still WANT to block when offline, you can keep this block.
        /*
        if (session('firebase_offline')) {
            Log::warning('[UserRoleRow] setRole aborted: offline flag set');
            $this->dispatch('toast', type: 'error', message: 'You are offline.');
            return;
        }
        */

        try {
            // 1) Spatie role sync (MySQL)
            Log::debug('[UserRoleRow] setRole Spatie sync:start', ['role' => $role]);
            Role::findByName($role, 'web'); // throws if missing
            $this->user->syncRoles([$role]);
            Log::info('[UserRoleRow] setRole Spatie sync:ok', ['role' => $role]);
            $this->user->update([
                'info_status' => false,
            ]);
            Log::info('[UserRoleRow] setRole info_status reset (MySQL only)', [
                'user_id' => $this->user->id,
            ]);
            // 2) MySQL-only now: Firestore mirror removed
            Log::debug('[UserRoleRow] setRole Firestore mirror skipped (MySQL-only mode)', [
                'user_id' => $this->user->id,
            ]);

            // 3) Local state (flip highlight immediately)
            $prev = $this->currentRole;
            $this->currentRole = $role;

            Log::info('[UserRoleRow] setRole local state updated', [
                'user_id' => $this->user->id,
                'prev'    => $prev,
                'now'     => $this->currentRole,
            ]);

            $this->dispatch('toast', type: 'success', message: 'Role updated successfully.');
        } catch (\Throwable $e) {
            Log::error('[UserRoleRow] setRole failed', [
                'user_id' => $this->user->id,
                'role'    => $role,
                'error'   => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Unable to update role. Please try again.');
        }
    }

    public function render()
    {
        Log::debug('[UserRoleRow] render', [
            'user_id'     => $this->user->id,
            'currentRole' => $this->currentRole,
        ]);
        return view('livewire.admin.rows.user-role-row');
    }
}
