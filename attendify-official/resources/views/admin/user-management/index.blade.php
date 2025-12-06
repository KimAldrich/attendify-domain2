{{-- resources/views/admin/user-management/index.blade.php --}}
<x-app-layout>
  <div class="px-6 pt-6">
    <h2 class="text-2xl font-semibold tracking-tight">User Management</h2>
    <p class="text-sm text-gray-500">Monitor account verification and user information. Manage account status and roles.</p>
  </div>

  <div class="px-0 md:px-6 py-4">
    <livewire:admin.users-manager />
  </div>
</x-app-layout>

