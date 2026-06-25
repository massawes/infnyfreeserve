@extends('layouts.app')
@section('page-title', 'HOD Dashboard')

@section('content')

{{-- Hero --}}
<div class="dash-hero">
    <div class="dash-hero-eyebrow"><i class='bx bx-briefcase-alt-2'></i> Head of Department</div>
    <h1 class="dash-hero-title">Welcome, {{ auth()->user()->name }}</h1>
    <div class="dash-hero-sub">
        <span class="dash-hero-chip"><i class='bx bx-buildings'></i> {{ $department->department_name ?? 'Your Department' }}</span>
        <span class="dash-hero-chip"><i class='bx bx-calendar'></i> {{ now()->format('d M Y') }}</span>
    </div>
    <div class="dash-hero-actions">
        <a href="{{ route('hodreport') }}"               class="dash-hero-btn primary"><i class='bx bx-file-blank'></i> Module Report</a>
        <a href="{{ route('hod.analysis') }}"            class="dash-hero-btn ghost"><i class='bx bx-bar-chart-alt-2'></i> Analysis</a>
        <a href="{{ route('moduledistribute.create') }}" class="dash-hero-btn ghost"><i class='bx bx-plus-circle'></i> Assign Module</a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="dash-stat dash-stat-blue">
            <div class="dash-stat-icon"><i class='bx bx-chalkboard'></i></div>
            <div class="dash-stat-value">{{ $lecturersCount }}</div>
            <div class="dash-stat-label">Lecturers</div>
            <div class="dash-stat-footer"><i class='bx bx-user-check'></i> Department staff</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat dash-stat-purple">
            <div class="dash-stat-icon"><i class='bx bx-book'></i></div>
            <div class="dash-stat-value">{{ $modulesCount }}</div>
            <div class="dash-stat-label">Modules</div>
            <div class="dash-stat-footer"><i class='bx bx-book-open'></i> Registered modules</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat dash-stat-green">
            <div class="dash-stat-icon"><i class='bx bx-bookmark'></i></div>
            <div class="dash-stat-value">{{ $programsCount }}</div>
            <div class="dash-stat-label">Programs</div>
            <div class="dash-stat-footer"><i class='bx bx-layer'></i> Academic programs</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dash-stat dash-stat-amber">
            <div class="dash-stat-icon"><i class='bx bx-graduation'></i></div>
            <div class="dash-stat-value">{{ $studentsCount }}</div>
            <div class="dash-stat-label">Students</div>
            <div class="dash-stat-footer"><i class='bx bx-group'></i> Enrolled students</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Module assignments table --}}
    <div class="col-lg-8">
        <div class="ent-card h-100">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-git-branch'></i> Module Assignments</h2>
                <span class="ent-badge ent-badge-primary">{{ $moduleDistributions->count() }} shown</span>
            </div>
            <div class="ent-card-body" style="padding:0">
                @if($moduleDistributions->isEmpty())
                    <div class="ent-empty">
                        <i class='bx bx-book-open'></i>
                        <p>No module assignments yet.</p>
                    </div>
                @else
                    <table class="ent-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Module</th>
                                <th>Program</th>
                                <th>Lecturer</th>
                                <th>NTA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleDistributions as $i => $dist)
                                <tr>
                                    <td style="color:var(--ent-text-muted);font-weight:600">{{ $i+1 }}</td>
                                    <td style="font-weight:600">{{ $dist->module_name }}</td>
                                    <td style="color:var(--ent-text-muted)">{{ $dist->program_name }}</td>
                                    <td>
                                        <span style="display:flex;align-items:center;gap:.4rem;color:var(--ent-text-muted)">
                                            <span style="width:1.6rem;height:1.6rem;border-radius:50%;background:rgba(15,76,129,.1);color:var(--ent-primary);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0">
                                                {{ strtoupper(substr($dist->lecturer_name ?? '?', 0, 2)) }}
                                            </span>
                                            {{ $dist->lecturer_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td><span class="ent-badge ent-badge-info">NTA {{ $dist->nta_level }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">
        {{-- Quick actions --}}
        <div class="ent-card mb-3">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-rocket'></i> Quick Actions</h2>
            </div>
            <div class="ent-card-body">
                <div class="dash-action-grid" style="grid-template-columns:repeat(2,1fr)">
                    <a href="{{ route('hodreport') }}"               class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(59,130,246,.1);color:#3b82f6"><i class='bx bx-file-blank'></i></div>
                        <div class="dash-action-label">Module Report</div>
                    </a>
                    <a href="{{ route('hod.analysis') }}"            class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6"><i class='bx bx-bar-chart-alt-2'></i></div>
                        <div class="dash-action-label">Analysis</div>
                    </a>
                    <a href="{{ route('moduledistribute.create') }}" class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(16,185,129,.1);color:#10b981"><i class='bx bx-plus-circle'></i></div>
                        <div class="dash-action-label">Assign Module</div>
                    </a>
                    <a href="{{ route('students.index') }}"          class="dash-action-card">
                        <div class="dash-action-icon" style="background:rgba(245,158,11,.1);color:#f59e0b"><i class='bx bx-graduation'></i></div>
                        <div class="dash-action-label">Students</div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Lecturers list --}}
        <div class="ent-card">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-group'></i> Lecturers</h2>
                <span class="ent-badge ent-badge-default">{{ $lecturers->count() }}</span>
            </div>
            <div class="ent-card-body" style="padding:0">
                @if($lecturers->isEmpty())
                    <div class="ent-empty" style="padding:1.5rem">
                        <i class='bx bx-user-x'></i>
                        <p>No lecturers yet.</p>
                    </div>
                @else
                    @foreach($lecturers as $lecturer)
                        <div class="dash-person">
                            <div class="dash-person-avatar">
                                {{ strtoupper(substr($lecturer->lecturer_name ?? $lecturer->user->name ?? '?', 0, 2)) }}
                            </div>
                            <div style="flex:1;min-width:0">
                                <div class="dash-person-name">{{ $lecturer->lecturer_name ?? $lecturer->user->name ?? 'N/A' }}</div>
                                <div class="dash-person-sub">Lecturer</div>
                            </div>
                            <span class="ent-status-dot online"></span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
