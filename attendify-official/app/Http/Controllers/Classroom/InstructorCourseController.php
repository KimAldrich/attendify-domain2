<?php

namespace App\Http\Controllers\Classroom;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\CourseSection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class InstructorCourseController extends Controller
{
    /**
     * Ensure current user is a teaching user (or admin).
     */
    protected function ensureTeachingUser($user): void
    {
        if (! $user->is_teaching && ! $user->hasRole('admin')) {
            abort(403, 'You are not configured as a teaching faculty.');
        }
    }

    /**
     * GET /classroom/instructor/courses
     * Route: classroom.instructor.courses.index
     *
     * My Courses page: list of sections for this instructor under selected AY-Term.
     * Includes timetable board similar to admin courses page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $this->ensureTeachingUser($user);

        $periodId = $request->integer('period_id');
        $search   = trim((string) $request->query('q', ''));

        // All periods for selector
        $periods = AcademicPeriod::orderByDesc('year_start')->get();

        // Selected period: explicit -> current -> first
        $selectedPeriod = $periodId
            ? $periods->firstWhere('id', $periodId)
            : AcademicPeriod::current()->first() ?? $periods->first();

        // Sections for this instructor in that period
        $sectionsQuery = CourseSection::query()
            ->where('instructor_id', $user->id)
            ->when($selectedPeriod, function ($q) use ($selectedPeriod) {
                $q->where('academic_period_id', $selectedPeriod->id);
            });

        if ($search !== '') {
            $sectionsQuery->where(function ($q) use ($search) {
                $q->where('course_code', 'like', "%{$search}%")
                    ->orWhere('course_name', 'like', "%{$search}%")
                    ->orWhere('section_label', 'like', "%{$search}%");
            });
        }

        $sections = $sectionsQuery
            ->withCount('enrollments as students_count')
            ->orderBy('course_code')
            ->orderBy('section_label')
            ->paginate(15)
            ->withQueryString();

        // Presets for course code/name auto-fill
        $coursePresets = CourseSection::select('course_code', 'course_name')
            ->whereNotNull('course_code')
            ->groupBy('course_code', 'course_name')
            ->orderBy('course_code')
            ->get();

        // Timetable (copied from admin style, scoped to this instructor)
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

        if ($selectedPeriod) {
            $timetableSections = CourseSection::query()
                ->with([
                    'schedules' => function ($q) {
                        $q->orderBy('day_of_week')
                          ->orderBy('start_time');
                    },
                    'schedules.room',
                ])
                ->where('instructor_id', $user->id)
                ->where('academic_period_id', $selectedPeriod->id)
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('course_code', 'like', "%{$search}%")
                            ->orWhere('course_name', 'like', "%{$search}%")
                            ->orWhere('section_label', 'like', "%{$search}%");
                    });
                })
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

                    $block['lane'] = $laneIndex;
                    $lanes[$laneIndex] = $block['end_min'];
                }
                unset($block);

                $timetable[$dow]['blocks']     = $blocks;
                $timetable[$dow]['lane_count'] = count($lanes);
            }
        }

        return view('classroom.roles.instructor.courses.index', [
            'periods'        => $periods,
            'selectedPeriod' => $selectedPeriod,
            'sections'       => $sections,
            'search'         => $search,
            'coursePresets'  => $coursePresets,
            'timetable'      => $timetable,
            'instructor'     => $user,
        ]);
    }

    /**
     * POST /classroom/instructor/courses
     * Route: classroom.instructor.courses.store
     *
     * Instructor creates a new section for the selected academic period.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $this->ensureTeachingUser($user);

        $validated = $request->validate([
            'academic_period_id' => ['required', 'exists:academic_periods,id'],
            'course_code'        => ['required', 'string', 'max:50'],
            'course_name'        => ['required', 'string', 'max:255'],
            'section_label'      => ['required', 'string', 'max:100'],
        ]);

        $section = CourseSection::create([
            'academic_period_id' => $validated['academic_period_id'],
            'instructor_id'      => $user->id,
            'course_code'        => $validated['course_code'],
            'course_name'        => $validated['course_name'],
            'section_label'      => $validated['section_label'],
        ]);

        Alert::toast('Section created successfully.', 'success')->autoClose(6000);

        return redirect()->route('classroom.instructor.courses.index', [
            'period_id' => $section->academic_period_id,
        ]);
    }

    /**
     * PUT /classroom/instructor/courses/{section}
     * Route: classroom.instructor.courses.update
     */
    public function update(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureTeachingUser($user);

        // Only allow owner instructor or admin
        if (! $user->hasRole('admin') && $section->instructor_id !== $user->id) {
            abort(403, 'You are not allowed to edit this section.');
        }

        $validated = $request->validate([
            'course_code'   => ['required', 'string', 'max:50'],
            'course_name'   => ['required', 'string', 'max:255'],
            'section_label' => ['required', 'string', 'max:100'],
        ]);

        $section->update($validated);

        Alert::toast('Section updated successfully.', 'success')->autoClose(6000);

        return redirect()->route('classroom.instructor.courses.index', [
            'period_id' => $section->academic_period_id,
        ]);
    }

    /**
     * DELETE /classroom/instructor/courses/{section}
     * Route: classroom.instructor.courses.destroy
     */
    public function destroy(Request $request, CourseSection $section)
    {
        $user = $request->user();
        $this->ensureTeachingUser($user);

        if (! $user->hasRole('admin') && $section->instructor_id !== $user->id) {
            abort(403, 'You are not allowed to delete this section.');
        }

        $periodId = $section->academic_period_id;

        $section->delete();

        Alert::toast('Section deleted.', 'success')->autoClose(6000);

        return redirect()->route('classroom.instructor.courses.index', [
            'period_id' => $periodId,
        ]);
    }
}
