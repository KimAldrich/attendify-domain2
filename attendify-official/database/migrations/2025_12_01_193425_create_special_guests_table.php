<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_guests', function (Blueprint $table) {
            $table->id();

            // Each guest belongs to an event
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');              // Full name of the guest
            $table->string('title')->nullable(); // e.g. "Bachelor in Information Technology"
            $table->text('description')->nullable(); // Optional explanation / intro to the audience

            $table->string('photo_path')->nullable(); // R2 path (e.g. "special-guests/{event_id}/filename.jpg")

            // Optional: ordering on the page
            $table->unsignedSmallInteger('order_index')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_guests');
    }
};
