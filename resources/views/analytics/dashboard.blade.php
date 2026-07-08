@extends('layouts.app')
@section('page-title', 'Attendance Statistics')

@section('content')

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="ent-page-header">
    <div>
        <h1 class="ent-page-title">Attendance Statistics</h1>
        <p class="ent-page-sub">
            Detailed attendance analysis — department, program, module and students
            <span class="an-scope-badge">
                <i class='bx bx-buildings'></i>
                {{ $scopeLabel ?: 'Entire Institution' }}
            </span>
        </p>
    </div>
    <div class="ent-page-actions">
        <div class="an-live-badge">
            <span class="ent-status-dot online"></span>
            <span id="lastUpdated">Updated: {{ now()->format('H:i:s') }}</span>
        </div>
        <button onclick="location.reload()" class="ent-btn ent-btn-outline ent-btn-sm" title="Refresh">
            <i class='bx bx-refresh'></i> Refresh
        </button>
        <a href="{{ route('management.attendance-report') }}" class="ent-btn ent-btn-primary ent-btn-sm">
            <i class='bx bx-file-blank'></i> Full Report
        </a>
    </div>
</div>

{{-- ── Filter Bar ───────────────────────────────────────────────────────────── --}}
<div class="ent-card mb-4">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-filter-alt'></i> Filter Data</h2>
        @if($selectedDepartmentId || $selectedProgramId || $selectedModuleId)
            <a href="{{ route('analytics.dashboard') }}" class="ent-badge ent-badge-danger" style="text-decoration:none;cursor:pointer">
                <i class='bx bx-x'></i> Clear Filters
            </a>
        @endif
    </div>
    <div class="ent-card-body">
        <form method="GET" action="{{ route('analytics.dashboard') }}" id="filterForm">
            <div class="row g-3 align-items-end">
                @if($canFilterDepartment)
                    <div class="col-md-4 col-lg-3">
                        <label class="ent-label">Department</label>
                        <select name="department_id" id="department_id" class="ent-input" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected((string)$selectedDepartmentId === (string)$dept->id)>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-4 col-lg-3">
                    <label class="ent-label">Program</label>
                    <select name="program_id" id="program_id" class="ent-input" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Programs</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}"
                                data-department-id="{{ $prog->department_id }}"
                                @selected((string)$selectedProgramId === (string)$prog->id)>
                                {{ $prog->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="ent-label">Module</label>
                    <select name="module_id" id="module_id" class="ent-input" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Modules</option>
                        @foreach($modules as $mod)
                            <option value="{{ $mod->id }}"
                                data-program-id="{{ $mod->program_id }}"
                                data-department-id="{{ $mod->department_id }}"
                                @selected((string)$selectedModuleId === (string)$mod->id)>
                                {{ $mod->module_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="ent-btn ent-btn-primary w-100">
                        <i class='bx bx-search-alt'></i> Search
                    </button>
                    <a href="{{ route('analytics.dashboard') }}" class="ent-btn ent-btn-outline">
                        <i class='bx bx-reset'></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── KPI Stat Cards ────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat">
            <div class="ent-stat-icon"><i class='bx bx-group'></i></div>
            <div class="ent-stat-value">{{ number_format($totalStudents) }}</div>
            <div class="ent-stat-label">Students Recorded</div>
            <div class="ent-stat-trend up">
                <i class='bx bx-calendar-check'></i>
                {{ number_format($totalRecords) }} total records
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat {{ $attendanceRate >= 75 ? 'ent-stat-success' : 'ent-stat-warning' }}">
            <div class="ent-stat-icon"><i class='bx bx-user-check'></i></div>
            <div class="ent-stat-value">{{ $attendanceRate }}%</div>
            <div class="ent-stat-label">Average Attendance</div>
            <div class="ent-stat-trend {{ $attendanceRate >= 75 ? 'up' : 'down' }}">
                <i class='bx {{ $attendanceRate >= 75 ? "bx-trending-up" : "bx-trending-down" }}'></i>
                {{ $attendanceRate >= 75 ? 'Meets the 75% target' : 'Below the 75% target' }}
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat ent-stat-success">
            <div class="ent-stat-icon"><i class='bx bx-check-circle'></i></div>
            <div class="ent-stat-value">{{ number_format($present) }}</div>
            <div class="ent-stat-label">Present Records</div>
            <div class="ent-stat-trend up">
                <i class='bx bx-trending-up'></i>
                {{ $totalRecords > 0 ? round(($present / $totalRecords) * 100, 1) : 0 }}% of total
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat ent-stat-danger">
            <div class="ent-stat-icon"><i class='bx bx-error-circle'></i></div>
            <div class="ent-stat-value">{{ number_format($atRiskStudents) }}</div>
            <div class="ent-stat-label">Students At Risk</div>
            <div class="ent-stat-trend down">
                <i class='bx bx-alarm-exclamation'></i>
                Below 75% attendance
            </div>
        </div>
    </div>
</div>

{{-- ── Charts Row ───────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    {{-- Weekly trend line chart --}}
    <div class="col-xl-8">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-line-chart'></i> Weekly Attendance Trend</h2>
                <span class="ent-badge ent-badge-info">{{ $weeklyStats->count() }} weeks</span>
            </div>
            <div class="ent-card-body">
                @if($weeklyStats->isEmpty())
                    <div class="ent-empty">
                        <i class='bx bx-line-chart'></i>
                        <p>No weekly data yet. Try this year.</p>
                    </div>
                @else
                    <div class="an-chart-wrap">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Doughnut — present vs absent --}}
    <div class="col-xl-4">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-pie-chart-alt-2'></i> Present vs Absent</h2>
            </div>
            <div class="ent-card-body" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
                @if($totalRecords === 0)
                    <div class="ent-empty">
                        <i class='bx bx-pie-chart'></i>
                        <p>No records yet.</p>
                    </div>
                @else
                    <div class="an-donut-wrap">
                        <canvas id="summaryChart"></canvas>
                        <div class="an-donut-center">
                            <div class="an-donut-val">{{ $attendanceRate }}%</div>
                            <div class="an-donut-lbl">Present</div>
                        </div>
                    </div>
                    <div class="an-donut-legend">
                        <div class="an-legend-item">
                            <span class="an-legend-dot" style="background:#2E7D32"></span>
                            <span>Present: <strong>{{ number_format($present) }}</strong></span>
                        </div>
                        <div class="an-legend-item">
                            <span class="an-legend-dot" style="background:#D32F2F"></span>
                            <span>Absent: <strong>{{ number_format($absent) }}</strong></span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Program & Module Charts ──────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-bar-chart-alt-2'></i> Performance by Program</h2>
                <span class="ent-badge ent-badge-primary">{{ $programStats->count() }} programs</span>
            </div>
            <div class="ent-card-body">
                @if($programStats->isEmpty())
                    <div class="ent-empty">
                        <i class='bx bx-bookmark'></i>
                        <p>No program data available.</p>
                    </div>
                @else
                    <div class="an-chart-wrap">
                        <canvas id="programChart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-book-open'></i> Performance by Module</h2>
                <span class="ent-badge ent-badge-warning">{{ $moduleStats->count() }} modules</span>
            </div>
            <div class="ent-card-body">
                @if($moduleStats->isEmpty())
                    <div class="ent-empty">
                        <i class='bx bx-book'></i>
                        <p>No module data available.</p>
                    </div>
                @else
                    <div class="an-chart-wrap">
                        <canvas id="moduleChart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Bottom Row: Dept table + Highlights ──────────────────────────────────── --}}
<div class="row g-3">

    @if($canFilterDepartment)
    <div class="col-xl-5">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-buildings'></i> Department Comparison</h2>
                <span class="ent-badge ent-badge-primary">{{ $departmentStats->count() }} departments</span>
            </div>
            <div class="ent-card-body" style="padding:0">
                @if($departmentStats->isEmpty())
                    <div class="ent-empty">
                        <i class='bx bx-building'></i>
                        <p>No department data available.</p>
                    </div>
                @else
                    <table class="ent-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th style="text-align:right">Records</th>
                                <th style="text-align:right">Attendance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentStats as $dept)
                                <tr>
                                    <td style="font-weight:600">{{ $dept->department_name }}</td>
                                    <td style="text-align:right;color:var(--ent-text-muted)">{{ number_format($dept->total_records) }}</td>
                                    <td style="text-align:right">
                                        @if($dept->attendance_percentage >= 75)
                                            <span class="ent-badge ent-badge-success">
                                                <i class='bx bx-trending-up'></i> {{ $dept->attendance_percentage }}%
                                            </span>
                                        @else
                                            <span class="ent-badge ent-badge-danger">
                                                <i class='bx bx-trending-down'></i> {{ $dept->attendance_percentage }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="{{ $canFilterDepartment ? 'col-xl-7' : 'col-xl-12' }}">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-trophy'></i> Performance Summary</h2>
            </div>
            <div class="ent-card-body">
                <div class="row g-3">
                    {{-- Top programs --}}
                    <div class="col-md-6">
                        <div class="an-highlight-box an-highlight-success">
                            <div class="an-highlight-head">
                                <i class='bx bx-star'></i> Top Programs (High Attendance)
                            </div>
                            @forelse($topPrograms as $prog)
                                <div class="an-highlight-row">
                                    <div class="an-highlight-label">
                                        <div class="an-highlight-name">{{ $prog->program_name }}</div>
                                        <div class="an-highlight-sub">{{ $prog->present_records }}/{{ $prog->total_records }} present</div>
                                    </div>
                                    <span class="ent-badge ent-badge-success">{{ $prog->attendance_percentage }}%</span>
                                </div>
                            @empty
                                <div class="an-highlight-empty">No program data available.</div>
                            @endforelse
                        </div>
                    </div>
                    {{-- Low modules --}}
                    <div class="col-md-6">
                        <div class="an-highlight-box an-highlight-danger">
                            <div class="an-highlight-head">
                                <i class='bx bx-error'></i> Modules Needing Attention
                            </div>
                            @forelse($lowModules as $mod)
                                <div class="an-highlight-row">
                                    <div class="an-highlight-label">
                                        <div class="an-highlight-name">{{ $mod->module_name }}</div>
                                        <div class="an-highlight-sub">{{ $mod->present_records }}/{{ $mod->total_records }} present</div>
                                    </div>
                                    @if($mod->attendance_percentage >= 75)
                                        <span class="ent-badge ent-badge-warning">{{ $mod->attendance_percentage }}%</span>
                                    @else
                                        <span class="ent-badge ent-badge-danger">{{ $mod->attendance_percentage }}%</span>
                                    @endif
                                </div>
                            @empty
                                <div class="an-highlight-empty">No modules with issues.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
/* ── Analytics page-specific styles ─────────────────────────────────────── */
.an-scope-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: rgba(15,76,129,.08);
    color: var(--ent-primary);
    font-size: .72rem;
    font-weight: 600;
    padding: .2rem .55rem;
    border-radius: 999px;
    margin-left: .5rem;
    vertical-align: middle;
}

.an-live-badge {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .75rem;
    font-weight: 500;
    color: var(--ent-text-muted);
    background: var(--ent-bg);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius-sm);
    padding: .3rem .65rem;
    white-space: nowrap;
}

.an-chart-wrap {
    position: relative;
    width: 100%;
}

.an-donut-wrap {
    position: relative;
    width: 160px;
    height: 160px;
}

.an-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.an-donut-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--ent-text);
    line-height: 1;
    letter-spacing: -.02em;
}

