<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ClassTimingController extends Controller
{
    public function index()
    {
        $lecturer_id = auth()->id();

        // 🔥 pata timetable ya lecturer kupitia modules alizopewa
        $timetable = DB::table('class_timings as ct')
            ->join('module_distributions as md', 'ct.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')

            ->where('md.user_id', $lecturer_id) // 👈 muhimu sana
            ->where('m.semester', 'Semester 2')

            ->select(
                'ct.day',
                'ct.time',
                'm.module_name as subject',
                'ct.room',
                'm.module_name'
            )
            ->orderBy('ct.time')
            ->get();

        // 🔥 kupanga grid (MUHIMU SANA)
        $days = ['monday','tuesday','wednesday','thursday','friday'];

        $formatted = [];

        foreach ($timetable as $t) {
            $formatted[$t->time][strtolower($t->day)] = $t;
        }

        return view('lecturer.class_timing', compact('formatted','days'));
    }
}