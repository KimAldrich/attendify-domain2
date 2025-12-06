<?php

// app/Http/Controllers/Classroom/AdminClassroomController.php
namespace App\Http\Controllers\Classroom;

use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Room;
use App\Models\AcademicPeriod;
use App\Models\CourseSection;
use App\Models\AttendanceRecord;
use App\Models\SectionEnrollment;
use App\Models\ClassPhotoAttendance;
use App\Models\ClassSchedule; 
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AdminClassroomController extends Controller
{
    // --- PAGE: Rooms table ---
public function rooms(Request $request)
{
    $search = trim((string) $request->query('q', ''));
    $status = $request->query('status'); // 'enabled', 'disabled', or null

    $roomsQuery = Room::query()
        ->orderBy('room_number');

    // Text search on room number or endpoint
    if ($search !== '') {
        $roomsQuery->where(function ($q) use ($search) {
            $q->where('room_number', 'like', "%{$search}%")
              ->orWhere('camera_endpoint', 'like', "%{$search}%");
        });
    }

    // Status filter
    if ($status === 'enabled') {
        $roomsQuery->where('is_face_recognition_enabled', true);
    } elseif ($status === 'disabled') {
        $roomsQuery->where('is_face_recognition_enabled', false);
    }

    $rooms = $roomsQuery
        ->paginate(25)
        ->withQueryString();

    // --- Summary cards (global, not filtered) ---
    $enabledCount = Room::where('is_face_recognition_enabled', true)->count();
    $disabledCount = Room::where('is_face_recognition_enabled', false)->count();

    $roomsWithIpCount = Room::whereNotNull('camera_endpoint')
        ->where('camera_endpoint', '!=', '')
        ->count();

    $enabledWithIpCount = Room::where('is_face_recognition_enabled', true)
        ->whereNotNull('camera_endpoint')
        ->where('camera_endpoint', '!=', '')
        ->count();

    $percentEnabledWithIp = $roomsWithIpCount > 0
        ? round(($enabledWithIpCount / $roomsWithIpCount) * 100)
        : 0;

    return view('classroom.roles.admin.rooms.index', [
        'rooms'                  => $rooms,
        'search'                 => $search,
        'status'                 => $status,
        'roomsEnabledCount'      => $enabledCount,
        'roomsDisabledCount'     => $disabledCount,
        'roomsWithIpCount'       => $roomsWithIpCount,
        'percentEnabledWithIp'   => $percentEnabledWithIp,
    ]);
}

    public function storeRoom(Request $request)
    {
        $data = $request->validate([
            'room_number'                 => ['required', 'string', 'max:100'],
            'camera_endpoint'             => ['nullable', 'string', 'max:255'],
            'is_face_recognition_enabled' => ['nullable', 'boolean'],
        ]);

        // Checkbox fix: if not sent, it should be false
        $data['is_face_recognition_enabled'] = (bool) ($data['is_face_recognition_enabled'] ?? false);

        Room::create($data);
        Alert::toast('New room created!', 'success')->autoClose(6000);
        return redirect()
            ->route('classroom.admin.rooms.index')
            ->with('status', 'room-created');
    }

        public function updateRoom(Request $request, Room $room)
    {
        // Simple safe update; no error bag so we don't fight your create modal logic.
        $data = $request->only([
            'room_number',
            'camera_endpoint',
            'is_face_recognition_enabled',
        ]);

        $data['room_number'] = trim((string) ($data['room_number'] ?? ''));

        if ($data['room_number'] === '') {
            return back()->with('status', 'room-update-failed');
        }

        $data['is_face_recognition_enabled'] = (bool) ($data['is_face_recognition_enabled'] ?? false);

        $room->update($data);

        Alert::toast('Room updated!', 'success')->autoClose(6000);
        return redirect()
            ->route('classroom.admin.rooms.index')
            ->with('status', 'room-updated');
    }

    public function destroyRoom(Room $room)
    {
        $room->delete();
        Alert::toast('Room deleted!', 'success')->autoClose(6000);
        return redirect()
            ->route('classroom.admin.rooms.index')
            ->with('status', 'room-deleted');
    }

    // --- PAGE: Instructors (AY-Term selector + instructors table) ---
public function instructors(Request $request)
{
    $periodId    = $request->integer('period_id');
    $search      = trim((string) $request->query('q', ''));
    $minCourses  = $request->filled('min_courses')
        ? (int) $request->query('min_courses')
        : null;
    $maxCourses  = $request->filled('max_courses')
        ? (int) $request->query('max_courses')
        : null;

    $periods = AcademicPeriod::orderByDesc('year_start')->get();

    $selectedPeriod = $periodId
        ? $periods->firstWhere('id', $periodId)
        : AcademicPeriod::current()->first() ?? $periods->first();

    if (! $selectedPeriod) {
        return view('classroom.roles.admin.instructors.index', [
            'periods'        => $periods,
            'selectedPeriod' => null,
            'instructors'    => collect(),
            'search'         => $search,
            'minCourses'     => $minCourses,
            'maxCourses'     => $maxCourses,
        ]);
    }

    $instructorsQuery = User::query()
        ->where('is_teaching', true)
        ->whereHas('taughtSections', function ($q) use ($selectedPeriod) {
            $q->where('academic_period_id', $selectedPeriod->id);
        })
        // search by name or email
        ->when($search !== '', function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        })
        ->withCount([
            'taughtSections as courses_count' => function ($q) use ($selectedPeriod) {
                $q->where('academic_period_id', $selectedPeriod->id);
            },
        ]);

    // range filter on # of courses
    if (! is_null($minCourses)) {
        $instructorsQuery->having('courses_count', '>=', $minCourses);
    }
    if (! is_null($maxCourses)) {
        $instructorsQuery->having('courses_count', '<=', $maxCourses);
    }

    $instructors = $instructorsQuery
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->paginate(25)
        ->withQueryString();

    return view('classroom.roles.admin.instructors.index', [
        'periods'        => $periods,
        'selectedPeriod' => $selectedPeriod,
        'instructors'    => $instructors,
        'search'         => $search,
        'minCourses'     => $minCourses,
        'maxCourses'     => $maxCourses,
    ]);
}


