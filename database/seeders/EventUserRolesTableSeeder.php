<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventUserRolesTableSeeder extends Seeder
{
    public function run(): void
    {
        // These are NOT inserted into the DB.
        // This seeder simply documents the "allowed" values.
        // EventUserRole uses a string column `role` and we validate this elsewhere.

        // If you want to enforce only these values:
        // You may create a config file listing the allowed roles.

        DB::table('event_role_types')->insertOrIgnore([
            ['name' => 'owner'],
            ['name' => 'co_organizer'],
            ['name' => 'staff'],
        ]);
    }
}
