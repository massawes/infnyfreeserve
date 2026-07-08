<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleDistribution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ModuleDistributionController extends Controller
{
    public function index(Request $request)
    {
        $distributions = $this->distributionsQuery($request)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('management.module_distributions.index', compact('distributions'));
    }

    public function export(Request $request)
    {
        $distributions = $this->distributionsQuery($request)
            ->latest()
            ->get();

        return response()->json([
            'sheet_name' => 'Module Distributions',
            'filename' => 'module-distributions.xlsx',
            'rows' => $distributions->map(fn ($distribution) => [
                'module_code' => $distribution->module?->module_code,
                'module_name' => $distribution->module?->module_name,
                'lecturer_name' => $distribution->lecturer?->name,
                'academic_year' => $distribution->academic_year,
            ])->values(),
        ]);
    }

    public function create(Request $request)
    {
        $departmentId = $this->hodDepartmentId();
        $selectedAcademicYear = $this->selectedAcademicYear($request);

        $modules = Module::whereHas('program', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->where('semester', 'Semester 2')->orderBy('module_name')->paginate(6)->withQueryString();

        $lecturers = User::where('role_id', 2)
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();

        $existingDistributions = ModuleDistribution::query()
            ->whereIn('module_id', $modules->getCollection()->pluck('id'))
            ->where('academic_year', $selectedAcademicYear)
            ->get()
            ->keyBy('module_id');

        return view('module_distributions.create', compact(
            'modules',
            'lecturers',
            'selectedAcademicYear',
            'existingDistributions'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string|max:20',
            'distributions' => 'required|array',
        ]);

        $academicYear = trim($validated['academic_year']);
        $distributions = collect($validated['distributions'])
            ->filter(fn ($userId, $moduleId) => filled($userId))
            ->filter(fn ($userId, $moduleId) => $this->isAllowedModule($moduleId) && $this->isAllowedLecturer($userId));

        if ($distributions->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Select at least one lecturer before saving.');
        }

        try {
            $created = 0;
            $updated = 0;

            DB::transaction(function () use ($distributions, $academicYear, &$created, &$updated) {
                foreach ($distributions as $moduleId => $userId) {
                    $distribution = ModuleDistribution::updateOrCreate(
                        [
                            'module_id' => $moduleId,
                            'academic_year' => $academicYear,
                        ],
                        [
                            'user_id' => $userId,
                        ]
                    );

                    $distribution->wasRecentlyCreated ? $created++ : $updated++;
                }
            });

            return redirect()
                ->route('moduledistribute.index', ['academic_year' => $academicYear])
                ->with('success', "Lecturers assigned successfully. Created {$created}, updated {$updated}.");
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Something went wrong while saving module distributions.');
        }
    }

    public function show($id)
    {
        $distribution = ModuleDistribution::with(['module.program', 'lecturer'])
            ->whereHas('module.program', function ($query) {
                $query->where('department_id', $this->hodDepartmentId());
            })
            ->findOrFail($id);

        return view('module_distributions.show', compact('distribution'));
    }

    public function edit($id)
    {
        $distribution = ModuleDistribution::whereHas('module.program', function ($query) {
            $query->where('department_id', $this->hodDepartmentId());
        })->findOrFail($id);

        $departmentId = $this->hodDepartmentId();

        $modules = Module::whereHas('program', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        })->where('semester', 'Semester 2')->get();

        $lecturers = User::where('role_id', 2)
            ->where('department_id', $departmentId)
            ->get();

        return view('module_distributions.edit', compact('distribution', 'modules', 'lecturers'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'user_id' => 'required|exists:users,id',
            'academic_year' => 'required|string|max:20',
        ]);

        abort_unless($this->isAllowedModule($request->module_id), 403);
        abort_unless($this->isAllowedLecturer($request->user_id), 403);

        $distribution = ModuleDistribution::whereHas('module.program', function ($query) {
            $query->where('department_id', $this->hodDepartmentId());
        })->findOrFail($id);

        $distribution->update([
            'module_id' => $request->module_id,
            'user_id' => $request->user_id,
            'academic_year' => $request->academic_year,
        ]);

        return redirect()->route('moduledistribute.index')->with('success', 'Distribution updated successfully!');
    }

    public function destroy($id)
    {
        $distribution = ModuleDistribution::whereHas('module.program', function ($query) {
            $query->where('department_id', $this->hodDepartmentId());
        })->findOrFail($id);

        $distribution->delete();

        return redirect()->route('moduledistribute.index')->with('success', 'Distribution deleted successfully!');
    }

    private function hodDepartmentId(): ?int
    {
        return auth()->user()->hod->department_id ?? auth()->user()->department_id;
    }

    private function isAllowedModule(int|string $moduleId): bool
    {
        return Module::where('id', $moduleId)
            ->where('semester', 'Semester 2')
            ->whereHas('program', function ($query) {
                $query->where('department_id', $this->hodDepartmentId());
            })
            ->exists();
    }

    private function isAllowedLecturer(int|string $userId): bool
    {
        return User::where('id', $userId)
            ->where('role_id', 2)
            ->where('department_id', $this->hodDepartmentId())
            ->exists();
    }

    private function distributionsQuery(Request $request)
    {
        $departmentId = $this->hodDepartmentId();

        return ModuleDistribution::with(['module.program', 'lecturer'])
            ->whereHas('module.program', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->when($request->filled('academic_year'), function ($query) use ($request) {
                $query->where('academic_year', trim((string) $request->academic_year));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('academic_year', 'like', "%{$search}%")
                        ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->where('module_name', 'like', "%{$search}%"))
                        ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->where('module_code', 'like', "%{$search}%"))
                        ->orWhereHas('lecturer', fn ($lecturerQuery) => $lecturerQuery->where('name', 'like', "%{$search}%"));
                });
            });
    }

    private function selectedAcademicYear(Request $request): string
    {
        if ($request->filled('academic_year')) {
            return trim((string) $request->academic_year);
        }

        $latestAcademicYear = ModuleDistribution::query()
            ->whereHas('module.program', function ($query) {
                $query->where('department_id', $this->hodDepartmentId());
            })
            ->max('academic_year');

        return $latestAcademicYear ?: $this->defaultAcademicYear();
    }

    private function defaultAcademicYear(): string
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');

        if ($month >= 7) {
            return $year . '/' . ($year + 1);
        }

        return ($year - 1) . '/' . $year;
    }
}
