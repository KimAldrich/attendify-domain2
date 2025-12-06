<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excuse_letters', function (Blueprint $table) {
            $table->id();

            // Which section and student this excuse belongs to
            $table->foreignId('course_section_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Which meeting date the excuse refers to
            $table->date('meeting_date');

            // R2 path of the uploaded excuse letter file
            $table->string('file_path');

            // Optional metadata
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();

            // Optional notes / reason text
            $table->text('notes')->nullable();

            // For future workflows: submitted / reviewed / approved / rejected
            $table->string('status')->default('submitted');

            $table->timestamps();

            $table->index(['course_section_id', 'student_id', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excuse_letters');
    }
};