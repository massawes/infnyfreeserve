<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $student = DB::table('students as s')
            ->join('programs as p', 's.program_id', '=', 'p.id')
            ->where('s.user_id', $userId)
            ->select('s.program_id', 'p.department_id')
            ->first();

        if (! $student) {
            return back()->with('error', 'Student record not found');
        }

        $modules = DB::table('modules as m')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->leftJoin('module_distributions as md', 'm.id', '=', 'md.module_id')
            ->leftJoin('users as u', 'md.user_id', '=', 'u.id')
            ->where('m.program_id', $student->program_id)
            ->where('p.department_id', $student->department_id)
            ->where('m.semester', 'Semester 2')
            ->select(
                'm.module_code',
                'm.module_name',
                'p.program_name',
                'm.module_credit',
                'm.semester',
                'm.nta_level',
                DB::raw('COALESCE(u.name, "Not Assigned") as lecturer_name')
            )
            ->distinct()
            ->orderBy('m.semester')
            ->orderBy('m.module_name')
            ->get();

        return view('student.modules', compact('modules'));
    }
}
