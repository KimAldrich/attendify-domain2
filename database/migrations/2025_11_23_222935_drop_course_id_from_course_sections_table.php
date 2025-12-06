<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            // If the column is a foreignId, this helper will drop the FK + column
            if (Schema::hasColumn('course_sections', 'course_id')) {
                $table->dropConstrainedForeignId('course_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            // Recreate it if you ever rollback (adjust as needed)
            $table->foreignId('course_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
        });
    }
};
