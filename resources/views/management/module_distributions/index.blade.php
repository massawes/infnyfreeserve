@extends('layouts.app')
@section('page-title', 'Module Distribution')

@section('content')

<div class="ent-page-header">
    <div>
        <h1 class="ent-page-title">Module Distribution</h1>
        <p class="ent-page-sub">All module distribution records for lecturers — most recent shown first</p>
    </div>
    <div class="ent-page-actions">
        <a href="{{ route('moduledistribute.create', request()->only('academic_year')) }}"
           class="ent-btn ent-btn-primary ent-btn-sm">
            <i class='bx bx-plus-circle'></i> Assign New Module
        </a>
    </div>
</div>

{{-- Filter bar --}}
<div class="ent-card mb-4">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-search-alt'></i> Search Distribution</h2>
    </div>
    <div class="ent-card-body">
        <form method="GET" action="{{ route('moduledistribute.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-4">
                    <label class="ent-label">Search</label>
                    <div style="position:relative">
                        <i class='bx bx-search' style="position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--ent-text-light);pointer-events:none"></i>
                        <input type="text" name="search" class="ent-input" style="padding-left:2rem"
                            placeholder="Module, lecturer, year…"
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="ent-label">Academic Year</label>
                    <input type="text" name="academic_year" class="ent-input"
                        placeholder="e.g. 2025/2026"
                        value="{{ request('academic_year') }}">
                </div>
                <div class="col-sm-12 col-lg-4 d-flex gap-2">
                    <button type="submit" class="ent-btn ent-btn-primary w-100">
                        <i class='bx bx-search-alt'></i> Search
                    </button>
                    <a href="{{ route('moduledistribute.index') }}" class="ent-btn ent-btn-outline">
                        <i class='bx bx-reset'></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ent-card">
    <div class="ent-card-header">
        <h2 class="ent-card-title"><i class='bx bx-git-branch'></i> Distribution Records</h2>
        @if(request('search') || request('academic_year'))
            <span class="ent-badge ent-badge-info">
                <i class='bx bx-filter-alt'></i> Filtered
            </span>
        @endif
    </div>
    <div class="ent-card-body" style="padding:0">
        @if($distributions->isEmpty())
            <div class="ent-empty">
                <i class='bx bx-git-branch'></i>
                <p>No distribution records match your search.</p>
            </div>
        @else
            <div style="overflow-x:auto">
                <table class="ent-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Module</th>
                            <th>Program</th>
                            <th>Lecturer</th>
                            <th>Academic Year</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($distributions as $i => $dist)
                            <tr>
                                <td style="color:var(--ent-text-muted);font-weight:600">
                                    {{ $distributions->firstItem() + $i }}
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--ent-text)">
                                        {{ $dist->module?->module_name ?? '—' }}
                                    </div>
                                    @if($dist->module?->module_code)
                                        <div style="margin-top:.2rem">
                                            <span class="ent-badge ent-badge-primary" style="font-size:.65rem">
                                                {{ $dist->module->module_code }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td style="color:var(--ent-text-muted)">
                                    {{ $dist->module?->program?->program_name ?? '—' }}
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.5rem">
                                        <div style="width:1.7rem;height:1.7rem;border-radius:50%;background:rgba(15,76,129,.1);color:var(--ent-primary);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0">
                                            {{ strtoupper(substr($dist->lecturer?->name ?? '?', 0, 2)) }}
                                        </div>
                                        <span style="font-weight:500">{{ $dist->lecturer?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="ent-badge ent-badge-default">
                                        <i class='bx bx-calendar'></i>
                                        {{ $dist->academic_year ?? '—' }}
                                    </span>
                                </td>
                                <td style="text-align:right">
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:.35rem;flex-wrap:nowrap">
                                        <a href="{{ route('moduledistribute.show', $dist->id) }}"
                                           class="ent-btn ent-btn-outline ent-btn-sm" title="View">
                                            <i class='bx bx-show'></i>
                                        </a>
                                        <a href="{{ route('moduledistribute.edit', $dist->id) }}"
                                           class="ent-btn ent-btn-outline ent-btn-sm" title="Edit">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <form action="{{ route('moduledistribute.destroy', $dist->id) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this distribution?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ent-btn ent-btn-danger ent-btn-sm" title="Delete">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:2.5rem;color:var(--ent-text-muted)">
                                    No records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($distributions->hasPages())
        <div style="padding:.85rem 1.25rem;border-top:1px solid var(--ent-border)">
            {{ $distributions->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection
