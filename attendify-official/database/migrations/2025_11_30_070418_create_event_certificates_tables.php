<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // a) event_certificate_settings
        Schema::create('event_certificate_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('background_path')->nullable();   // custom bg, nullable
            $table->string('main_title');                    // e.g. "Certificate of Participation"
            $table->longText('body_text')->nullable();       // template/body text
            $table->string('date_label')->nullable();        // e.g. "Given this 5th day of…"

            $table->json('sponsor_logos_json')->nullable();  // list of logo paths
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // b) event_certificates
        Schema::create('event_certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('registration_id')
                ->constrained('event_registrations')
                ->cascadeOnDelete();

            $table->foreignId('certificate_settings_id')
                ->constrained('event_certificate_settings')
                ->cascadeOnDelete();

            $table->string('verification_code')->unique();   // for QR verification
            $table->string('file_path')->nullable();         // rendered PDF path
            $table->dateTime('issued_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop child first because it references settings
        Schema::dropIfExists('event_certificates');
        Schema::dropIfExists('event_certificate_settings');
    }
};
