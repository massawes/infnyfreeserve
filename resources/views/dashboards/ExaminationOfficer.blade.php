@extends('layouts.app')
@section('page-title', 'Examination Officer Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero">
    <div class="dash-hero-eyebrow"><i class='bx bx-check-shield'></i> Examination Office</div>
    <h1 class="dash-hero-title">Welcome, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('l, d M Y') }}</span>
        <span class="dash-hero-chip {{ $attendanceRate >= 75 ? '' : '' }}" style="{{ $attendanceRate >= 75 ? '' : 'background:rgba(239,68,68,.2)' }}">
            <i class='bx bx-bar-chart-alt-2'></i> Overall Attendance: {{ $attendanceRate }}%
        </span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('exam.eligibility') }}"  class="dash-hero-btn primary"><i class='bx bx-list-check'></i> Eligibility List</a>
        <a href="{{ route('exam.timetable') }}"    class="dash-hero-btn ghost"><i class='bx bx-calendar-event'></i> Timetable</a>
        <a href="{{ route('exam.reports') }}"      class="dash-hero-btn ghost"><i class='bx bx-bar-chart-alt-2'></i> Reports</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-graduation'></i></div>
            <div class="dash-stat-value">{{ $totalStudents }}</div>
            <div class="dash-stat-label">Total Students</div>
            <div class="dash-stat-footer"><i class='bx bx-group'></i> Enrolled in system</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat">
            <div class="dash-stat-icon"><i class='bx bx-book'></i></div>
            <div class="dash-stat-value">{{ $totalModules }}</div>
            <div class="dash-stat-label">Total Modules</div>
            <div class="dash-stat-footer"><i class='bx bx-book-open'></i> Active modules</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat {{ $attendanceRate >= 75 ? 'dash-stat-green' : 'dash-stat-amber' }}">
            <div class="dash-stat-icon"><i class='bx bx-user-check'></i></div>
            <div class="dash-stat-value">{{ $attendanceRate }}%</div>
            <div class="dash-stat-label">Attendance Rate</div>
            <div class="dash-progress mt-2">
                <div class="dash-progress-track">
                    <div class="dash-progress-fill {{ $attendanceRate < 75 ? 'warning' : '' }}"
                         style="width:{{ min($attendanceRate,100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat {{ $studentRiskCount > 0 ? 'dash-stat-red' : 'dash-stat-green' }}">
            <div class="dash-stat-icon"><i class='bx bx-error-alt'></i></div>
            <div class="dash-stat-value">{{ $studentRiskCount }}</div>
            <div class="dash-stat-label">At-Risk Students</div>
            <div class="dash-stat-footer"><i class='bx bx-trending-down'></i> Below 75% attendance</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Eligibility snapshot --}}
    <div class="col-lg-5">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-shield-check'></i> Exam Eligibility</h2>
                <a href="{{ route('exam.eligibility') }}" class="ent-btn ent-btn-sm ent-btn-ghost">View All</a>
            </div>
            <div class="ent-card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value" style="color:#10b981">{{ $clearedCount }}</div>
                            <div class="dash-metric-label" style="color:#10b981"><i class='bx bx-check-circle'></i> Cleared</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value" style="color:#ef4444">{{ $notClearedCount }}</div>
                            <div class="dash-metric-label" style="color:#ef4444"><i class='bx bx-x-circle'></i> Not Cleared</div>
                        </div>
                    </div>
                </div>
                @php
                    $total = $clearedCount + $notClearedCount;
                    $clearPct = $total > 0 ? round(($clearedCount / $total) * 100) : 0;
                @endphp
                <div class="dash-progress">
                    <div class="dash-progress-info">
                        <span style="color:#10b981;font-weight:700">{{ $clearPct }}% cleared</span>
                        <span>{{ $total }} module-student records</span>
                    </div>
                    <div class="dash-progress-track">
                        <div class="dash-progress-fill" style="width:{{ $clearPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="col-lg-7">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
            </div>
            <div class="ent-card-body">
                <div class="dash-action-grid" style="grid-template-columns:repeat(3,1fr)">
                    <a href="{{ route('exam.eligibility') }}" class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-list-check'></i></div>
                        <div class="dash-action-label">Eligibility</div>
                        <div class="dash-action-desc">Check clearance</div>
                    </a>
                    <a href="{{ route('exam.timetable') }}"   class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-calendar-event'></i></div>
                        <div class="dash-action-label">Timetable</div>
                        <div class="dash-action-desc">Exam schedule</div>
                    </a>
                    <a href="{{ route('exam.reports') }}"     class="dash-action-card">
                        <div class="dash-action-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
                        <div class="dash-action-label">Reports</div>
                        <div class="dash-action-desc">Program stats</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Low attendance modules --}}
<div class="ent-card">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-trending-down'></i> Low Attendance Modules</h2>
        <span class="ent-badge ent-badge-danger">Below 75%</span>
    </div>
    <div class="ent-card-body" style="padding:0">
        @if($lowAttendanceModules->isEmpty())
            <div class="ent-empty">
                <i class='bx bx-check-circle' style="color:#10b981"></i>
                <p>All modules have satisfactory attendance!</p>
            </div>
        @else
            <table class="ent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Module</th>
                        <th>Program</th>
                        <th>Sessions</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowAttendanceModules as $i => $mod)
                        <tr>
                            <td style="color:var(--ent-text-muted);font-weight:600">{{ $i+1 }}</td>
                            <td>
                                <div style="font-weight:600">{{ $mod->module_name }}</div>
                                <div style="font-size:.72rem;color:var(--ent-text-muted)">{{ $mod->module_code }}</div>
                            </td>
                            <td style="color:var(--ent-text-muted)">{{ $mod->program_name ?? 'N/A' }}</td>
                            <td style="color:var(--ent-text-muted)">{{ $mod->total_records }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.5rem;min-width:100px">
                                    <div style="flex:1">
                                        <div class="dash-progress-track">
                                            <div class="dash-progress-fill danger"
                                                 style="width:{{ min($mod->attendance_rate,100) }}%"></div>
                                        </div>
                                    </div>
                                    <span class="ent-badge ent-badge-danger">{{ $mod->attendance_rate }}%</span>
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
