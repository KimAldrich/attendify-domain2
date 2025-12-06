<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Classroom\EntryController;
use App\Http\Controllers\Classroom\AdminAcademicPeriodController;
use App\Http\Controllers\Classroom\AdminClassroomController;
use App\Http\Controllers\Classroom\InstructorCourseController;
use App\Http\Controllers\Classroom\InstructorSectionController;
use App\Http\Controllers\Classroom\StudentAttendanceController;

Route::middleware(['auth', 'fresh.session', 'verified'])
    ->prefix('classroom')
    ->as('classroom.')
    ->group(function () {

        /*
        |--------------------------------------------------------------
        | Entry points for sidebar (“My Attendance”, “Manage Attendance”)
        |--------------------------------------------------------------
        */

        // Used by config/nav “My Attendance”
        Route::get('/', [EntryController::class, 'index'])
            ->middleware('can:view attendance')
            ->name('index');

        // Used by config/nav “Manage Attendance”
        Route::get('/manage', [EntryController::class, 'manage'])
            ->middleware('can:manage attendance')
            ->name('manage');

        /*
        |--------------------------------------------------------------
        | ADMIN CLASSROOM PAGES
        | /classroom/admin/*
        | name: classroom.admin.*
        |--------------------------------------------------------------
        */

        Route::prefix('admin')
            ->as('admin.')
            ->middleware('can:manage attendance') // you can add ->middleware('role:admin') if you want
            ->group(function () {

                // MANAGE CLASSROOM
                // Page Rooms
                Route::get('/rooms', [AdminClassroomController::class, 'rooms'])
                    ->name('rooms.index');

                Route::post('/rooms', [AdminClassroomController::class, 'storeRoom'])
                    ->name('rooms.store');

                Route::put('/rooms/{room}', [AdminClassroomController::class, 'updateRoom'])
                    ->name('rooms.update');

                Route::delete('/rooms/{room}', [AdminClassroomController::class, 'destroyRoom'])
                    ->name('rooms.destroy');

                Route::get('/periods', [AdminAcademicPeriodController::class, 'index'])
                    ->name('periods.index');

                Route::post('/periods', [AdminAcademicPeriodController::class, 'store'])
                    ->name('periods.store');

                Route::put('/periods/{period}', [AdminAcademicPeriodController::class, 'update'])
                    ->name('periods.update');

                Route::post('/periods/{period}/set-current', [AdminAcademicPeriodController::class, 'setCurrent'])
                    ->name('periods.set-current');

                Route::delete('/periods/{period}', [AdminAcademicPeriodController::class, 'destroy'])
                    ->name('periods.destroy');

                // Page Instructors (AY-Term selector)
                Route::get('/instructors', [AdminClassroomController::class, 'instructors'])
                    ->name('instructors.index');

                // Page Courses (per AY-Term + instructor)
                Route::get('/courses', [AdminClassroomController::class, 'courses'])
                    ->name('courses.index');

                Route::post('/courses', [AdminClassroomController::class, 'storeSection'])
                    ->name('courses.store');

                Route::put('/courses/{section}', [AdminClassroomController::class, 'updateSection'])
                    ->name('courses.update');

                Route::delete('/courses/{section}', [AdminClassroomController::class, 'destroySection'])
                    ->name('courses.destroy');

                // Page Selected Section
                Route::get('/sections/{section}', [AdminClassroomController::class, 'section'])
                    ->name('sections.show');

                // =====================
                // SCHEDULE MANAGEMENT
                // =====================
                Route::post('/sections/{section}/schedules', [AdminClassroomController::class, 'storeSchedule'])
                    ->name('sections.schedules.store');

                Route::put('/sections/{section}/schedules/{schedule}', [AdminClassroomController::class, 'updateSchedule'])
                    ->name('sections.schedules.update');

                Route::delete('/sections/{section}/schedules/{schedule}', [AdminClassroomController::class, 'destroySchedule'])
                    ->name('sections.schedules.destroy');


                // =====================
                // STUDENT ENROLLMENT
                // =====================
                Route::post('/sections/{section}/students', [AdminClassroomController::class, 'storeStudent'])
                    ->name('sections.students.store');

                Route::delete('/sections/{section}/students/{enrollment}', [AdminClassroomController::class, 'destroyStudent'])
                    ->name('sections.students.destroy');


                // =====================
                // CSV IMPORT / EXPORT
                // =====================
                Route::post('/sections/{section}/students/import', [AdminClassroomController::class, 'importStudents'])
                    ->name('sections.students.import');

                Route::get('/sections/{section}/students/export', [AdminClassroomController::class, 'exportStudents'])
                    ->name('sections.students.export');


                // =====================
                // PER-STUDENT ATTENDANCE LIST (AJAX/MODAL)
                // =====================
                Route::get('/sections/{section}/students/{student}/attendance', [AdminClassroomController::class, 'studentAttendance'])
                    ->name('sections.students.attendance');


                // =====================
                // SEND FACE-REC NOTIFICATION
                // =====================
                Route::post('/sections/{section}/students/{student}/notify-face', [AdminClassroomController::class, 'notifyStudentFace'])
                    ->name('sections.students.notify-face');
            });

        /*
        |--------------------------------------------------------------
        | INSTRUCTOR CLASSROOM PAGES
        | /classroom/instructor/*
        | name: classroom.instructor.*
        |--------------------------------------------------------------
        */

        Route::prefix('instructor')
            ->as('instructor.')
            ->middleware('can:manage attendance') // plus is_teaching check in controller
            ->group(function () {

                /*
                |-------------------------
                | MANAGE COURSES (LIST)
                |-------------------------
                */

                // Page: My Courses (instructor’s own sections)
                Route::get('/courses', [InstructorCourseController::class, 'index'])
                    ->name('courses.index');

                // Create a new section for this instructor & period
                Route::post('/courses', [InstructorCourseController::class, 'store'])
                    ->name('courses.store');

                // Update a section (only if owned by this instructor)
                Route::put('/courses/{section}', [InstructorCourseController::class, 'update'])
                    ->name('courses.update');

                // Delete a section (only if owned by this instructor)
                Route::delete('/courses/{section}', [InstructorCourseController::class, 'destroy'])
                    ->name('courses.destroy');

                /*
                |-------------------------
                | SECTION MANAGEMENT
                |-------------------------
                */

                // Section overview (schedules + roster + summary)
                Route::get('/sections/{section}', [InstructorSectionController::class, 'show'])
                    ->name('sections.show');

                // SCHEDULE MANAGEMENT (for this instructor’s section only)
                Route::post('/sections/{section}/schedules', [InstructorSectionController::class, 'storeSchedule'])
                    ->name('sections.schedules.store');

                Route::put('/sections/{section}/schedules/{schedule}', [InstructorSectionController::class, 'updateSchedule'])
                    ->name('sections.schedules.update');

                Route::delete('/sections/{section}/schedules/{schedule}', [InstructorSectionController::class, 'destroySchedule'])
                    ->name('sections.schedules.destroy');

                // STUDENT ENROLLMENT (for this instructor’s section only)
                Route::post('/sections/{section}/students', [InstructorSectionController::class, 'storeStudent'])
                    ->name('sections.students.store');

                Route::delete('/sections/{section}/students/{enrollment}', [InstructorSectionController::class, 'destroyStudent'])
                    ->name('sections.students.destroy');

                // CSV IMPORT / EXPORT (roster)
                Route::post('/sections/{section}/students/import', [InstructorSectionController::class, 'importStudents'])
                    ->name('sections.students.import');

                Route::get('/sections/{section}/students/export', [InstructorSectionController::class, 'exportStudents'])
                    ->name('sections.students.export');

                // PER-STUDENT ATTENDANCE LIST (AJAX/MODAL)
                Route::get('/sections/{section}/students/{student}/attendance', [InstructorSectionController::class, 'studentAttendance'])
                    ->name('sections.students.attendance');

                // SEND FACE-REC NOTIFICATION
                Route::post('/sections/{section}/students/{student}/notify-face', [InstructorSectionController::class, 'notifyStudentFace'])
                    ->name('sections.students.notify-face');

                /*
                |-------------------------
                | DAILY ATTENDANCE VIEWS
                |-------------------------
                */

                // Today’s sections for this instructor
                Route::get('/sections-today', [InstructorSectionController::class, 'sectionsToday'])
                    ->name('sections-today');

                // Today’s attendance for a specific section
                Route::get('/sections/{section}/attendance-today', [InstructorSectionController::class, 'attendanceToday'])
                    ->name('attendance-today');

                // Upload class photo for today's attendance (R2)
                Route::post('/sections/{section}/photos', [InstructorSectionController::class, 'storePhoto'])
                    ->name('sections.photos.store');

                // Mark / update a student's attendance for today (manual marking)
                Route::post('/sections/{section}/attendance-today/{student}', [InstructorSectionController::class, 'markTodayAttendance'])
                    ->name('sections.attendance.mark');

                // Reset (delete) a student's attendance record for today
                Route::delete('/sections/{section}/attendance-today/{student}', [InstructorSectionController::class, 'resetTodayAttendance'])
                    ->name('sections.attendance.reset');
            });

        /*
        |--------------------------------------------------------------
        | STUDENT CLASSROOM PAGES
        | /classroom/student/*
        | name: classroom.student.*
        |--------------------------------------------------------------
        */

        Route::prefix('student')
    ->as('student.')
    ->middleware('can:view attendance')
    ->group(function () {

        // Today’s classes + per-section status
        Route::get('/attendance-today', [StudentAttendanceController::class, 'today'])
            ->name('attendance-today');

        // Upload excuse letter for a specific section for TODAY
        Route::post('/attendance-today/excuse', [StudentAttendanceController::class, 'uploadExcuseToday'])
            ->name('attendance-today.excuse.store');

        // Overall attendance per period
        Route::get('/overall-attendance', [StudentAttendanceController::class, 'overall'])
            ->name('overall-attendance');

        // Per-section attendance records (for modal, JSON)
        Route::get('/sections/{section}/records', [StudentAttendanceController::class, 'sectionRecords'])
            ->name('sections.records');
    });
    });
