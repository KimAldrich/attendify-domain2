<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class StudentManagementController extends Controller
{
    public function index()
    {
        // Just return the Blade view that wraps the Livewire component
        return view('admin.student-management.index');
    }
}
