<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // 🔵 HOD REPORT
    public function hodReport(Request $request)
    {
        $reports = $this->hodReportQuery($request)
            ->orderBy('m.module_name');

        if ($request->boolean('export')) {
            return response()->json([
                'sheet_name' => 'HOD Report',
                'filename' => 'hod-report.xlsx',
                'rows' => $reports->get()->map(fn ($report) => [
                    'module_name' => $report->module_name,
                    'program_name' => $report->program_name,
                    'lecturer_name' => $report->lecturer_name,
                    'nta_level' => $report->nta_level,
                    'academic_year' => $report->academic_year,
                ])->values(),
            ]);
        }

        $reports = $reports->paginate(6)->withQueryString();

        return view('reports.hod_report', compact('reports'));
    }

    // 🟢 LECTURER REPORT
    public function lecturerReport(Request $request)
    {
        $reports = $this->lecturerReportQuery()
            ->orderBy('m.module_name');

        if ($request->boolean('export')) {
            return response()->json([
                'sheet_name' => 'Lecturer Report',
                'filename' => 'lecturer-report.xlsx',
                'rows' => $reports->get()->map(fn ($report) => [
                    'module_name' => $report->module_name,
                    'nta_level' => $report->nta_level,
                    'program_name' => $report->program_name,
                ])->values(),
            ]);
        }

        $reports = $reports->get();

        return view('reports.lecturer_report', compact('reports'));
    }

    private function hodReportQuery(Request $request)
    {
        $departmentId = $this->departmentId();

        return DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->join('users as u', 'md.user_id', '=', 'u.id')
            ->where('p.department_id', $departmentId)
            ->where('m.semester', 'Semester 2')
            ->when($request->filled('academic_year'), function ($query) use ($request) {
                $query->where('md.academic_year', trim((string) $request->academic_year));
            })
            ->select(
                'm.module_name',
                'u.name as lecturer_name',
                'm.nta_level',
                'p.program_name',
                'md.academic_year'
            );
    }

    private function lecturerReportQuery()
    {
        $user = auth()->user();

        return DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->where('md.user_id', $user->id)
            ->where('m.semester', 'Semester 2')
            ->select(
                'm.module_name',
                'm.nta_level',
                'p.program_name'
            );
    }

    private function departmentId(): ?int
    {
        return auth()->user()->hod->department_id ?? auth()->user()->department_id;
    }
}
