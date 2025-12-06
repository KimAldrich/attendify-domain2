<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SystemManagementController extends Controller
{
    public function index()
    {
        return view('admin.system-management.index');
    }
}
