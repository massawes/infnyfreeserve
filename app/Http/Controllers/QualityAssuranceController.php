<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\Module;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class QualityAssuranceController extends Controller
{
    public function dashboard()
    {
        $totalModules = Module::where('semester', 'Semester 2')->count();
        $totalLecturers = Lecturer::count();
        $totalPrograms = Program::count();

        $modulesWithoutTimetables = DB::table('modules as m')
            ->leftJoin('module_distributions as md', 'm.id', '=', 'md.module_id')
            ->where('m.semester', 'Semester 2')
            ->whereNull('md.id')
            ->count();

        $lowAttendanceModules = DB::table('modules as m')
            ->leftJoin('module_distributions as md', 'm.id', '=', 'md.module_id')
            ->leftJoin('programs as p', 'm.program_id', '=', 'p.id')
            ->leftJoin('attendances as a', 'md.id', '=', 'a.module_distribution_id')
            ->where('m.semester', 'Semester 2')
            ->select(
                'm.id',
                'm.module_name',
                'm.module_code',
                'p.program_name',
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                DB::raw('ROUND(CASE WHEN COUNT(a.id) = 0 THEN 0 ELSE (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id)) END, 1) as attendance_rate')
            )
            ->groupBy('m.id', 'm.module_name', 'm.module_code', 'p.program_name')
            ->havingRaw('ROUND(CASE WHEN COUNT(a.id) = 0 THEN 0 ELSE (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id)) END, 1) < ?', [70])
            ->orderBy('attendance_rate')
            ->limit(3)
            ->get();

        $modulesWithTimetables = DB::table('modules as m')
            ->join('module_distributions as md', 'm.id', '=', 'md.module_id')
            ->join('class_timings as ct', 'md.id', '=', 'ct.module_distribution_id')
            ->where('m.semester', 'Semester 2')
            ->distinct()
            ->count('m.id');

        $coverageRate = $totalModules > 0
            ? round(($modulesWithTimetables / $totalModules) * 100, 1)
            : 0;
        $pendingReviews = $lowAttendanceModules->count() + $modulesWithoutTimetables;

        return view('dashboards.QualityAssurance', compact(
            'totalModules',
            'totalLecturers',
            'totalPrograms',
            'pendingReviews',
            'coverageRate',
            'modulesWithoutTimetables',
            'lowAttendanceModules'
        ));
    }
}
