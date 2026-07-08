<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\ModuleDistribution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LecturerController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $distributions = ModuleDistribution::where('user_id', $user->id)
            ->whereHas('module', function ($query) {
                $query->where('semester', 'Semester 2');
            })
            ->with('module.program')
            ->get();

        $totalModules = $distributions->count();
        $totalClasses = DB::table('class_timings')
            ->join('module_distributions as md', 'class_timings.module_distribution_id', '=', 'md.id')
            ->where('md.user_id', $user->id)
            ->count();

        $totalStudents = DB::table('students as s')
            ->join('programs as p', 's.program_id', '=', 'p.id')
            ->join('modules as m', 'm.program_id', '=', 'p.id')
            ->join('module_distributions as md', 'md.module_id', '=', 'm.id')
            ->where('md.user_id', $user->id)
            ->count(DB::raw('DISTINCT s.id'));

        $recentModules = $distributions->take(3);

        return view('dashboards.Lecturer', compact(
            'distributions',
            'totalModules',
            'totalClasses',
            'totalStudents',
            'recentModules'
        ));
    }

    public function index(Request $request)
    {
        $lecturers = Lecturer::with(['user', 'department'])
            ->where('department_id', $this->hodDepartmentId())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('lecturer_name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
                });
            })
            ->paginate(10);

        return view('management.lecturers.index', compact('lecturers'));
    }

    public function export(Request $request)
    {
        $lecturers = Lecturer::with(['user', 'department'])
            ->where('department_id', $this->hodDepartmentId())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('lecturer_name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy('lecturer_name')
            ->get();

        return response()->json([
            'sheet_name' => 'Lecturers',
            'filename' => 'lecturers-export.xlsx',
            'rows' => $lecturers->map(fn ($lecturer) => [
                'lecturer_name' => $lecturer->lecturer_name,
                'email' => $lecturer->user?->email,
                'department_name' => $lecturer->department?->department_name,
            ])->values(),
        ]);
    }

    public function create()
    {
        $departments = Department::where('id', $this->hodDepartmentId())->get();

        return view('management.lecturers.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'lecturer_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->lecturer_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => Role::where('name', 'lecturer')->first()->id ?? 2,
                'department_id' => $this->hodDepartmentId(),
            ]);

            Lecturer::create([
                'user_id' => $user->id,
                'lecturer_name' => $request->lecturer_name,
                'department_id' => $this->hodDepartmentId(),
            ]);
        });

        return redirect()->route('lecturers.index')->with('success', 'Lecturer created successfully.');
    }

    public function edit($id)
    {
        $lecturer = Lecturer::with('user')
            ->where('department_id', $this->hodDepartmentId())
            ->findOrFail($id);
        $departments = Department::where('id', $this->hodDepartmentId())->get();

        return view('management.lecturers.edit', compact('lecturer', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $lecturer = Lecturer::where('department_id', $this->hodDepartmentId())->findOrFail($id);
        $user = $lecturer->user;

        $request->validate([
            'lecturer_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $user->update([
            'name' => $request->lecturer_name,
            'email' => $request->email,
            'department_id' => $this->hodDepartmentId(),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $lecturer->update([
            'lecturer_name' => $request->lecturer_name,
            'department_id' => $this->hodDepartmentId(),
        ]);

        return redirect()->route('lecturers.index')->with('success', 'Lecturer updated successfully.');
    }

    public function destroy($id)
    {
        $lecturer = Lecturer::where('department_id', $this->hodDepartmentId())->findOrFail($id);
        optional($lecturer->user)->delete();

        return redirect()->route('lecturers.index')->with('success', 'Lecturer deleted successfully.');
    }

    private function hodDepartmentId(): ?int
    {
        return auth()->user()->hod->department_id ?? auth()->user()->department_id;
    }
}
