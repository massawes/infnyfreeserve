@extends('layouts.app')
@section('page-title', 'Student Dashboard')

@section('content')

@php
    $heroColor = $attendanceRate >= 75
        ? 'linear-gradient(135deg,#065f46 0%,#059669 55%,#14b8a6 100%)'
        : 'linear-gradient(135deg,#7f1d1d 0%,#dc2626 55%,#f43f5e 100%)';
@endphp

{{-- Hero --}}
<div class="dash-hero" style="background:{{ $heroColor }}">
    <div class="dash-hero-eyebrow"><i class='bx bx-graduation'></i> Student Portal</div>
    <h1 class="dash-hero-title">Hello, {{ $student->student_name ?? auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-bookmark'></i> {{ $programName ?? 'Your Program' }}</span>
        @if($departmentName)
            <span class="dash-hero-chip"><i class='bx bx-buildings'></i> {{ $departmentName }}</span>
        @endif
        <span class="dash-hero-chip">
            @if($attendanceRate >= 75)
                <i class='bx bx-check-circle'></i> Attendance Good
            @else
                <i class='bx bx-error-circle'></i> Attendance Below 75%
            @endif
        </span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('studentmodules') }}"  class="dash-hero-btn primary"><i class='bx bx-book'></i> My Modules</a>
        <a href="{{ route('studenttimetable') }}" class="dash-hero-btn ghost"><i class='bx bx-calendar'></i> Timetable</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="dash-stat {{ $attendanceRate >= 75 ? 'dash-stat-green' : 'dash-stat-red' }}">
            <div class="dash-stat-icon">
                <i class='bx bx-user-check'></i>
            </div>
            <div class="dash-stat-value">{{ $attendanceRate }}%</div>
            <div class="dash-stat-label">Attendance Rate</div>
            <div class="dash-progress mt-2">
                <div class="dash-progress-info">
                    <span>{{ $attendanceStatus }}</span>
                    <span>75% required</span>
                </div>
                <div class="dash-progress-track">
                    <div class="dash-progress-fill {{ $attendanceRate < 75 ? 'danger' : '' }}"
                         style="width:{{ min($attendanceRate, 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-book-open'></i></div>
            <div class="dash-stat-value">{{ $totalModules }}</div>
            <div class="dash-stat-label">Enrolled Modules</div>
            <div class="dash-stat-footer"><i class='bx bx-check'></i> This semester</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-calendar-check'></i></div>
            <div class="dash-stat-value">{{ $totalRecords }}</div>
            <div class="dash-stat-label">Total Sessions</div>
            <div class="dash-stat-footer"><i class='bx bx-history'></i> Recorded sessions</div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="ent-card mb-4">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
    </div>
    <div class="ent-card-body">
        <div class="dash-action-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr))">
            <a href="{{ route('studentmodules') }}"  class="dash-action-card">
                <div class="dash-action-icon"><i class='bx bx-book'></i></div>
                <div class="dash-action-label">My Modules</div>
            </a>
            <a href="{{ route('studenttimetable') }}" class="dash-action-card">
                <div class="dash-action-icon"><i class='bx bx-calendar'></i></div>
                <div class="dash-action-label">Timetable</div>
            </a>
            <a href="{{ route('profile.edit') }}"    class="dash-action-card">
                <div class="dash-action-icon"><i class='bx bx-user-circle'></i></div>
                <div class="dash-action-label">My Profile</div>
            </a>
        </div>
    </div>
</div>

{{-- Recent attendance --}}
<div class="ent-card">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-history'></i> Recent Attendance</h2>
        <span class="ent-badge ent-badge-primary">{{ $recentAttendance->count() }} records</span>
    </div>
    <div class="ent-card-body" style="padding:0">
        @if($recentAttendance->isEmpty())
            <div class="ent-empty">
                <i class='bx bx-calendar-x'></i>
                <p>No attendance records yet.</p>
            </div>
        @else
            <table class="ent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Module</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAttendance as $i => $rec)
                        <tr>
                            <td style="color:var(--ent-text-muted);font-weight:600">{{ $i+1 }}</td>
                            <td style="font-weight:600">{{ $rec->module_name }}</td>
                            <td style="color:var(--ent-text-muted)">{{ $rec->date }}</td>
                            <td>
                                @if($rec->is_present)
                                    <span class="ent-badge ent-badge-success"><i class='bx bx-check-circle'></i> Present</span>
                                @else
                                    <span class="ent-badge ent-badge-danger"><i class='bx bx-x-circle'></i> Absent</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
