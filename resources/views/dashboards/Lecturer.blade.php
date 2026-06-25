@extends('layouts.app')
@section('page-title', 'Lecturer Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero" style="background:linear-gradient(135deg,#065f46 0%,#059669 55%,#0ea5e9 100%)">
    <div class="dash-hero-eyebrow"><i class='bx bx-chalkboard'></i> Lecturer Portal</div>
    <h1 class="dash-hero-title">Good day, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('l, d M Y') }}</span>
        <span class="dash-hero-chip"><i class='bx bx-book'></i> {{ $totalModules }} Modules Assigned</span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('attendanceindex') }}"     class="dash-hero-btn primary"><i class='bx bx-user-check'></i> Mark Attendance</a>
        <a href="{{ route('lecturerclasstiming') }}" class="dash-hero-btn ghost"><i class='bx bx-calendar-check'></i> Timetable</a>
        <a href="{{ route('lecturerireport') }}"     class="dash-hero-btn ghost"><i class='bx bx-bar-chart-alt-2'></i> Analysis</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-navy">
            <div class="dash-stat-icon"><i class='bx bx-book'></i></div>
            <div class="dash-stat-value">{{ $totalModules }}</div>
            <div class="dash-stat-label">Assigned Modules</div>
            <div class="dash-stat-footer"><i class='bx bx-check-circle'></i> Active this semester</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-green">
            <div class="dash-stat-icon"><i class='bx bx-door-open'></i></div>
            <div class="dash-stat-value">{{ $totalClasses }}</div>
            <div class="dash-stat-label">Total Classes</div>
            <div class="dash-stat-footer"><i class='bx bx-time'></i> Scheduled sessions</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-blue">
            <div class="dash-stat-icon"><i class='bx bx-graduation'></i></div>
            <div class="dash-stat-value">{{ $totalStudents }}</div>
            <div class="dash-stat-label">Students</div>
            <div class="dash-stat-footer"><i class='bx bx-group'></i> Enrolled in your modules</div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="ent-card mb-4">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
        <span class="ent-badge ent-badge-success">Shortcuts</span>
    </div>
    <div class="ent-card-body">
        <div class="dash-action-grid">
            <a href="{{ route('lecturerireport') }}"    class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6"><i class='bx bx-pie-chart-alt-2'></i></div>
                <div class="dash-action-label">Attendance Analysis</div>
                <div class="dash-action-desc">View statistics</div>
            </a>
            <a href="{{ route('lecturerclasstiming') }}" class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(20,184,166,.1);color:#14b8a6"><i class='bx bx-calendar-check'></i></div>
                <div class="dash-action-label">Class Timetable</div>
                <div class="dash-action-desc">Schedule & timings</div>
            </a>
            <a href="{{ route('lecturerclasses') }}"    class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(59,130,246,.1);color:#3b82f6"><i class='bx bx-door-open'></i></div>
                <div class="dash-action-label">My Classes</div>
                <div class="dash-action-desc">Manage sessions</div>
            </a>
            <a href="{{ route('attendanceindex') }}"    class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(16,185,129,.1);color:#10b981"><i class='bx bx-pencil'></i></div>
                <div class="dash-action-label">Manual Attendance</div>
                <div class="dash-action-desc">Mark present/absent</div>
            </a>
            <a href="{{ route('attendance.records.index') }}" class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(245,158,11,.1);color:#f59e0b"><i class='bx bx-spreadsheet'></i></div>
                <div class="dash-action-label">Attendance Records</div>
                <div class="dash-action-desc">View all records</div>
            </a>
            <a href="{{ route('devices.index') }}"      class="dash-action-card">
                <div class="dash-action-icon" style="background:rgba(79,70,229,.1);color:#4f46e5"><i class='bx bx-fingerprint'></i></div>
                <div class="dash-action-label">Biometric Devices</div>
                <div class="dash-action-desc">Device management</div>
            </a>
        </div>
    </div>
</div>

{{-- Assigned modules table --}}
<div class="ent-card">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-chalkboard'></i> Assigned Modules</h2>
        <span class="ent-badge ent-badge-primary">{{ $recentModules->count() }} modules</span>
    </div>
    <div class="ent-card-body" style="padding:0">
        @if($recentModules->isEmpty())
            <div class="ent-empty">
                <i class='bx bx-book-open'></i>
                <p>No modules assigned yet.</p>
            </div>
        @else
            <table class="ent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Module Name</th>
                        <th>Program</th>
                        <th>NTA Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentModules as $i => $dist)
                        <tr>
                            <td style="color:var(--ent-text-muted);font-weight:600">{{ $i+1 }}</td>
                            <td style="font-weight:600">{{ $dist->module->module_name ?? 'N/A' }}</td>
                            <td style="color:var(--ent-text-muted)">{{ $dist->module->program->program_name ?? 'N/A' }}</td>
                            <td><span class="ent-badge ent-badge-info">NTA {{ $dist->module->nta_level ?? '—' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
