<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UsersManager extends Component
{
    use WithPagination;

    public string $tab = 'verification';
    public string $q = '';

    // Tab 1
    public string $verif = '';
    public string $sort  = 'created_at';
    public string $dir   = 'desc';

    // Tab 2
    public string $status = '';
    public string $statusSort = 'name';
    public string $statusDir  = 'asc';

    // Tab 3
    public string $rolesSort = 'name';
    public string $rolesDir  = 'asc';

    // Tab 4
    public string $infoSort = 'name';
    public string $infoDir  = 'asc';

    public int $perPage = 25;

    public ?int $inspectUserId = null;

    /** Disabled flags for current session: [uid => bool] */
    public array $fbDisabled = [];

    protected $listeners = [
        'rowStatusChanged' => 'onRowStatusChanged', // child tells us one row changed
    ];

    #[On('rowStatusChanged')]
    public function onRowStatusChanged(string $uid, bool $disabled): void
    {
        $this->fbDisabled[$uid] = $disabled;
        Log::debug('[UsersManager] onRowStatusChanged', ['uid' => $uid, 'disabled' => $disabled]);
    }

    protected $queryString = [
        'tab'        => ['except' => 'verification'],
        'q'          => ['except' => ''],

        'verif'      => ['except' => ''],
        'sort'       => ['except' => 'created_at'],
        'dir'        => ['except' => 'desc'],

        'status'     => ['except' => ''],
        'statusSort' => ['except' => 'name'],
        'statusDir'  => ['except' => 'asc'],

        'rolesSort'  => ['except' => 'name'],
        'rolesDir'   => ['except' => 'asc'],

        'infoSort'   => ['except' => 'name'],
        'infoDir'    => ['except' => 'asc'],

        'perPage'    => ['except' => 25],
        'page'       => ['except' => 1],
    ];

    public function updating($name, $value)
    {
        if (preg_match('/^(q|verif|sort|dir|status|statusSort|statusDir|rolesSort|rolesDir|infoSort|infoDir|tab|perPage)$/', $name)) {
            Log::debug('[UsersManager] updating -> resetPage', ['prop' => $name, 'value' => $value]);
            $this->resetPage();
        }
    }

    public function setTab(string $tab): void
    {
        $from = $this->tab;
        $allowed = ['verification','status','roles','info'];
        $this->tab = in_array($tab, $allowed, true) ? $tab : 'verification';
        Log::info('[UsersManager] setTab', ['from' => $from, 'to' => $this->tab, 'q' => $this->q]);
    }

    public function resetVerificationFilters(): void
    {
        $this->verif = '';
        $this->sort  = 'created_at';
        $this->dir   = 'desc';
        $this->resetPage();
    }

    public function resetStatusFilters(): void
    {
        $this->status     = '';
        $this->statusSort = 'name';
        $this->statusDir  = 'asc';
        $this->resetPage();
    }

    public function resetRolesFilters(): void
    {
        $this->rolesSort = 'name';
        $this->rolesDir  = 'asc';
        $this->resetPage();
    }

    public function resetInfoFilters(): void
    {
        $this->infoSort = 'name';
        $this->infoDir  = 'asc';
        $this->resetPage();
    }

    public function render()
    {
        Log::debug('[UsersManager] render:start', ['tab' => $this->tab, 'page' => $this->page ?? null, 'perPage' => $this->perPage]);
        $t0 = microtime(true);

        if ($this->tab === 'verification') {
            $users = $this->queryVerification();
        } elseif ($this->tab === 'status') {
            $users = $this->queryStatus();
            //$this->loadDisabledFlags($users);
        } elseif ($this->tab === 'roles') {
            $users = $this->queryRoles();
        } else { // info
            $users = $this->queryInfo();
        }

        $tz = config('app.timezone');

        Log::debug('[UsersManager] render:end', [
            'tab' => $this->tab,
            'rows' => $users?->count() ?? 0,
            'time_ms' => (int) ((microtime(true) - $t0) * 1000),
        ]);

        return view('livewire.admin.users-manager', compact('users', 'tz'));
    }

    public function updatedPerPage($value): void
    {
        $allowed = [10, 25, 50, 100];
        $v = (int) $value;
        if (!in_array($v, $allowed, true)) {
            $this->perPage = 25;
        }
        $this->resetPage();
    }

    /* -------------------- Queries -------------------- */

    protected function baseQuery()
    {
        Log::debug('[UsersManager] baseQuery');
        return User::query()
            ->select([
                'id','firebase_uid','first_name','last_name',
                'address', 'name','cp_no','email',
                'email_verified_at','created_at'
            ])
            ->with('roles:id,name')
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'));
    }

    protected function applySearch($query)
    {
        if ($this->q === '') return;
        Log::debug('[UsersManager] applySearch', ['q' => $this->q]);
        $like = '%'.mb_strtolower($this->q).'%';
        $query->where(function ($w) use ($like) {
            $w->whereRaw('LOWER(email) LIKE ?', [$like])
            ->orWhereRaw('LOWER(first_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(last_name)  LIKE ?', [$like])
            ->orWhereRaw('LOWER(name)       LIKE ?', [$like])
            ->orWhereRaw('LOWER(address)    LIKE ?', [$like]); // ← NEW
        });
    }

    protected function queryVerification()
    {
        $t0 = microtime(true);
        Log::info('[UsersManager] queryVerification:start', [
            'verif' => $this->verif, 'sort' => $this->sort, 'dir' => $this->dir, 'perPage' => $this->perPage
        ]);

        $q = $this->baseQuery();
        $this->applySearch($q);

        if ($this->verif === 'verified')   $q->whereNotNull('email_verified_at');
        if ($this->verif === 'unverified') $q->whereNull('email_verified_at');

        $sort = in_array($this->sort, ['created_at','email','name','email_verified_at'], true) ? $this->sort : 'created_at';
        $dir  = $this->dir === 'asc' ? 'asc' : 'desc';

        $sort === 'name'
            ? $q->orderBy('first_name', $dir)->orderBy('last_name', $dir)
            : $q->orderBy($sort, $dir);

        $page = $q->paginate($this->perPage);

        Log::info('[UsersManager] queryVerification:end', [
            'rows' => $page->count(),
            'time_ms' => (int) ((microtime(true) - $t0) * 1000),
        ]);

        return $page;
    }

    protected function queryStatus()
    {
        $t0 = microtime(true);
        Log::info('[UsersManager] queryStatus:start', [
            'status' => $this->status, 'statusSort' => $this->statusSort, 'statusDir' => $this->statusDir, 'perPage' => $this->perPage
        ]);

        $q = $this->baseQuery();
        $this->applySearch($q);

        $sort = in_array($this->statusSort, ['name','email','created_at'], true) ? $this->statusSort : 'name';
        $dir  = $this->statusDir === 'desc' ? 'desc' : 'asc';

        $sort === 'name'
            ? $q->orderBy('first_name', $dir)->orderBy('last_name', $dir)
            : $q->orderBy($sort, $dir);

        $page = $q->paginate($this->perPage);

        $this->loadDisabledFlags($page);

        Log::info('[UsersManager] queryStatus:end', [
            'rows' => $page->count(),
            'time_ms' => (int) ((microtime(true) - $t0) * 1000),
        ]);

        return $page;
    }


    protected function queryRoles()
    {
        $t0 = microtime(true);
        Log::info('[UsersManager] queryRoles:start', [
            'rolesSort' => $this->rolesSort, 'rolesDir' => $this->rolesDir, 'perPage' => $this->perPage
        ]);

        $q = $this->baseQuery();
        $this->applySearch($q);

        // Your constraint: only show verified users on Roles tab
        $q->whereNotNull('email_verified_at');

        $sort = in_array($this->rolesSort, ['name','email','created_at'], true) ? $this->rolesSort : 'name';
        $dir  = $this->rolesDir === 'desc' ? 'desc' : 'asc';

        $sort === 'name'
            ? $q->orderBy('first_name', $dir)->orderBy('last_name', $dir)
            : $q->orderBy($sort, $dir);

        $page = $q->paginate($this->perPage);

        Log::info('[UsersManager] queryRoles:end', [
            'rows' => $page->count(),
            'time_ms' => (int) ((microtime(true) - $t0) * 1000),
        ]);

        return $page;
    }

    protected function queryInfo()
    {
        $t0 = microtime(true);
        Log::info('[UsersManager] queryInfo:start', [
            'infoSort' => $this->infoSort, 'infoDir' => $this->infoDir, 'perPage' => $this->perPage
        ]);

        $q = $this->baseQuery();
        $this->applySearch($q);

        $q->whereNotNull('email_verified_at');

        $sort = in_array($this->infoSort, ['name','email','created_at'], true) ? $this->infoSort : 'name';
        $dir  = $this->infoDir === 'desc' ? 'desc' : 'asc';

        $sort === 'name'
            ? $q->orderBy('first_name', $dir)->orderBy('last_name', $dir)
            : $q->orderBy($sort, $dir);

        $page = $q->paginate($this->perPage);

        Log::info('[UsersManager] queryInfo:end', [
            'rows' => $page->count(),
            'time_ms' => (int) ((microtime(true) - $t0) * 1000),
        ]);

        return $page;
    }

    /* -------------------- Server-side flag fetch (single pass) -------------------- */

    /**
     * Fill $fbDisabled[uid] for current page, cached briefly to avoid spam.
     * This runs once per render of the Status tab to avoid row-level duplication.
     */
    protected function loadDisabledFlags($paginatedUsers): void
    {
        $ttl = now()->addMinutes(3);

        Log::info('[UsersManager] loadDisabledFlags:start', ['page_rows' => $paginatedUsers->count()]);

        foreach ($paginatedUsers as $u) {
            $uid = $u->firebase_uid;
            if (!$uid) continue;

            if (array_key_exists($uid, $this->fbDisabled)) {
                Log::debug('[UsersManager] loadDisabledFlags in-memory', ['uid' => $uid, 'disabled' => $this->fbDisabled[$uid]]);
                continue;
            }

            $cacheKey = "fb_disabled_{$uid}";
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $this->fbDisabled[$uid] = (bool) $cached;
                Log::debug('[UsersManager] loadDisabledFlags cached', ['uid' => $uid, 'disabled' => $this->fbDisabled[$uid]]);
                continue;
            }

            try {
                Log::debug('[UsersManager] loadDisabledFlags fetch Firebase', ['uid' => $uid]);
                $user = app(FirebaseAuth::class)->getUser($uid);
                $disabled = (bool) $user->disabled;

                $this->fbDisabled[$uid] = $disabled;
                Cache::put($cacheKey, $disabled, $ttl);

                Log::debug('[UsersManager] loadDisabledFlags fetched', ['uid' => $uid, 'disabled' => $disabled]);
            } catch (\Throwable $e) {
                Log::warning('[UsersManager] loadDisabledFlags error', ['uid' => $uid, 'e' => $e->getMessage()]);
                // Best-effort default keeps UI interactive
                $this->fbDisabled[$uid] = false;
            }
        }

        // Optional filter by enabled/disabled AFTER we filled flags
        if ($this->status !== '') {
            $wantDisabled = $this->status === 'disabled';

            $filtered = $paginatedUsers->getCollection()->filter(function ($u) use ($wantDisabled) {
                $uid = $u->firebase_uid;
                $isDisabled = $uid ? ($this->fbDisabled[$uid] ?? false) : false;
                return $wantDisabled ? $isDisabled : !$isDisabled;
            })->values();

            $paginatedUsers->setCollection($filtered);

            if ($filtered->isEmpty() && method_exists($paginatedUsers, 'currentPage') && $paginatedUsers->currentPage() > 1) {
                $this->previousPage('page'); // WithPagination helper
            }
        }

        Log::info('[UsersManager] loadDisabledFlags:end', ['known_flags' => count($this->fbDisabled)]);
    }

    /* -------------------- Firebase flags (async; optional fallback) -------------------- */

    /**
     * Called from the blade via x-init with the current page UIDs.
     * If you keep this, DO NOT also call it on first render (to avoid duplication),
     * since loadDisabledFlags() already pre-fills the map.
     */
    public function prefetchFlags(array $uids): void
    {
        if (empty($uids)) return;

        // Circuit breaker: if we recently had a network failure, skip fetching.
        $offlineUntil = Cache::get('fb_offline_until');
        if ($offlineUntil && now()->lt($offlineUntil)) {
            Log::warning('[UsersManager] prefetchFlags: circuit breaker active, skipping', ['until' => $offlineUntil]);
            return;
        }

        Log::info('[UsersManager] prefetchFlags:start', ['uids' => $uids]);

        foreach ($uids as $uid) {
            if (!$uid) continue;

            // If we already have it in-memory, don't hit cache/network.
            if (array_key_exists($uid, $this->fbDisabled)) {
                Log::debug('[UsersManager] prefetchFlags in-memory', ['uid' => $uid, 'disabled' => $this->fbDisabled[$uid]]);
                continue;
            }

            // Try cache first
            $cached = Cache::get("fb_disabled_{$uid}");
            if ($cached !== null) {
                $this->fbDisabled[$uid] = (bool) $cached;
                Log::debug('[UsersManager] prefetchFlags cached', ['uid' => $uid, 'disabled' => $this->fbDisabled[$uid]]);
                continue;
            }

            // Fallback to network
            try {
                Log::debug('[UsersManager] prefetchFlags fetch Firebase', ['uid' => $uid]);
                $user = app(FirebaseAuth::class)->getUser($uid);
                $disabled = (bool) $user->disabled;

                $this->fbDisabled[$uid] = $disabled;
                Cache::put("fb_disabled_{$uid}", $disabled, now()->addMinutes(3));

                Log::debug('[UsersManager] prefetchFlags fetched', ['uid' => $uid, 'disabled' => $disabled]);
            } catch (\Throwable $e) {
                Log::warning('[UsersManager] prefetchFlags error', ['uid' => $uid, 'e' => $e->getMessage()]);
                // Activate circuit breaker for 60s on first failure
                Cache::put('fb_offline_until', now()->addSeconds(60), now()->addMinutes(10));
                // Use best-effort default (assume enabled)
                $this->fbDisabled[$uid] = false;
                // Do not rethrow—keep UI responsive.
                break;
            }
        }

        Log::info('[UsersManager] prefetchFlags:end', ['known_flags' => count($this->fbDisabled)]);
    }

    /* -------------------- Row actions (unchanged) -------------------- */

    public function enableUser(int $id): void
    {
        $this->setUserDisabled($id, false);
        $this->dispatch('toast', type: 'success', message: 'User enabled successfully.');
    }

    public function disableUser(int $id): void
    {
        $this->setUserDisabled($id, true);
        $this->dispatch('toast', type: 'success', message: 'User disabled successfully.');
    }

    protected function setUserDisabled(int $id, bool $disabled): void
    {
        $user = User::find($id);

        if (!$user || !$user->firebase_uid) {
            Log::warning('[UsersManager] setUserDisabled: user or firebase_uid missing', [
                'id' => $id,
                'has_user' => (bool) $user,
                'firebase_uid' => $user->firebase_uid ?? null,
            ]);
            return;
        }

        if (session('firebase_offline')) {
            Log::warning('[UsersManager] setUserDisabled: firebase_offline flag set', [
                'id' => $id,
                'firebase_uid' => $user->firebase_uid,
                'disabled' => $disabled,
            ]);
            $this->dispatch('toast', type: 'error', message: 'Offline: change queued.');
            return;
        }

        try {
            Log::info('[UsersManager] setUserDisabled: updating Firebase', [
                'id' => $id,
                'firebase_uid' => $user->firebase_uid,
                'disabled' => $disabled,
            ]);

            app(FirebaseAuth::class)->updateUser($user->firebase_uid, ['disabled' => $disabled]);

            Cache::put("fb_disabled_{$user->firebase_uid}", $disabled, now()->addMinutes(3));
            $this->fbDisabled[$user->firebase_uid] = $disabled;

            Log::info('[UsersManager] setUserDisabled: success', [
                'id' => $id,
                'firebase_uid' => $user->firebase_uid,
                'disabled' => $disabled,
            ]);

            $this->dispatch('toast', type: 'success', message: $disabled ? 'User disabled' : 'User enabled');
        } catch (\Throwable $e) {
            Log::error('[UsersManager] setUserDisabled: exception', [
                'id' => $id,
                'firebase_uid' => $user->firebase_uid,
                'disabled' => $disabled,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', message: 'Unable to update status. You may be offline');
        }
    }

    public function inspect(int $userId): void
    {
        $this->inspectUserId = $userId;
    }

    public function backToList(): void
    {
        $this->inspectUserId = null;  
    }
}
