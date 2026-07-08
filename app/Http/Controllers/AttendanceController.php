<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricAttendanceSession;
use App\Models\ClassTiming;
use App\Models\ModuleDistribution;
use App\Models\Student;
use App\Models\Week;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $lecturer_id = auth()->id();
        $hasAttendanceSource = Schema::hasColumn('attendances', 'attendance_source');
        $hasAdminNumber = Schema::hasColumn('students', 'admin_number');
        $perPage = 10;

        $selectedWeek = $request->week_id;
        $selectedCourse = $request->course_id;
        $selectedDay = $request->day;
        $selectedSubject = $request->subject;

        $weeks = DB::table('weeks')->orderBy('id')->get();

        $courses = DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->join('programs as p', 'm.program_id', '=', 'p.id')
            ->where('md.user_id', $lecturer_id)
            ->select('p.id', 'p.program_name')
            ->distinct()
            ->orderBy('p.program_name')
            ->get();

        $days = DB::table('class_timings')
            ->join('module_distributions as md', 'class_timings.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->when($selectedCourse, fn ($query) => $query->where('m.program_id', $selectedCourse))
            ->where('md.user_id', $lecturer_id)
            ->select('day')
            ->distinct()
            ->orderBy('day')
            ->get();

        $subjectsQuery = DB::table('module_distributions as md')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->where('md.user_id', $lecturer_id)
            ->where('m.semester', 'Semester 2');

        if ($selectedCourse) {
            $subjectsQuery->where('m.program_id', $selectedCourse);
        }

        if ($selectedDay) {
            $subjectsQuery
                ->join('class_timings as ct', 'ct.module_distribution_id', '=', 'md.id')
                ->where('ct.day', $selectedDay);
        }

        $subjects = $subjectsQuery
            ->select('m.module_name as subject')
            ->distinct()
            ->orderBy('m.module_name')
            ->get();

        $students = collect();
        $classAdminNumbers = collect();
        $activeBiometricSession = null;
        $biometricPresentCount = 0;

        if ($selectedWeek && $selectedCourse && $selectedDay && $selectedSubject) {
            $classContext = $this->resolveClassContext($lecturer_id, $selectedCourse, $selectedDay, $selectedSubject);

            if (! $classContext) {
                return view('lecturer.attendance', compact(
                    'students',
                    'classAdminNumbers',
                    'weeks',
                    'courses',
                    'days',
                    'subjects',
                    'selectedWeek',
                    'selectedCourse',
                    'selectedDay',
                    'selectedSubject',
                    'activeBiometricSession',
                    'biometricPresentCount'
                ));
            }

            $activeBiometricSession = BiometricAttendanceSession::query()
                ->where('lecturer_id', $lecturer_id)
                ->where('week_id', $selectedWeek)
                ->where('course_id', $selectedCourse)
                ->where('class_timing_id', $classContext->class_timing_id)
                ->where('module_distribution_id', $classContext->module_distribution_id)
                ->where('is_active', true)
                ->latest()
                ->first();

            $biometricPresentCount = Attendance::query()
                ->where('attendance_source', 'zkbio')
                ->where('week_id', $selectedWeek)
                ->where('class_timing_id', $classContext->class_timing_id)
                ->where('module_distribution_id', $classContext->module_distribution_id)
                ->where('is_present', true)
                ->count();

            $classAdminNumbers = DB::table('class_timings as ct')
                ->join('module_distributions as md', 'ct.module_distribution_id', '=', 'md.id')
                ->join('modules as m', 'md.module_id', '=', 'm.id')
                ->join('programs as p', 'm.program_id', '=', 'p.id')
                ->join('students as s', 'p.id', '=', 's.program_id')
                ->where('md.user_id', $lecturer_id)
                ->where('ct.id', $classContext->class_timing_id)
                ->where('md.id', $classContext->module_distribution_id)
                ->when($hasAdminNumber, fn ($query) => $query->whereNotNull('s.admin_number'))
                ->select('s.admin_number')
                ->distinct()
                ->orderBy('s.admin_number')
                ->pluck('s.admin_number');

            $students = DB::table('class_timings as ct')
                ->join('module_distributions as md', 'ct.module_distribution_id', '=', 'md.id')
                ->join('modules as m', 'md.module_id', '=', 'm.id')
                ->join('programs as p', 'm.program_id', '=', 'p.id')
                ->join('students as s', 'p.id', '=', 's.program_id')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->leftJoin('attendances as a', function ($join) use ($selectedWeek) {
                    $join->on('ct.id', '=', 'a.class_timing_id')
                        ->on('s.id', '=', 'a.student_id')
                        ->on('md.id', '=', 'a.module_distribution_id')
                        ->where('a.week_id', $selectedWeek);
                })
                ->where('md.user_id', $lecturer_id)
                ->where('ct.id', $classContext->class_timing_id)
                ->where('md.id', $classContext->module_distribution_id)
                ->select(
                    'u.name as student_name',
                    's.id as student_id',
                    'ct.id as class_timing_id',
                    'ct.day',
                    'ct.time',
                    'ct.room',
                    'm.module_name as subject',
                    'md.id as module_distribution_id',
                    'a.id as attendance_id',
                    'a.is_present as is_present'
                )
                ->when($hasAdminNumber, fn ($query) => $query->addSelect('s.admin_number'))
                ->when(! $hasAdminNumber, fn ($query) => $query->addSelect(DB::raw('NULL as admin_number')))
                ->whereNull('a.id')
                ->orderBy('ct.day')
                ->orderBy('ct.time')
                ->orderBy('u.name')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('lecturer.attendance', compact(
            'students',
            'classAdminNumbers',
            'weeks',
            'courses',
            'days',
            'subjects',
            'selectedWeek',
            'selectedCourse',
            'selectedDay',
            'selectedSubject',
            'activeBiometricSession',
            'biometricPresentCount'
        ));
    }

    public function startBiometric(Request $request)
    {
        $validated = $request->validate([
            'week_id' => 'required|exists:weeks,id',
            'course_id' => 'required|exists:programs,id',
            'day' => 'required|string',
            'subject' => 'required|string',
        ]);

        $lecturerId = auth()->id();
        $classContext = $this->resolveClassContext(
            $lecturerId,
            $validated['course_id'],
            $validated['day'],
            $validated['subject']
        );

        if (! $classContext) {
            return back()->with('error', 'Class context not found for the selected filters.');
        }

        BiometricAttendanceSession::query()
            ->where('lecturer_id', $lecturerId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
            ]);

        $sessionData = [
            'lecturer_id' => $lecturerId,
            'week_id' => $validated['week_id'],
            'course_id' => $validated['course_id'],
            'module_distribution_id' => $classContext->module_distribution_id,
            'class_timing_id' => $classContext->class_timing_id,
            'day' => $validated['day'],
            'subject' => $validated['subject'],
            'started_at' => now(),
            'is_active' => true,
        ];

        if (Schema::hasColumn('biometric_attendance_sessions', 'zkbio_start_transaction_id')) {
            $sessionData['zkbio_start_transaction_id'] = Schema::hasTable('iclock_transaction')
                ? (int) DB::table('iclock_transaction')->max('id')
                : 0;
        }

        BiometricAttendanceSession::create($sessionData);

        return redirect()->route('attendanceindex', $validated)
            ->with('success', 'Biometric attendance started. Only new scans after this start will be synced.');
    }

    public function stopBiometric(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:biometric_attendance_sessions,id',
        ]);

        $session = BiometricAttendanceSession::where('lecturer_id', auth()->id())
            ->whereKey($validated['session_id'])
            ->firstOrFail();

        $session->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        return redirect()->route('attendanceindex', [
            'week_id' => $session->week_id,
            'course_id' => $session->course_id,
            'day' => $session->day,
            'subject' => $session->subject,
        ])->with('success', 'Biometric attendance stopped.');
    }

    public function quickMark(Request $request)
    {
        if (! Schema::hasColumn('students', 'admin_number')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Admin number search is not available yet.',
                ], 422);
            }

            return back()->with('error', 'Admin number search is not available yet.');
        }

        $validated = $request->validate([
            'week_id' => 'required|exists:weeks,id',
            'course_id' => 'required|exists:programs,id',
            'day' => 'required|string',
            'subject' => 'required|string',
            'admin_number' => 'required|string|max:50',
        ]);

        $lecturerId = auth()->id();
        $hasAttendanceSource = Schema::hasColumn('attendances', 'attendance_source');
        $adminNumber = trim((string) $validated['admin_number']);

        $classContext = $this->resolveClassContext(
            $lecturerId,
            $validated['course_id'],
            $validated['day'],
            $validated['subject']
        );

        if (! $classContext) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Class context not found for the selected filters.',
                ], 422);
            }

            return back()->with('error', 'Class context not found for the selected filters.');
        }

        $studentQuery = Student::query()
            ->where('program_id', $validated['course_id'])
            ->whereNotNull('admin_number');

        $student = (clone $studentQuery)
            ->where('admin_number', $adminNumber)
            ->first();

        if (! $student) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Student not found for that admin number.',
                ], 404);
            }

            return back()->with('error', 'Student not found for that admin number.');
        }

        $attributes = [
            'student_id' => $student->id,
            'module_distribution_id' => $classContext->module_distribution_id,
            'class_timing_id' => $classContext->class_timing_id,
            'week_id' => $validated['week_id'],
        ];

        if ($hasAttendanceSource) {
            $attributes['attendance_source'] = 'manual';
        }

        $values = [
            'academic_year' => date('Y'),
            'date' => now()->toDateString(),
            'is_present' => 1,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        DB::table('attendances')->updateOrInsert($attributes, $values);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $student->student_name . ' marked present successfully.',
                'student_name' => $student->student_name,
                'student_id' => $student->id,
                'admin_number' => $student->admin_number,
            ]);
        }

        return redirect()->route('attendanceindex', [
            'week_id' => $validated['week_id'],
            'course_id' => $validated['course_id'],
            'day' => $validated['day'],
            'subject' => $validated['subject'],
        ])->with('success', $student->student_name . ' marked present successfully.')
          ->with('attendance_sound', 'present');
    }

    public function store(Request $request)
    {
        if (!$request->has('attendance')) {
            return back()->with('error', 'No attendance data');
        }

        $hasAttendanceSource = Schema::hasColumn('attendances', 'attendance_source');

        foreach ($request->attendance as $student_id => $data) {
            if (!isset($data['status']) || $data['status'] === '') {
                continue;
            }

            $is_present = $data['status'] === 'present' ? 1 : 0;

            $attributes = [
                'student_id' => $student_id,
                'module_distribution_id' => $data['module_distribution_id'],
                'class_timing_id' => $data['class_timing_id'],
                'week_id' => $data['week_id'],
            ];

            if ($hasAttendanceSource) {
                $attributes['attendance_source'] = 'manual';
            }

            $values = [
                'academic_year' => date('Y'),
                'date' => now()->toDateString(),
                'is_present' => $is_present,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            DB::table('attendances')->updateOrInsert($attributes, $values);
        }

        return back()->with('success', 'Attendance saved successfully');
    }

    public function recordsIndex(Request $request)
    {
        if (! auth()->user()->hasPermission('view_attendance')) {
            abort(403, 'You are not authorized to view attendance records.');
        }

        $hasAttendanceSource = Schema::hasColumn('attendances', 'attendance_source');
        $sourceFilter = $hasAttendanceSource ? $request->input('source', 'all') : 'all';
        $showRawZkbioLogs = $request->boolean('show_raw_logs');
        $latestZkbioLogs = collect();
        $zkbioStudentMap = collect();
        $zkbioSyncMap = collect();

        $attendances = Attendance::with([
            'student.user',
            'moduleDistribution.module',
            'classTiming',
            'week',
        ])
            ->when($request->filled('search'), function ($query) use ($request, $hasAttendanceSource) {
                $search = $request->search;
                $query->where(function ($innerQuery) use ($search, $hasAttendanceSource) {
                    $innerQuery->where('academic_year', 'like', "%{$search}%")
                        ->orWhere('date', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('student_name', 'like', "%{$search}%"))
                        ->orWhereHas('moduleDistribution.module', fn ($moduleQuery) => $moduleQuery->where('module_name', 'like', "%{$search}%"));

                    if ($hasAttendanceSource) {
                        $innerQuery->orWhere('attendance_source', 'like', "%{$search}%");
                    }
                });
            })
            ->when($hasAttendanceSource && $sourceFilter !== 'all', function ($query) use ($sourceFilter) {
                if ($sourceFilter === 'manual') {
                    $query->where(function ($innerQuery) {
                        $innerQuery->where('attendance_source', 'manual')
                            ->orWhereNull('attendance_source');
                    });

                    return;
                }

                $query->where('attendance_source', $sourceFilter);
            })
            ->latest()
            ->paginate(15);

        if ($showRawZkbioLogs && Schema::hasTable('iclock_transaction')) {
            $latestZkbioLogs = DB::table('iclock_transaction')
                ->select('id', 'emp_code', 'punch_time', 'terminal_sn', 'verify_type', 'is_attendance')
                ->where('is_attendance', 1)
                ->whereDate('punch_time', now()->toDateString())
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            $empCodes = $latestZkbioLogs
                ->pluck('emp_code')
                ->filter()
                ->map(fn ($empCode) => (string) $empCode)
                ->unique()
                ->values();

            if ($empCodes->isNotEmpty()) {
                $numericEmpCodes = $empCodes
                    ->filter(fn ($empCode) => ctype_digit($empCode))
                    ->map(fn ($empCode) => (int) $empCode)
                    ->values();
                $zkbioAdminNumbers = $empCodes
                    ->map(fn ($empCode) => 'ZKBIO-' . $empCode)
                    ->values();

                $students = Student::query()
                    ->select('id', 'student_name', 'admin_number', 'fingerprint_id')
                    ->where(function ($query) use ($empCodes, $numericEmpCodes, $zkbioAdminNumbers) {
                        if ($numericEmpCodes->isNotEmpty()) {
                            $query->whereIn('fingerprint_id', $numericEmpCodes);
                        }

                        if (Schema::hasColumn('students', 'admin_number')) {
                            $query->orWhereIn('admin_number', $empCodes)
                                ->orWhereIn('admin_number', $zkbioAdminNumbers);
                        }
                    })
                    ->get();

                $students->each(function ($student) use ($zkbioStudentMap) {
                    if ($student->fingerprint_id !== null) {
                        $zkbioStudentMap->put((string) $student->fingerprint_id, $student);
                    }

                    if ($student->admin_number) {
                        $zkbioStudentMap->put($student->admin_number, $student);

                        if (str_starts_with($student->admin_number, 'ZKBIO-')) {
                            $zkbioStudentMap->put(substr($student->admin_number, 6), $student);
                        }
                    }
                });
            }

            if (Schema::hasTable('zkbio_attendance_syncs') && $latestZkbioLogs->isNotEmpty()) {
                $zkbioSyncMap = DB::table('zkbio_attendance_syncs')
                    ->select('zkbio_transaction_id', 'status', 'message', 'attendance_id')
                    ->whereIn('zkbio_transaction_id', $latestZkbioLogs->pluck('id'))
                    ->get()
                    ->keyBy('zkbio_transaction_id');
            }
        }

        return view('management.attendance.index', compact(
            'attendances',
            'hasAttendanceSource',
            'sourceFilter',
            'showRawZkbioLogs',
            'latestZkbioLogs',
            'zkbioStudentMap',
            'zkbioSyncMap'
        ));
    }

    public function recordsCreate()
    {
        if (! auth()->user()->hasPermission('create_attendance')) {
            abort(403, 'You are not authorized to create attendance records.');
        }

        return view('management.attendance.create', $this->attendanceFormData());
    }

    public function recordsStore(Request $request)
    {
        if (! auth()->user()->hasPermission('create_attendance')) {
            abort(403, 'You are not authorized to create attendance records.');
        }

        $validated = $this->validateAttendanceRecord($request);
        if (Schema::hasColumn('attendances', 'attendance_source')) {
            $validated['attendance_source'] = 'manual';
        }

        Attendance::create($validated);

        return redirect()->route('attendance.records.index')->with('success', 'Attendance record created successfully.');
    }

    public function recordsEdit(Attendance $attendance)
    {
        if (! auth()->user()->hasPermission('edit_attendance')) {
            abort(403, 'You are not authorized to edit attendance records.');
        }

        return view('management.attendance.edit', array_merge(
            $this->attendanceFormData(),
            compact('attendance')
        ));
    }

    public function recordsUpdate(Request $request, Attendance $attendance)
    {
        if (! auth()->user()->hasPermission('edit_attendance')) {
            abort(403, 'You are not authorized to edit attendance records.');
        }

        $validated = $this->validateAttendanceRecord($request);

        $attendance->update($validated);

        return redirect()->route('attendance.records.index')->with('success', 'Attendance record updated successfully.');
    }

    public function recordsDestroy(Attendance $attendance)
    {
        if (! auth()->user()->hasPermission('delete_attendance')) {
            abort(403, 'You are not authorized to delete attendance records.');
        }

        $attendance->delete();

        return redirect()->route('attendance.records.index')->with('success', 'Attendance record deleted successfully.');
    }

    private function attendanceFormData(): array
    {
        return [
            'students' => Student::with('user')->orderBy('student_name')->get(),
            'moduleDistributions' => ModuleDistribution::with('module')
                ->whereHas('module', function ($query) {
                    $query->where('semester', 'Semester 2');
                })
                ->get(),
            'classTimings' => $this->hasAttendanceColumn('class_timing_id')
                ? ClassTiming::orderBy('day')->orderBy('time')->get()
                : collect(),
            'weeks' => $this->hasAttendanceColumn('week_id')
                ? Week::orderBy('id')->get()
                : collect(),
            'hasClassTiming' => $this->hasAttendanceColumn('class_timing_id'),
            'hasWeek' => $this->hasAttendanceColumn('week_id'),
        ];
    }

    private function validateAttendanceRecord(Request $request): array
    {
        $rules = [
            'module_distribution_id' => 'required|exists:module_distributions,id',
            'student_id' => 'required|exists:students,id',
            'academic_year' => 'required|string|max:20',
            'date' => 'required|date',
            'is_present' => 'required|boolean',
        ];

        if ($this->hasAttendanceColumn('class_timing_id')) {
            $rules['class_timing_id'] = 'nullable|exists:class_timings,id';
        }

        if ($this->hasAttendanceColumn('week_id')) {
            $rules['week_id'] = 'nullable|exists:weeks,id';
        }

        return $request->validate($rules);
    }

    private function hasAttendanceColumn(string $column): bool
    {
        return Schema::hasColumn('attendances', $column);
    }

    private function resolveClassContext(int $lecturerId, string $courseId, string $day, string $subject): ?object
    {
        return DB::table('class_timings as ct')
            ->join('module_distributions as md', 'ct.module_distribution_id', '=', 'md.id')
            ->join('modules as m', 'md.module_id', '=', 'm.id')
            ->where('md.user_id', $lecturerId)
            ->where('m.program_id', $courseId)
            ->where('ct.day', $day)
            ->where('m.module_name', $subject)
            ->select(
                'ct.id as class_timing_id',
                'ct.day',
                'ct.time',
                'md.id as module_distribution_id',
                'm.program_id'
            )
            ->first();
    }
}
