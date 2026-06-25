@extends('layouts.app')
@section('page-title', 'Rector Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero" style="background:linear-gradient(135deg,#7f1d1d 0%,#b91c1c 45%,#dc2626 70%,#f59e0b 100%)">
    <div class="dash-hero-eyebrow"><i class='bx bx-crown'></i> Office of the Rector</div>
    <h1 class="dash-hero-title">Welcome, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('l, d M Y') }}</span>
        <span class="dash-hero-chip"><i class='bx bx-bar-chart-alt-2'></i> Institution Attendance: {{ $attendanceRate }}%</span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('reports.management') }}" class="dash-hero-btn primary"><i class='bx bx-file-blank'></i> System Report</a>
        <a href="{{ route('departments.index') }}"  class="dash-hero-btn ghost"><i class='bx bx-buildings'></i> Departments</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-rose">
            <div class="dash-stat-icon"><i class='bx bx-graduation'></i></div>
            <div class="dash-stat-value">{{ $totalStudents }}</div>
            <div class="dash-stat-label">Total Students</div>
            <div class="dash-stat-footer"><i class='bx bx-group'></i> Institution-wide enrollment</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat dash-stat-amber">
            <div class="dash-stat-icon"><i class='bx bx-chalkboard'></i></div>
            <div class="dash-stat-value">{{ $totalLecturers }}</div>
            <div class="dash-stat-label">Lecturers</div>
            <div class="dash-stat-footer"><i class='bx bx-user-check'></i> Teaching staff</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dash-stat {{ $attendanceRate >= 75 ? 'dash-stat-green' : 'dash-stat-red' }}">
            <div class="dash-stat-icon"><i class='bx bx-user-check'></i></div>
            <div class="dash-stat-value">{{ $attendanceRate }}%</div>
            <div class="dash-stat-label">Attendance Rate</div>
            <div class="dash-progress mt-2">
                <div class="dash-progress-info">
                    <span>Institution-wide</span>
                    <span>75% target</span>
                </div>
                <div class="dash-progress-track">
                    <div class="dash-progress-fill {{ $attendanceRate < 75 ? 'danger' : '' }}"
                         style="width:{{ min($attendanceRate,100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Department performance --}}
<div class="ent-card">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-buildings'></i> Department Performance</h2>
        <a href="{{ route('departments.index') }}" class="ent-btn ent-btn-sm ent-btn-ghost">All Departments</a>
    </div>
    <div class="ent-card-body" style="padding:0">
        @if($departmentPerformance->isEmpty())
            <div class="ent-empty">
                <i class='bx bx-buildings'></i>
                <p>No department data available yet.</p>
            </div>
        @else
            <table class="ent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Department</th>
                        <th>Programs</th>
                        <th>Students</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departmentPerformance as $i => $dept)
                        <tr>
                            <td style="color:var(--ent-text-muted);font-weight:600">{{ $i+1 }}</td>
                            <td style="font-weight:600">{{ $dept->department_name }}</td>
                            <td>
                                <span class="ent-badge ent-badge-info">{{ $dept->total_programs }} programs</span>
                            </td>
                            <td style="color:var(--ent-text-muted)">{{ $dept->total_students }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.5rem;min-width:120px">
                                    <div style="flex:1">
                                        <div class="dash-progress-track">
                                            <div class="dash-progress-fill {{ $dept->attendance_rate < 75 ? 'danger' : '' }}"
                                                 style="width:{{ min($dept->attendance_rate,100) }}%"></div>
                                        </div>
                                    </div>
                                    <span class="ent-badge {{ $dept->attendance_rate >= 75 ? 'ent-badge-success' : 'ent-badge-danger' }}">
                                        {{ $dept->attendance_rate }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
