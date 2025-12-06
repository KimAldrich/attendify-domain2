{{-- resources/views/events/manage/_create-event-modal.blade.php --}}

<div
    class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 overflow-y-auto"
    x-show="createEventOpen"
    x-transition.opacity
    x-cloak
>
    <div
        class="bg-white rounded-2xl shadow-xl w-full max-w-2xl h-[70vh] my-8 overflow-hidden flex flex-col"
        @click.away="createEventOpen = false"
    >
        {{-- Header --}}
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    Create New Event
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Step <span x-text="createStep"></span> of 5
                </p>
            </div>

            <button
                type="button"
                class="p-1 rounded-full hover:bg-slate-100 text-slate-500"
                @click="createEventOpen = false"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Error summary for this form --}}
        @if ($errors->createEvent->any())
            <div class="px-5 pt-3">
                <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                    <p class="font-semibold mb-1">There were problems with your input:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->createEvent->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Form --}}
        <form
            method="POST"
        action="{{ route('events.manage.store') }}"
        enctype="multipart/form-data"
        class="flex-1 min-h-0 flex flex-col"
    >
        @csrf

        <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4 space-y-6 custom-scroll">

            {{-- STEP 1: Basic info --}}
            <div x-show="createStep === 1">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Basic details</h3>

                <div class="space-y-4">

                    {{-- Title --}}
                    <div>
                        <label for="title" class="block text-xs font-medium text-slate-700">
                            Event title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            maxlength="120"
                            value="{{ old('title') }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                            placeholder="e.g. IT Freshmen Orientation"
                            required
                        >
                        <p class="mt-1 text-[11px] text-slate-500">Max 120 characters.</p>
                    </div>
                    <p class="mt-1 text-[11px] text-red-500"
                    x-show="createErrors.title"
                    x-text="createErrors.title"></p>

                    {{-- Subtitle --}}
                    <div>
                        <label for="subtitle" class="block text-xs font-medium text-slate-700">
                            Subtitle / tagline <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="subtitle"
                            id="subtitle"
                            maxlength="180"
                            value="{{ old('subtitle') }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                            placeholder="e.g. Welcoming our new IT students"
                        >
                        <p class="mt-1 text-[11px] text-slate-500">Max 180 characters.</p>
                    </div>
                    <p class="mt-1 text-[11px] text-red-500"
                    x-show="createErrors.subtitle"
                    x-text="createErrors.subtitle"></p>

                    {{-- Event type --}}
                    <div>
                        <label for="event_type" class="block text-xs font-medium text-slate-700">
                            Event type <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="event_type"
                            id="event_type"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                bg-white focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                        >
                            <option value="">Select event type</option>
                            @php
                                $eventTypes = [
                                    'seminar' => 'Seminar',
                                    'workshop' => 'Workshop',
                                    'orientation' => 'Orientation',
                                    'academic_event' => 'Academic Event',
                                    'lecture' => 'Lecture',
                                    'training' => 'Training',
                                    'symposium' => 'Symposium',
                                    'conference' => 'Conference',
                                    'panel_discussion' => 'Panel Discussion',
                                    'research_presentation' => 'Research Presentation',
                                    'thesis_capstone_defense' => 'Thesis/Capstone Defense',
                                    'student_activity' => 'Student Activity',
                                    'club_org_meeting' => 'Club/Org Meeting',
                                    'community_outreach' => 'Community Outreach',
                                    'career_fair' => 'Career Fair',
                                    'ceremony' => 'Ceremony',
                                    'exhibit_showcase' => 'Exhibit/Showcase',
                                    'competition' => 'Competition',
                                    'special_event' => 'Special Event',
                                ];
                            @endphp
                            @foreach ($eventTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('event_type') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-500">
                            Helps classify the event and supports filters/analytics.
                        </p>
                    </div>
                    <p class="mt-1 text-[11px] text-red-500"
                    x-show="createErrors.event_type"
                    x-text="createErrors.event_type"></p>
                </div>
            </div>

            {{-- STEP 2: Media --}}
            <div x-show="createStep === 2">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Images (optional)</h3>

                <p class="text-xs text-slate-500 mb-4">
                    You may upload images now or later in the Details tab.
                </p>

                <div class="space-y-4">

                    {{-- Hero --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Hero image</label>
                        <input
                            type="file"
                            name="hero_image"
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1 block w-full text-xs text-slate-600
                                file:mr-3 file:py-1.5 file:px-3 file:rounded-md
                                file:border-0 file:text-xs file:font-medium
                                file:bg-slate-100 file:text-slate-700
                                hover:file:bg-slate-200"
                        >
                        <p class="mt-1 text-[11px] text-slate-500">
                            16:9 recommended. JPG/PNG/WebP up to 3MB.
                        </p>
                    </div>

                    {{-- Banner --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Banner image</label>
                        <input
                            type="file"
                            name="banner_image"
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1 block w-full text-xs text-slate-600
                                file:mr-3 file:py-1.5 file:px-3 file:rounded-md
                                file:border-0 file:text-xs file:font-medium
                                file:bg-slate-100 file:text-slate-700
                                hover:file:bg-slate-200"
                        >
                        <p class="mt-1 text-[11px] text-slate-500">
                            Optional vertical banner. JPG/PNG/WebP up to 5MB.
                        </p>
                    </div>

                </div>
            </div>

            {{-- STEP 3: Visibility + capacity --}}
            <div x-show="createStep === 3">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Visibility and capacity</h3>

                <div class="space-y-5">

                    {{-- Visibility --}}
                    <div>
                        <p class="block text-xs font-medium text-slate-700 mb-2">
                            Who can see this event? <span class="text-red-500">*</span>
                        </p>

                        @php $visibilityOld = old('visibility', 'public'); @endphp

                        <div class="space-y-2 text-xs text-slate-700">
                            <label class="flex items-start gap-2">
                                <input
                                    type="radio"
                                    name="visibility"
                                    value="public"
                                    @checked($visibilityOld === 'public')
                                    class="mt-0.5 text-[#0052CC] border-slate-300"
                                >
                                <span>
                                    <span class="font-medium">Public</span>
                                    <span class="block text-slate-500">Visible to everyone.</span>
                                </span>
                            </label>

                            <label class="flex items-start gap-2">
                                <input
                                    type="radio"
                                    name="visibility"
                                    value="institution"
                                    @checked($visibilityOld === 'institution')
                                    class="mt-0.5 text-[#0052CC] border-slate-300"
                                >
                                <span>
                                    <span class="font-medium">Students and Faculty</span>
                                    <span class="block text-slate-500">Requires user login.</span>
                                </span>
                            </label>

                            <label class="flex items-start gap-2">
                                <input
                                    type="radio"
                                    name="visibility"
                                    value="faculty_only"
                                    @checked($visibilityOld === 'faculty_only')
                                    class="mt-0.5 text-[#0052CC] border-slate-300"
                                >
                                <span>
                                    <span class="font-medium">Faculty only</span>
                                    <span class="block text-slate-500">Restricted to faculty and admins.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <p class="mt-1 text-[11px] text-red-500"
                    x-show="createErrors.visibility"
                    x-text="createErrors.visibility"></p>

                    {{-- Target Audience --}}
                    <div class="border-t border-slate-200 pt-4 mt-4"
                        x-data='audiencePicker(@json($audienceOptions), @json(old("audience.selected", [])))'
                        @click.away="isOpen = false"
                    >
                        <h4 class="text-xs font-semibold text-slate-800 mb-1.5">Target audience</h4>
                        <p class="text-[11px] text-slate-500 mb-2">
                            Select who can register. Leave blank for all eligible users.
                        </p>

                        {{-- Token input --}}
                        <div
                            class="min-h-[40px] w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs
                                flex flex-wrap gap-1 items-center cursor-text"
                            @click="isOpen = true; $nextTick(() => $refs.audienceInput.focus())"
                        >
                            <template x-for="item in selected" :key="item.value">
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-2 py-0.5">
                                    <span x-text="item.label"></span>
                                    <button type="button"
                                            class="text-slate-400 hover:text-slate-600"
                                            @click.stop="remove(item.value)">
                                        <x-heroicon-o-x-mark class="w-3 h-3"/>
                                    </button>
                                    <input type="hidden" name="audience[selected][]" :value="item.value">
                                </span>
                            </template>

                            <input
                                x-ref="audienceInput"
                                type="text"
                                x-model="query"
                                placeholder="Search audience filters..."
                                class="flex-1 border-none focus:outline-none focus:ring-0 text-xs text-slate-700 min-w-[80px]"
                            >
                        </div>

                        {{-- Dropdown --}}
                        <div
                            class="mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm text-xs"
                            x-show="isOpen && filtered.length"
                            x-transition
                        >
                            <template x-for="item in filtered" :key="item.value">
                                <button type="button"
                                        class="w-full flex items-center justify-between px-3 py-1.5 hover:bg-slate-50 text-left"
                                        @click="add(item)">
                                    <div>
                                        <div class="font-medium text-slate-800" x-text="item.label"></div>
                                        <div class="text-[10px] text-slate-500" x-text="item.group"></div>
                                    </div>
                                </button>
                            </template>
                        </div>

                        {{-- No-account toggle --}}
                        <div class="mt-3 flex items-center gap-2 text-[11px] text-slate-700">
                            <input
                                type="checkbox"
                                name="audience[allow_no_account]"
                                value="1"
                                class="rounded border-slate-300 text-[#0052CC]"
                                {{ old('audience.allow_no_account') ? 'checked' : '' }}
                            >
                            <span>Allow registration from visitors (no account guests)</span>
                        </div>
                    </div>

                    {{-- Capacity --}}
                    <div>
                        <p class="block text-xs font-medium text-slate-700 mb-1.5">Capacity</p>

                        @php $capacityTypeOld = old('capacity') ? 'limited' : 'no_limit'; @endphp

                        <div class="flex flex-col gap-3 text-xs text-slate-700">

                            <label class="inline-flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="capacity_type"
                                    value="no_limit"
                                    @checked($capacityTypeOld === 'no_limit')
                                    class="text-[#0052CC] border-slate-300"
                                >
                                <span>No limit</span>
                            </label>

                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="radio"
                                        name="capacity_type"
                                        value="limited"
                                        @checked($capacityTypeOld === 'limited')
                                        class="text-[#0052CC] border-slate-300"
                                    >
                                    <span>Limit</span>
                                </label>

                                <input
                                    type="number"
                                    name="capacity"
                                    value="{{ old('capacity') }}"
                                    min="1"
                                    max="10000"
                                    placeholder="100"
                                    class="w-24 rounded-lg border border-slate-300 px-2 py-1 text-xs
                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                >

                                <span>attendees</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- STEP 4: Schedule --}}
            <div x-show="createStep === 4">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Schedule</h3>

                <div class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Start --}}
                        <div>
                            <label for="start_at" class="block text-xs font-medium text-slate-700">
                                Start date and time (required)
                            </label>
                            <input
                                type="datetime-local"
                                name="start_at"
                                id="start_at"
                                value="{{ old('start_at') }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                    focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                            >
                            <p class="mt-1 text-[11px] text-red-500"
                            x-show="createErrors.start_at"
                            x-text="createErrors.start_at"></p>
                        </div>

                        {{-- End --}}
                        <div>
                            <label for="end_at" class="block text-xs font-medium text-slate-700">
                                End date and time (optional)
                            </label>
                            <input
                                type="datetime-local"
                                name="end_at"
                                id="end_at"
                                value="{{ old('end_at') }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                    focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                            >
                        </div>

                    </div>

                    <p class="text-[11px] text-slate-500">
                        Detailed program activities can be set later in the Program tab.
                    </p>
                </div>
            </div>

            {{-- STEP 5: Co-organizers + staff --}}
            <div x-show="createStep === 5"
                x-data='coStaffPicker(@json($roleCandidateOptions))'
                @click.away="isOpen = false"
            >
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Co-organizers and staff</h3>

                <p class="text-xs text-slate-500 mb-3">
                    Add people who will help manage the event.
                </p>

                {{-- Selected list --}}
                <div class="mb-3">
                    <template x-if="entries.length === 0">
                        <p class="text-[11px] text-slate-500">
                            You will be set as the event owner by default.
                        </p>
                    </template>

                    <template x-for="entry in entries" :key="entry.user_id">
                        <div
                            class="mt-1 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs"
                        >
                            <div>
                                <div class="font-medium text-slate-800" x-text="entry.name"></div>
                                <div class="text-[11px] text-slate-500" x-text="entry.email"></div>
                                <div class="text-[11px] text-slate-600 mt-0.5">
                                    Role: <span class="font-medium" x-text="entry.role_label"></span>
                                </div>
                            </div>

                            <button type="button"
                                    class="text-[11px] text-slate-500 hover:text-red-600"
                                    @click="remove(entry.user_id)">
                                Remove
                            </button>

                            {{-- Backend role inputs --}}
                            <template x-if="entry.role === 'co_organizer'">
                                <input type="hidden" name="co_organizers[]" :value="entry.user_id">
                            </template>
                            <template x-if="entry.role === 'staff'">
                                <input type="hidden" name="staff[]" :value="entry.user_id">
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Picker --}}
                <div class="space-y-2">

                    {{-- Role selector --}}
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <label class="text-[11px] font-medium text-slate-700">Role for next added user:</label>
                        <select
                            x-model="currentRole"
                            class="rounded-md border border-slate-300 px-2 pr-6 py-1 text-xs
                                focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                        >
                            <option value="co_organizer">Co-organizer</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div class="relative">
                        <div
                            class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs"
                        >
                            <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5 text-slate-400" />
                            <input
                                type="text"
                                x-model="query"
                                @focus="isOpen = true"
                                placeholder="Search users..."
                                class="flex-1 border-none focus:outline-none focus:ring-0 text-xs text-slate-700"
                            >
                            <button type="button"
                                    class="text-[11px] text-slate-500 hover:text-slate-700"
                                    @click="clearSearch()"
                                    x-show="query">
                                Clear
                            </button>
                        </div>

                        {{-- Dropdown --}}
                        <div
                            class="absolute z-10 mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-slate-200
                                bg-white shadow-sm text-xs"
                            x-show="isOpen && filtered.length"
                            x-transition
                        >
                            <template x-for="user in filtered" :key="user.id">
                                <button
                                    type="button"
                                    class="w-full px-3 py-1.5 text-left hover:bg-slate-50 flex items-center justify-between"
                                    @click="add(user)"
                                >
                                    <div>
                                        <div class="font-medium text-slate-800" x-text="user.name"></div>
                                        <div class="text-[11px] text-slate-500" x-text="user.email"></div>
                                    </div>
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600"
                                        x-text="currentRole === 'co_organizer' ? 'Co-organizer' : 'Staff'"
                                    ></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer: navigation buttons --}}
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs
                        text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="createStep = Math.max(1, createStep - 1)"
                    :disabled="createStep === 1"
                >
                    <x-heroicon-o-chevron-left class="w-3.5 h-3.5 mr-1" />
                    Back
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs
                        text-slate-700 hover:bg-slate-100"
                    @click="createEventOpen = false"
                >
                    Cancel
                </button>

                {{-- Next --}}
                <button
                    type="button"
                    x-show="createStep < 5"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                        text-white hover:bg-[#0042a3]"
                    @click="if (validateStep(createStep)) { createStep = Math.min(5, createStep + 1) }"
                >
                    Next
                    <x-heroicon-o-chevron-right class="w-3.5 h-3.5 ml-1" />
                </button>

                {{-- Create --}}
                <button
                    type="submit"
                    x-show="createStep === 5"
                    class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                        text-white hover:bg-[#0042a3]"
                >
                    Create event
                </button>
            </div>
        </div>
        </form>

    </div>
</div>
