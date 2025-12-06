<?php
// database/migrations/2025_11_23_000000_create_academic_periods_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();

            $table->string('label'); // e.g. "AY 2024-2025 • 1st Semester"
            $table->smallInteger('year_start');
            $table->smallInteger('year_end');
            $table->string('term', 32); // "1st", "2nd", "Summer", etc.

            $table->boolean('is_current')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
