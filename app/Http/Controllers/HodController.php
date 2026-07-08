<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Hod;
use App\Models\Module;
use App\Models\Program;
use App\Models\Student;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HodController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $hod = Hod::where('user_id', $user->id)->first();
        $department_id = $hod ? $hod->department_id : $user->department_id;

        $department = Department::find($department_id);

        $lecturersCount = \App\Models\Lecturer::where('department_id', $department_id)->count();
        $modulesCount = Module::whereHas('program', function ($query) use ($department_id) {
            $query->where('department_id', $department_id);
        })->where('semester', 'Semester 2')->count();
        $programsCount = Program::where('department_id', $department_id)->count();
        $studentsCount = Student::whereHas('program', function ($query) use ($department_id) {
            $query->where('department_id', $department_id);
        })->count();

        $lecturers = \App\Models\Lecturer::where('department_id', $department_id)
            ->with('user')
            ->orderBy('lecturer_name')
            ->take(5)
            ->get();

        $programs = Program::where('department_id', $department_id)
            ->withCount('modules')
            ->orderBy('program_name')
            ->take(6)
            ->get();

        $moduleDistributions = \App\Models\ModuleDistribution::query()
            ->join('modules as m', 'module_distributions.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->join('users as u', 'module_distributions.user_id', '=', 'u.id')
            ->where('p.department_id', $department_id)
            ->where('m.semester', 'Semester 2')
            ->select(
                'module_distributions.id',
                'm.module_name',
                'm.nta_level',
                'p.program_name',
                'u.name as lecturer_name'
            )
            ->orderBy('m.module_name')
            ->take(6)
            ->get();

        return view('dashboards.Hod', compact(
            'department',
            'lecturersCount',
            'modulesCount',
            'programsCount',
            'studentsCount',
            'lecturers',
            'programs',
            'moduleDistributions'
        ));
    }

    public function index(Request $request)
    {
        $hods = Hod::with(['user', 'department'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('hod_name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('department_name', 'like', "%{$search}%"));
                });
            })
            ->paginate(6);

        return view('management.hods.index', compact('hods'));
    }

    public function export(Request $request)
    {
        $hods = Hod::with(['user', 'department'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('hod_name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('department_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('hod_name')
            ->get();

        return response()->json([
            'sheet_name' => 'HODs',
            'filename' => 'hods-export.xlsx',
            'rows' => $hods->map(fn ($hod) => [
                'hod_name' => $hod->hod_name,
                'email' => $hod->user?->email,
                'department_name' => $hod->department?->department_name,
                'department_id' => $hod->department_id,
            ])->values(),
        ]);
    }

    public function create()
    {
        $departments = Department::all();

        return view('management.hods.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'hod_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'required|exists:departments,id',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->hod_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => Role::where('name', 'HOD')->first()->id ?? 3,
                'department_id' => $request->department_id,
            ]);

            Hod::create([
                'user_id' => $user->id,
                'hod_name' => $request->hod_name,
                'department_id' => $request->department_id,
            ]);

            DB::commit();

            return redirect()->route('hods.index')->with('success', 'HOD created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $hod = Hod::with('user')->findOrFail($id);
        $departments = Department::all();

        return view('management.hods.edit', compact('hod', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $hod = Hod::findOrFail($id);
        $user = $hod->user;

        $request->validate([
            'hod_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'department_id' => 'required|exists:departments,id',
            'password' => 'nullable|string|min:8',
        ]);

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $request->hod_name,
                'email' => $request->email,
                'department_id' => $request->department_id,
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            $hod->update([
                'hod_name' => $request->hod_name,
                'department_id' => $request->department_id,
            ]);

            DB::commit();

            return redirect()->route('hods.index')->with('success', 'HOD updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $hod = Hod::findOrFail($id);
        optional($hod->user)->delete();

        return redirect()->route('hods.index')->with('success', 'HOD deleted successfully.');
    }
}
