<?php

// database/migrations/2025_11_23_000050_create_section_enrollments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('section_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_section_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['course_section_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_enrollments');
    }
};
