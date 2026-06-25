<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassTimingManagementController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DirectorAcademicController;
use App\Http\Controllers\ExaminationOfficerController;
use App\Http\Controllers\HodController;
use App\Http\Controllers\Lecturer\ClassController;
use App\Http\Controllers\Lecturer\ClassTimingController;
use App\Http\Controllers\Lecturer\ReportiController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\ModuleDistributionController;
use App\Http\Controllers\ModuleManagementController;
use App\Http\Controllers\ManagementAttendanceReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramManagementController;
use App\Http\Controllers\SpreadsheetController;
use App\Http\Controllers\QualityAssuranceController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\RectorController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Student\ModuleController as StudentModuleController;
use App\Http\Controllers\Student\TimetableController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WeekManagementController;
use App\Http\Controllers\ZkbioRealtimeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// TEMPORARY SETUP ROUTE — DELETE THIS AFTER FIRST DEPLOYMENT!
// Visit: yourdomain.com/run-setup-once-then-delete
Route::get('/run-setup-once-then-delete', function () {
    if (app()->environment('local')) {
        return 'Not allowed on local.';
    }
    try {
        Artisan::call('migrate', ['--force' => true]);
        $migrate = Artisan::output();
        Artisan::call('storage:link');
        return '<pre>DONE!<br>' . $migrate . '<br>Setup complete. DELETE this route from routes/web.php now!</pre>';
    } catch (\Exception $e) {
        return '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
});

Route::middleware(['auth'])->group(function () {
    Route::get('/zkbio/realtime-sync', [ZkbioRealtimeController::class, 'sync'])
        ->name('zkbio.realtime-sync');

    Route::post('/spreadsheets/import/{entity}', [SpreadsheetController::class, 'import'])
        ->name('spreadsheets.import');

    Route::get('/analytics/dashboard', function () {
        if (!in_array(auth()->guard('web')->user()->role->name, [
            'HOD',
            'registrar',
            'examination_officer',
            'quality_assurance',
            'director_academic',
            'rector',
        ])) {
            abort(403);
        }

        return app(AnalyticsController::class)->index(request());
    })->name('analytics.dashboard');

    Route::get('/management/attendance-report', [ManagementAttendanceReportController::class, 'index'])
        ->name('management.attendance-report');
});

Route::middleware(['auth'])->group(function () {
    Route::middleware('role:student')->get('/student/dashboard', [StudentController::class, 'dashboard'])->name('studentdashboard');
    Route::middleware('role:lecturer')->get('/lecturer/dashboard', [LecturerController::class, 'dashboard'])->name('lecturerdashboard');
    Route::middleware('role:HOD')->get('/hod/dashboard', [HodController::class, 'dashboard'])->name('hoddashboard');
    Route::middleware('role:registrar')->get('/registrar/dashboard', [RegistrarController::class, 'dashboard'])->name('registrardashboard');

    Route::middleware('role:examination_officer')->group(function () {
        Route::get('/exam/dashboard', [ExaminationOfficerController::class, 'dashboard'])->name('examdashboard');
        Route::get('/exam/eligibility', [ExaminationOfficerController::class, 'eligibility'])->name('exam.eligibility');
        Route::get('/exam/reports', [ExaminationOfficerController::class, 'reports'])->name('exam.reports');
        Route::get('/exam/timetable', [ExaminationOfficerController::class, 'timetable'])->name('exam.timetable');
    });

    Route::middleware('role:quality_assurance')->get('/qa/dashboard', [QualityAssuranceController::class, 'dashboard'])->name('qadashboard');

    Route::middleware('role:director_academic')->group(function () {
        Route::get('/director/dashboard', [DirectorAcademicController::class, 'dashboard'])->name('directordashboard');
        Route::get('/director/faculties', [DirectorAcademicController::class, 'faculties'])->name('director.faculties');
        Route::get('/director-management/departments/export', [DepartmentController::class, 'export'])->name('departments.export');
        Route::get('/director-management/hods/export', [HodController::class, 'export'])->name('hods.export');
        Route::resource('/director-management/departments', DepartmentController::class)->except(['show'])->names('departments');
        Route::resource('/director-management/hods', HodController::class)->except(['show'])->names('hods');
    });

    Route::middleware('role:rector')->get('/rector/dashboard', [RectorController::class, 'dashboard'])->name('rectordashboard');
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'hod'])->group(function () {
    Route::get('/assign-modules', [ModuleDistributionController::class, 'create'])->name('moduledistribute.create');
    Route::post('/assign-modules', [ModuleDistributionController::class, 'store'])->name('moduledistribute.store');
    Route::get('/module-distributions', [ModuleDistributionController::class, 'index'])->name('moduledistribute.index');
    Route::get('/module-distributions/export', [ModuleDistributionController::class, 'export'])->name('moduledistribute.export');
    Route::get('/module-distributions/{id}', [ModuleDistributionController::class, 'show'])->name('moduledistribute.show');
    Route::get('/module-distributions/{id}/edit', [ModuleDistributionController::class, 'edit'])->name('moduledistribute.edit');
    Route::put('/module-distributions/{id}', [ModuleDistributionController::class, 'update'])->name('moduledistribute.update');
    Route::delete('/module-distributions/{id}', [ModuleDistributionController::class, 'destroy'])->name('moduledistribute.destroy');
    Route::get('/hod/report', [ReportController::class, 'hodReport'])->name('hodreport');
    Route::get('/hod/analysis', [ReportiController::class, 'hodIndex'])->name('hod.analysis');

    Route::prefix('hod-management')->group(function () {
        Route::get('users', [UserManagementController::class, 'hodIndex'])->name('hod.users.index');
        Route::post('users/assign-role', [UserManagementController::class, 'hodAssignRole'])->name('hod.users.assign');
        Route::get('modules/export', [ModuleManagementController::class, 'export'])->name('modules.export');
        Route::get('lecturers/export', [LecturerController::class, 'export'])->name('lecturers.export');
        Route::get('programs/export', [ProgramManagementController::class, 'export'])->name('programs.export');
        Route::get('roles/export', [RoleManagementController::class, 'export'])->name('roles.export');
        Route::get('weeks/export', [WeekManagementController::class, 'export'])->name('weeks.export');
        Route::get('class-timings/export', [ClassTimingManagementController::class, 'export'])->name('class-timings.export');
        Route::resource('modules', ModuleManagementController::class)->except(['show'])->names('modules');
        Route::resource('lecturers', LecturerController::class)->except(['show']);
        Route::resource('programs', ProgramManagementController::class)->except(['show']);
        Route::resource('roles', RoleManagementController::class)->except(['show']);
        Route::resource('weeks', WeekManagementController::class)->except(['show']);
        Route::resource('class-timings', ClassTimingManagementController::class)->except(['show']);
        Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
        Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])->name('role-permissions.update');
    });
});

