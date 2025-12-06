{{-- resources/views/classroom/roles/admin/rooms/index.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <div class="max-w-6xl mx-auto space-y-6">
            @include('classroom.roles.admin._tabs')
            {{-- Header --}}
            <div class="flex items-center justify-between">
                
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Room Designation List</h2>
                    <p class="text-sm text-slate-500">
                        Register classrooms and face-recognition devices.
                    </p>
                </div>

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', { name: 'create-room' })"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0052CC] text-white text-sm font-medium px-4 py-2 shadow-sm hover:bg-[#003fa3]"
                >
                    <span class="bi bi-plus-lg text-sm"></span>
                    Add New Room
                </button>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Rooms with IP/endpoint --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase">Rooms with Camera's/Endpoint Available</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $roomsWithIpCount }}
                    </p>
                </div>

                {{-- Face-recog Enabled --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase">Rooms with Face Recognition Enabled</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $roomsEnabledCount }}
                    </p>
                </div>

                {{-- Face-recog Disabled --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase">Rooms with Face Recognition Disabled</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $roomsDisabledCount }}
                    </p>
                </div>

                {{-- % of IP rooms that are enabled --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase">
                        Percentage of Face Recognition Working Rooms
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $percentEnabledWithIp }}%
                    </p>
                </div>
            </div>

            {{-- Filters: search + status --}}
            <form method="GET"
                class="bg-white rounded-xl shadow border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3 mt-4">
                <div class="flex flex-col">
                    <label class="text-xs font-medium text-slate-600">
                        Search rooms
                    </label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $search ?? '' }}"
                        placeholder="Room or camera endpoint…"
                        class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                    >
                </div>

                <div class="flex items-end gap-3">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-slate-600">
                            Face-recognition status
                        </label>
                        <select
                            name="status"
                            class="w-40 rounded-lg border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        >
                            <option value="">All statuses</option>
                            <option value="enabled"  @selected(($status ?? '') === 'enabled')>Enabled</option>
                            <option value="disabled" @selected(($status ?? '') === 'disabled')>Disabled</option>
                        </select>
                    </div>

                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 text-white text-xs font-semibold px-3 py-2 hover:bg-slate-800">
                        Apply
                    </button>
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Room Assignment</th>
                            <th class="px-4 py-3 text-left">Camera / Endpoint</th>
                            <th class="px-4 py-3 text-left">Face Recognition Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rooms as $room)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $room->room_number }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $room->camera_endpoint ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $room->is_face_recognition_enabled ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5
                                            {{ $room->is_face_recognition_enabled ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                                        {{ $room->is_face_recognition_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    {{-- Edit: opens modal for this specific room --}}
                                    <button
                                        type="button"
                                        x-data
                                        x-on:click="$dispatch('open-modal', { name: 'edit-room-{{ $room->id }}' })"
                                        class="inline-flex items-center rounded-md border border-slate-200 text-xs px-2 py-1.5 text-slate-700 hover:bg-slate-50"
                                    >
                                        Edit
                                    </button>

                                    {{-- Delete --}}
                                    <form
                                        action="{{ route('classroom.admin.rooms.destroy', $room) }}"
                                        method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Remove this room?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-md border border-red-200 text-xs px-2 py-1.5 text-red-700 hover:bg-red-50"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-400 text-sm">
                                    No rooms yet. Click <span class="font-medium">“Add New Room”</span> to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <x-table-footer :paginator="$rooms" />
            </div>

        </div>
    </div>

    
    {{-- Modal: Create Room --}}
    <div
        x-data="{ open: {{ session('open_room_modal') || $errors->has('room_number') || $errors->has('camera_endpoint') ? 'true' : 'false' }} }"
        x-on:open-modal.window="
            if ($event.detail.name === 'create-room') open = true
        "
        x-on:close-modal.window="
            if ($event.detail.name === 'create-room') open = false
        "
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
    >
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
            x-on:click.stop
        >
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">
                        Add New Room
                    </h2>
                    <p class="mt-1 text-xs text-slate-500 leading-snug">
                        Register a classroom and, optionally, link a face-recognition device or endpoint.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                    x-on:click="open = false"
                >
                    <i class="bi bi-x-lg text-xs text-slate-500"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <form action="{{ route('classroom.admin.rooms.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Room number --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Room Number
                    </label>
                    <input
                        type="text"
                        name="room_number"
                        value="{{ old('room_number') }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="e.g. LAB-301"
                        required
                    />
                    @error('room_number')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Camera / Endpoint --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Camera / Endpoint (optional)
                    </label>
                    <input
                        type="text"
                        name="camera_endpoint"
                        value="{{ old('camera_endpoint') }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        placeholder="e.g. rtsp://192.168.1.10 or device ID"
                    />
                    @error('camera_endpoint')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Face recognition toggle --}}
                <div class="flex items-center justify-between pt-2">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input
                            type="checkbox"
                            name="is_face_recognition_enabled"
                            value="1"
                            class="rounded border-slate-300 text-[#0052CC] focus:ring-[#0052CC]"
                            {{ old('is_face_recognition_enabled') ? 'checked' : '' }}
                        >
                        <span>Enable face-recognition in this room</span>
                    </label>
                </div>

                {{-- Footer buttons --}}
                <div class="flex items-center justify-end gap-2 pt-3">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        x-on:click="open = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                    >
                        Save Room
                    </button>
                </div>
            </form>
        </div>
    </div>
    @foreach ($rooms as $room)
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="
            if ($event.detail.name === 'edit-room-{{ $room->id }}') open = true
        "
        x-on:close-modal.window="
            if ($event.detail.name === 'edit-room-{{ $room->id }}') open = false
        "
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 bg-black/60 z-[2100] flex items-center justify-center px-4"
    >
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
            x-on:click.stop
        >
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">
                        Edit Room
                    </h2>
                    <p class="mt-1 text-xs text-slate-500 leading-snug">
                        Update the classroom information or face-recognition settings.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center h-7 w-7 rounded-full hover:bg-slate-100"
                    x-on:click="open = false"
                >
                    <i class="bi bi-x-lg text-xs text-slate-500"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <form action="{{ route('classroom.admin.rooms.update', $room) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Room number --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Room Number
                    </label>
                    <input
                        type="text"
                        name="room_number"
                        value="{{ old('room_number', $room->room_number) }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                        required
                    />
                </div>

                {{-- Camera / Endpoint --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-slate-700">
                        Camera / Endpoint (optional)
                    </label>
                    <input
                        type="text"
                        name="camera_endpoint"
                        value="{{ old('camera_endpoint', $room->camera_endpoint) }}"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:ring-[#0052CC] focus:border-[#0052CC]"
                    />
                </div>

                {{-- Face recognition toggle --}}
                <div class="flex items-center justify-between pt-2">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input
                            type="checkbox"
                            name="is_face_recognition_enabled"
                            value="1"
                            class="rounded border-slate-300 text-[#0052CC] focus:ring-[#0052CC]"
                            {{ old('is_face_recognition_enabled', $room->is_face_recognition_enabled) ? 'checked' : '' }}
                        >
                        <span>Enable face-recognition in this room</span>
                    </label>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 pt-3">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        x-on:click="open = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-3 py-1.5 rounded-md bg-[#0052CC] text-xs font-semibold text-white hover:bg-[#003fa3]"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach
</x-app-layout>
