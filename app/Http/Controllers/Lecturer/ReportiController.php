<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportiController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $lecturerId = $user->id;

        $selectedWeek = $request->input('week_id');
        $selectedModule = $request->input('module_id');
        $selectedNtaLevel = $request->input('nta_level');

        $hasWeekColumn = Schema::hasColumn('attendances', 'week_id');
        $weeks = $hasWeekColumn && Schema::hasTable('weeks')
            ? DB::table('weeks')
                ->select('id', 'week_name')
                ->orderBy('id')
                ->get()
            : collect();

        $modules = DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->where('md.user_id', $lecturerId)
            ->where('m.semester', 'Semester 2')
            ->select(
                'm.id',
                'm.module_name',
                'm.module_code',
                'm.nta_level',
                'p.program_name'
            )
            ->distinct()
            ->orderBy('m.module_name')
            ->get();

        $selectedModuleOption = filled($selectedModule)
            ? $modules->firstWhere('id', (int) $selectedModule)
            : null;

        if ($selectedModuleOption && blank($selectedNtaLevel)) {
            $selectedNtaLevel = $selectedModuleOption->nta_level;
        }

        $ntaLevels = DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->where('md.user_id', $lecturerId)
            ->select('m.nta_level')
            ->distinct()
            ->orderBy('m.nta_level')
            ->pluck('nta_level');

        $moduleWeekMap = DB::table('attendances as a')
            ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->where('md.user_id', $lecturerId)
            ->select('m.id as module_id', 'a.week_id')
            ->distinct()
            ->orderBy('a.week_id')
            ->get()
            ->groupBy('module_id')
            ->map(fn ($items) => $items->pluck('week_id')->filter()->values()->all())
            ->all();

        $selectedModuleLabel = $selectedModuleOption?->module_name;
        $selectedModuleDisplayLabel = $selectedModuleOption
            ? "{$selectedModuleOption->module_name} ({$selectedModuleOption->module_code}) - {$selectedModuleOption->program_name} - NTA {$selectedModuleOption->nta_level}"
            : null;

        $records = collect();
        $summary = (object) [
            'total_records' => 0,
            'present_records' => 0,
            'total_students' => 0,
            'total_modules' => 0,
            'attendance_rate' => 0,
        ];

        $showResults = filled($selectedWeek) || filled($selectedModule) || filled($selectedNtaLevel);

        if ($request->boolean('export') && ! $showResults) {
            return response()->json([
                'sheet_name' => 'Lecturer Attendance Analysis',
                'filename' => 'lecturer-attendance-analysis.xlsx',
                'rows' => [],
                'message' => 'Select at least one filter before exporting.',
            ]);
        }

        if ($showResults) {
            $baseQuery = DB::table('attendances as a')
                ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
                ->join('modules as m', 'md.module_id', '=', 'm.id')
                ->join('programs as p', 'm.program_id', '=', 'p.id')
                ->join('students as s', function ($join) {
                    $join->on('a.student_id', '=', 's.id')
                        ->on('s.program_id', '=', 'm.program_id');
                })
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('users as lecturer', 'md.user_id', '=', 'lecturer.id')
                ->where('md.user_id', $lecturerId);

            if ($hasWeekColumn && filled($selectedWeek)) {
                $baseQuery->leftJoin('weeks as w', 'a.week_id', '=', 'w.id');
                $baseQuery->where('a.week_id', $selectedWeek);
            } elseif ($hasWeekColumn) {
                $baseQuery->leftJoin('weeks as w', 'a.week_id', '=', 'w.id');
            }

            if (filled($selectedModule)) {
                $baseQuery->where('m.id', $selectedModule);
            }

            if (filled($selectedNtaLevel)) {
                $baseQuery->where('m.nta_level', $selectedNtaLevel);
            }

            $attendanceCount = 'COUNT(a.id)';
            $presentCount = 'SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END)';
            $weekSelect = $hasWeekColumn
                ? 'COALESCE(w.week_name, CONCAT("Week ", a.week_id)) as week_name'
                : '"N/A" as week_name';

            $records = (clone $baseQuery)
                ->select(
                    'u.name as student_name',
                    'p.program_name',
                    'm.module_name',
                    'm.nta_level',
                    DB::raw($weekSelect),
                    DB::raw("$presentCount as present_count"),
                    DB::raw("CASE WHEN $attendanceCount > 0 THEN ROUND(($presentCount / $attendanceCount) * 100, 2) ELSE 0 END as percentage")
                )
                ->orderBy('m.module_name')
                ->orderBy('u.name');

            if ($hasWeekColumn) {
                $records->groupBy('u.name', 'p.program_name', 'm.module_name', 'm.nta_level', 'a.week_id', 'w.week_name');
            } else {
                $records->groupBy('u.name', 'p.program_name', 'm.module_name', 'm.nta_level');
            }

            $records = $records->get();

            if ($request->boolean('export')) {
                return response()->json([
                    'sheet_name' => 'Lecturer Attendance Analysis',
                    'filename' => 'lecturer-attendance-analysis.xlsx',
                    'rows' => $records->map(fn ($record) => [
                        'student_name' => $record->student_name,
                        'program_name' => $record->program_name,
                        'module_name' => $record->module_name,
                        'nta_level' => $record->nta_level,
                        'week_name' => $record->week_name,
                        'present_count' => $record->present_count,
                        'percentage' => $record->percentage,
                    ])->values(),
                ]);
            }

            $currentPage = max(1, (int) $request->input('page', 1));
            $perPage = 12;
            $total = $records->count();
            $records = new LengthAwarePaginator(
                $records->forPage($currentPage, $perPage)->values(),
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            $summary = (clone $baseQuery)
                ->selectRaw('COUNT(a.id) as total_records')
                ->selectRaw('SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_records')
                ->selectRaw('COUNT(DISTINCT s.id) as total_students')
                ->selectRaw('COUNT(DISTINCT m.id) as total_modules')
                ->first() ?: (object) [
                    'total_records' => 0,
                    'present_records' => 0,
                    'total_students' => 0,
                    'total_modules' => 0,
                ];

            $summary->attendance_rate = (int) ($summary->total_records ?? 0) > 0
                ? round(((int) ($summary->present_records ?? 0) / (int) $summary->total_records) * 100, 2)
                : 0;
        }

        return view('lecturer.report', [
            'lecturerName' => $user->name,
            'weeks' => $weeks,
            'modules' => $modules,
            'ntaLevels' => $ntaLevels,
            'records' => $records,
            'summary' => $summary,
            'selectedWeek' => $selectedWeek,
            'selectedModule' => $selectedModule,
            'selectedModuleLabel' => $selectedModuleLabel,
            'selectedModuleDisplayLabel' => $selectedModuleDisplayLabel,
            'selectedNtaLevel' => $selectedNtaLevel,
            'moduleWeekMap' => $moduleWeekMap,
            'showResults' => $showResults,
            'hasWeekColumn' => $hasWeekColumn,
        ]);
    }

    public function hodIndex(Request $request)
    {
        $departmentId = auth()->user()->department_id;
        $selectedWeek = $request->input('week_id');
        $selectedModule = $request->input('module_id');
        $selectedNtaLevel = $request->input('nta_level');
        $hasWeekColumn = Schema::hasColumn('attendances', 'week_id');

        $weeks = collect();
        if ($hasWeekColumn && Schema::hasTable('weeks')) {
            $weeks = DB::table('weeks')
                ->orderBy('id')
                ->get();
        }

        $modules = DB::table('modules as m')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->where('p.department_id', $departmentId)
            ->where('m.semester', 'Semester 2')
            ->select('m.id', 'm.module_name', 'm.module_code', 'm.nta_level', 'p.program_name')
            ->distinct()
            ->orderBy('m.module_name')
            ->get();

        $selectedModuleOption = filled($selectedModule)
            ? $modules->firstWhere('id', (int) $selectedModule)
            : null;

        if ($selectedModuleOption && blank($selectedNtaLevel)) {
            $selectedNtaLevel = $selectedModuleOption->nta_level;
        }

        $hasAnyFilter = filled($selectedWeek) || filled($selectedModule) || filled($selectedNtaLevel);

        $moduleWeekMap = DB::table('attendances as a')
            ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->where('p.department_id', $departmentId)
            ->select('m.id as module_id', 'a.week_id')
            ->distinct()
            ->orderBy('a.week_id')
            ->get()
            ->groupBy('module_id')
            ->map(fn ($items) => $items->pluck('week_id')->filter()->values()->all())
            ->all();

        if ($request->boolean('export') && ! $hasAnyFilter) {
            return response()->json([
                'sheet_name' => 'HOD Analysis',
                'filename' => 'hod-analysis.xlsx',
                'rows' => [],
                'message' => 'Please apply a filter before exporting.',
            ]);
        }

        $records = collect();
        $summary = (object) [
            'total_records' => 0,
            'present_records' => 0,
            'attendance_rate' => 0,
        ];

        if ($hasAnyFilter) {
            $baseQuery = DB::table('attendances as a')
                ->join('students as s', 'a.student_id', '=', 's.id')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('module_distributions as md', 'a.module_distribution_id', '=', 'md.id')
                ->join('modules as m', 'md.module_id', '=', 'm.id')
                ->join('programs as p', 'm.program_id', '=', 'p.id')
                ->where('p.department_id', $departmentId);

            if ($hasWeekColumn) {
                $baseQuery->leftJoin('weeks as w', 'a.week_id', '=', 'w.id');
            }

            if (filled($selectedWeek) && $hasWeekColumn) {
                $baseQuery->where('a.week_id', $selectedWeek);
            }

            if (filled($selectedModule)) {
                $baseQuery->where('m.id', $selectedModule);
            }

            if (filled($selectedNtaLevel)) {
                $baseQuery->where('m.nta_level', $selectedNtaLevel);
            }

            $records = (clone $baseQuery)
                ->select(
                    'u.name as student_name',
                    'm.module_name',
                    DB::raw($hasWeekColumn ? 'COALESCE(w.week_name, CONCAT("Week ", a.week_id)) as week_name' : '"N/A" as week_name'),
                    DB::raw('COUNT(a.id) as total_classes'),
                    DB::raw('SUM(a.is_present) as present_count'),
                    DB::raw('ROUND((SUM(a.is_present) / COUNT(a.id)) * 100, 2) as percentage')
                )
                ->groupBy(
                    'u.name',
                    'm.module_name',
                    'a.week_id'
                );

            if ($hasWeekColumn) {
                $records->groupBy(DB::raw('COALESCE(w.week_name, CONCAT("Week ", a.week_id))'));
            }

            $records = $records
                ->orderBy('m.module_name')
                ->orderBy('u.name')
                ->get();

            if ($request->boolean('export')) {
                return response()->json([
                    'sheet_name' => 'HOD Analysis',
                    'filename' => 'hod-analysis.xlsx',
                    'rows' => $records->map(fn ($record) => [
                        'student_name' => $record->student_name,
                        'module_name' => $record->module_name,
                        'week_name' => $record->week_name,
                        'total_classes' => $record->total_classes,
                        'present_count' => $record->present_count,
                        'percentage' => $record->percentage,
                    ])->values(),
                ]);
            }

            $currentPage = max(1, (int) $request->input('page', 1));
            $perPage = 8;
            $total = $records->count();
            $records = new LengthAwarePaginator(
                $records->forPage($currentPage, $perPage)->values(),
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            $summary = (clone $baseQuery)
                ->selectRaw('COUNT(a.id) as total_records')
                ->selectRaw('SUM(a.is_present) as present_records')
                ->first();

            $summary->attendance_rate = (int) ($summary->total_records ?? 0) > 0
                ? round(((int) ($summary->present_records ?? 0) / (int) $summary->total_records) * 100, 2)
                : 0;
        }

        return view('reports.hod_analysis', [
            'weeks' => $weeks,
            'modules' => $modules,
            'records' => $records,
            'summary' => $summary,
            'hasAnyFilter' => $hasAnyFilter,
            'hasWeekColumn' => $hasWeekColumn,
            'selectedWeek' => $selectedWeek,
            'selectedModule' => $selectedModule,
            'selectedModuleLabel' => $selectedModuleOption?->module_name,
            'selectedModuleDisplayLabel' => $selectedModuleOption
                ? "{$selectedModuleOption->module_name} ({$selectedModuleOption->module_code}) - {$selectedModuleOption->program_name} - NTA {$selectedModuleOption->nta_level}"
                : null,
            'selectedNtaLevel' => $selectedNtaLevel,
            'moduleWeekMap' => $moduleWeekMap,
        ]);
    }
}
