<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_photo_attendances', function (Blueprint $table) {
            $table->id();

            // Which section this photo is associated with
            $table->foreignId('course_section_id')
                ->constrained()
                ->cascadeOnDelete();

            // Which date this photo represents (class meeting date)
            $table->date('meeting_date');

            // Which user uploaded it (usually the instructor)
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // R2 path to the stored image
            $table->string('photo_path');

            $table->timestamps();

            // Helpful index when querying per section/day
            $table->index(['course_section_id', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_photo_attendances');
    }
};