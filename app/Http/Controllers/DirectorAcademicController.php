<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

class DirectorAcademicController extends Controller
{
    public function dashboard()
    {
        $totalDepartments = Department::count();
        $totalPrograms = Program::count();
        $totalStudents = Student::count();
        $totalLecturers = Lecturer::count();

        $totalAttendances = Attendance::count();
        $presentAttendances = Attendance::where('is_present', 1)->count();

        $overallAttendanceRate = $totalAttendances > 0
            ? round(($presentAttendances / $totalAttendances) * 100, 1)
            : 0;

        $departmentPerformance = DB::table('departments as d')
            ->leftJoin('programs as p', 'd.id', '=', 'p.department_id')
            ->leftJoin('students as s', 'p.id', '=', 's.program_id')
            ->leftJoin('attendances as a', 's.id', '=', 'a.student_id')
            ->select(
                'd.id',
                'd.department_name',
                DB::raw('COUNT(DISTINCT p.id) as total_programs'),
                DB::raw('COUNT(DISTINCT s.id) as total_students'),
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                DB::raw('ROUND(CASE WHEN COUNT(a.id) = 0 THEN 0 ELSE (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id)) END, 1) as attendance_rate')
            )
            ->groupBy('d.id', 'd.department_name')
            ->orderByDesc('attendance_rate')
            ->take(3)
            ->get();

        return view('dashboards.DirectorAcademic', compact(
            'totalDepartments',
            'totalPrograms',
            'totalStudents',
            'totalLecturers',
            'overallAttendanceRate',
            'departmentPerformance'
        ));
    }

    public function faculties()
    {
        // Pata departments pamoja na counts za related data
        // Kwa kuwa relationship si complete sana kweny models, tuna-fallback kwenye basic queries
        $departments = Department::all();
        
        // Let's add basic attributes just for view consistency
        foreach ($departments as $dept) {
            // Hizi zingefaa kuwa relationships e.g., $dept->modules->count() lakini tutatumia fallback ikikosekana
            $dept->total_modules = Module::whereHas('program', function($q) use ($dept) {
                $q->where('department_id', $dept->id);
            })->where('semester', 'Semester 2')->count();
        }

        return view('dashboards.director_faculties', compact('departments'));
    }
}
