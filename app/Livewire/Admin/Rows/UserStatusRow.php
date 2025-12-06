<?php

namespace App\Livewire\Admin\Rows;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use Livewire\Component;

class UserStatusRow extends Component
{
    public User $user;

    /** Provided by parent; NOT nullable in this version (keeps UI simple). */
    public bool $disabled = false;

    public function mount(User $user, bool $disabled = false): void
    {
        $this->user = $user;
        $this->disabled = $disabled;

        Log::info('[UserStatusRow] mount', [
            'user_id'      => $this->user->id,
            'firebase_uid' => $this->user->firebase_uid,
            'disabled'     => $this->disabled,
        ]);
    }

    public function render()
    {
        Log::debug('[UserStatusRow] render', [
            'user_id'  => $this->user->id,
            'disabled' => $this->disabled,
        ]);

        return view('livewire.admin.rows.user-status-row');
    }

    public function enable(): void
    {
        Log::info('[UserStatusRow] enable clicked', [
            'user_id'  => $this->user->id,
            'disabled' => $this->disabled,
        ]);
        $this->setDisabled(false);
    }

    public function disable(): void
    {
        Log::info('[UserStatusRow] disable clicked', [
            'user_id'  => $this->user->id,
            'disabled' => $this->disabled,
        ]);
        $this->setDisabled(true);
    }

    protected function setDisabled(bool $wantDisabled): void
    {
        Log::info('[UserStatusRow] setDisabled:start', [
            'user_id'      => $this->user->id,
            'uid'          => $this->user->firebase_uid,
            'current'      => $this->disabled,
            'wantDisabled' => $wantDisabled,
        ]);

        if (session('firebase_offline')) {
            Log::warning('[UserStatusRow] setDisabled aborted: offline flag set');
            $this->dispatch('toast', type: 'error', message: 'You are offline.');
            return;
        }

        if (!$this->user->firebase_uid) {
            Log::warning('[UserStatusRow] setDisabled aborted: no firebase_uid', ['user_id' => $this->user->id]);
            return;
        }

        // Optimistic UI
        $prev = $this->disabled;
        $this->disabled = $wantDisabled;

        try {
            Log::debug('[UserStatusRow] Firebase updateUser:start', [
                'uid'  => $this->user->firebase_uid,
                'args' => ['disabled' => $wantDisabled],
            ]);

            app(FirebaseAuth::class)->updateUser($this->user->firebase_uid, ['disabled' => $wantDisabled]);

            Cache::put("fb_disabled_{$this->user->firebase_uid}", $wantDisabled, now()->addMinutes(3));

            Log::info('[UserStatusRow] setDisabled success', [
                'uid'  => $this->user->firebase_uid,
                'prev' => $prev,
                'now'  => $this->disabled,
            ]);

            // Tell parent so its in-memory map stays in sync
            Log::debug('[UserStatusRow] dispatch rowStatusChanged', [
                'uid'      => $this->user->firebase_uid,
                'disabled' => $wantDisabled,
            ]);
            $this->dispatch('rowStatusChanged', uid: $this->user->firebase_uid, disabled: $wantDisabled);

            $this->dispatch(
                'toast',
                type: $wantDisabled ? 'error' : 'success',
                message: $wantDisabled ? 'User disabled' : 'User enabled'
            );

        } catch (\Throwable $e) {
            // Roll back optimistic state on error
            $this->disabled = $prev;

            Log::error('[UserStatusRow] setDisabled failed; rolled back', [
                'uid'   => $this->user->firebase_uid,
                'prev'  => $prev,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', message: 'You are offline. Unable to update status.');
        }
    }
}