// --- PAGE: Courses (under selected AY-Term + Instructor) ---
public function courses(Request $request)
{
    $periodId     = $request->integer('period_id');
    $instructorId = $request->integer('instructor_id');
    $search       = trim((string) $request->query('q', ''));

    // All periods (for selector)
    $periods = AcademicPeriod::orderByDesc('year_start')->get();

    // Pick selected / current / first
    $selectedPeriod = $periodId
        ? $periods->firstWhere('id', $periodId)
        : AcademicPeriod::current()->first() ?? $periods->first();

    // All instructors (for selector)
    $instructors = User::where('is_teaching', true)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $selectedInstructor = $instructorId
        ? $instructors->firstWhere('id', $instructorId)
        : null;

    // --------------------------
    // Base sections query (for table)
    // --------------------------
    $sectionsQuery = CourseSection::with('instructor')
        ->when($selectedPeriod, fn ($q) => $q->where('academic_period_id', $selectedPeriod->id))
        ->when($selectedInstructor, fn ($q) => $q->where('instructor_id', $selectedInstructor->id));

    // apply search on course code/name
    if ($search !== '') {
        $sectionsQuery->where(function ($q) use ($search) {
            $q->where('course_code', 'like', "%{$search}%")
              ->orWhere('course_name', 'like', "%{$search}%");
        });
    }

    $sections = $sectionsQuery
        ->withCount('enrollments as students_count')
        ->orderBy('course_code')
        ->orderBy('section_label')
        ->paginate(15)
        ->withQueryString();

    // presets for the hybrid course code input (based on existing sections)
    $coursePresets = CourseSection::select('course_code', 'course_name')
        ->whereNotNull('course_code')
        ->groupBy('course_code', 'course_name')
        ->orderBy('course_code')
        ->get();

    // --------------------------
    // Build instructor timetable (6 AM – 6 PM)
    // --------------------------

    // If no instructor selected, timetable will be empty
    $timetable = [];

    if ($selectedInstructor) {
        $daysOfWeek = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $startOfDay   = Carbon::createFromTime(6, 0, 0);   // 6:00 AM
        $endOfDay     = Carbon::createFromTime(18, 0, 0);  // 6:00 PM
        $totalMinutes = $startOfDay->diffInMinutes($endOfDay); // 720

        // Base structure
        foreach ($daysOfWeek as $dow => $label) {
            $timetable[$dow] = [
                'label'  => $label,
                'blocks' => [],
            ];
        }

        // Separate query for all sections (no pagination limit) for timetable
        $timetableSectionsQuery = CourseSection::query()
            ->with([
                'schedules' => function ($q) {
                    $q->orderBy('day_of_week')
                      ->orderBy('start_time');
                },
                'schedules.room',
            ])
            ->where('instructor_id', $selectedInstructor->id)
            ->when($selectedPeriod, fn ($q) => $q->where('academic_period_id', $selectedPeriod->id));

        // Apply same search to timetable if used
        if ($search !== '') {
            $timetableSectionsQuery->where(function ($q) use ($search) {
                $q->where('course_code', 'like', "%{$search}%")
                  ->orWhere('course_name', 'like', "%{$search}%");
            });
        }

        $timetableSections = $timetableSectionsQuery->get();

        // Collect blocks
        foreach ($timetableSections as $section) {
            foreach ($section->schedules as $schedule) {
                $dow = (int) $schedule->day_of_week;

                if (! isset($timetable[$dow])) {
                    continue; // just in case
                }

                // Original times
                $origStart = Carbon::parse($schedule->start_time);
                $origEnd   = Carbon::parse($schedule->end_time);

                // Completely outside 6–18? skip
                if ($origEnd <= $startOfDay || $origStart >= $endOfDay) {
                    continue;
                }

                // Clamp to 6–18 for display
                $start = $origStart->copy();
                $end   = $origEnd->copy();

                if ($start < $startOfDay) {
                    $start = $startOfDay->copy();
                }
                if ($end > $endOfDay) {
                    $end = $endOfDay->copy();
                }

                $startMinutes = $startOfDay->diffInMinutes($start);
                $endMinutes   = $startOfDay->diffInMinutes($end);
                $widthMinutes = max(5, $endMinutes - $startMinutes); // min visual width

                $leftPercent  = ($startMinutes / $totalMinutes) * 100;
                $widthPercent = ($widthMinutes / $totalMinutes) * 100;

                $timetable[$dow]['blocks'][] = [
                    'section'      => $section,
                    'schedule'     => $schedule,
                    'room_label'   => optional($schedule->room)->room_number ?? 'Room ?',
                    'time_label'   => $origStart->format('g:i A') . ' – ' . $origEnd->format('g:i A'),
                    'start_min'    => $startMinutes,
                    'end_min'      => $endMinutes,
                    'left'         => $leftPercent,
                    'width'        => $widthPercent,
                    'lane'         => 0, // filled below
                ];
            }
        }

        // Assign lanes per day so overlapping blocks stack vertically
        foreach ($timetable as $dow => $day) {
            $blocks = $day['blocks'];

            // Sort by start time
            usort($blocks, function ($a, $b) {
                return $a['start_min'] <=> $b['start_min'];
            });

            $lanes = []; // laneIndex => lastEndMin

            foreach ($blocks as &$block) {
                $laneIndex = 0;

                // find lane with no overlap
                while (isset($lanes[$laneIndex]) && $block['start_min'] < $lanes[$laneIndex]) {
                    $laneIndex++;
                }

                $block['lane'] = $laneIndex;
                $lanes[$laneIndex] = $block['end_min'];
            }
            unset($block);

            $timetable[$dow]['blocks']     = $blocks;
            $timetable[$dow]['lane_count'] = count($lanes);
        }
    }

    return view('classroom.roles.admin.courses.index', [
        'periods'            => $periods,
        'selectedPeriod'     => $selectedPeriod,
        'instructors'        => $instructors,
        'selectedInstructor' => $selectedInstructor,
        'sections'           => $sections,
        'search'             => $search,
        'coursePresets'      => $coursePresets,
        'timetable'          => $timetable, // 👈 for the kanban-style board
    ]);
}


