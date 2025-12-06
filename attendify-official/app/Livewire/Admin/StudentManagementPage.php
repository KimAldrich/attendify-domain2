<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Notification;
use App\Models\Department; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class StudentManagementPage extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 25;
    public ?int $viewImageUserId = null;

    public ?int $departmentId = null;

    protected $queryString = [
        'search'  => ['except' => ''],
        'perPage' => ['except' => 25],
        'departmentId' => ['except' => null],
    ];

    public function applyFilters(): void
    {
        // Called by the Filter button
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'departmentId']);
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        // When rows-per-page changes, go back to first page
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function render()
{
    // 1) Base: all students
    $baseQuery = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'student'));

    // 2) Apply *current filters* (search + department) for both summary + table
    $filteredQuery = (clone $baseQuery)
        ->when($this->search !== '', function ($q) {
            $term = '%'.$this->search.'%';
            $q->where(function ($qq) use ($term) {
                $qq->where('first_name', 'like', $term)
                   ->orWhere('last_name', 'like', $term)
                   ->orWhere('name', 'like', $term)
                   ->orWhere('student_number', 'like', $term)
                   ->orWhere('email', 'like', $term);
            });
        })
        ->when($this->departmentId, function ($q) {
            $q->where('student_department_id', $this->departmentId);
        });

    // 3) Summary metrics – all of these are now *filtered* counts
    $totalStudents = (clone $filteredQuery)->count();

    $totalUnverified = (clone $filteredQuery)
        ->where('info_status', 0)
        ->count();

    $totalNoFace = (clone $filteredQuery)
        ->where(function ($q) {
            $q->whereNull('face_recognition_path')
              ->orWhere('face_recognition_path', '');
        })
        ->count();

    $totalVerifiedWithFace = (clone $filteredQuery)
        ->where('info_status', 1)
        ->whereNotNull('face_recognition_path')
        ->where('face_recognition_path', '!=', '')
        ->count();

    $verifiedWithFacePercentage = $totalStudents > 0
        ? round(($totalVerifiedWithFace / $totalStudents) * 100, 1)
        : 0;

    // 4) Paginated table – same filtered query, plus ordering + eager-load
    $students = (clone $filteredQuery)
        ->with('studentDepartment')
        ->orderByRaw('student_number IS NULL')
        ->orderBy('student_number')
        ->paginate($this->perPage);

    $currentViewUser = $this->viewImageUserId
        ? User::find($this->viewImageUserId)
        : null;

    $departments = Department::orderBy('abbrev')->orderBy('name')->get();

    return view('livewire.admin.student-management-page', [
        'students'                    => $students,
        'currentViewUser'             => $currentViewUser,
        'departments'                 => $departments,
        'totalStudents'               => $totalStudents,
        'totalUnverified'             => $totalUnverified,
        'totalNoFace'                 => $totalNoFace,
        'totalVerifiedWithFace'       => $totalVerifiedWithFace,
        'verifiedWithFacePercentage'  => $verifiedWithFacePercentage,
    ]);
}


    public function unlinkStudent(int $userId): void
    {
        $user = User::findOrFail($userId);

        Log::info('[StudentManagement] Unlinking student number', [
            'user_id'        => $user->id,
            'student_number' => $user->student_number,
            'info_status'    => $user->info_status,
            'face_path'      => $user->face_recognition_path,
        ]);

        if ($user->face_recognition_path) {
            try {
                Storage::disk('r2')->delete($user->face_recognition_path);
            } catch (\Throwable $e) {
                Log::error('[StudentManagement] Failed to delete face image on unlink', [
                    'user_id' => $user->id,
                    'path'    => $user->face_recognition_path,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $user->student_number        = null;
        $user->info_status           = 0;
        $user->face_recognition_path = null;
        $user->save();

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Student Number Unlinked',
            'message' => 'Your account has been unlinked from its student number and your face recognition data has been reset. Please review and complete your profile information again.',
            'link'    => route('profile.me'),
            'status'  => 'unread',
        ]);

        $this->dispatch('toast', type: 'success', message: 'Student number unlinked and recognition data cleared.');
    }

    public function deleteFaceImage(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! $user->face_recognition_path) {
            return;
        }

        try {
            Storage::disk('r2')->delete($user->face_recognition_path);
        } catch (\Throwable $e) {
            Log::error('[StudentManagement] Failed to delete face image', [
                'user_id' => $user->id,
                'path'    => $user->face_recognition_path,
                'error'   => $e->getMessage(),
            ]);
        }

        $user->face_recognition_path = null;
        $user->save();

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Face Image Data Reset',
            'message' => 'Your facial-recognition image has been removed from Attendify. You can upload a new image the next time you set up face recognition.',
            'link'    => route('profile.me'),
            'status'  => 'unread',
        ]);

        $this->dispatch('toast', type: 'success', message: 'Facial-recognition image deleted.');
    }

    public function openViewImage(int $userId): void
    {
        $user = User::findOrFail($userId);

        if (! $user->face_recognition_path) {
            return;
        }

        $this->viewImageUserId = $user->id;

        $this->dispatch('open-modal', name: 'view-face-image');
    }
}
