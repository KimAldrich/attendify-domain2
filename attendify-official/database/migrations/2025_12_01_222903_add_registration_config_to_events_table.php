<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {

            $table->boolean('auto_approve_registrations')
                  ->default(false)
                  ->after('requires_payment_proof');

            $table->boolean('enable_waitlist')
                  ->default(false)
                  ->after('auto_approve_registrations');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'auto_approve_registrations',
                'enable_waitlist',
            ]);
        });
    }
};
