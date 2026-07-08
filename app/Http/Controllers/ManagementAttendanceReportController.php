<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementAttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array(auth()->user()->role->name, [
            'registrar',
            'examination_officer',
            'quality_assurance',
            'rector',
        ]), 403);

        $weekId = $request->week_id;
        $programId = $request->program_id;
        $moduleId = $request->module_id;

        $baseQuery = DB::table('attendances as a')
            ->join('students as s', 'a.student_id', '=', 's.id')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->leftJoin('weeks as w', 'a.week_id', '=', 'w.id');

        if ($weekId) {
            $baseQuery->where('a.week_id', $weekId);
        }

        if ($programId) {
            $baseQuery->where('p.id', $programId);
        }

        if ($moduleId) {
            $baseQuery->where('m.id', $moduleId);
        }

        $recordsQuery = (clone $baseQuery)
            ->select(
                'u.name as student_name',
                'p.program_name',
                'm.module_name',
                'w.week_name',
                DB::raw('COUNT(a.id) as total_sessions'),
                DB::raw('SUM(a.is_present) as attended_sessions'),
                DB::raw('ROUND((SUM(a.is_present) / COUNT(a.id)) * 100, 1) as attendance_percentage')
            )
            ->groupBy('u.name', 'p.program_name', 'm.module_name', 'w.week_name')
            ->orderBy('p.program_name')
            ->orderBy('m.module_name')
            ->orderBy('u.name');

        if ($request->boolean('export')) {
            return response()->json([
                'sheet_name' => 'Attendance Report',
                'filename' => 'attendance-report.xlsx',
                'rows' => $recordsQuery->get()->map(fn ($record) => [
                    'student_name' => $record->student_name,
                    'program_name' => $record->program_name,
                    'module_name' => $record->module_name,
                    'week_name' => $record->week_name,
                    'total_sessions' => $record->total_sessions,
                    'attended_sessions' => $record->attended_sessions,
                    'attendance_percentage' => $record->attendance_percentage,
                ])->values(),
            ]);
        }

        $records = $recordsQuery->paginate(15);

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(a.id) as total_records')
            ->selectRaw('SUM(a.is_present) as present_records')
            ->selectRaw('COUNT(DISTINCT s.id) as total_students')
            ->first();

        $totalRecords = (int) ($summary->total_records ?? 0);
        $presentRecords = (int) ($summary->present_records ?? 0);
        $absentRecords = $totalRecords - $presentRecords;
        $attendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0;

        $weeks = DB::table('weeks')->orderBy('id')->get();
        $programs = DB::table('programs')->orderBy('program_name')->get();
        $modules = DB::table('modules')
            ->where('semester', 'Semester 2')
            ->select('id', 'module_name', 'program_id')
            ->orderBy('module_name')
            ->get();

        return view('reports.management_attendance', compact(
            'records',
            'weeks',
            'programs',
            'modules',
            'totalRecords',
            'presentRecords',
            'absentRecords',
            'attendanceRate'
        ));
    }
}
