<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) attendance_sessions
        Schema::create('event_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('day_id')
                ->constrained('event_days')
                ->cascadeOnDelete();

            // e.g. "morning", "afternoon" (or "am", "pm")
            $table->string('period', 32);

            $table->string('label'); // "Day 1 – Morning Session"
            $table->dateTime('scheduled_start_time')->nullable();

            $table->timestamps();
        });

        // 2) attendance_records
        Schema::create('event_attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_attendance_session_id')
                ->constrained('event_attendance_sessions')
                ->cascadeOnDelete();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // Either link to registration or user directly (registration is preferred)
            $table->foreignId('registration_id')
                ->nullable()
                ->constrained('event_registrations')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // who scanned this attendee in
            $table->foreignId('scanned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('scanned_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance_records');
        Schema::dropIfExists('event_attendance_sessions');
    }
};
