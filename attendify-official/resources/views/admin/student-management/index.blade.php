{{-- resources/views/admin/student-management/index.blade.php --}}
<x-app-layout>
    <div class="px-6 pt-6">
        <h2 class="text-2xl font-semibold tracking-tight">Student Management</h2>
        <p class="text-sm text-gray-500">
            Manage student numbers and facial-recognition images for student accounts.
        </p>
    </div>

    <div class="px-0 md:px-6 py-4">
        <livewire:admin.student-management-page />
    </div>
</x-app-layout>
