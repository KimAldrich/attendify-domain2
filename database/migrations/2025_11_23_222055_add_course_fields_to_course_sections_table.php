<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            // If you already have data you care about, set these nullable first.
            $table->string('course_code', 50)->after('academic_period_id');
            $table->string('course_name', 255)->after('course_code');

            // Optional: if you want to stop using course_id entirely,
            // you can make it nullable or drop the FK. Comment out if not needed.
            // $table->unsignedBigInteger('course_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropColumn(['course_code', 'course_name']);
        });
    }
};
