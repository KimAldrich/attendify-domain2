<?php

namespace App\Http\Controllers\Classroom;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\ClassSchedule;
use App\Models\CourseSection;
use App\Models\ExcuseLetter;
use App\Models\SectionEnrollment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class StudentAttendanceController extends Controller
{
    protected function ensureStudent($user): void
    {
        // You can make this stricter if you want, e.g. $user->hasRole('student')
        if (! $user) {
            abort(403);
        }
    }

    /**
     * GET /classroom/student/attendance-today
     * Route: classroom.student.attendance-today
     *
     * List all classes the student has today, plus their attendance status.
     */
    public function today(Request $request)
    {
        $student = $request->user();
        $this->ensureStudent($student);

        $today     = Carbon::today();
        $dayOfWeek = $today->dayOfWeek; // 0=Sun..6=Sat; your schedules use 1-6 so we’ll assume Mon=1..Sat=6

        // All sections where this student is enrolled
        $sections = CourseSection::query()
            ->whereHas('enrollments', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->with([
                'instructor',
                'academicPeriod',
                'schedules' => function ($q) use ($dayOfWeek) {
                    // schedules that match today's day_of_week
                    $q->where('day_of_week', $dayOfWeek)
                      ->orderBy('start_time');
                },
                'schedules.room',
            ])
            ->get();

        // Filter out sections with no schedules today
        $sectionsToday = $sections->filter(function ($section) {
            return $section->schedules->isNotEmpty();
        });

        $sectionIds = $sectionsToday->pluck('id')->all();

        // Fetch today’s attendance records for this student, for any of these sections
        $todayRecords = AttendanceRecord::where('student_id', $student->id)
            ->whereIn('course_section_id', $sectionIds)
            ->forDate($today)
            ->get()
            ->keyBy('course_section_id');

        // Build a flat list of "class instances" for today (section + schedule)
        $classesToday = [];

        foreach ($sectionsToday as $section) {
            foreach ($section->schedules as $schedule) {
                $record = $todayRecords->get($section->id);

                $status     = $record?->status ?? 'pending';
                $timeArrive = null;

                if ($record && in_array($record->status, ['present', 'tardy']) && $record->time_in) {
                    $timeArrive = Carbon::parse($record->time_in)->format('h:i A');
                }

                $classesToday[] = (object) [
                    'section'   => $section,
                    'schedule'  => $schedule,
                    'record'    => $record,
                    'status'    => $status,
                    'timeLabel' => Carbon::parse($schedule->start_time)->format('g:i A') .
                                   ' – ' .
                                   Carbon::parse($schedule->end_time)->format('g:i A'),
                    'roomLabel' => optional($schedule->room)->room_number ?? 'Room ?',
                    'timeArrive'=> $timeArrive ?? '-',
                ];
            }
        }

        // Sort by start time
        usort($classesToday, function ($a, $b) {
            return strcmp($a->schedule->start_time, $b->schedule->start_time);
        });

        // Summary counts for today
        $summary = [
            'total'   => count($classesToday),
            'present' => 0,
            'tardy'   => 0,
            'excused' => 0,
            'absent'  => 0,
            'pending' => 0,
        ];

        foreach ($classesToday as $cls) {
            $st = $cls->status;
            if (isset($summary[$st])) {
                $summary[$st]++;
            } else {
                $summary['pending']++;
            }
        }

        $excuseLettersToday = ExcuseLetter::where('student_id', $student->id)
            ->whereDate('meeting_date', $today)
            ->get()
            ->keyBy('course_section_id');


        return view('classroom.roles.student.attendance-today', [
            'today'        => $today,
            'classesToday' => $classesToday,
            'summary'      => $summary,
    'excuseLettersToday'  => $excuseLettersToday,
        ]);
    }

    /**
     * POST /classroom/student/attendance-today/excuse
     * Route: classroom.student.attendance-today.excuse.store
     *
     * Student uploads an excuse letter (image/PDF) for TODAY, per section.
     * R2 storage; we keep history (no deletion).
     */
    public function uploadExcuseToday(Request $request)
    {
        $student = $request->user();
        $this->ensureStudent($student);

        $today = Carbon::today();

        $validated = $request->validate([
            'course_section_id' => ['required', 'exists:course_sections,id'],
            'file'              => ['required', 'file', 'max:8192'], // 8MB
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $section = CourseSection::where('id', $validated['course_section_id'])
            ->whereHas('enrollments', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->firstOrFail();

        $file       = $validated['file'];
        $extension  = $file->getClientOriginalExtension() ?: 'bin';
        $origName   = $file->getClientOriginalName();
        $mime       = $file->getMimeType() ?: null;

        // Ensure student has a slug-like directory
        $slug = $student->slug ?: ('student-' . $student->id);
        if (! $student->slug) {
            $student->slug = Str::slug($slug);
            $student->save();
        }

        $dir      = "excuse-letters/{$slug}";
        $filename = $today->format('Ymd') . "_section-{$section->id}." . $extension;
        $path     = "{$dir}/{$filename}";

        // Upload to R2 (no delete; keep history)
        Storage::disk('r2')->putFileAs($dir, $file, $filename);

        // Create or update an excuse letter for this (student, section, date)
        $excuse = ExcuseLetter::updateOrCreate(
            [
                'course_section_id' => $section->id,
                'student_id'        => $student->id,
                'meeting_date'      => $today,
            ],
            [
                'file_path'     => $path,
                'original_name' => $origName,
                'mime_type'     => $mime,
                'notes'         => $validated['notes'] ?? null,
                'status'        => 'submitted',
            ]
        );

        Alert::toast('Excuse letter uploaded for today.', 'success')->autoClose(7000);

        return back();
    }

    /**
     * GET /classroom/student/overall-attendance
     * Route: classroom.student.overall-attendance
     *
     * Selector of AY-Term, summary cards, per-section stats, + timetable.
     */
    public function overall(Request $request)
    {
        $student = $request->user();
        $this->ensureStudent($student);

        $periodId = $request->integer('period_id');

        $periods = AcademicPeriod::orderByDesc('year_start')->get();

        $selectedPeriod = $periodId
            ? $periods->firstWhere('id', $periodId)
            : AcademicPeriod::current()->first() ?? $periods->first();

        // Sections this student is enrolled in for the selected period
        $sectionsQuery = CourseSection::query()
            ->whereHas('enrollments', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->with(['instructor', 'academicPeriod']);

        if ($selectedPeriod) {
            $sectionsQuery->where('academic_period_id', $selectedPeriod->id);
        }

        $sections = $sectionsQuery
            ->orderBy('course_code')
            ->orderBy('section_label')
            ->get();

        $sectionIds = $sections->pluck('id')->all();

        // Aggregate attendance per section for this student
        $perSectionAgg = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->when($sectionIds, fn($q) => $q->whereIn('course_section_id', $sectionIds))
            ->selectRaw("
                course_section_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'tardy' THEN 1 ELSE 0 END) as tardy_count,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
            ")
            ->groupBy('course_section_id')
            ->get()
            ->keyBy('course_section_id');

        // Overall totals (for summary cards)
        $overallTotals = [
            'sections'   => $sections->count(),
            'present'    => 0,
            'tardy'      => 0,
            'excused'    => 0,
            'absent'     => 0,
            'total_days' => 0,
            'pct'        => 0.0,
        ];

        foreach ($perSectionAgg as $agg) {
            $overallTotals['present'] += $agg->present_count;
            $overallTotals['tardy']   += $agg->tardy_count;
            $overallTotals['excused'] += $agg->excused_count;
            $overallTotals['absent']  += $agg->absent_count;
            $overallTotals['total_days'] += $agg->total;
        }

        if ($overallTotals['total_days'] > 0) {
            $overallTotals['pct'] = round(
                (($overallTotals['present'] + $overallTotals['tardy']) / $overallTotals['total_days']) * 100,
                2
            );
        }

        // Build timetable (same style as instructor, but from student’s sections)
        $daysOfWeek = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $startOfDay   = Carbon::createFromTime(6, 0, 0);
        $endOfDay     = Carbon::createFromTime(18, 0, 0);
        $totalMinutes = $startOfDay->diffInMinutes($endOfDay);

        $timetable = [];
        foreach ($daysOfWeek as $dow => $label) {
            $timetable[$dow] = [
                'label'  => $label,
                'blocks' => [],
            ];
        }

        if ($selectedPeriod && !empty($sectionIds)) {
            $timetableSections = CourseSection::query()
                ->with([
                    'schedules' => function ($q) {
                        $q->orderBy('day_of_week')
                          ->orderBy('start_time');
                    },
                    'schedules.room',
                ])
                ->whereIn('id', $sectionIds)
                ->get();

            foreach ($timetableSections as $section) {
                foreach ($section->schedules as $schedule) {
                    $dow = (int) $schedule->day_of_week;
                    if (! isset($timetable[$dow])) {
                        continue;
                    }

                    $origStart = Carbon::parse($schedule->start_time);
                    $origEnd   = Carbon::parse($schedule->end_time);

                    if ($origEnd <= $startOfDay || $origStart >= $endOfDay) {
                        continue;
                    }

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
                    $widthMinutes = max(5, $endMinutes - $startMinutes);

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
                        'lane'         => 0,
                    ];
                }
            }

            // lane assignment
            foreach ($timetable as $dow => $day) {
                $blocks = $day['blocks'];

                usort($blocks, function ($a, $b) {
                    return $a['start_min'] <=> $b['start_min'];
                });

                $lanes = [];

                foreach ($blocks as &$block) {
                    $laneIndex = 0;
                    while (isset($lanes[$laneIndex]) && $block['start_min'] < $lanes[$laneIndex]) {
                        $laneIndex++;
                    }
                    $block['lane']   = $laneIndex;
                    $lanes[$laneIndex] = $block['end_min'];
                }
                unset($block);

                $timetable[$dow]['blocks']     = $blocks;
                $timetable[$dow]['lane_count'] = count($lanes);
            }
        }

        $sectionRecords = AttendanceRecord::query()
    ->where('student_id', $student->id)
    ->when($sectionIds, fn ($q) => $q->whereIn('course_section_id', $sectionIds))
    ->orderBy('meeting_date', 'desc')
    ->orderBy('id', 'desc')
    ->get()
    ->groupBy(groupBy: 'course_section_id');

        return view('classroom.roles.student.overall-attendance', [
            'periods'        => $periods,
            'selectedPeriod' => $selectedPeriod,
            'sections'       => $sections,
            'perSectionAgg'  => $perSectionAgg,
            'overallTotals'  => $overallTotals,
            'timetable'      => $timetable,
    'sectionRecords' => $sectionRecords,
        ]);
    }

    /**
     * GET /classroom/student/sections/{section}/records
     * Route: classroom.student.sections.records
     *
     * JSON: all attendance records of THIS STUDENT in this section.
     * (Used by the modal in Overall Attendance page.)
     *
     * NOTE for future Python integration:
     * - This is a clean endpoint you can hit from Python to get historical
     *   attendance for ML / analytics, per student/section.
     */
    public function sectionRecords(Request $request, CourseSection $section)
    {
        $student = $request->user();
        $this->ensureStudent($student);

        // enforce that the student belongs to this section
        $enrolled = SectionEnrollment::where('course_section_id', $section->id)
            ->where('student_id', $student->id)
            ->exists();

        if (! $enrolled) {
            abort(403);
        }

        $records = AttendanceRecord::where('course_section_id', $section->id)
            ->where('student_id', $student->id)
            ->orderBy('meeting_date', 'desc')
            ->orderBy('id', 'desc')
            ->get(['meeting_date', 'status', 'time_in']);

        return response()->json([
            'section' => [
                'id'           => $section->id,
                'course_code'  => $section->course_code,
                'course_name'  => $section->course_name,
                'section_label'=> $section->section_label,
            ],
            'records' => $records,
        ]);
    }
}
