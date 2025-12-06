<?php

namespace App\Http\Controllers\Classroom;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassSchedule;
use App\Models\CourseSection;
use App\Models\Notification;
use App\Models\Room;
use App\Models\SectionEnrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Storage;
use App\Models\ClassPhotoAttendance;
use App\Models\ExcuseLetter;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonInterface;

class InstructorSectionController extends Controller
{
    protected function ensureTeachingUser($user): void
    {
        if (! $user->is_teaching && ! $user->hasRole('admin')) {
            abort(403, 'You are not configured as a teaching faculty.');
        }
    }

    protected function ensureOwnSection($user, CourseSection $section): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        if (! $user->is_teaching || $section->instructor_id !== $user->id) {
            abort(403, 'You are not assigned to this section.');
        }
    }

    /**
     * GET /classroom/instructor/sections/{section}
     * Route: classroom.instructor.sections.show
     *
     * Section overview (schedules + roster + metrics) for this instructor.
     */
public function show(Request $request, CourseSection $section)
{
    $user = $request->user();
    $this->ensureOwnSection($user, $section);

    $search = trim((string) $request->query('q', ''));

    $section->load(['instructor', 'schedules.room', 'academicPeriod']);

    // Base enrollments query
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

    $enrollments = $enrollmentsQuery
        ->paginate(25)
        ->withQueryString();

        // ---- NEW: per-student attendance aggregates -----------------------
    $perStudentStats = AttendanceRecord::where('course_section_id', $section->id)
        ->selectRaw("
            student_id,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END)  AS present_count,
            SUM(CASE WHEN status = 'tardy'   THEN 1 ELSE 0 END)  AS tardy_count,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END)  AS excused_count,
            SUM(CASE WHEN status = 'absent'  THEN 1 ELSE 0 END)  AS absent_count,
            COUNT(*)                                            AS total_classes
        ")
        ->groupBy('student_id')
        ->get()
        ->keyBy('student_id');
    // -------------------------------------------------------------------

    $rooms = Room::orderBy('room_number')->get();

    $studentsForSelect = User::query()
        ->whereNotNull('student_number')
        ->where('student_number', '!=', '')
        ->orderBy('student_number')
        ->limit(500)
        ->get();

    // Summary metrics (already correct)
    $totalStudents = $section->enrollments()->count();

    $inactiveFaceCount = $section->enrollments()
        ->whereHas('student', function ($q) {
            $q->whereNull('face_recognition_path')
                ->orWhere('face_recognition_path', '');
        })
        ->count();

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

    return view('classroom.roles.instructor.sections.show', [
        'section'                 => $section,
        'enrollments'             => $enrollments,
        'search'                  => $search,
        'rooms'                   => $rooms,
        'studentsForSelect'       => $studentsForSelect,
        'summaryTotalStudents'    => $totalStudents,
        'summaryInactiveFace'     => $inactiveFaceCount,
        'summaryPresentPct'       => $overallPresentPct,
        'importFailures'          => $importFailures,
        'photoGroups'             => $photoGroups,
        'perStudentStats'         => $perStudentStats,
    ]);
}


    /**
     * SCHEDULE MANAGEMENT
     */
    public function storeSchedule(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'room_id'     => ['required', 'exists:rooms,id'],
        ], [
            'end_time.after' => 'End time must be later than the start time.',
        ]);

        $validated['course_section_id'] = $section->id;

        ClassSchedule::create($validated);

        Alert::toast('Schedule added successfully!', 'success')->autoClose(5000);

        return back();
    }

    public function updateSchedule(Request $request, CourseSection $section, ClassSchedule $schedule)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        if ($schedule->course_section_id !== $section->id) {
            abort(404);
        }

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

    public function destroySchedule(Request $request, CourseSection $section, ClassSchedule $schedule)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        if ($schedule->course_section_id !== $section->id) {
            abort(404);
        }

        $schedule->delete();

        Alert::toast('Schedule removed!', 'success')->autoClose(5000);

        return back();
    }

    /**
     * STUDENT ENROLLMENT
     */
    public function storeStudent(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $validated = $request->validate([
            'student_number' => ['required', 'string'],
        ]);

        $studentNumber = trim($validated['student_number']);

        $student = User::where('student_number', $studentNumber)->first();

        if (! $student) {
            Alert::toast("No student found with number {$studentNumber}.", 'error')->autoClose(7000);
            return back();
        }

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

    public function destroyStudent(Request $request, CourseSection $section, SectionEnrollment $enrollment)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        if ($enrollment->course_section_id !== $section->id) {
            abort(404);
        }

        $enrollment->delete();

        Alert::toast('Student removed from section.', 'success')->autoClose(5000);

        return back();
    }

    