public function storeSection(Request $request)
{
    $validated = $request->validate([
        'academic_period_id' => ['required', 'exists:academic_periods,id'],
        'instructor_id'      => ['required', 'exists:users,id'],
        'course_code'        => ['required', 'string', 'max:50'],
        'course_name'        => ['required', 'string', 'max:255'],
        'section_label'      => ['required', 'string', 'max:100'],
    ]);

    CourseSection::create($validated);

    Alert::toast('Section created successfully.', 'success')->autoClose(6000);

    return redirect()->route('classroom.admin.courses.index', [
        'period_id'     => $validated['academic_period_id'],
        'instructor_id' => $validated['instructor_id'],
    ]);
}


public function updateSection(Request $request, CourseSection $section)
{
    $validated = $request->validate([
        'course_code'   => ['required', 'string', 'max:50'],
        'course_name'   => ['required', 'string', 'max:255'],
        'section_label' => ['required', 'string', 'max:100'],
    ]);

    $section->update($validated);

    Alert::toast('Section updated successfully.', 'success')->autoClose(6000);

    return redirect()->route('classroom.admin.courses.index', [
        'period_id'     => $section->academic_period_id,
        'instructor_id' => $section->instructor_id,
    ]);
}


public function destroySection(Request $request, CourseSection $section)
{
    $periodId     = $section->academic_period_id;
    $instructorId = $section->instructor_id;

    $section->delete();

    Alert::toast('Section deleted.', 'success')->autoClose(6000);

    return redirect()->route('classroom.admin.courses.index', [
        'period_id'     => $periodId,
        'instructor_id' => $instructorId,
    ]);
}


