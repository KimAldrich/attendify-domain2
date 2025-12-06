<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // If you're on MySQL with ENUM
        DB::statement("
            ALTER TABLE attendance_records
            MODIFY COLUMN status ENUM('present','absent','excused','tardy') NOT NULL
        ");
    }

    public function down(): void
    {
        // Rollback to original enum
        DB::statement("
            ALTER TABLE attendance_records
            MODIFY COLUMN status ENUM('present','absent','excused') NOT NULL
        ");
    }
};
