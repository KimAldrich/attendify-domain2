<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->index('created_at');
            $t->index('email_verified_at');
            $t->index('first_name');
            $t->index('last_name');
            // email and firebase_uid are likely already indexed/unique.
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex(['created_at']);
            $t->dropIndex(['email_verified_at']);
            $t->dropIndex(['first_name']);
            $t->dropIndex(['last_name']);
        });
    }
};
