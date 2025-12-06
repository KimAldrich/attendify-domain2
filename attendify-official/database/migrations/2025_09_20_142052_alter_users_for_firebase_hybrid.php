<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make password nullable (since Firebase owns credentials)
            $table->string('password')->nullable()->change();

            // Add firebase_uid for cross-link (unique, nullable for existing rows)
            if (!Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->unique()->after('id');
            }

            // Optional: extra profile fields if you’re using them in views
            if (!Schema::hasColumn('users', 'first_name')) $table->string('first_name')->nullable()->after('name');
            if (!Schema::hasColumn('users', 'last_name'))  $table->string('last_name')->nullable()->after('first_name');
            if (!Schema::hasColumn('users', 'middle_name'))$table->string('middle_name')->nullable()->after('last_name');
            if (!Schema::hasColumn('users', 'cp_no'))      $table->string('cp_no', 30)->nullable()->after('middle_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // revert only if needed; leaving as-is is fine for a capstone
            // $table->dropColumn(['firebase_uid','first_name','last_name','middle_name','cp_no']);
            // $table->string('password')->nullable(false)->change();
        });
    }
};
