<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Module;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    //
    public function dashboard()
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->with('program.department')->first();
        
        $program_id = $student ? $student->program_id : null;
        $programName = $student?->program?->program_name;
        $departmentName = $student?->program?->department?->department_name;

        $modules = Module::where('program_id', $program_id)
            ->where('semester', 'Semester 2')
            ->orderBy('module_name')
            ->get();
        $totalModules = $modules->count();

        $totalPresent = Attendance::where('student_id', $student->id ?? 0)
            ->where('is_present', 1)
            ->count();
        $totalRecords = Attendance::where('student_id', $student->id ?? 0)->count();
        
        $attendanceRate = $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0;
        $attendanceStatus = $attendanceRate >= 75 ? 'Good Standing' : 'Needs Attention';

        $upcomingClasses = DB::table('class_timings as ct')
            ->join('module_distributions as md', 'ct.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->select('ct.day', 'ct.time', DB::raw('COALESCE(ct.room, \'TBA\') as room'), 'm.module_name')
            ->where('m.program_id', $program_id)
            ->orderByRaw("CASE LOWER(ct.day)
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
                WHEN 'sunday' THEN 7
                ELSE 8
            END")
            ->orderBy('ct.time')
            ->limit(3)
            ->get();

        $recentAttendance = DB::table('attendances as a')
            ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->select('a.date', 'a.is_present', 'm.module_name')
            ->where('a.student_id', $student->id ?? 0)
            ->orderByDesc('a.date')
            ->limit(3)
            ->get();

        return view('dashboards.student', compact(
            'student',
            'modules',
            'totalModules',
            'attendanceRate',
            'attendanceStatus',
            'programName',
            'departmentName',
            'recentAttendance',
            'totalPresent',
            'totalRecords'
        ));
    } 
}
