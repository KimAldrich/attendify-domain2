<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        // Sticky-ish params (mirror Livewire)
        $q       = trim((string) $request->query('q', ''));
        $verif   = $request->query('verif'); // '', 'verified', 'unverified'
        $sort    = $request->query('sort', 'created_at'); // created_at|email_verified_at|name|email
        $dir     = $request->query('dir', 'desc');        // asc|desc
        $perPage = (int) $request->query('perPage', 25);  // 10|25|50|100

        // Clamp + whitelist
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }

        $allowedSorts = ['created_at', 'email_verified_at', 'name', 'email'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $dir = $dir === 'asc' ? 'asc' : 'desc';

        // Base query (mirror Livewire baseQuery: exclude admins)
        $query = User::query()
            ->select([
                'id','firebase_uid','first_name','last_name','name','cp_no','email',
                'email_verified_at','created_at'
            ])
            ->with('roles:id,name')
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'));

        // Search (case-insensitive across fields)
        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(email)      LIKE ?', [$like])
                  ->orWhereRaw('LOWER(first_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(last_name)  LIKE ?', [$like])
                  ->orWhereRaw('LOWER(name)       LIKE ?', [$like]);
            });
        }

        // Verification filter
        if ($verif === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verif === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        // Sorting
        if ($sort === 'name') {
            $query->orderBy('first_name', $dir)->orderBy('last_name', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        // Paginate with the same default as Livewire and keep querystring
        $users = $query->paginate($perPage)->withQueryString();

        // If you still render a partial for legacy AJAX, keep this,
        // otherwise the Livewire view uses the component.
        if ($request->ajax()) {
            return view('admin.user-management._table', compact('users'));
        }

        return view('admin.user-management.index', compact('users'));
    }
}