public function section(Request $request, CourseSection $section)
{
    $search = trim((string) $request->query('q', ''));

    $section->load(['instructor', 'schedules.room', 'academicPeriod']);

    $enrollmentsQuery = $section->enrollments()->with('student');

    if ($search !== '') {
        $enrollmentsQuery->whereHas('student', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%");
        });
    }

    $enrollments = $enrollmentsQuery->paginate(25)->withQueryString();

    // Per-student attendance aggregates for students on this page
    $studentIds = $enrollments->pluck('student_id')->all();

    $perStudentStats = AttendanceRecord::selectRaw("
            student_id,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END)  as present_count,
            SUM(CASE WHEN status = 'tardy'   THEN 1 ELSE 0 END)  as tardy_count,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END)  as excused_count,
            SUM(CASE WHEN status = 'absent'  THEN 1 ELSE 0 END)  as absent_count
        ")
        ->where('course_section_id', $section->id)
        ->whereIn('student_id', $studentIds)
        ->groupBy('student_id')
        ->get()
        ->keyBy('student_id');

    $rooms = Room::orderBy('room_number')->get();

    $studentsForSelect = User::query()
        ->whereNotNull('student_number')
        ->where('student_number', '!=', '')
        ->orderBy('student_number')
        ->limit(500) // safety cap
        ->get();

    // 🔹 Summary metrics
    $totalStudents = $section->enrollments()->count();

    // students whose face profile is NOT active
    $inactiveFaceCount = $section->enrollments()
        ->whereHas('student', function ($q) {
            $q->whereNull('face_recognition_path')
              ->orWhere('face_recognition_path', '');
        })
        ->count();

    // overall attendance percentage for the section
    $attendanceAgg = AttendanceRecord::where('course_section_id', $section->id)
        ->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('present','tardy') THEN 1 ELSE 0 END) as present_like
        ")
        ->first();

    $overallPresentPct = 0.0;
    if ($attendanceAgg && $attendanceAgg->total > 0) {
        $overallPresentPct = round(
            ($attendanceAgg->present_like / $attendanceAgg->total) * 100,
            2
        );
    }

    $photoGroups = ClassPhotoAttendance::where('course_section_id', $section->id)
        ->orderByDesc('meeting_date')
        ->orderByDesc('created_at')
        ->get()
        ->groupBy('meeting_date');

    $importFailures = session('import_failures', []);

    return view('classroom.roles.admin.sections.show', [
        'section'     => $section,
        'enrollments' => $enrollments,
        'search'      => $search,
        'rooms'       => $rooms,
        'studentsForSelect' => $studentsForSelect,
        'summaryTotalStudents' => $totalStudents,
        'summaryInactiveFace'  => $inactiveFaceCount,
        'summaryPresentPct'    => $overallPresentPct,
        'importFailures'       => $importFailures,
        'photoGroups'             => $photoGroups,  
        'perStudentStats'      => $perStudentStats,
    ]);
}



    public function storeSchedule(Request $request, CourseSection $section)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'room_id'     => ['required', 'exists:rooms,id'],
        ],[
            'end_time.after' => 'End time must be later than the start time.',
        ]
        );

        $validated['course_section_id'] = $section->id;

        ClassSchedule::create($validated);

        Alert::toast('Schedule added successfully!', 'success')->autoClose(5000);

        return back();
    }


    public function updateSchedule(Request $request, CourseSection $section, ClassSchedule $schedule)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'room_id'     => ['required', 'exists:rooms,id'],
        ]);

        $schedule->update($validated);

        Alert::toast('Schedule updated!', 'success')->autoClose(5000);

        return back();
    }


    public function destroySchedule(CourseSection $section, ClassSchedule $schedule)
    {
        $schedule->delete();

        Alert::toast('Schedule removed!', 'success')->autoClose(5000);

        return back();
    }

