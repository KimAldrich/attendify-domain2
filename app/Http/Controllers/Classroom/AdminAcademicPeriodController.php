<?php

namespace App\Http\Controllers\Classroom;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AdminAcademicPeriodController extends Controller
{
    public function index()
    {
        $periods = AcademicPeriod::query()
            ->orderByDesc('year_start')
            ->orderBy('term')
            ->withCount('sections')
            ->paginate(25)    
            ->withQueryString();

        return view('classroom.roles.admin.periods.index', compact('periods'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year_pair' => ['required', 'string'],
            'term'      => ['required', 'string', 'max:50'],
            'label'     => ['nullable', 'string', 'max:255'],
            'is_current'=> ['nullable', 'boolean'],
        ]);

        [$year_start, $year_end] = explode('|', $data['year_pair']);

        $data['year_start'] = (int) $year_start;
        $data['year_end']   = (int) $year_end;
        unset($data['year_pair']); 

        $data['is_current'] = (bool) ($data['is_current'] ?? false);

        // If this is set as current, make all others not current
        if ($data['is_current']) {
            AcademicPeriod::query()->update(['is_current' => false]);
        }

        $period = AcademicPeriod::create($data);

        Alert::toast('Academic period created!', 'success')->autoClose(6000);

        return redirect()
            ->route('classroom.admin.periods.index')
            ->with('status', 'period-created');
    }

    public function update(Request $request, AcademicPeriod $period)
    {
        // Manual handling to avoid mixing error bags with create modal
        $data = $request->validate([
            'year_pair' => ['required', 'string'],
            'term'      => ['required', 'string', 'max:50'],
            'label'     => ['nullable', 'string', 'max:255'],
            'is_current'=> ['nullable', 'boolean'],
        ]);

        [$start, $end] = explode('|', $data['year_pair']);

        $data['year_start'] = (int) $start;
        $data['year_end']   = (int) $end;

        unset($data['year_pair']);
        $data['term']       = trim((string) ($data['term'] ?? $period->term));
        $data['label']      = trim((string) ($data['label'] ?? $period->label));
        $data['is_current'] = (bool) ($data['is_current'] ?? false);

        if ($data['year_start'] < 2000 || $data['year_start'] > 2100) {
            return back()->with('status', 'period-update-failed');
        }

        if ($data['year_end'] < $data['year_start']) {
            return back()->with('status', 'period-update-failed');
        }

        // If this one is set current, clear all others
        if ($data['is_current']) {
            AcademicPeriod::where('id', '!=', $period->id)->update(['is_current' => false]);
        }

        $period->update($data);

        Alert::toast('Academic period updated!', 'success')->autoClose(6000);

        return redirect()
            ->route('classroom.admin.periods.index')
            ->with('status', 'period-updated');
    }

    public function setCurrent(AcademicPeriod $period)
    {
        // Set this one as current, all others false
        AcademicPeriod::query()->update(['is_current' => false]);

        $period->update(['is_current' => true]);

        Alert::toast('Current academic period updated.', 'success')->autoClose(6000);

        return redirect()
            ->route('classroom.admin.periods.index')
            ->with('status', 'period-set-current');
    }

    public function destroy(AcademicPeriod $period)
    {
        if ($period->sections()->exists()) {
            Alert::toast('Cannot delete an academic period with attached sections.', 'error')->autoClose(6000);

            return redirect()
                ->route('classroom.admin.periods.index')
                ->with('status', 'period-delete-blocked');
        }

        // Avoid having no current period at all by mistake
        $wasCurrent = $period->is_current;

        $period->delete();

        if ($wasCurrent) {
            // Optionally: set the latest remaining as current
            $fallback = AcademicPeriod::orderByDesc('year_start')->first();
            if ($fallback) {
                $fallback->update(['is_current' => true]);
            }
        }

        Alert::toast('Academic period deleted.', 'success')->autoClose(6000);

        return redirect()
            ->route('classroom.admin.periods.index')
            ->with('status', 'period-deleted');
    }
}
