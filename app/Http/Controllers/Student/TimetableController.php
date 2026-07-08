<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $student = DB::table('students as s')
            ->where('s.user_id', $userId)
            ->first();

        if (! $student) {
            return back()->with('error', 'Student not found');
        }

        $timetable = DB::table('class_timings as ct')
            ->join('module_distributions as md', 'ct.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->where('m.program_id', $student->program_id)
            ->where('m.semester', 'Semester 2')
            ->select(
                'ct.day',
                'ct.time',
                'm.module_name',
                'm.module_code',
                DB::raw('COALESCE(ct.room, \'TBA\') as room')
            )
            ->orderByRaw("CASE LOWER(ct.day)
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
                WHEN 'sunday' THEN 7
                ELSE 8
            END")
            ->orderBy('ct.time')
            ->get();

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $formatted = [];

        foreach ($timetable as $entry) {
            $dayKey = strtolower($entry->day);
            $formatted[$entry->time][$dayKey] = $entry;
        }

        return view('student.timetable', compact('formatted', 'days', 'timetable'));
    }
}