Route::middleware(['auth', 'role:HOD,lecturer'])->group(function () {
    Route::prefix('hod-management')->group(function () {
        Route::get('students/export', [StudentsController::class, 'export'])->name('students.export');
        Route::resource('students', StudentsController::class)->except(['show']);
    });
});

Route::middleware(['auth', 'rolelecturer'])->group(function () {
    Route::get('/lecturer/report', [ReportController::class, 'lecturerReport'])->name('lecturerreport');
    Route::get('/lecturer/attendance', [AttendanceController::class, 'index'])->name('attendanceindex');
    Route::post('/lecturer/attendance/quick-mark', [AttendanceController::class, 'quickMark'])->name('attendance.quick-mark');
    Route::post('/lecturer/attendance/biometric/start', [AttendanceController::class, 'startBiometric'])->name('attendance.biometric.start');
    Route::post('/lecturer/attendance/biometric/stop', [AttendanceController::class, 'stopBiometric'])->name('attendance.biometric.stop');
    Route::post('/lecturer/attendance', [AttendanceController::class, 'store'])->name('attendancestore');
    Route::get('/lecturer/class-timing', [ClassTimingController::class, 'index'])->name('lecturerclasstiming');
    Route::get('/lecturer/classes', [ClassController::class, 'index'])->name('lecturerclasses');
    Route::get('/lecturer/reporti', [ReportiController::class, 'index'])->name('lecturerireport');
    Route::get('/lecturer/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('/lecturer/devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::post('/lecturer/devices/enrollment-request', [DeviceController::class, 'startEnrollment'])->name('devices.enrollment.request');
    Route::delete('/lecturer/devices/{id}', [DeviceController::class, 'destroy'])->name('devices.delete');
    Route::post('/lecturer/devices/{id}/uid', [DeviceController::class, 'updateUID'])->name('devices.uid');
    Route::post('/lecturer/devices/{id}/mode', [DeviceController::class, 'changeMode'])->name('devices.mode');

    Route::prefix('lecturer-management/attendance-records')->name('attendance.records.')->group(function () {
        Route::get('/', [AttendanceController::class, 'recordsIndex'])->name('index');
        Route::get('/create', [AttendanceController::class, 'recordsCreate'])->name('create');
        Route::post('/', [AttendanceController::class, 'recordsStore'])->name('store');
        Route::get('/{attendance}/edit', [AttendanceController::class, 'recordsEdit'])->name('edit');
        Route::put('/{attendance}', [AttendanceController::class, 'recordsUpdate'])->name('update');
        Route::delete('/{attendance}', [AttendanceController::class, 'recordsDestroy'])->name('destroy');
    });
});

Route::middleware(['auth', 'role:examination_officer'])->group(function () {
    Route::prefix('exam-management')->group(function () {
        Route::get('users/export', [UserManagementController::class, 'export'])->name('users.export');
        Route::resource('users', UserManagementController::class)->except(['show']);
    });
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/modules', [StudentModuleController::class, 'index'])->name('studentmodules');
    Route::get('/student/timetable', [TimetableController::class, 'index'])->name('studenttimetable');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
