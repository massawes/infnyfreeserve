<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassTiming;
use App\Models\Student;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

class ExaminationOfficerController extends Controller
{
    public function dashboard()
    {
        $totalStudents = Student::count();
        $totalModules = Module::where('semester', 'Semester 2')->count();

        $attendanceStats = DB::table('attendances')
            ->select(
                DB::raw('COUNT(*) as total_records'),
                DB::raw('SUM(is_present) as total_present')
            )
            ->first();

        $attendanceRate = $attendanceStats->total_records > 0
            ? round(($attendanceStats->total_present / $attendanceStats->total_records) * 100, 1)
            : 0;

        $eligibilityStats = DB::table('attendances')
            ->select(
                'student_id',
                'module_distribution_id',
                DB::raw('SUM(is_present) * 100.0 / COUNT(*) as percentage')
            )
            ->groupBy('student_id', 'module_distribution_id')
            ->get();

        $clearedCount = $eligibilityStats->where('percentage', '>=', 75.0)->count();
        $notClearedCount = $eligibilityStats->where('percentage', '<', 75.0)->count();

        $studentRiskCount = DB::table('attendances')
            ->select('student_id', DB::raw('ROUND(SUM(is_present) * 100.0 / COUNT(*), 1) as percentage'))
            ->groupBy('student_id')
            ->having('percentage', '<', 75)
            ->get()
            ->count();

        $lowAttendanceModules = DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->leftJoin('programs as p', 'm.program_id', '=', 'p.id')
            ->leftJoin('attendances as a', 'md.id', '=', 'a.module_distribution_id')
            ->select(
                'md.id',
                'm.module_name',
                'm.module_code',
                'p.program_name',
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                DB::raw('ROUND(CASE WHEN COUNT(a.id) = 0 THEN 0 ELSE (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id)) END, 1) as attendance_rate')
            )
            ->groupBy('md.id', 'm.module_name', 'm.module_code', 'p.program_name')
            ->havingRaw('ROUND(CASE WHEN COUNT(a.id) = 0 THEN 0 ELSE (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id)) END, 1) < ?', [75])
            ->orderBy('attendance_rate')
            ->limit(3)
            ->get();

        return view('dashboards.ExaminationOfficer', compact(
            'totalStudents',
            'totalModules',
            'attendanceRate',
            'clearedCount',
            'notClearedCount',
            'studentRiskCount',
            'lowAttendanceModules'
        ));
    }

    public function eligibility(Request $request)
    {
        if (! auth()->user()->hasPermission('exam_view_eligibility')) {
            abort(403, 'You do not have permission to view exam eligibility.');
        }

        $query = DB::table('attendances as a')
            ->join('students as s', 'a.student_id', '=', 's.id')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->select(
                'u.name as student_name',
                's.id as student_id',
                'm.module_name',
                DB::raw('COUNT(*) as total_sessions'),
                DB::raw('SUM(a.is_present) as attended_sessions'),
                DB::raw('ROUND(SUM(a.is_present) * 100.0 / COUNT(*), 1) as attendance_percentage')
            )
            ->groupBy('s.id', 'u.name', 'm.module_name', 'a.module_distribution_id');

        // Filters
        if ($request->has('module_id') && $request->module_id != '') {
            $query->where('md.module_id', $request->module_id);
        }

        if ($request->has('status')) {
            if ($request->status == 'cleared') {
                $query->having('attendance_percentage', '>=', 75);
            } elseif ($request->status == 'not_cleared') {
                $query->having('attendance_percentage', '<', 75);
            }
        }

        if ($request->boolean('export')) {
            return response()->json([
                'sheet_name' => 'Exam Eligibility',
                'filename' => 'exam-eligibility.xlsx',
                'rows' => $query->get()->map(fn ($data) => [
                    'student_name' => $data->student_name,
                    'student_id' => $data->student_id,
                    'module_name' => $data->module_name,
                    'total_sessions' => $data->total_sessions,
                    'attended_sessions' => $data->attended_sessions,
                    'attendance_percentage' => $data->attendance_percentage,
                ])->values(),
            ]);
        }

        $eligibilityData = $query->paginate(10)->withQueryString();
        $modules = Module::where('semester', 'Semester 2')->get();

        return view('exam.eligibility', compact('eligibilityData', 'modules'));
    }

    public function reports(Request $request)
    {
        if (! auth()->user()->hasPermission('exam_view_reports')) {
            abort(403, 'You do not have permission to view exam reports.');
        }

        $programStatsQuery = DB::table('programs as p')
            ->leftJoin('students as s', 'p.id', '=', 's.program_id')
            ->leftJoin('attendances as a', 's.id', '=', 'a.student_id')
            ->select(
                'p.program_name',
                DB::raw('COUNT(DISTINCT s.id) as total_students'),
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as total_present'),
                DB::raw('ROUND(AVG(CASE WHEN a.is_present = 1 THEN 100 ELSE 0 END), 1) as attendance_rate')
            )
            ->groupBy('p.id', 'p.program_name')
            ->orderByDesc('attendance_rate');

        if ($request->boolean('export')) {
            return response()->json([
                'sheet_name' => 'Exam Reports',
                'filename' => 'exam-reports.xlsx',
                'rows' => $programStatsQuery->get()->map(fn ($stat) => [
                    'program_name' => $stat->program_name,
                    'total_students' => $stat->total_students,
                    'total_records' => $stat->total_records,
                    'total_present' => $stat->total_present,
                    'attendance_rate' => $stat->attendance_rate,
                ])->values(),
            ]);
        }

        $programStats = $programStatsQuery
            ->paginate(6)
            ->withQueryString();

        return view('exam.reports', compact('programStats'));
    }

    public function timetable(Request $request)
    {
        if (! auth()->user()->hasPermission('exam_manage_timetable')) {
            abort(403, 'You do not have permission to manage the exam timetable.');
        }

        $examData = ClassTiming::with(['week', 'moduleDistribution.module.program', 'moduleDistribution.lecturer'])
            ->select('class_timings.*')
            ->join('module_distributions as md', 'class_timings.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->orderByRaw("CASE LOWER(class_timings.day)
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
                WHEN 'sunday' THEN 7
                ELSE 8
            END")
            ->orderBy('class_timings.time')
            ->paginate(10);

        return view('exam.timetable', [
            'examData' => $examData,
        ]);
    }
}
