<?php

// database/migrations/2025_11_23_000060_create_attendance_records_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_section_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('section_enrollment_id')
                ->nullable()
                ->constrained('section_enrollments')
                ->nullOnDelete();

            $table->date('meeting_date');

            $table->enum('status', ['present', 'absent', 'excused']);

            $table->time('time_in')->nullable(); // if present

            $table->foreignId('marked_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('source')->default('manual'); // "manual", "face_recog", etc.

            $table->timestamps();

            $table->index(['course_section_id', 'meeting_date']);
            $table->index(['student_id', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
