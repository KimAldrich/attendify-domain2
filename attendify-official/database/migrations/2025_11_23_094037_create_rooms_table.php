<?php

// database/migrations/2025_11_23_000010_create_rooms_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->string('room_number'); // "LAB-301" etc.
            $table->string('camera_endpoint')->nullable(); // IP / RTSP / URL / token
            $table->boolean('is_face_recognition_enabled')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