public function storeStudent(Request $request, CourseSection $section)
{
    $validated = $request->validate([
        'student_number' => ['required', 'string'],
    ]);

    $studentNumber = trim($validated['student_number']);

    $student = User::where('student_number', $studentNumber)->first();

    if (! $student) {
        Alert::toast("No student found with number {$studentNumber}.", 'error')->autoClose(7000);
        return back();
    }

    // prevent duplicate
    if ($section->enrollments()->where('student_id', $student->id)->exists()) {
        Alert::toast('Student already enrolled in this section.', 'warning')->autoClose(5000);
        return back();
    }

    $section->enrollments()->create([
        'student_id' => $student->id,
    ]);

    Alert::toast("Student {$student->full_name} added!", 'success')->autoClose(5000);
    return back();
}

    public function destroyStudent(CourseSection $section, SectionEnrollment $enrollment)
    {
        $enrollment->delete();

        Alert::toast('Student removed from section.', 'success')->autoClose(5000);
        return back();
    }

    public function notifyStudentFace(CourseSection $section, User $student)
    {
        $link = route('profile.me');

        Notification::create([
            'user_id' => $student->id,
            'title'   => 'Face-recognition portrait required',
            'message' => sprintf(
                'Please upload your face-recognition portrait for %s.',
                $section->display_name // uses CourseSection accessor
            ),
            'link'    => $link,
            'status'  => 'unread',
        ]);

        Alert::toast("Notification sent to {$student->full_name}.", 'success')->autoClose(6000);

        return back();
    }

public function importStudents(Request $request, CourseSection $section)
{
    $request->validate([
        'csv_file' => ['required', 'file', 'mimes:csv,txt'],
    ]);

    $path = $request->file('csv_file')->getRealPath();
    $handle = fopen($path, 'r');

    if (! $handle) {
        Alert::toast('Unable to read CSV file.', 'error')->autoClose(7000);
        return back();
    }

    $rowIndex = 0;
    $newStudentNumbers = [];

    while (($row = fgetcsv($handle)) !== false) {
        // Skip metadata rows
        if ($rowIndex < 3) {
            $rowIndex++;
            continue;
        }

        // Skip header row
        if ($rowIndex === 3) {
            $rowIndex++;
            continue;
        }

        $rowIndex++;

        // Columns: [Student Number, Student Name, Contact]
        $studentNumber = trim($row[0] ?? '');

        if ($studentNumber === '' || str_starts_with($studentNumber, '#')) {
            continue;
        }

        $newStudentNumbers[] = $studentNumber;
    }

    fclose($handle);

    // De-duplicate
    $newStudentNumbers = array_values(array_unique($newStudentNumbers));

    // Collect failed student number list
    $failedNumbers = [];

    // Current enrollments (by student_number)
    $section->load('enrollments.student');

    $currentByNumber = [];
    foreach ($section->enrollments as $enrollment) {
        $number = $enrollment->student->student_number;
        if ($number) {
            $currentByNumber[$number] = $enrollment;
        }
    }

    $currentNumbers = array_keys($currentByNumber);

    // Determine additions & removals
    $toRemove = array_diff($currentNumbers, $newStudentNumbers);
    $toAdd    = array_diff($newStudentNumbers, $currentNumbers);

    $removedCount = 0;
    $addedCount   = 0;

    // ============================
    // REMOVE enrollments
    // ============================
    foreach ($toRemove as $number) {
        $enrollment = $currentByNumber[$number] ?? null;

        if (! $enrollment) {
            continue;
        }

        $enrollment->delete();
        $removedCount++;
    }

    // ============================
    // ADD enrollments
    // ============================
    foreach ($toAdd as $number) {

        $student = User::where('student_number', $number)->first();

        if (! $student) {
            // REAL failure → Only student numbers not found in DB
            $failedNumbers[] = $number;
            continue;
        }

        // Extra safety
        $already = $section->enrollments()
            ->where('student_id', $student->id)
            ->exists();

        if ($already) {
            // Do not count as failure
            continue;
        }

        $section->enrollments()->create([
            'student_id' => $student->id,
        ]);

        $addedCount++;
    }

    // De-duplicate failed list
    $failedNumbers = array_values(array_unique($failedNumbers));

    // Toast
    if (count($failedNumbers) > 0) {
        Alert::toast(
            "Roster updated — {$addedCount} added, {$removedCount} removed. Some student numbers were invalid.",
            'warning'
        )->autoClose(9000);
    } else {
        Alert::toast(
            "Roster updated successfully. Added {$addedCount}, removed {$removedCount}.",
            'success'
        )->autoClose(8000);
    }

    // Pass failures to view
    return redirect()
        ->route('classroom.admin.sections.show', $section)
        ->with('import_failures', $failedNumbers);
}

