@extends('layouts.app')
@section('page-title', 'Director of Academics Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero">
    <div class="dash-hero-eyebrow"><i class='bx bx-building'></i> Academic Directorate</div>
    <h1 class="dash-hero-title">Welcome, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('l, d M Y') }}</span>
        <span class="dash-hero-chip"><i class='bx bx-bar-chart-alt-2'></i> Overall Attendance: {{ $overallAttendanceRate }}%</span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('reports.management') }}"    class="dash-hero-btn primary"><i class='bx bx-file-blank'></i> System Report</a>
        <a href="{{ route('departments.index') }}"     class="dash-hero-btn ghost"><i class='bx bx-buildings'></i> Departments</a>
        <a href="{{ route('director.faculties') }}"   class="dash-hero-btn ghost"><i class='bx bx-category'></i> Faculties</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-buildings'></i></div>
            <div class="dash-stat-value">{{ $totalDepartments }}</div>
            <div class="dash-stat-label">Departments</div>
            <div class="dash-stat-footer"><i class='bx bx-category'></i> Academic departments</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-bookmark'></i></div>
            <div class="dash-stat-value">{{ $totalPrograms }}</div>
            <div class="dash-stat-label">Programs</div>
            <div class="dash-stat-footer"><i class='bx bx-layer'></i> Offered programs</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-graduation'></i></div>
            <div class="dash-stat-value">{{ $totalStudents }}</div>
            <div class="dash-stat-label">Students</div>
            <div class="dash-stat-footer"><i class='bx bx-group'></i> Enrolled institution-wide</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat {{ $overallAttendanceRate >= 75 ? 'dash-stat-green' : 'dash-stat-red' }}">
            <div class="dash-stat-icon"><i class='bx bx-user-check'></i></div>
            <div class="dash-stat-value">{{ $overallAttendanceRate }}%</div>
            <div class="dash-stat-label">Attendance Rate</div>
            <div class="dash-progress mt-2">
                <div class="dash-progress-info">
                    <span>Overall</span>
                    <span>75% target</span>
                </div>
                <div class="dash-progress-track">
                    <div class="dash-progress-fill {{ $overallAttendanceRate < 75 ? 'danger' : '' }}"
                         style="width:{{ min($overallAttendanceRate,100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Department performance --}}
    <div class="col-lg-8">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-buildings'></i> Department Performance</h2>
                <a href="{{ route('departments.index') }}" class="ent-btn ent-btn-sm ent-btn-ghost">View All</a>
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
                                        <span class="ent-badge ent-badge-info">{{ $dept->total_programs }}</span>
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
    </div>

    {{-- Right panel --}}
    <div class="col-lg-4">
        {{-- Summary metrics --}}
        <div class="ent-card mb-3">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-bar-chart-alt-2'></i> Key Metrics</h2>
            </div>
            <div class="ent-card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value">{{ $totalDepartments }}</div>
                            <div class="dash-metric-label"><i class='bx bx-buildings'></i> Departments</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value">{{ $totalPrograms }}</div>
                            <div class="dash-metric-label"><i class='bx bx-bookmark'></i> Programs</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value">{{ $totalStudents }}</div>
                            <div class="dash-metric-label"><i class='bx bx-graduation'></i> Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value">{{ $totalLecturers }}</div>
                            <div class="dash-metric-label"><i class='bx bx-chalkboard'></i> Lecturers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="ent-card">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
            </div>
            <div class="ent-card-body">
                <div class="dash-action-grid" style="grid-template-columns:repeat(2,1fr)">
                    <a href="{{ route('departments.index') }}"  class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-buildings'></i></div>
                        <div class="dash-action-label">Departments</div>
                    </a>
                    <a href="{{ route('programs.index') }}"     class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-bookmark'></i></div>
                        <div class="dash-action-label">Programs</div>
                    </a>
                    <a href="{{ route('modules.index') }}"      class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-book'></i></div>
                        <div class="dash-action-label">Modules</div>
                    </a>
                    <a href="{{ route('reports.management') }}" class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-file-blank'></i></div>
                        <div class="dash-action-label">Reports</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
