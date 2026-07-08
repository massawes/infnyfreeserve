<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Program;
use Illuminate\Http\Request;

class ModuleManagementController extends Controller
{
    public function index(Request $request)
    {
        $modules = $this->modulesQuery($request)
            ->paginate(10);

        return view('management.modules.index', compact('modules'));
    }

    public function export(Request $request)
    {
        $modules = $this->modulesQuery($request)
            ->orderBy('module_name')
            ->get();

        return response()->json([
            'sheet_name' => 'Modules',
            'filename' => 'modules-export.xlsx',
            'rows' => $modules->map(fn ($module) => [
                'module_name' => $module->module_name,
                'module_code' => $module->module_code,
                'module_credit' => $module->module_credit,
                'semester' => $module->semester,
                'nta_level' => $module->nta_level,
                'program_name' => $module->program?->program_name,
                'program_id' => $module->program_id,
            ])->values(),
        ]);
    }

    public function create()
    {
        $programs = Program::where('department_id', $this->hodDepartmentId())->get();

        return view('management.modules.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_name' => 'required|string|max:255',
            'module_code' => 'required|string|max:20|unique:modules,module_code',
            'module_credit' => 'required|integer',
            'semester' => 'required|string|max:50',
            'nta_level' => 'required|string|max:50',
            'program_id' => 'required|exists:programs,id',
        ]);

        abort_unless(
            Program::where('id', $validated['program_id'])->where('department_id', $this->hodDepartmentId())->exists(),
            403
        );

        Module::create($validated);

        return redirect()->route('modules.index')->with('success', 'Module created successfully.');
    }

    public function edit($id)
    {
        $module = Module::whereHas('program', function ($query) {
            $query->where('department_id', $this->hodDepartmentId());
        })->findOrFail($id);
        $programs = Program::where('department_id', $this->hodDepartmentId())->get();

        return view('management.modules.edit', compact('module', 'programs'));
    }

    public function update(Request $request, $id)
    {
        $module = Module::whereHas('program', function ($query) {
            $query->where('department_id', $this->hodDepartmentId());
        })->findOrFail($id);

        $validated = $request->validate([
            'module_name' => 'required|string|max:255',
            'module_code' => 'required|string|max:20|unique:modules,module_code,' . $module->id,
            'module_credit' => 'required|integer',
            'semester' => 'required|string|max:50',
            'nta_level' => 'required|string|max:50',
            'program_id' => 'required|exists:programs,id',
        ]);

        abort_unless(
            Program::where('id', $validated['program_id'])->where('department_id', $this->hodDepartmentId())->exists(),
            403
        );

        $module->update($validated);

        return redirect()->route('modules.index')->with('success', 'Module updated successfully.');
    }

    public function destroy($id)
    {
        $module = Module::whereHas('program', function ($query) {
            $query->where('department_id', $this->hodDepartmentId());
        })->findOrFail($id);
        $module->delete();

        return redirect()->route('modules.index')->with('success', 'Module deleted successfully.');
    }

    private function hodDepartmentId(): ?int
    {
        return auth()->user()->hod->department_id ?? auth()->user()->department_id;
    }

    private function modulesQuery(Request $request)
    {
        return Module::with('program')
            ->whereHas('program', function ($query) {
                $query->where('department_id', $this->hodDepartmentId());
            })
            ->where('semester', 'Semester 2')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('module_name', 'like', "%{$search}%")
                        ->orWhere('module_code', 'like', "%{$search}%")
                        ->orWhere('semester', 'like', "%{$search}%")
                        ->orWhere('nta_level', 'like', "%{$search}%")
                        ->orWhereHas('program', fn ($programQuery) => $programQuery->where('program_name', 'like', "%{$search}%"));
                });
            });
    }
}
