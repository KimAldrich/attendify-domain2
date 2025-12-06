<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Already present in your DB: slug, firebase_uid, first_name, middle_name, last_name, cp_no, address, photo_path, etc.
            // We will add only the fields not yet in your table.

            // Address parts (you currently only have `address`)
            if (!Schema::hasColumn('users', 'address_house')) {
                $table->string('address_house')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'address_brgy')) {
                $table->string('address_brgy')->nullable()->after('address_house');
            }
            if (!Schema::hasColumn('users', 'address_city')) {
                $table->string('address_city')->nullable()->after('address_brgy');
            }
            if (!Schema::hasColumn('users', 'address_province')) {
                $table->string('address_province')->nullable()->after('address_city');
            }

            // Social links + bio + completeness flag
            if (!Schema::hasColumn('users', 'link_facebook')) {
                $table->string('link_facebook', 2048)->nullable()->after('address_province');
            }
            if (!Schema::hasColumn('users', 'link_linkedin')) {
                $table->string('link_linkedin', 2048)->nullable()->after('link_facebook');
            }
            if (!Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 150)->nullable()->after('link_linkedin');
            }
            if (!Schema::hasColumn('users', 'info_status')) {
                $table->boolean('info_status')->default(false)->after('bio');
            }

            // Guest
            if (!Schema::hasColumn('users', 'organization')) {
                $table->string('organization')->nullable()->after('info_status');
            }

            // Student
            if (!Schema::hasColumn('users', 'student_number')) {
                $table->string('student_number', 50)->nullable()->unique()->after('organization');
            }
            if (!Schema::hasColumn('users', 'year_level')) {
                // use ENUM to match your validation; change to string if you prefer portability
                $table->enum('year_level', ['1st','2nd','3rd','4th','5th+'])->nullable()->after('student_number');
            }
            if (!Schema::hasColumn('users', 'student_department_id')) {
                $table->unsignedBigInteger('student_department_id')->nullable()->after('year_level');
            }
            if (!Schema::hasColumn('users', 'student_campus_id')) {
                $table->unsignedBigInteger('student_campus_id')->nullable()->after('student_department_id');
            }
            if (!Schema::hasColumn('users', 'is_moderator')) {
                $table->boolean('is_moderator')->default(false)->after('student_campus_id');
            }

            // Faculty
            if (!Schema::hasColumn('users', 'is_teaching')) {
                $table->boolean('is_teaching')->default(false)->after('is_moderator');
            }
            if (!Schema::hasColumn('users', 'faculty_department_id')) {
                $table->unsignedBigInteger('faculty_department_id')->nullable()->after('is_teaching');
            }
            if (!Schema::hasColumn('users', 'faculty_office_id')) {
                $table->unsignedBigInteger('faculty_office_id')->nullable()->after('faculty_department_id');
            }
            if (!Schema::hasColumn('users', 'faculty_campus_id')) {
                $table->unsignedBigInteger('faculty_campus_id')->nullable()->after('faculty_office_id');
            }
        });

        // Foreign keys (only if the target tables exist)
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasTable('departments')) {
                if (Schema::hasColumn('users', 'student_department_id')) {
                    $table->foreign('student_department_id')
                          ->references('id')->on('departments')
                          ->nullOnDelete();
                }
                if (Schema::hasColumn('users', 'faculty_department_id')) {
                    $table->foreign('faculty_department_id')
                          ->references('id')->on('departments')
                          ->nullOnDelete();
                }
            }

            if (Schema::hasTable('campuses')) {
                if (Schema::hasColumn('users', 'student_campus_id')) {
                    $table->foreign('student_campus_id')
                          ->references('id')->on('campuses')
                          ->nullOnDelete();
                }
                if (Schema::hasColumn('users', 'faculty_campus_id')) {
                    $table->foreign('faculty_campus_id')
                          ->references('id')->on('campuses')
                          ->nullOnDelete();
                }
            }

            if (Schema::hasTable('offices')) {
                if (Schema::hasColumn('users', 'faculty_office_id')) {
                    $table->foreign('faculty_office_id')
                          ->references('id')->on('offices')
                          ->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        // Drop FKs first if these columns exist
        Schema::table('users', function (Blueprint $table) {
            $dropFkIfExists = function (string $col) use ($table) {
                if (Schema::hasColumn('users', $col)) {
                    // Laravel names: users_{column}_foreign
                    try { $table->dropForeign("users_{$col}_foreign"); } catch (\Throwable $e) {}
                }
            };

            $dropFkIfExists('student_department_id');
            $dropFkIfExists('faculty_department_id');
            $dropFkIfExists('student_campus_id');
            $dropFkIfExists('faculty_campus_id');
            $dropFkIfExists('faculty_office_id');
        });

        // Then drop columns we added (guarded)
        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'faculty_campus_id','faculty_office_id','faculty_department_id','is_teaching',
                'is_moderator','student_campus_id','student_department_id','year_level','student_number',
                'organization','info_status','bio','link_linkedin','link_facebook',
                'address_province','address_city','address_brgy','address_house',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if (in_array($col, ['student_number'])) {
                        // drop unique index if present
                        try { $table->dropUnique("users_{$col}_unique"); } catch (\Throwable $e) {}
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
