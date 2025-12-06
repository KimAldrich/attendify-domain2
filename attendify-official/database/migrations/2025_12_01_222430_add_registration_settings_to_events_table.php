<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_registration_settings_to_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->longText('registration_instructions')->nullable()->after('capacity');
            $table->boolean('requires_payment_proof')->default(false)->after('registration_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['registration_instructions', 'requires_payment_proof']);
        });
    }
};

