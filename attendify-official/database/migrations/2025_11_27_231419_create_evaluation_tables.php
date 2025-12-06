<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) evaluation_questions
        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->string('label');                // question text
            $table->string('scope_type');           // "event", "activity", "both"
            $table->string('scale_type');           // "likert_1_5", "text"
            $table->string('category')->nullable(); // e.g. "content", "logistics"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2) evaluation_forms
        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('event_activities')
                ->nullOnDelete();

            $table->string('name');        // "Overall Event Evaluation", "Activity Feedback"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // 3) evaluation_form_questions (pivot)
        Schema::create('evaluation_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')
                ->constrained('evaluation_forms')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained('evaluation_questions')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('order_index')->default(1);
            $table->boolean('is_required')->default(true);

            $table->timestamps();
        });

        // 4) evaluation_responses
        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('registration_id')
                ->constrained('event_registrations')
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('event_activities')
                ->nullOnDelete();

            $table->foreignId('form_id')
                ->constrained('evaluation_forms')
                ->cascadeOnDelete();

            $table->dateTime('submitted_at');

            $table->timestamps();
        });

        // 5) evaluation_answers
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('response_id')
                ->constrained('evaluation_responses')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained('evaluation_questions')
                ->cascadeOnDelete();

            // For Likert 1–5 questions
            $table->tinyInteger('numeric_answer')->nullable();

            // For open-ended questions
            $table->text('text_answer')->nullable();

            $table->timestamps();
        });
        Schema::create('evaluation_summary_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('event_activities')
                ->nullOnDelete();

            $table->foreignId('question_id')
                ->constrained('evaluation_questions')
                ->cascadeOnDelete();

            // average Likert score, typically between 1.00 and 5.00
            $table->decimal('average_score', 4, 2)->nullable();

            // how many responses contributed to this average
            $table->unsignedInteger('response_count')->default(0);

            $table->dateTime('last_calculated_at')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'activity_id', 'question_id'], 'eval_summary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_summary_stats');
        Schema::dropIfExists('evaluation_answers');
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_form_questions');
        Schema::dropIfExists('evaluation_forms');
        Schema::dropIfExists('evaluation_questions');
    }
};
