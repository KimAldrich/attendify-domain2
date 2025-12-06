<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_user_roles', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Event-level role for this user on this event
            // Allowed values: owner, co_organizer, staff
            $table->string('role', 50);

            $table->timestamps();

            // A user should have at most one event-level role per event
            $table->unique(['event_id', 'user_id'], 'unique_event_user_role');

            // Helpful indexes for queries
            $table->index(['event_id', 'role']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_user_roles');
    }
};
