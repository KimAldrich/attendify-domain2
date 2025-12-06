<?php

// app/Http/Controllers/Classroom/EntryController.php
namespace App\Http\Controllers\Classroom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    // Sidebar: "My Attendance" -> classroom.index
    public function index(Request $request)
    {
        $user = $request->user();

        // If the user is teaching (instructor/admin with is_teaching = true)
        if ($user->is_teaching) {
            return redirect()->route('classroom.instructor.sections-today');
        }

        // Otherwise student-style view
        return redirect()->route('classroom.student.attendance-today');
    }

    // Sidebar: "Manage Attendance" -> classroom.manage
    public function manage(Request $request)
    {
        $user = $request->user();

        // You can use roles here if you like (admin/faculty)
        if ($user->hasRole('admin')) {
            return redirect()->route('classroom.admin.periods.index');
        }

        // Default manage page for instructors
        return redirect()->route('classroom.instructor.courses.index');
    }
}
