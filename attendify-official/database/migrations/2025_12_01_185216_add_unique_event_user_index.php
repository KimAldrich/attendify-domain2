<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_user_roles', function (Blueprint $table) {
            // Prevent the same user having multiple rows per event
            $table->unique(
                ['event_id', 'user_id'],
                'event_user_roles_event_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('event_user_roles', function (Blueprint $table) {
            $table->dropUnique('event_user_roles_event_user_unique');
        });
    }
};
