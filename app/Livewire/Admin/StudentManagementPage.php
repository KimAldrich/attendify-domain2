<?php

namespace App\Livewire\Admin;

use App\Models\User;
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

    protected $queryString = [
        'search'  => ['except' => ''],
        'perPage' => ['except' => 25],
    ];

    public function applyFilters(): void
    {
        // Called by the Filter button
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        // When rows-per-page changes, go back to first page
        $this->resetPage();
    }

    public function render()
    {
        $students = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
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
            ->orderByRaw('student_number IS NULL')
            ->orderBy('student_number')
            ->paginate($this->perPage);

        $currentViewUser = $this->viewImageUserId
            ? User::find($this->viewImageUserId)
            : null;

        return view('livewire.admin.student-management-page', [
            'students'        => $students,
            'currentViewUser' => $currentViewUser,
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
