@extends('layouts.app')
@section('page-title', 'Quality Assurance Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero" style="background:linear-gradient(135deg,#065f46 0%,#0d9488 50%,#0891b2 100%)">
    <div class="dash-hero-eyebrow"><i class='bx bx-badge-check'></i> Quality Assurance Office</div>
    <h1 class="dash-hero-title">Welcome, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('l, d M Y') }}</span>
        <span class="dash-hero-chip"><i class='bx bx-bar-chart-alt-2'></i> Coverage Rate: {{ $coverageRate }}%</span>
        @if($pendingReviews > 0)
            <span class="dash-hero-chip" style="background:rgba(239,68,68,.25)">
                <i class='bx bx-error-circle'></i> {{ $pendingReviews }} Pending Reviews
            </span>
        @endif
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('reports.management') }}" class="dash-hero-btn primary"><i class='bx bx-file-blank'></i> Attendance Report</a>
        <a href="{{ route('modules.index') }}"       class="dash-hero-btn ghost"><i class='bx bx-book'></i> Modules</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="dash-stat dash-stat-teal">
            <div class="dash-stat-icon"><i class='bx bx-book'></i></div>
            <div class="dash-stat-value">{{ $totalModules }}</div>
            <div class="dash-stat-label">Total Modules</div>
            <div class="dash-stat-footer"><i class='bx bx-book-open'></i> System modules</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat {{ $coverageRate >= 75 ? 'dash-stat-green' : 'dash-stat-amber' }}">
            <div class="dash-stat-icon"><i class='bx bx-pie-chart-alt'></i></div>
            <div class="dash-stat-value">{{ $coverageRate }}%</div>
            <div class="dash-stat-label">Coverage Rate</div>
            <div class="dash-progress mt-2">
                <div class="dash-progress-track">
                    <div class="dash-progress-fill {{ $coverageRate < 75 ? 'warning' : '' }}"
                         style="width:{{ min($coverageRate,100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat {{ $pendingReviews > 0 ? 'dash-stat-red' : 'dash-stat-green' }}">
            <div class="dash-stat-icon"><i class='bx bx-alarm-exclamation'></i></div>
            <div class="dash-stat-value">{{ $pendingReviews }}</div>
            <div class="dash-stat-label">Pending Reviews</div>
            <div class="dash-stat-footer"><i class='bx bx-info-circle'></i> Need attention</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat dash-stat-blue">
            <div class="dash-stat-icon"><i class='bx bx-chalkboard'></i></div>
            <div class="dash-stat-value">{{ $totalLecturers }}</div>
            <div class="dash-stat-label">Lecturers</div>
            <div class="dash-stat-footer"><i class='bx bx-user-check'></i> Teaching staff</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Low attendance modules table --}}
    <div class="col-lg-8">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-trending-down'></i> Low Attendance Modules</h2>
                <span class="ent-badge ent-badge-danger">Below 70%</span>
            </div>
            <div class="ent-card-body" style="padding:0">
                @if($lowAttendanceModules->isEmpty())
                    <div class="ent-empty">
                        <i class='bx bx-check-circle' style="color:#10b981"></i>
                        <p>All modules meet the attendance standard!</p>
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
                                        <div style="display:flex;align-items:center;gap:.5rem;min-width:110px">
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
    </div>

    {{-- Summary panel --}}
    <div class="col-lg-4">
        <div class="ent-card mb-3">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-bar-chart-alt-2'></i> QA Summary</h2>
            </div>
            <div class="ent-card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value" style="color:#14b8a6">{{ $totalModules }}</div>
                            <div class="dash-metric-label"><i class='bx bx-book'></i> Modules</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value" style="color:#3b82f6">{{ $totalPrograms }}</div>
                            <div class="dash-metric-label"><i class='bx bx-bookmark'></i> Programs</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value" style="color:#f59e0b">{{ $modulesWithoutTimetables }}</div>
                            <div class="dash-metric-label"><i class='bx bx-calendar-x'></i> No Timetable</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-metric-block">
                            <div class="dash-metric-value" style="{{ $coverageRate >= 75 ? 'color:#10b981' : 'color:#f59e0b' }}">{{ $coverageRate }}%</div>
                            <div class="dash-metric-label"><i class='bx bx-pie-chart-alt'></i> Coverage</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ent-card">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
            </div>
            <div class="ent-card-body">
                <div class="dash-action-grid" style="grid-template-columns:repeat(2,1fr)">
                    <a href="{{ route('modules.index') }}"        class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(20,184,166,.1);color:#14b8a6"><i class='bx bx-book'></i></div>
                        <div class="dash-action-label">Modules</div>
                    </a>
                    <a href="{{ route('reports.management') }}"   class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(59,130,246,.1);color:#3b82f6"><i class='bx bx-file-blank'></i></div>
                        <div class="dash-action-label">Reports</div>
                    </a>
                    <a href="{{ route('programs.index') }}"       class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6"><i class='bx bx-bookmark'></i></div>
                        <div class="dash-action-label">Programs</div>
                    </a>
                    <a href="{{ route('departments.index') }}"    class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(245,158,11,.1);color:#f59e0b"><i class='bx bx-buildings'></i></div>
                        <div class="dash-action-label">Departments</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
