<?php

// database/migrations/2025_11_23_000030_create_course_sections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('academic_period_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('section_label'); // "BSIT-3A"
            $table->unsignedInteger('enrollment_limit')->nullable();

            $table->boolean('is_archived')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