.an-donut-lbl {
    font-size: .65rem;
    font-weight: 600;
    color: var(--ent-text-muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-top: .2rem;
}

.an-donut-legend {
    display: flex;
    gap: 1.2rem;
    flex-wrap: wrap;
    justify-content: center;
}

.an-legend-item {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .79rem;
    color: var(--ent-text-muted);
}

.an-legend-dot {
    width: .65rem;
    height: .65rem;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Highlight boxes */
.an-highlight-box {
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    overflow: hidden;
    height: 100%;
}

.an-highlight-box.an-highlight-success { border-top: 3px solid var(--ent-success); }
.an-highlight-box.an-highlight-danger  { border-top: 3px solid var(--ent-danger);  }

.an-highlight-head {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--ent-text-muted);
    padding: .65rem 1rem;
    background: var(--ent-bg);
    border-bottom: 1px solid var(--ent-border);
    display: flex;
    align-items: center;
    gap: .4rem;
}

.an-highlight-success .an-highlight-head { color: var(--ent-success); }
.an-highlight-danger  .an-highlight-head { color: var(--ent-danger);  }

.an-highlight-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .6rem 1rem;
    border-bottom: 1px solid var(--ent-border-light);
    gap: .5rem;
    &:last-child { border-bottom: none; }
}

