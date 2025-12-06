<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ── Events / schedules ─────────────────────────────────────────────
            'view events',          // view event listings & details (for all roles)
            'manage events',        // create/manage events (owners, event admins)
            'co-organize events',   // eligible to be assigned as co-organizer for specific events
            'staff events',         // eligible to be event staff (attendance scanning, limited metrics)

            // ── Attendance (classroom + events integration) ───────────────────
            'manage attendance',    // for faculty and admin (classroom mgmt)
            'view attendance',      // for students and admin
            'open scanner',         // existing permission (keep for backwards compatibility)

            // ── Admin (system-level) ───────────────────────────────────────────
            'manage users',
            'manage system',
            'review role applications',
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate([
                'name'       => $p,
                'guard_name' => 'web',
            ]);
        }

        // ── Roles ─────────────────────────────────────────────────────────────
        $roles = [
            'guest',
            'student',
            'faculty',
            'admin',
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate([
                'name'       => $r,
                'guard_name' => 'web',
            ]);
        }

        // ── Map permissions to roles ──────────────────────────────────────────

        // Guests (external accounts)
        Role::findByName('guest', 'web')->syncPermissions([
            'view events',
        ]);

        // Students
        // - Can view events (public & own).
        // - Can view classroom attendance.
        // - Can serve as event staff (scanner/metrics) via 'staff events'.
        // - Co-organizer capability (co-organize events) will be given
        //   individually to selected students, NOT to all.
        Role::findByName('student', 'web')->syncPermissions([
            'view events',
            'view attendance',
            'staff events',
        ]);

        // Faculty
        // - Can create/manage events.
        // - Can be co-organizers and staff for events.
        // - Have full attendance + scanner capabilities.
        Role::findByName('faculty', 'web')->syncPermissions([
            'view events',
            'manage events',
            'co-organize events',
            'staff events',

            'view attendance',
            'manage attendance',
            'open scanner',
        ]);

        // Admin – full permissions
        Role::findByName('admin', 'web')->syncPermissions($permissions);

        // ── Ensure primary admin user has admin role ──────────────────────────
        $user = User::where('email', 'psurdattendify@gmail.com')->first();
        if ($user) {
            $user->syncRoles(['admin']);
        }
    }
}
