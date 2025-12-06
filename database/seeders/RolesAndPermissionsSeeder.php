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
            // events / schedules
            'view events', //for all roles
            'manage events', //for all roles except guests(yes, students with moderator status can also manage events), inside management

            // attendance
            'manage attendance', //for faculty and admin, inside management
            'view attendance', //for students and admin
            'open scanner', //for faculty and admin, inside management

            // admin (only for admin)
            'manage users',
            'manage system',
            'review role applications',
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2) Define roles
        $roles = [
            'guest',
            'student',
            'faculty',
            'admin',
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // 3) Map permissions to roles (adjust to your policy)
        Role::findByName('guest', 'web')->syncPermissions([
            'view events'
        ]);

        Role::findByName('student', 'web')->syncPermissions([
            'view events',
            'view attendance',
        ]);

        Role::findByName('faculty', 'web')->syncPermissions([
            'view events',
            'manage events',
            'manage attendance',
            'open scanner',
        ]);

        Role::findByName('admin', 'web')->syncPermissions($permissions); 

        $user = User::where('email', 'psurdattendify@gmail.com')->first();
        if ($user) {
            $user->syncRoles(['admin']);
        }
    }
}
