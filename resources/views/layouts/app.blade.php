<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('page-title')@yield('page-title') — @endif ATC Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"></noscript>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('styles')
    <script>
        (function () {
            var t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>

<body class="ega-app-body">
@php
    $user          = auth()->user();
    $roleName      = strtolower($user->role->name ?? '');
    $isStudent     = $roleName === 'student';
    $isLecturer    = $roleName === 'lecturer';
    $isHod         = $roleName === 'hod';
    $isRegistrar   = $roleName === 'registrar';
    $isExamOfficer = $roleName === 'examination_officer';
    $isQa          = $roleName === 'quality_assurance';
    $isDirector    = $roleName === 'director_academic';
    $isRector      = $roleName === 'rector';

    // Group open states
    $hodReportOpen    = request()->routeIs('hodreport','hod.analysis');
    $hodModuleOpen    = request()->routeIs('moduledistribute.create','moduledistribute.index');
    $hodDataOpen      = request()->routeIs('students.*','hod.users.*','programs.*','modules.*',
                            'lecturers.*','roles.*','weeks.*','class-timings.*','role-permissions.*');
    $lecTeachOpen     = request()->routeIs('lecturerreport','lecturerclasstiming','lecturerclasses',
                            'students.*','attendanceindex','attendance.records.*');
    $lecAnalysisOpen  = request()->routeIs('lecturerireport');
    $lecDevicesOpen   = request()->routeIs('devices.*');
    $stuAcademicsOpen = request()->routeIs('studentdashboard','studentmodules','studenttimetable');

    $isActive = fn(...$p) => collect($p)->contains(fn($x) => request()->routeIs($x));
    $ac       = fn(...$p) => $isActive(...$p) ? 'active' : '';

    $roleLabels = [
        'student'             => ['label' => 'Student',              'icon' => 'bx-graduation'],
        'lecturer'            => ['label' => 'Lecturer',             'icon' => 'bx-chalkboard'],
        'hod'                 => ['label' => 'Head of Department',   'icon' => 'bx-briefcase-alt-2'],
        'registrar'           => ['label' => 'Registrar',            'icon' => 'bx-id-card'],
        'examination_officer' => ['label' => 'Examination Officer',  'icon' => 'bx-check-shield'],
        'quality_assurance'   => ['label' => 'Quality Assurance',    'icon' => 'bx-badge-check'],
        'director_academic'   => ['label' => 'Director Academic',    'icon' => 'bx-building'],
        'rector'              => ['label' => 'Rector',               'icon' => 'bx-crown'],
    ];
    $ri = $roleLabels[$roleName] ?? ['label' => ucfirst($roleName), 'icon' => 'bx-user'];
@endphp

<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

<div class="ega-shell">

{{-- ═══════════════════════════════════════════════════════ SIDEBAR ════════ --}}
<aside id="appSidebar" class="ega-sidebar" aria-label="Navigation">

    {{-- Brand --}}
    <div class="ega-sidebar-brand">
        <a href="{{ route($isStudent ? 'studentdashboard' : ($isLecturer ? 'lecturerdashboard' : ($isHod ? 'hoddashboard' : 'home'))) }}" class="ega-sidebar-brand-inner" style="text-decoration:none">
            <div class="ega-sidebar-logo">
                <img src="{{ asset('images/logo.png') }}" alt="ATC">
            </div>
            <div>
                <div class="ega-sidebar-brand-name">ATC System</div>
                <div class="ega-sidebar-brand-sub">Attendance Portal</div>
            </div>
        </a>
        <button class="ega-sidebar-close d-md-none" onclick="closeSidebar()" aria-label="Close">
            <i class="bx bx-x"></i>
        </button>
    </div>

    {{-- Role pill --}}
    <div class="ega-sidebar-role">
        <i class='bx {{ $ri["icon"] }}'></i>
        {{ $ri['label'] }}
    </div>

    {{-- Navigation --}}
    <nav class="ega-sidebar-nav">
        <ul class="ega-nav-list">

        {{-- ████████ HOD ████████████████████████████████████████████████████ --}}
        @if ($isHod)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('hoddashboard') }}" class="ega-nav-link {{ $ac('hoddashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics.dashboard') }}" class="ega-nav-link {{ $ac('analytics.dashboard') }}">
                    <i class='bx bx-bar-chart-alt-2'></i><span>Statistics</span>
                </a>
            </li>

            <li><div class="ega-nav-section">Reports</div></li>
            <li>
                <details class="ega-nav-group {{ $hodReportOpen ? 'is-open' : '' }}" @if($hodReportOpen) open @endif>
                    <summary>
                        <div class="ega-nav-group-toggle">
                            <i class='bx bx-file-blank'></i><span>Reports</span>
                            <i class='bx bx-chevron-down ega-caret ms-auto'></i>
                        </div>
                    </summary>
                    <div class="ega-nav-sub">
                        <a href="{{ route('hodreport') }}" class="ega-nav-sub-link {{ $ac('hodreport') }}">
                            <i class='bx bx-list-ul'></i> Lecturer Modules
                        </a>
                        <a href="{{ route('hod.analysis') }}" class="ega-nav-sub-link {{ $ac('hod.analysis') }}">
                            <i class='bx bx-line-chart'></i> Analysis
                        </a>
                    </div>
                </details>
            </li>

            <li><div class="ega-nav-section">Modules</div></li>
            <li>
                <details class="ega-nav-group {{ $hodModuleOpen ? 'is-open' : '' }}" @if($hodModuleOpen) open @endif>
                    <summary>
                        <div class="ega-nav-group-toggle">
                            <i class='bx bx-book-open'></i><span>Module Distribution</span>
                            <i class='bx bx-chevron-down ega-caret ms-auto'></i>
                        </div>
                    </summary>
                    <div class="ega-nav-sub">
                        <a href="{{ route('moduledistribute.create') }}" class="ega-nav-sub-link {{ $ac('moduledistribute.create') }}">
                            <i class='bx bx-plus-circle'></i> Assign Module
                        </a>
                        <a href="{{ route('moduledistribute.index') }}" class="ega-nav-sub-link {{ $ac('moduledistribute.index') }}">
                            <i class='bx bx-git-branch'></i> Distribution
                        </a>
                    </div>
                </details>
            </li>

            <li><div class="ega-nav-section">Data Management</div></li>
            <li>
                <details class="ega-nav-group {{ $hodDataOpen ? 'is-open' : '' }}" @if($hodDataOpen) open @endif>
                    <summary>
                        <div class="ega-nav-group-toggle">
                            <i class='bx bx-data'></i><span>Data Entry</span>
                            <i class='bx bx-chevron-down ega-caret ms-auto'></i>
                        </div>
                    </summary>
                    <div class="ega-nav-sub">
                        <a href="{{ route('students.index') }}"      class="ega-nav-sub-link {{ $ac('students.*') }}">
                            <i class='bx bx-graduation'></i> Students
                        </a>
                        <a href="{{ route('hod.users.index') }}"     class="ega-nav-sub-link {{ $ac('hod.users.*') }}">
                            <i class='bx bx-group'></i> Users
                        </a>
                        <a href="{{ route('programs.index') }}"      class="ega-nav-sub-link {{ $ac('programs.*') }}">
                            <i class='bx bx-bookmark'></i> Programs
                        </a>
                        <a href="{{ route('modules.index') }}"       class="ega-nav-sub-link {{ $ac('modules.*') }}">
                            <i class='bx bx-book'></i> Modules
                        </a>
                        <a href="{{ route('lecturers.index') }}"     class="ega-nav-sub-link {{ $ac('lecturers.*') }}">
                            <i class='bx bx-chalkboard'></i> Lecturers
                        </a>
                        <a href="{{ route('roles.index') }}"         class="ega-nav-sub-link {{ $ac('roles.*') }}">
                            <i class='bx bx-shield'></i> Roles
                        </a>
                        <a href="{{ route('weeks.index') }}"         class="ega-nav-sub-link {{ $ac('weeks.*') }}">
                            <i class='bx bx-calendar-week'></i> Weeks
                        </a>
                        <a href="{{ route('class-timings.index') }}" class="ega-nav-sub-link {{ $ac('class-timings.*') }}">
                            <i class='bx bx-time'></i> Class Timings
                        </a>
                        <a href="{{ route('role-permissions.index') }}" class="ega-nav-sub-link {{ $ac('role-permissions.*') }}">
                            <i class='bx bx-lock-open-alt'></i> Permissions
                        </a>
                    </div>
                </details>
            </li>
        @endif

        {{-- ████████ LECTURER ███████████████████████████████████████████████ --}}
        @if ($isLecturer)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('lecturerdashboard') }}" class="ega-nav-link {{ $ac('lecturerdashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>

            <li><div class="ega-nav-section">Teaching</div></li>
            <li>
                <details class="ega-nav-group {{ $lecTeachOpen ? 'is-open' : '' }}" @if($lecTeachOpen) open @endif>
                    <summary>
                        <div class="ega-nav-group-toggle">
                            <i class='bx bx-chalkboard'></i><span>Teaching & Attendance</span>
                            <i class='bx bx-chevron-down ega-caret ms-auto'></i>
                        </div>
                    </summary>
                    <div class="ega-nav-sub">
                        <a href="{{ route('lecturerreport') }}"         class="ega-nav-sub-link {{ $ac('lecturerreport') }}">
                            <i class='bx bx-book-open'></i> My Modules
                        </a>
                        <a href="{{ route('lecturerclasstiming') }}"    class="ega-nav-sub-link {{ $ac('lecturerclasstiming') }}">
                            <i class='bx bx-calendar-check'></i> Class Timetable
                        </a>
                        <a href="{{ route('lecturerclasses') }}"        class="ega-nav-sub-link {{ $ac('lecturerclasses') }}">
                            <i class='bx bx-door-open'></i> Classes
                        </a>
                        <a href="{{ route('students.index') }}"         class="ega-nav-sub-link {{ $ac('students.*') }}">
                            <i class='bx bx-graduation'></i> Students
                        </a>
                        <a href="{{ route('attendanceindex') }}"        class="ega-nav-sub-link {{ $ac('attendanceindex') }}">
                            <i class='bx bx-user-check'></i> Manual Attendance
                        </a>
                        <a href="{{ route('attendance.records.index') }}" class="ega-nav-sub-link {{ $ac('attendance.records.*') }}">
                            <i class='bx bx-spreadsheet'></i> Attendance Records
                        </a>
                    </div>
                </details>
            </li>

            <li><div class="ega-nav-section">Analysis & Devices</div></li>
            <li>
                <details class="ega-nav-group {{ $lecAnalysisOpen ? 'is-open' : '' }}" @if($lecAnalysisOpen) open @endif>
                    <summary>
                        <div class="ega-nav-group-toggle">
                            <i class='bx bx-line-chart'></i><span>Analysis</span>
                            <i class='bx bx-chevron-down ega-caret ms-auto'></i>
                        </div>
                    </summary>
                    <div class="ega-nav-sub">
                        <a href="{{ route('lecturerireport') }}" class="ega-nav-sub-link {{ $ac('lecturerireport') }}">
                            <i class='bx bx-pie-chart-alt-2'></i> Attendance Analysis
                        </a>
                    </div>
                </details>
            </li>
            <li>
                <details class="ega-nav-group {{ $lecDevicesOpen ? 'is-open' : '' }}" @if($lecDevicesOpen) open @endif>
                    <summary>
                        <div class="ega-nav-group-toggle">
                            <i class='bx bx-devices'></i><span>Device Management</span>
                            <i class='bx bx-chevron-down ega-caret ms-auto'></i>
                        </div>
                    </summary>
                    <div class="ega-nav-sub">
                        <a href="{{ route('devices.index') }}" class="ega-nav-sub-link {{ $ac('devices.*') }}">
                            <i class='bx bx-fingerprint'></i> Biometric Devices
                        </a>
                    </div>
                </details>
            </li>
        @endif

        {{-- ████████ STUDENT ████████████████████████████████████████████████ --}}
        @if ($isStudent)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('studentdashboard') }}" class="ega-nav-link {{ $ac('studentdashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>

            <li><div class="ega-nav-section">My Studies</div></li>
            <li>
                <a href="{{ route('studentmodules') }}" class="ega-nav-link {{ $ac('studentmodules') }}">
                    <i class='bx bx-book'></i><span>My Modules</span>
                </a>
            </li>
            <li>
                <a href="{{ route('studenttimetable') }}" class="ega-nav-link {{ $ac('studenttimetable') }}">
                    <i class='bx bx-calendar'></i><span>My Timetable</span>
                </a>
            </li>

            <li><div class="ega-nav-section">Account</div></li>
            <li>
                <a href="{{ route('profile.edit') }}" class="ega-nav-link {{ $ac('profile.edit') }}">
                    <i class='bx bx-user-circle'></i><span>My Profile</span>
                </a>
            </li>
        @endif

        {{-- ████████ REGISTRAR ██████████████████████████████████████████████ --}}
        @if ($isRegistrar)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('registrardashboard') }}" class="ega-nav-link {{ $ac('registrardashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Reports & Statistics</div></li>
            <li>
                <a href="{{ route('management.attendance-report') }}" class="ega-nav-link {{ $ac('management.attendance-report') }}">
                    <i class='bx bx-file-blank'></i><span>Attendance Report</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics.dashboard') }}" class="ega-nav-link {{ $ac('analytics.dashboard') }}">
                    <i class='bx bx-bar-chart-alt-2'></i><span>Statistics</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Account</div></li>
            <li>
                <a href="{{ route('profile.edit') }}" class="ega-nav-link {{ $ac('profile.edit') }}">
                    <i class='bx bx-user-circle'></i><span>My Profile</span>
                </a>
            </li>
        @endif

        {{-- ████████ EXAMINATION OFFICER ████████████████████████████████████ --}}
        @if ($isExamOfficer)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('examdashboard') }}" class="ega-nav-link {{ $ac('examdashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Examinations</div></li>
            <li>
                <a href="{{ route('exam.eligibility') }}" class="ega-nav-link {{ $ac('exam.eligibility') }}">
                    <i class='bx bx-check-shield'></i><span>Eligibility List</span>
                </a>
            </li>
            <li>
                <a href="{{ route('exam.timetable') }}" class="ega-nav-link {{ $ac('exam.timetable') }}">
                    <i class='bx bx-calendar-alt'></i><span>Exam Timetable</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Reports & Users</div></li>
            <li>
                <a href="{{ route('exam.reports') }}" class="ega-nav-link {{ $ac('exam.reports') }}">
                    <i class='bx bx-file-blank'></i><span>Attendance Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('management.attendance-report') }}" class="ega-nav-link {{ $ac('management.attendance-report') }}">
                    <i class='bx bx-clipboard'></i><span>Attendance Report</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics.dashboard') }}" class="ega-nav-link {{ $ac('analytics.dashboard') }}">
                    <i class='bx bx-bar-chart-alt-2'></i><span>Statistics</span>
                </a>
            </li>
            <li>
                <a href="{{ route('users.index') }}" class="ega-nav-link {{ $ac('users.*') }}">
                    <i class='bx bx-group'></i><span>Manage Users</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Account</div></li>
            <li>
                <a href="{{ route('profile.edit') }}" class="ega-nav-link {{ $ac('profile.edit') }}">
                    <i class='bx bx-user-circle'></i><span>My Profile</span>
                </a>
            </li>
        @endif

        {{-- ████████ QUALITY ASSURANCE ██████████████████████████████████████ --}}
        @if ($isQa)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('qadashboard') }}" class="ega-nav-link {{ $ac('qadashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Quality Control</div></li>
            <li>
                <a href="{{ route('management.attendance-report') }}" class="ega-nav-link {{ $ac('management.attendance-report') }}">
                    <i class='bx bx-file-blank'></i><span>Attendance Report</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics.dashboard') }}" class="ega-nav-link {{ $ac('analytics.dashboard') }}">
                    <i class='bx bx-bar-chart-alt-2'></i><span>Statistics</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Account</div></li>
            <li>
                <a href="{{ route('profile.edit') }}" class="ega-nav-link {{ $ac('profile.edit') }}">
                    <i class='bx bx-user-circle'></i><span>My Profile</span>
                </a>
            </li>
        @endif

        {{-- ████████ DIRECTOR ACADEMIC ██████████████████████████████████████ --}}
        @if ($isDirector)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('directordashboard') }}" class="ega-nav-link {{ $ac('directordashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Academic Leadership</div></li>
            <li>
                <a href="{{ route('director.faculties') }}" class="ega-nav-link {{ $ac('director.faculties') }}">
                    <i class='bx bx-building'></i><span>Faculties & Departments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('departments.index') }}" class="ega-nav-link {{ $ac('departments.*') }}">
                    <i class='bx bx-category'></i><span>Departments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('hods.index') }}" class="ega-nav-link {{ $ac('hods.*') }}">
                    <i class='bx bx-briefcase-alt-2'></i><span>Heads of Department</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics.dashboard') }}" class="ega-nav-link {{ $ac('analytics.dashboard') }}">
                    <i class='bx bx-bar-chart-alt-2'></i><span>Statistics</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Account</div></li>
            <li>
                <a href="{{ route('profile.edit') }}" class="ega-nav-link {{ $ac('profile.edit') }}">
                    <i class='bx bx-user-circle'></i><span>My Profile</span>
                </a>
            </li>
        @endif

        {{-- ████████ RECTOR █████████████████████████████████████████████████ --}}
        @if ($isRector)
            <li><div class="ega-nav-section">Overview</div></li>
            <li>
                <a href="{{ route('rectordashboard') }}" class="ega-nav-link {{ $ac('rectordashboard') }}">
                    <i class='bx bx-tachometer'></i><span>Dashboard</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Executive Leadership</div></li>
            <li>
                <a href="{{ route('management.attendance-report') }}" class="ega-nav-link {{ $ac('management.attendance-report') }}">
                    <i class='bx bx-file-blank'></i><span>Attendance Report</span>
                </a>
            </li>
            <li>
                <a href="{{ route('analytics.dashboard') }}" class="ega-nav-link {{ $ac('analytics.dashboard') }}">
                    <i class='bx bx-bar-chart-alt-2'></i><span>Statistics</span>
                </a>
            </li>
            <li><div class="ega-nav-section">Account</div></li>
            <li>
                <a href="{{ route('profile.edit') }}" class="ega-nav-link {{ $ac('profile.edit') }}">
                    <i class='bx bx-user-circle'></i><span>My Profile</span>
                </a>
            </li>
        @endif

        </ul>
    </nav>

    {{-- Sidebar footer / user --}}
    <div class="ega-sidebar-footer">
        <div class="ega-sidebar-user">
            <div class="ega-sidebar-user-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            <div class="ega-sidebar-user-info">
                <div class="ega-sidebar-user-name">{{ $user->name }}</div>
                <div class="ega-sidebar-user-role">{{ $ri['label'] }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit" class="ega-sidebar-logout" title="Log Out">
                    <i class='bx bx-log-out'></i>
                </button>
            </form>
        </div>
        <div class="ega-sidebar-footer-note">
            <i class='bx bx-shield-check'></i> eGA Tanzania · ATC &copy; {{ date('Y') }}
        </div>
    </div>

</aside>
{{-- ══════════════════════════════════════════════════ END SIDEBAR ══════════ --}}

{{-- Main --}}
<div class="ega-main">
    @include('layouts.navigation')

    <div class="ega-content">
        {{-- Flash messages --}}
        @if (session('success'))
            <div class="ega-alert ega-alert-success alert alert-dismissible" role="alert">
                <i class='bx bx-check-circle'></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="ega-alert ega-alert-danger alert alert-dismissible" role="alert">
                <i class='bx bx-error-circle'></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    {{-- Footer --}}
    <footer class="ega-app-footer">
        <span>ATC Student Attendance Management System</span>
        <span class="ega-footer-divider">·</span>
        <span><i class='bx bx-shield-check'></i> Inafuata viwango vya eGA Tanzania</span>
        <span class="ega-footer-divider">·</span>
        <span>&copy; {{ date('Y') }} ATC. Haki zote zimehifadhiwa.</span>
    </footer>
</div>

</div>{{-- .ega-shell --}}

<script>
    // ── Sidebar toggle ──────────────────────────────────────────────────────────
    function openSidebar() {
        document.getElementById('appSidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        document.getElementById('appSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }
    function toggleSidebar() {
        if (window.innerWidth < 768) {
            var sidebar = document.getElementById('appSidebar');
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        } else {
            var shell = document.querySelector('.ega-shell');
            var hidden = shell.classList.toggle('sidebar-hidden');
            localStorage.setItem('sidebarHidden', hidden);
        }
    }

    // ── Dark / Light mode ───────────────────────────────────────────────────────
    function toggleTheme() {
        var html = document.documentElement;
        var isDark = html.getAttribute('data-theme') === 'dark';
        var next = isDark ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        _applyThemeIcon(next);
    }
    function _applyThemeIcon(theme) {
        var btn = document.getElementById('themeToggleBtn');
        if (!btn) return;
        var icon = btn.querySelector('i');
        if (theme === 'dark') {
            icon.className = 'bx bx-sun';
            btn.title = 'Badilisha kwenye Mwanga (Light Mode)';
        } else {
            icon.className = 'bx bx-moon';
            btn.title = 'Badilisha kwenye Giza (Dark Mode)';
        }
    }

    // ── Init on page load ───────────────────────────────────────────────────────
    (function () {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
        _applyThemeIcon(theme);

        if (window.innerWidth >= 768 && localStorage.getItem('sidebarHidden') === 'true') {
            document.querySelector('.ega-shell').classList.add('sidebar-hidden');
        }
    })();
</script>
@stack('scripts')
</body>
</html>
