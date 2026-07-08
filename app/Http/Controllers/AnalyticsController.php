<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->role->name;

        $selectedDepartmentId = $request->department_id;
        $selectedProgramId = $request->program_id;
        $selectedModuleId = $request->module_id;

        $hodDepartmentId = $user->hod->department_id ?? $user->department_id;
        $isHod = $role === 'HOD';
        $canFilterDepartment = in_array($role, ['registrar', 'examination_officer', 'quality_assurance', 'director_academic', 'rector']);

        if ($isHod) {
            $selectedDepartmentId = $hodDepartmentId;
        }

        if ($selectedProgramId) {
            $programDepartmentId = DB::table('programs')
                ->where('id', $selectedProgramId)
                ->value('department_id');

            if (!$programDepartmentId || ($selectedDepartmentId && (string) $programDepartmentId !== (string) $selectedDepartmentId)) {
                $selectedProgramId = null;
            }
        }

        if ($selectedModuleId) {
            $moduleScope = DB::table('modules as m')
                ->join('programs as p', 'm.program_id', '=', 'p.id')
                ->where('m.id', $selectedModuleId)
                ->select('m.program_id', 'p.department_id')
                ->first();

            if (
                !$moduleScope
                || ($selectedDepartmentId && (string) $moduleScope->department_id !== (string) $selectedDepartmentId)
                || ($selectedProgramId && (string) $moduleScope->program_id !== (string) $selectedProgramId)
            ) {
                $selectedModuleId = null;
            }
        }

        $attendanceBase = DB::table('attendances as a')
            ->join('students as s', 'a.student_id', '=', 's.id')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->join('departments as d', 'p.department_id', '=', 'd.id')
            ->leftJoin('weeks as w', 'a.week_id', '=', 'w.id');

        if ($selectedDepartmentId) {
            $attendanceBase->where('d.id', $selectedDepartmentId);
        }

        if ($selectedProgramId) {
            $attendanceBase->where('p.id', $selectedProgramId);
        }

        if ($selectedModuleId) {
            $attendanceBase->where('m.id', $selectedModuleId);
        }

        $summary = (clone $attendanceBase)
            ->selectRaw('COUNT(a.id) as total_records')
            ->selectRaw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records')
            ->selectRaw('COUNT(DISTINCT s.id) as total_students')
            ->first();

        $totalStudents = (int) ($summary->total_students ?? 0);
        $totalRecords = (int) ($summary->total_records ?? 0);
        $present = (int) ($summary->present_records ?? 0);
        $absent = $totalRecords - $present;
        $attendanceRate = $totalRecords > 0 ? round(($present / $totalRecords) * 100, 1) : 0;

        $atRiskStudents = (clone $attendanceBase)
            ->select(
                's.id',
                DB::raw('ROUND((SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_percentage')
            )
            ->groupBy('s.id')
            ->having('attendance_percentage', '<', 75)
            ->get()
            ->count();

        $programStats = (clone $attendanceBase)
            ->select(
                'p.program_name',
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                DB::raw('ROUND((SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_percentage')
            )
            ->groupBy('p.id', 'p.program_name')
            ->orderByDesc('attendance_percentage')
            ->get();

        $moduleStats = (clone $attendanceBase)
            ->select(
                'm.module_name',
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                DB::raw('ROUND((SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_percentage')
            )
            ->groupBy('m.id', 'm.module_name')
            ->orderByDesc('attendance_percentage')
            ->limit(8)
            ->get();

        $weeklyStats = (clone $attendanceBase)
            ->select(
                DB::raw('COALESCE(w.week_name, CONCAT("Week ", a.week_id)) as week_label'),
                DB::raw('MIN(a.week_id) as week_sort'),
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                DB::raw('ROUND((SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_percentage')
            )
            ->whereNotNull('a.week_id')
            ->groupBy('week_label')
            ->orderBy('week_sort')
            ->get();

        $departmentStats = $canFilterDepartment
            ? (clone $attendanceBase)
                ->select(
                    'd.department_name',
                    DB::raw('COUNT(a.id) as total_records'),
                    DB::raw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records'),
                    DB::raw('ROUND((SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_percentage')
                )
                ->groupBy('d.id', 'd.department_name')
                ->orderByDesc('attendance_percentage')
                ->get()
            : collect();

        $topPrograms = $programStats->take(5);
        $lowModules = $moduleStats->sortBy('attendance_percentage')->take(5)->values();

        $departments = $canFilterDepartment
            ? DB::table('departments')->orderBy('department_name')->get()
            : collect();

        $programs = DB::table('programs')
            ->when($isHod && $selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->orderBy('program_name')
            ->get();

        $modules = DB::table('modules as m')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->where('m.semester', 'Semester 2')
            ->when($isHod && $selectedDepartmentId, fn ($query) => $query->where('p.department_id', $selectedDepartmentId))
            ->select('m.id', 'm.module_name', 'm.program_id', 'p.department_id')
            ->orderBy('m.module_name')
            ->get();

        $scopeLabel = $isHod
            ? DB::table('departments')->where('id', $selectedDepartmentId)->value('department_name')
            : 'University Wide';

        return view('analytics.dashboard', compact(
            'departments',
            'programs',
            'modules',
            'totalStudents',
            'totalRecords',
            'present',
            'absent',
            'attendanceRate',
            'atRiskStudents',
            'programStats',
            'moduleStats',
            'weeklyStats',
            'departmentStats',
            'topPrograms',
            'lowModules',
            'scopeLabel',
            'isHod',
            'canFilterDepartment',
            'selectedDepartmentId',
            'selectedProgramId',
            'selectedModuleId'
        ));
    }
}
