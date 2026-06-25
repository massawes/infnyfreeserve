@extends('layouts.app')
@section('page-title', 'Registrar Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 55%,#7c3aed 100%)">
    <div class="dash-hero-eyebrow"><i class='bx bx-id-card'></i> Registrar Office</div>
    <h1 class="dash-hero-title">Welcome, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('l, d M Y') }}</span>
        <span class="dash-hero-chip"><i class='bx bx-group'></i> {{ $totalStudents }} Students Enrolled</span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('students.index') }}"    class="dash-hero-btn primary"><i class='bx bx-group'></i> Manage Students</a>
        <a href="{{ route('programs.index') }}"    class="dash-hero-btn ghost"><i class='bx bx-bookmark'></i> Programs</a>
        <a href="{{ route('departments.index') }}" class="dash-hero-btn ghost"><i class='bx bx-buildings'></i> Departments</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-blue">
            <div class="dash-stat-icon"><i class='bx bx-group'></i></div>
            <div class="dash-stat-value">{{ $totalStudents }}</div>
            <div class="dash-stat-label">Total Students</div>
            <div class="dash-stat-footer"><i class='bx bx-graduation'></i> Enrolled across all programs</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-green">
            <div class="dash-stat-icon"><i class='bx bx-bookmark'></i></div>
            <div class="dash-stat-value">{{ $totalPrograms }}</div>
            <div class="dash-stat-label">Total Programs</div>
            <div class="dash-stat-footer"><i class='bx bx-layer'></i> Academic programs offered</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-purple">
            <div class="dash-stat-icon"><i class='bx bx-buildings'></i></div>
            <div class="dash-stat-value">{{ $totalDepartments }}</div>
            <div class="dash-stat-label">Departments</div>
            <div class="dash-stat-footer"><i class='bx bx-category'></i> Academic departments</div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="ent-card mb-4">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
        <span class="ent-badge ent-badge-primary">Shortcuts</span>
    </div>
    <div class="ent-card-body">
        <div class="dash-action-grid">
            <a href="{{ route('students.index') }}"    class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(59,130,246,.1);color:#3b82f6"><i class='bx bx-group'></i></div>
                <div class="dash-action-label">Student List</div>
                <div class="dash-action-desc">All enrolled students</div>
            </a>
            <a href="{{ route('students.create') }}"   class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(16,185,129,.1);color:#10b981"><i class='bx bx-user-plus'></i></div>
                <div class="dash-action-label">Register Student</div>
                <div class="dash-action-desc">Add new student</div>
            </a>
            <a href="{{ route('programs.index') }}"    class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6"><i class='bx bx-bookmark'></i></div>
                <div class="dash-action-label">Programs</div>
                <div class="dash-action-desc">Manage programs</div>
            </a>
            <a href="{{ route('programs.create') }}"   class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(245,158,11,.1);color:#f59e0b"><i class='bx bx-plus-circle'></i></div>
                <div class="dash-action-label">New Program</div>
                <div class="dash-action-desc">Add a program</div>
            </a>
            <a href="{{ route('departments.index') }}" class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(20,184,166,.1);color:#14b8a6"><i class='bx bx-buildings'></i></div>
                <div class="dash-action-label">Departments</div>
                <div class="dash-action-desc">Manage departments</div>
            </a>
            <a href="{{ route('management.users.index') }}" class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(79,70,229,.1);color:#4f46e5"><i class='bx bx-user-pin'></i></div>
                <div class="dash-action-label">User Accounts</div>
                <div class="dash-action-desc">System users</div>
            </a>
            <a href="{{ route('modules.index') }}"     class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(15,76,129,.1);color:#0f4c81"><i class='bx bx-book'></i></div>
                <div class="dash-action-label">Modules</div>
                <div class="dash-action-desc">Academic modules</div>
            </a>
            <a href="{{ route('reports.management') }}" class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(244,63,94,.1);color:#f43f5e"><i class='bx bx-file-blank'></i></div>
                <div class="dash-action-label">Attendance Report</div>
                <div class="dash-action-desc">Full system report</div>
            </a>
        </div>
    </div>
</div>

{{-- Summary metric blocks --}}
<div class="row g-3">
    <div class="col-md-4">
        <div class="dash-metric-block">
            <div class="dash-metric-value" style="color:#3b82f6">{{ $totalStudents }}</div>
            <div class="dash-metric-label"><i class='bx bx-group'></i> Students Enrolled</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dash-metric-block">
            <div class="dash-metric-value" style="color:#10b981">{{ $totalPrograms }}</div>
            <div class="dash-metric-label"><i class='bx bx-bookmark'></i> Academic Programs</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dash-metric-block">
            <div class="dash-metric-value" style="color:#8b5cf6">{{ $totalDepartments }}</div>
            <div class="dash-metric-label"><i class='bx bx-buildings'></i> Departments</div>
        </div>
    </div>
</div>

@endsection