public function exportStudents(CourseSection $section)
{
    // Make sure we have the related models
    $section->load(['academicPeriod', 'enrollments.student']);

    $periodLabel  = $section->academicPeriod?->display_label ?? 'AY-Term';
    $courseCode   = $section->course_code ?? 'COURSE';
    $sectionLabel = $section->section_label ?? 'SECTION';

    // For human-friendly first row
    $courseLine   = trim(($section->course_code ? $section->course_code . ' - ' : '') . ($section->course_name ?? 'Course Name'));

    // For filename: AY-Term_CourseCode-SectionLabel_roster
    $periodSlug  = Str::slug($periodLabel, '-');
    $courseSlug  = Str::slug($courseCode, '-');
    $sectionSlug = Str::slug($sectionLabel, '-');

    $filename = "{$periodSlug}_{$courseSlug}-{$sectionSlug}_roster.csv";

    $handle = fopen('php://temp', 'w');

    // Row 0: AY-Term: Course Code - Course Name
    fputcsv($handle, [
        "{$periodLabel}: {$courseLine}"
    ]);

    // Row 1: Section label + instruction
    fputcsv($handle, [
        "Section {$sectionLabel} - EDIT ONLY the 'Student Number' column below. " .
        "Add student numbers to enroll, remove them to unenroll. Do NOT edit the header layout."
    ]);

    // Row 2: blank spacer
    fputcsv($handle, ['']);

    // Row 3: Headers
    fputcsv($handle, [
        'Student Number',
        'Student Name',
        'Student Contact Number',
    ]);

    $enrollments = $section->enrollments;

    if ($enrollments->isEmpty()) {
        // Example row if empty
        fputcsv($handle, [
            '25UR0001',
            'Dela Cruz, Juan',
            '09xxxxxxxxxx',
        ]);
    } else {
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            fputcsv($handle, [
                $student->student_number,
                $student->full_name,
                $student->cp_no,
            ]);
        }
    }

    rewind($handle);
    $content = stream_get_contents($handle);
    fclose($handle);

    return response($content)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
}

public function studentAttendance(CourseSection $section, User $student)
{
    // Safety: make sure this student belongs to the section
    $isEnrolled = $section->enrollments()
        ->where('student_id', $student->id)
        ->exists();

    if (! $isEnrolled) {
        // ⬇️ Instead of abort(404), just return empty records
        return response()->json([
            'student' => $student->full_name,
            'records' => [],
        ]);
    }

    $records = AttendanceRecord::where('course_section_id', $section->id)
        ->where('student_id', $student->id)
        ->orderByDesc('meeting_date')
        ->orderByDesc('created_at')
        ->get();

    $formatted = $records->map(function ($rec) {
        // --- meeting_date ---
        $meetingDate = $rec->meeting_date;

        if ($meetingDate instanceof CarbonInterface) {
            $meetingDate = $meetingDate->format('M d, Y');
        } elseif (! empty($meetingDate)) {
            $meetingDate = Carbon::parse($meetingDate)->format('M d, Y');
        } else {
            $meetingDate = null;
        }

        // --- time_in ---
        $timeIn = '—';
        if ($rec->time_in && in_array($rec->status, ['present', 'tardy'], true)) {
            $raw = $rec->time_in;

            if ($raw instanceof CarbonInterface) {
                $timeIn = $raw->format('h:i A');
            } else {
                $timeIn = Carbon::parse($raw)->format('h:i A');
            }
        }

        return [
            'id'           => $rec->id,
            'meeting_date' => $meetingDate,
            'status'       => $rec->status,
            'time_in'      => $timeIn,
        ];
    })->values();

    return response()->json([
        'student' => $student->full_name,
        'records' => $formatted,
    ]);
}



}