.an-highlight-label { min-width: 0; flex: 1; }

.an-highlight-name {
    font-size: .84rem;
    font-weight: 600;
    color: var(--ent-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.an-highlight-sub {
    font-size: .72rem;
    color: var(--ent-text-muted);
    margin-top: .1rem;
}

.an-highlight-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    font-size: .83rem;
    color: var(--ent-text-light);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ── Design tokens (match EGA enterprise palette) ─────────────────────────
    const P = '#0F4C81'; // primary
    const S = '#2E7D32'; // success
    const D = '#D32F2F'; // danger
    const W = '#F9A825'; // warning
    const I = '#0288D1'; // info
    const M = '#6B7280'; // muted

    const chartFont = "'Inter', 'Segoe UI', system-ui, sans-serif";

    Chart.defaults.font.family  = chartFont;
    Chart.defaults.font.size    = 12;
    Chart.defaults.color        = M;
    Chart.defaults.plugins.legend.display = false;

    const gridColor = 'rgba(0,0,0,.05)';

    // ── Weekly Trend ─────────────────────────────────────────────────────────
    @if($weeklyStats->isNotEmpty())
    const weeklyLabels = {!! json_encode($weeklyStats->pluck('week_label')->values()) !!};
    const weeklyData   = {!! json_encode($weeklyStats->pluck('attendance_percentage')->values()) !!};

    new Chart(document.getElementById('weeklyChart'), {
        type: 'line',
        data: {
            labels: weeklyLabels,
            datasets: [{
                label: 'Attendance %',
                data: weeklyData,
                borderColor: P,
                backgroundColor: 'rgba(15,76,129,.07)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: P,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
            }, {
                label: 'Minimum Target (75%)',
                data: weeklyLabels.map(() => 75),
                borderColor: D,
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: { boxWidth: 12, padding: 12, font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: '#1E293B',
                    titleFont: { weight: '700' },
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.raw}%`
                    }
                }
            },
            scales: {
                x: { grid: { color: gridColor }, border: { display: false } },
                y: {
                    beginAtZero: true, max: 100,
                    grid: { color: gridColor }, border: { display: false },
                    ticks: { callback: v => v + '%' }
                }
            }
        }
    });
    @endif

    // ── Doughnut ─────────────────────────────────────────────────────────────
    @if($totalRecords > 0)
    new Chart(document.getElementById('summaryChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent'],
            datasets: [{
                data: [{{ $present }}, {{ $absent }}],
                backgroundColor: [S, D],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1E293B',
                    callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` }
                }
            }
        }
    });
    @endif

    // ── Program Bar ──────────────────────────────────────────────────────────
    @if($programStats->isNotEmpty())
    const programLabels = {!! json_encode($programStats->pluck('program_name')->values()) !!};
    const programData   = {!! json_encode($programStats->pluck('attendance_percentage')->values()) !!};

    new Chart(document.getElementById('programChart'), {
        type: 'bar',
        data: {
            labels: programLabels,
            datasets: [{
                label: 'Attendance %',
                data: programData,
                backgroundColor: programData.map(v => v >= 75 ? 'rgba(46,125,50,.8)' : 'rgba(249,168,37,.85)'),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    backgroundColor: '#1E293B',
                    callbacks: { label: ctx => ` ${ctx.raw}% attendance` }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: {
                    beginAtZero: true, max: 100,
                    grid: { color: gridColor }, border: { display: false },
                    ticks: { callback: v => v + '%' }
                }
            }
        }
    });
    @endif

    // ── Module Bar ───────────────────────────────────────────────────────────
    @if($moduleStats->isNotEmpty())
    const moduleLabels = {!! json_encode($moduleStats->pluck('module_name')->values()) !!};
    const moduleData   = {!! json_encode($moduleStats->pluck('attendance_percentage')->values()) !!};

    new Chart(document.getElementById('moduleChart'), {
        type: 'bar',
        data: {
            labels: moduleLabels,
            datasets: [{
                label: 'Attendance %',
                data: moduleData,
                backgroundColor: moduleData.map(v => v >= 75 ? 'rgba(46,125,50,.8)' : 'rgba(211,47,47,.8)'),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    backgroundColor: '#1E293B',
                    callbacks: { label: ctx => ` ${ctx.raw}% attendance` }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: {
                    beginAtZero: true, max: 100,
                    grid: { color: gridColor }, border: { display: false },
                    ticks: { callback: v => v + '%' }
                }
            }
        }
    });
    @endif

    // ── Filter cascade (department → program → module) ────────────────────────
    const deptSel    = document.getElementById('department_id');
    const progSel    = document.getElementById('program_id');
    const modSel     = document.getElementById('module_id');
    const locked     = @json((string)($selectedDepartmentId ?? ''));

    const getDeptId = () => deptSel ? deptSel.value : locked;

    function syncPrograms() {
        const dId = getDeptId();
        progSel.querySelectorAll('option[data-department-id]').forEach(o => {
            const match = !dId || o.dataset.departmentId === dId;
            o.hidden = !match; o.disabled = !match;
        });
        if (progSel.selectedOptions[0]?.disabled) progSel.value = '';
    }

    function syncModules() {
        const dId = getDeptId();
        const pId = progSel.value;
        modSel.querySelectorAll('option[data-program-id]').forEach(o => {
            const dm = !dId || o.dataset.departmentId === dId;
            const pm = !pId || o.dataset.programId === pId;
            o.hidden = !(dm && pm); o.disabled = !(dm && pm);
        });
        if (modSel.selectedOptions[0]?.disabled) modSel.value = '';
    }

    deptSel?.addEventListener('change', () => { syncPrograms(); syncModules(); });
    progSel?.addEventListener('change', syncModules);
    syncPrograms(); syncModules();

    // ── Live clock refresh indicator ─────────────────────────────────────────
    const updEl = document.getElementById('lastUpdated');
    setInterval(() => {
        const now = new Date();
        const h = String(now.getHours()).padStart(2,'0');
        const m = String(now.getMinutes()).padStart(2,'0');
        const s = String(now.getSeconds()).padStart(2,'0');
        updEl.textContent = `Imesasishwa: ${h}:${m}:${s}`;
    }, 1000);

    // Auto-refresh page every 5 minutes to pull fresh DB data
    setTimeout(() => location.reload(), 5 * 60 * 1000);
})();
</script>
@endpush

@endsection
