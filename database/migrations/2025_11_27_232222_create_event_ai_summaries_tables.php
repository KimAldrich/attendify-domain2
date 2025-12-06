<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_ai_summaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // If you later want per-activity AI summaries, you can use this:
            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('event_activities')
                ->nullOnDelete();

            // Main narrative summary for the event (or activity)
            $table->longText('summary_overall')->nullable();

            // Optional focused sections
            $table->longText('summary_strengths')->nullable();
            $table->longText('summary_weaknesses')->nullable();
            $table->longText('summary_recommendations')->nullable();

            // Optional metadata for model/version info
            $table->json('metadata')->nullable();

            $table->dateTime('last_generated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_ai_summaries');
    }
};
