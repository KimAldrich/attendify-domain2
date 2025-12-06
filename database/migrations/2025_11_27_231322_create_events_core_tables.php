<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) events
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();

            $table->string('hero_image_path')->nullable();
            $table->string('banner_image_path')->nullable();

            $table->string('event_type')->nullable(); // seminar, workshop, etc.
            $table->string('mode')->nullable(); // onsite / online / hybrid

            $table->string('visibility')->default('public'); // public / campus_only / department_only / role_restricted
            $table->json('target_audience_json')->nullable(); // roles, campuses, programs, etc.

            $table->unsignedInteger('capacity')->nullable();

            $table->dateTime('reg_open_at')->nullable();
            $table->dateTime('reg_close_at')->nullable();

            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            $table->string('status')->default('draft'); // draft/published/ongoing/finished/archived/cancelled

            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        // 2) event_days
        Schema::create('event_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('order_index')->default(1); // display order of days
            $table->timestamps();
        });

        // 3) event_tracks
        Schema::create('event_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->string('name'); // "Main Hall", "Breakout Room A", etc.
            $table->string('location')->nullable(); // optional extra details
            $table->unsignedSmallInteger('order_index')->default(1); // display order of tracks
            $table->timestamps();
        });

        // 4) event_activities
        Schema::create('event_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->foreignId('day_id')
                ->constrained('event_days')
                ->cascadeOnDelete();
            $table->foreignId('track_id')
                ->nullable()
                ->constrained('event_tracks')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('type')->nullable(); // talk, panel, break, etc.
            $table->boolean('needs_analytics')->default(false);

            $table->unsignedSmallInteger('order_index')->default(1); // order within day/track

            $table->timestamps();
        });

        // 5) event_speakers
        Schema::create('event_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('title')->nullable(); // e.g. "Dean, College of Computing"
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();

            $table->unsignedSmallInteger('order_index')->default(1); // order in speakers list

            $table->timestamps();
        });

        // 6) event_registrations
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // null for no-account visitors
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // student / faculty / guest / no_account
            $table->string('attendee_type');

            // pending / approved / rejected / waitlisted
            $table->string('status')->default('pending');

            // optional proof of payment
            $table->string('proof_of_payment_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('event_speakers');
        Schema::dropIfExists('event_activities');
        Schema::dropIfExists('event_tracks');
        Schema::dropIfExists('event_days');
        Schema::dropIfExists('events');
    }
};