public function studentAttendance(Request $request, $sectionId, $studentId)
{
    // Logged-in instructor
    $instructor = $request->user();

    // Manually resolve section & student (no implicit binding)
    $section = CourseSection::findOrFail($sectionId);
    $student = User::findOrFail($studentId);

    // Ensure this instructor owns the section (or is admin)
    $this->ensureOwnSection($instructor, $section);

    // Safety: make sure this student belongs to the section
    $isEnrolled = $section->enrollments()
        ->where('student_id', $student->id)
        ->exists();

    if (! $isEnrolled) {
        return response()->json([
            'student' => $student->full_name,
            'records' => [],
        ]);
    }

    // Fetch ALL attendance records for this student in this section
    $records = AttendanceRecord::where('course_section_id', $section->id)
        ->where('student_id', $student->id)
        ->orderByDesc('meeting_date')
        ->orderByDesc('created_at')
        ->get();

    $formatted = $records->map(function ($rec) {
        // meeting_date
        $meetingDate = $rec->meeting_date;
        if ($meetingDate instanceof \Carbon\CarbonInterface) {
            $meetingDate = $meetingDate->format('M d, Y');
        } elseif (! empty($meetingDate)) {
            $meetingDate = \Carbon\Carbon::parse($meetingDate)->format('M d, Y');
        } else {
            $meetingDate = null;
        }

        // time_in (only for present/tardy)
        $timeIn = '—';
        if ($rec->time_in && in_array($rec->status, ['present', 'tardy'], true)) {
            $raw = $rec->time_in;
            if ($raw instanceof \Carbon\CarbonInterface) {
                $timeIn = $raw->format('h:i A');
            } else {
                $timeIn = \Carbon\Carbon::parse($raw)->format('h:i A');
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

    public function notifyStudentFace(CourseSection $section, User $student)
    {
        $user = request()->user();
        $this->ensureOwnSection($user, $section);

        $link = route('profile.me');

        Notification::create([
            'user_id' => $student->id,
            'title'   => 'Face-recognition portrait required',
            'message' => sprintf(
                'Please upload your face-recognition portrait for %s.',
                $section->display_name
            ),
            'link'    => $link,
            'status'  => 'unread',
        ]);

        Alert::toast("Notification sent to {$student->full_name}.", 'success')->autoClose(6000);

        return back();
    }

    /**
     * CSV IMPORT / EXPORT
     */
    public function importStudents(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path   = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            Alert::toast('Unable to read CSV file.', 'error')->autoClose(7000);
            return back();
        }

        $rowIndex         = 0;
        $newStudentNumbers = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($rowIndex < 3) {
                $rowIndex++;
                continue;
            }

            if ($rowIndex === 3) {
                $rowIndex++;
                continue;
            }

            $rowIndex++;

            $studentNumber = trim($row[0] ?? '');

            if ($studentNumber === '' || str_starts_with($studentNumber, '#')) {
                continue;
            }

            $newStudentNumbers[] = $studentNumber;
        }

        fclose($handle);

        $newStudentNumbers = array_values(array_unique($newStudentNumbers));
        $failedNumbers     = [];

        $section->load('enrollments.student');

        $currentByNumber = [];
        foreach ($section->enrollments as $enrollment) {
            $number = $enrollment->student->student_number;
            if ($number) {
                $currentByNumber[$number] = $enrollment;
            }
        }

        $currentNumbers = array_keys($currentByNumber);

        $toRemove = array_diff($currentNumbers, $newStudentNumbers);
        $toAdd    = array_diff($newStudentNumbers, $currentNumbers);

        $removedCount = 0;
        $addedCount   = 0;

        // Remove enrollments
        foreach ($toRemove as $number) {
            $enrollment = $currentByNumber[$number] ?? null;

            if (! $enrollment) {
                continue;
            }

            $enrollment->delete();
            $removedCount++;
        }

        // Add enrollments
        foreach ($toAdd as $number) {
            $student = User::where('student_number', $number)->first();

            if (! $student) {
                $failedNumbers[] = $number;
                continue;
            }

            $already = $section->enrollments()
                ->where('student_id', $student->id)
                ->exists();

            if ($already) {
                continue;
            }

            $section->enrollments()->create([
                'student_id' => $student->id,
            ]);

            $addedCount++;
        }

        $failedNumbers = array_values(array_unique($failedNumbers));

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

        return redirect()
            ->route('classroom.instructor.sections.show', $section)
            ->with('import_failures', $failedNumbers);
    }

    public function exportStudents(CourseSection $section)
    {
        $user = request()->user();
        $this->ensureOwnSection($user, $section);

        $section->load(['academicPeriod', 'enrollments.student']);

        $periodLabel  = $section->academicPeriod?->display_label ?? 'AY-Term';
        $courseCode   = $section->course_code ?? 'COURSE';
        $sectionLabel = $section->section_label ?? 'SECTION';

        $courseLine = trim(
            ($section->course_code ? $section->course_code . ' - ' : '')
            . ($section->course_name ?? 'Course Name')
        );

        $periodSlug  = Str::slug($periodLabel, '-');
        $courseSlug  = Str::slug($courseCode, '-');
        $sectionSlug = Str::slug($sectionLabel, '-');

        $filename = "{$periodSlug}_{$courseSlug}-{$sectionSlug}_roster.csv";

        $handle = fopen('php://temp', 'w');

        fputcsv($handle, [
            "{$periodLabel}: {$courseLine}",
        ]);

        fputcsv($handle, [
            "Section {$sectionLabel} - EDIT ONLY the 'Student Number' column below. " .
            "Add student numbers to enroll, remove them to unenroll. Do NOT edit the header layout.",
        ]);

        fputcsv($handle, ['']);

        fputcsv($handle, [
            'Student Number',
            'Student Name',
            'Student Contact Number',
        ]);

        $enrollments = $section->enrollments;

        if ($enrollments->isEmpty()) {
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

    /**
     * DAILY ATTENDANCE VIEWS
     */

    public function sectionsToday(Request $request)
    {
        $user = $request->user();
        $this->ensureTeachingUser($user);

        $today     = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        $sections = CourseSection::query()
            ->where('instructor_id', $user->id)
            ->forDayOfWeek($dayOfWeek)
            ->with([
                'schedules' => function ($q) use ($dayOfWeek) {
                    $q->where('day_of_week', $dayOfWeek)
                      ->orderBy('start_time');
                },
                'schedules.room',
                'academicPeriod',
            ])
            ->orderBy('course_code')
            ->orderBy('section_label')
            ->get();

        return view('classroom.roles.instructor.sections-today', [
            'today'    => $today,
            'sections' => $sections,
        ]);
    }

    public function attendanceToday(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        // Load schedules for today + academic period
        $section->load([
            'schedules' => function ($q) use ($dayOfWeek) {
                $q->where('day_of_week', $dayOfWeek)
                ->orderBy('start_time');
            },
            'schedules.room',
            'academicPeriod',
        ]);

        // Determine earliest start time for today (for the 15-min present/tardy UI)
        $classStartTime = null;
        if ($section->schedules->isNotEmpty()) {
            $classStartTime = Carbon::parse($section->schedules->first()->start_time);
        }

        // --- Enrollments + students (base data for both tabs) --------------------
        $enrollments = $section->enrollments()
            ->with('student')
            ->get()
            ->sortBy(function ($enrollment) {
                $student = $enrollment->student;
                return trim(($student->last_name ?? '') . ' ' . ($student->first_name ?? ''));
            })
            ->values();

        $totalStudents = $enrollments->count();

        // --- Today's attendance records (for this section + date) ---------------
        $todayRecords = AttendanceRecord::where('course_section_id', $section->id)
            ->forDate($today) // assumes you already have this local scope
            ->get();

        // Keyed by student_id for quick lookup
        $recordsByStudent = $todayRecords->keyBy('student_id');

        // --- Summary counts for cards -------------------------------------------
        $presentCount = $todayRecords->where('status', 'present')->count();
        $tardyCount   = $todayRecords->where('status', 'tardy')->count();
        $excusedCount = $todayRecords->where('status', 'excused')->count();
        $absentCount  = $todayRecords->where('status', 'absent')->count();

        $withRecordCount = $recordsByStudent->count();
        $noRecordCount   = max(0, $totalStudents - $withRecordCount);

        $attendancePct = 0.0;
        if ($totalStudents > 0) {
            // Present + tardy out of total enrolled
            $attendancePct = round((($presentCount + $tardyCount) / $totalStudents) * 100, 1);
        }

        // --- Filters -------------------------------------------------------------
        $pendingQuery  = trim((string) $request->query('pending_q', ''));
        $recordedQuery = trim((string) $request->query('recorded_q', ''));
        $statusFilter  = trim((string) $request->query('status', ''));

        // --- Split into pending vs recorded -------------------------------------
        $pending = [];
        $recorded = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $record  = $recordsByStudent->get($student->id);

            if ($record) {
                // Recorded list
                $recorded[] = [
                    'enrollment' => $enrollment,
                    'student'    => $student,
                    'record'     => $record,
                ];
            } else {
                // Pending list
                $pending[] = [
                    'enrollment' => $enrollment,
                    'student'    => $student,
                ];
            }
        }

        // Apply filters (simple in-memory filtering for now)
        if ($pendingQuery !== '') {
            $pending = array_values(array_filter($pending, function ($row) use ($pendingQuery) {
                $q = mb_strtolower($pendingQuery);
                $s = $row['student'];
                $haystack = mb_strtolower(
                    ($s->full_name ?? '') . ' ' .
                    ($s->email ?? '')
                );
                return str_contains($haystack, $q);
            }));
        }

        if ($recordedQuery !== '' || $statusFilter !== '') {
            $recorded = array_values(array_filter($recorded, function ($row) use ($recordedQuery, $statusFilter) {
                $ok = true;

                if ($recordedQuery !== '') {
                    $q = mb_strtolower($recordedQuery);
                    $s = $row['student'];
                    $haystack = mb_strtolower(
                        ($s->full_name ?? '') . ' ' .
                        ($s->email ?? '')
                    );
                    $ok = $ok && str_contains($haystack, $q);
                }

                if ($statusFilter !== '') {
                    $ok = $ok && $row['record']->status === $statusFilter;
                }

                return $ok;
            }));
        }

        // --- Simple pagination (25 per table, array-based) -----------------------
        $pendingPage  = max(1, (int) $request->query('pending_page', 1));
        $recordedPage = max(1, (int) $request->query('recorded_page', 1));
        $perPage      = 25;

        $pendingCollection  = collect($pending);
        $recordedCollection = collect($recorded);

        $pendingPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pendingCollection->forPage($pendingPage, $perPage)->values(),
            $pendingCollection->count(),
            $perPage,
            $pendingPage,
            ['path' => $request->url(), 'pageName' => 'pending_page']
        );

        $recordedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $recordedCollection->forPage($recordedPage, $perPage)->values(),
            $recordedCollection->count(),
            $perPage,
            $recordedPage,
            ['path' => $request->url(), 'pageName' => 'recorded_page']
        );

        // --- Class photo for *today* (for quick preview in the page) ------------
        $todayPhoto = ClassPhotoAttendance::where('course_section_id', $section->id)
            ->whereDate('meeting_date', $today)
            ->latest('id')
            ->first();

        // --- Excuse letters for today (for the "Letter" link) -------------------
        $excuseLetters = ExcuseLetter::where('course_section_id', $section->id)
            ->whereDate('meeting_date', $today)
            ->get()
            ->keyBy('student_id');

        // Compute one-time "grace" booleans for present/tardy UI
        $isWithinGrace   = false;
        $isAfterGrace    = false;
        $now             = Carbon::now();

        if ($classStartTime) {
            $graceEnd = $classStartTime->copy()->addMinutes(15);

            $isWithinGrace = $now->between($classStartTime, $graceEnd);
            $isAfterGrace  = $now->greaterThan($graceEnd);
        }

        return view('classroom.roles.instructor.attendance-today', [
            'section'            => $section,
            'today'              => $today,
            'pending'            => $pendingPaginator,
            'recorded'           => $recordedPaginator,
            'summaryNoRecord'    => $noRecordCount,
            'summaryPresent'     => $presentCount,
            'summaryTardy'       => $tardyCount,
            'summaryExcused'     => $excusedCount,
            'summaryAbsent'      => $absentCount,
            'summaryAttendancePct' => $attendancePct,
            'pendingQuery'       => $pendingQuery,
            'recordedQuery'      => $recordedQuery,
            'statusFilter'       => $statusFilter,
            'classStartTime'     => $classStartTime,
            'isWithinGrace'      => $isWithinGrace,
            'isAfterGrace'       => $isAfterGrace,
            'todayPhoto'         => $todayPhoto,
            'excuseLetters'      => $excuseLetters,
        ]);
    }

    /**
     * POST /classroom/instructor/sections/{section}/photos
     * Route: classroom.instructor.sections.photos.store
     *
     * Upload a "class attendance" photo to R2 for today's date.
     */
    public function storePhoto(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $today = Carbon::today();

        $data = $request->validate([
            'photo' => [
                'required',
                'image',
                // 8 MB = 8192 KB
                File::image()->max(8192),
            ],
        ]);

        $file = $data['photo'];

        // Decide extension (keep original if possible)
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        // R2 directory: class-photos/{section_id}/{YYYY-MM-DD}
        $dir      = "class-photos/{$section->id}/" . $today->format('Y-m-d');
        $filename = 'photo_' . time() . '.' . $extension;
        $path     = "{$dir}/{$filename}";

        Log::info('[InstructorSection] Uploading class photo to R2', [
            'section_id' => $section->id,
            'user_id'    => $user->id,
            'path'       => $path,
        ]);

        Storage::disk('r2')->putFileAs($dir, $file, $filename);

        ClassPhotoAttendance::create([
            'course_section_id' => $section->id,
            'meeting_date'      => $today,
            'uploaded_by'       => $user->id,
            'photo_path'        => $path,
        ]);

        Alert::toast('Class photo uploaded successfully.', 'success')->autoClose(6000);

        return back();
    }

    /**
     * POST /classroom/instructor/sections/{section}/attendance-today/{student}
     * Route: classroom.instructor.sections.attendance.mark
     *
     * This method is the **single place** where manual attendance for TODAY
     * is created/updated. Future Python face-recognition code should
     * follow the same pattern:
     *
     *  - one row per (course_section_id, student_id, meeting_date)
     *  - use updateOrCreate() to upsert on this unique key
     *  - status in: present | tardy | excused | absent
     *  - time_in is only set for present/tardy
     */
    public function markTodayAttendance(Request $request, CourseSection $section, User $student)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $today = Carbon::today();

        $data = $request->validate([
            'status' => ['required', 'in:present,tardy,excused,absent'],
        ]);

        $status = $data['status'];

        // Decide time_in: only for present/tardy; null for others
        $timeIn = null;
        if (in_array($status, ['present', 'tardy'], true)) {
            $timeIn = Carbon::now()->format('H:i:s');
        }

        // ⚠️ IMPORTANT for Python devs:
        // This is the canonical "upsert" logic. Python face-recognition
        // should also call/update the AttendanceRecord model (or underlying table)
        // using the SAME where() keys: course_section_id + student_id + meeting_date.
        $record = AttendanceRecord::updateOrCreate(
            [
                'course_section_id' => $section->id,
                'student_id'        => $student->id,
                'meeting_date'      => $today,
            ],
            [
                'status'  => $status,
                'time_in' => $timeIn,
                // optional: add 'source' => 'manual' vs 'python'
            ]
        );

        Alert::toast("Attendance marked as {$status} for {$student->full_name}.", 'success')
            ->autoClose(6000);

        return back();
    }

    /**
     * DELETE /classroom/instructor/sections/{section}/attendance-today/{student}
     * Route: classroom.instructor.sections.attendance.reset
     *
     * Deletes today's attendance for this student/section.
     * After this, the student will appear again in the "Pending" tab.
     *
     * Python face-recognition code can also safely delete/reset
     * using the same keys if needed, but usually it will only upsert.
     */
    public function resetTodayAttendance(Request $request, CourseSection $section, User $student)
    {
        $user = $request->user();
        $this->ensureOwnSection($user, $section);

        $today = Carbon::today();

        AttendanceRecord::where('course_section_id', $section->id)
            ->where('student_id', $student->id)
            ->forDate($today)
            ->delete();

        Alert::toast("Today's attendance reset for {$student->full_name}.", 'success')
            ->autoClose(6000);

        return back();
    }


}
