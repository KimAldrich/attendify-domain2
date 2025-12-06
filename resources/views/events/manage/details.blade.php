{{-- resources/views/events/manage/details.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

        @include('events.manage._event-header', ['event' => $event])
        @include('events.manage._event-tabs', ['event' => $event, 'active' => 'details'])

        {{-- Flash status --}}
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->updateDetails->any())
            <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                <p class="font-semibold mb-1">There were problems with your changes:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->updateDetails->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            id="event-details-form"
            method="POST"
            action="{{ route('events.manage.details.update', $event) }}"
            enctype="multipart/form-data"
            class="space-y-4"
        >
            @csrf
            @method('PUT')

            <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)]">

                {{-- LEFT: Basic info + Media --}}
                <div class="space-y-4">

                    {{-- Basic info --}}
                    <div class="border border-slate-200 rounded-lg bg-white p-4 space-y-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-slate-900">Basic information</h2>
                            <p class="text-[11px] text-slate-500">
                                These fields are shown on the public event page.
                            </p>
                        </div>

                        <div class="space-y-3 text-sm">

                            {{-- Title --}}
                            <div>
                                <label for="title" class="block text-xs font-medium text-slate-700 mb-1">
                                    Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    value="{{ old('title', $event->title) }}"
                                    maxlength="120"
                                    class="w-full border border-slate-300 rounded-md text-sm px-2 py-1.5
                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                >
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Max 120 characters. Use a clear, searchable title.
                                </p>
                            </div>

                            {{-- Subtitle --}}
                            <div>
                                <label for="subtitle" class="block text-xs font-medium text-slate-700 mb-1">
                                    Subtitle / tagline <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="subtitle"
                                    name="subtitle"
                                    value="{{ old('subtitle', $event->subtitle) }}"
                                    maxlength="180"
                                    class="w-full border border-slate-300 rounded-md text-sm px-2 py-1.5
                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                >
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Max 180 characters. Shown as the short tagline under the title.
                                </p>
                            </div>

                            {{-- Event type --}}
                            <div>
                                <label for="event_type" class="block text-xs font-medium text-slate-700 mb-1">
                                    Event type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="event_type"
                                    name="event_type"
                                    class="w-full border border-slate-300 rounded-md text-sm px-2 py-1.5 bg-white
                                           focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                >
                                    <option value="">Select event type</option>
                                    @php
                                        $eventTypes = [
                                            'seminar'                 => 'Seminar',
                                            'workshop'                => 'Workshop',
                                            'orientation'             => 'Orientation',
                                            'academic_event'          => 'Academic Event',
                                            'lecture'                 => 'Lecture',
                                            'training'                => 'Training',
                                            'symposium'               => 'Symposium',
                                            'conference'              => 'Conference',
                                            'panel_discussion'        => 'Panel Discussion',
                                            'research_presentation'   => 'Research Presentation',
                                            'thesis_capstone_defense' => 'Thesis/Capstone Defense',
                                            'student_activity'        => 'Student Activity',
                                            'club_org_meeting'        => 'Club/Org Meeting',
                                            'community_outreach'      => 'Community Outreach',
                                            'career_fair'             => 'Career Fair',
                                            'ceremony'                => 'Ceremony',
                                            'exhibit_showcase'        => 'Exhibit/Showcase',
                                            'competition'             => 'Competition',
                                            'special_event'           => 'Special Event',
                                        ];
                                    @endphp
                                    @foreach ($eventTypes as $key => $label)
                                        <option value="{{ $key }}" @selected(old('event_type', $event->event_type) === $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Used for filters and analytics (e.g., “Seminar”, “Orientation”, “Training”).
                                </p>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="description" class="block text-xs font-medium text-slate-700 mb-1">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="8"
                                    maxlength="2000"
                                    class="w-full border border-slate-300 rounded-md text-sm px-2 py-1.5
                                        focus:outline-none focus:ring-1 focus:ring-[#0052CC] focus:border-[#0052CC]"
                                >{{ old('description', $event->description) }}</textarea>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Max 2,000 characters. Shown on the event page body.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Media --}}
                    <div class="border border-slate-200 rounded-lg bg-white p-4 space-y-3 text-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Media</h3>

                        {{-- Hero preview + upload --}}
                        <div class="space-y-2">
                            <p class="text-xs font-medium text-slate-700">Hero image</p>
                            @if ($event->hero_image_path)
                                <img
                                    src="{{ $event->hero_image_url }}"
                                    alt="Hero image"
                                    class="w-full rounded-md border border-slate-200 max-h-70 object-cover"
                                >
                            @else
                                <div class="w-full h-48 rounded-md border border-dashed border-slate-300
                                            flex items-center justify-center text-[11px] text-slate-400">
                                    No hero image uploaded yet.
                                </div>
                            @endif

                            <input
                                type="file"
                                name="hero_image"
                                accept="image/*"
                                class="block w-full text-xs text-slate-600
                                    file:mr-3 file:py-1.5 file:px-3 file:rounded-md
                                    file:border-0 file:text-xs file:font-medium
                                    file:bg-slate-100 file:text-slate-700
                                    hover:file:bg-slate-200"
                            >
                            <p class="text-[11px] text-slate-500">
                                JPG/PNG/WebP, up to 3MB. Recommended 16:9 image. Used in the event header and listings.
                            </p>
                        </div>

                        {{-- Banner preview + upload --}}
                        <div class="border-t border-slate-100 pt-3 mt-2 space-y-2">
                            <p class="text-xs font-medium text-slate-700">Banner image</p>
                            @if ($event->banner_image_path)
                                <img
                                    src="{{ $event->banner_image_url }}"
                                    alt="Banner image"
                                    class="w-full rounded-md border border-slate-200 max-h-70 object-cover"
                                >
                            @else
                                <div class="w-full h-48 rounded-md border border-dashed border-slate-300
                                            flex items-center justify-center text-[11px] text-slate-400">
                                    No banner image uploaded yet.
                                </div>
                            @endif

                            <input
                                type="file"
                                name="banner_image"
                                accept="image/*"
                                class="block w-full text-xs text-slate-600
                                    file:mr-3 file:py-1.5 file:px-3 file:rounded-md
                                    file:border-0 file:text-xs file:font-medium
                                    file:bg-slate-100 file:text-slate-700
                                    hover:file:bg-slate-200"
                            >
                            <p class="text-[11px] text-slate-500">
                                JPG/PNG/WebP, up to 5MB. Optional vertical tarpaulin-style image for printing or gallery use.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Status / visibility / audience / schedule / meta --}}
                <div class="space-y-4">

                    {{-- Status, visibility & target audience --}}
                    <div class="border border-slate-200 rounded-lg bg-white p-4 space-y-4 text-sm">

                        {{-- Header + Description --}}
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Status & visibility</h3>
                            <p class="mt-1 text-[11px] text-slate-500">
                                Control who can see and register for this event.
                            </p>
                        </div>

                        {{-- Current status badge --}}
                        @php
                            $statusLabelMap = [
                                'draft'     => 'Draft',
                                'published' => 'Published',
                                'ongoing'   => 'Ongoing',
                                'finished'  => 'Finished',
                                'archived'  => 'Archived',
                            ];
                            $statusLabel = $statusLabelMap[$event->status] ?? ucfirst($event->status);
                        @endphp

                        <div>
                            <p class="text-xs font-medium text-slate-600 mb-1">Current status</p>

                            <span
                                @class([
                                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-slate-100 text-slate-800 border border-slate-200' => $event->status === 'draft',
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200' => $event->status === 'published',
                                    'bg-sky-50 text-sky-700 border border-sky-200' => $event->status === 'ongoing',
                                    'bg-indigo-50 text-indigo-700 border border-indigo-200' => $event->status === 'finished',
                                    'bg-slate-200 text-slate-800 border border-slate-300' => $event->status === 'archived',
                                ])
                            >
                                {{ $statusLabel }}
                            </span>

                            {{-- Hidden input just for validation --}}
                            <input type="hidden" name="status" value="{{ $event->status }}">
                        </div>

                        {{-- Publish + Copy buttons (always side-by-side) --}}
                        <div class="flex flex-wrap items-center gap-2 pt-1">

                            {{-- Publish button --}}
                            @if ($event->status === 'draft')
                                <button
                                    type="submit"
                                    form="publish-event-form"
                                    class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                                        text-white hover:bg-[#0042a3]"
                                >
                                    Publish event
                                </button>
                            @endif

                            {{-- Copy link --}}
                            <div x-data="{ copied: false }" class="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    class="inline-flex items-center px-4 py-1.5 rounded-lg border border-slate-300 text-xs
                                        text-slate-700 hover:bg-slate-100"
                                    @click="
                                        navigator.clipboard.writeText('{{ route('events.show', $event) }}');
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                >
                                    Copy link
                                </button>
                                <span
                                    x-show="copied"
                                    x-transition
                                    class="text-[11px] text-emerald-600"
                                >
                                    Copied!
                                </span>
                            </div>

                        </div>

                        {{-- Visibility --}}
                        <div class="border-t border-slate-100 pt-3 mt-1">
                            <p class="block text-xs font-medium text-slate-700 mb-2">
                                Who can see this event?
                            </p>

                            @php
                                $visibilityOld = old('visibility', $event->visibility);
                            @endphp

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
                                        <span class="block text-slate-500">
                                            Anyone can view the event (including visitors).
                                        </span>
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
                                        <span class="block text-slate-500">
                                            Only logged-in students and faculty (plus admins) can see this event.
                                        </span>
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
                                        <span class="block text-slate-500">
                                            Only faculty accounts (plus admins) can see this event.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Target Audience --}}
                        <div
                            class="border-t border-slate-100 pt-3 mt-1"
                            x-data='audiencePicker(
                                @json($audienceOptions),
                                @json(old("audience.selected", $audienceSelected ?? []))
                            )'
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
                                    {{ old('audience.allow_no_account', $audienceAllowNoAccount ?? false) ? 'checked' : '' }}
                                >
                                <span>Allow registration from all users (even with no account guests)</span>
                            </div>
                        </div>
                    </div>

                    {{-- Schedule --}}
                    <div class="border border-slate-200 rounded-lg bg-white p-4 space-y-3">
                        <h2 class="text-sm font-semibold text-slate-900">Schedule</h2>

                        <p class="text-[11px] text-slate-500">
                            These dates are automatically managed in the <b>Programs</b> section. This page only shows the current start and end of the event.
                        </p>

                        <div class="grid gap-4 md:grid-cols-2 text-sm">
                            <div>
                                <p class="block text-xs font-medium text-slate-700 mb-1">
                                    Event start date
                                </p>
                                <div
                                    class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-700"
                                >
                                    @if ($event->start_at)
                                        {{ $event->start_at->format('M d, Y - H:i:s') }}
                                    @else
                                        <span class="text-slate-400">Not set yet</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <p class="block text-xs font-medium text-slate-700 mb-1">
                                    Event end date
                                </p>
                                <div
                                    class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-700"
                                >
                                    @if ($event->end_at)
                                        {{ $event->end_at->format('M d, Y - H:i:s') }}
                                    @else
                                        <span class="text-slate-400">Not set yet</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- Meta --}}
                    <div class="border border-slate-200 rounded-lg bg-white p-4 text-xs text-slate-600 space-y-1.5">
                        <h3 class="text-sm font-semibold text-slate-900 mb-1.5">Event meta</h3>

                        <p>
                            <span class="font-medium text-slate-700">Slug:</span>
                            <span class="font-mono text-[11px] bg-slate-50 border border-slate-200 rounded px-1 py-0.5">
                                {{ $event->slug }}
                            </span>
                        </p>
                        <p>
                            <span class="font-medium text-slate-700">Owner:</span>
                            {{ $event->owner?->full_name ?? '—' }}
                        </p>
                        <p>
                            <span class="font-medium text-slate-700">Created:</span>
                            {{ $event->created_at?->format('M d, Y H:i') ?? '—' }}
                        </p>
                        <p>
                            <span class="font-medium text-slate-700">Last updated:</span>
                            {{ $event->updated_at?->format('M d, Y H:i') ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer actions: back / save --}}
            <div
                class="pt-4 mt-4 border-t border-slate-200
                       flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3"
            >
                <p class="text-[13px] text-slate-500">
                    Changes for the event will be updated upon saving.
                </p>
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-1.5 rounded-lg bg-[#0052CC] text-xs font-medium
                        text-white hover:bg-[#0042a3]"
                >
                    Save changes
                </button>

                <a
                    href="{{ route('events.manage.index') }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-xs
                        text-slate-700 hover:bg-slate-100"
                >
                    Back to Manage Events
                </a>

            </div>
        </form>

        {{-- Hidden form used by the "Publish event" button --}}
        <form
            id="publish-event-form"
            method="POST"
            action="{{ route('events.manage.publish', $event) }}"
            class="hidden"
        >
            @csrf
        </form>

        {{-- Dangerous actions (Delete / Cancel / Archive) --}}
        <div
            class="mt-6 border-t border-slate-200 pt-4"
            x-data="{ showDelete: false, showCancel: false, showArchive: false }"
        >
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-[13px] text-slate-500">
                    Remove this event? This method cannot be undone.
                </p>

                @if ($event->status === 'draft')
                    <button
                        type="button"
                        @click="showDelete = true"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-xs font-medium
                            text-white hover:bg-red-700"
                    >
                        Delete event
                    </button>

                @elseif (in_array($event->status, ['published', 'ongoing']))
                    <button
                        type="button"
                        @click="showCancel = true"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-xs font-medium
                            text-white hover:bg-red-700"
                    >
                        Cancel event
                    </button>

                @elseif ($event->status === 'finished')
                    <button
                        type="button"
                        @click="showArchive = true"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-sky-100 text-xs font-medium
                            text-sky-800 hover:bg-sky-200 border border-sky-200"
                    >
                        Archive event
                    </button>

                @elseif ($event->status === 'archived')
                    <button
                        type="button"
                        disabled
                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-200 text-xs font-medium
                            text-slate-600 border border-slate-300 cursor-not-allowed"
                    >
                        Disabled
                    </button>
                @endif
            </div>

            {{-- DELETE MODAL (for drafts) --}}
            @if ($event->status === 'draft')
                <div
                    x-show="showDelete"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                >
                    <div class="bg-white rounded-lg shadow-lg border border-slate-200 w-full max-w-sm p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">Delete event</h3>
                        <p class="text-xs text-slate-600">
                            This will permanently delete this draft event. Type
                            <span class="font-mono">delete event</span> to confirm.
                        </p>

                        <form method="POST" action="{{ route('events.manage.destroy', $event) }}" class="space-y-3">
                            @csrf
                            @method('DELETE')

                            <input
                                type="text"
                                name="confirm_phrase"
                                placeholder="delete event"
                                class="w-full border border-slate-300 rounded-md px-2 py-1.5 text-xs"
                            >

                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="showDelete = false"
                                    class="px-3 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 hover:bg-slate-100"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="px-3 py-1.5 rounded-md bg-red-600 text-xs font-medium text-white hover:bg-red-700"
                                >
                                    Delete
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- CANCEL MODAL (for published / ongoing) --}}
            @if (in_array($event->status, ['published', 'ongoing']))
                <div
                    x-show="showCancel"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                >
                    <div class="bg-white rounded-lg shadow-lg border border-slate-200 w-full max-w-sm p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">Cancel event</h3>
                        <p class="text-xs text-slate-600">
                            This will mark the event as <span class="font-semibold">archived</span>.
                            Type <span class="font-mono">cancel event</span> to confirm.
                        </p>

                        <form method="POST" action="{{ route('events.manage.cancel', $event) }}" class="space-y-3">
                            @csrf

                            <input
                                type="text"
                                name="confirm_phrase"
                                placeholder="cancel event"
                                class="w-full border border-slate-300 rounded-md px-2 py-1.5 text-xs"
                            >

                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="showCancel = false"
                                    class="px-3 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 hover:bg-slate-100"
                                >
                                    Keep event
                                </button>
                                <button
                                    type="submit"
                                    class="px-3 py-1.5 rounded-md bg-red-600 text-xs font-medium text-white hover:bg-red-700"
                                >
                                    Confirm cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ARCHIVE MODAL (for finished) --}}
            @if ($event->status === 'finished')
                <div
                    x-show="showArchive"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                >
                    <div class="bg-white rounded-lg shadow-lg border border-slate-200 w-full max-w-sm p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900">Archive event</h3>
                        <p class="text-xs text-slate-600">
                            This will move the event to the <span class="font-semibold">Archived</span> status.
                            You can still view analytics and records later.
                        </p>

                        <form method="POST" action="{{ route('events.manage.archive', $event) }}" class="space-y-3">
                            @csrf

                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="showArchive = false"
                                    class="px-3 py-1.5 rounded-md border border-slate-300 text-xs text-slate-700 hover:bg-slate-100"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="px-3 py-1.5 rounded-md bg-sky-100 text-xs font-medium text-sky-800
                                           hover:bg-sky-200 border border-sky-200"
                                >
                                    Archive event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                window.audiencePicker = function (options, preselectedValues = []) {
                    return {
                        options: options || [],
                        query: '',
                        isOpen: false,

                        // initial selected items based on values passed by Blade
                        selected: (options || []).filter(o =>
                            (preselectedValues || []).includes(o.value)
                        ),

                        get filtered() {
                            const q = this.query.trim().toLowerCase();

                            return this.options.filter(option => {
                                // skip already selected
                                if (this.selected.some(s => s.value === option.value)) {
                                    return false;
                                }

                                if (!q) return true;

                                return (
                                    option.label.toLowerCase().includes(q) ||
                                    (option.group || '').toLowerCase().includes(q)
                                );
                            });
                        },

                        add(item) {
                            if (!this.selected.some(s => s.value === item.value)) {
                                this.selected.push(item);
                            }
                            this.query = '';
                            this.isOpen = false;
                        },

                        remove(value) {
                            this.selected = this.selected.filter(s => s.value !== value);
                        }
                    };
                };
            });
        </script>
    @endpush

</x-app-layout>
