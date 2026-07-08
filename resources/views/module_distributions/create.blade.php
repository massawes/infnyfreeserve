@extends('layouts.app')
@section('page-title', 'Assign Modules to Lecturers')

@section('content')

{{-- Page header --}}
<div class="ent-page-header">
    <div>
        <h1 class="ent-page-title">Assign Modules to Lecturers</h1>
        <p class="ent-page-sub">Manage module distribution for your department's academic year</p>
    </div>
    <div class="ent-page-actions">
        <button
            type="button"
            class="ent-btn ent-btn-outline ent-btn-sm"
            data-excel-export
            data-export-url="{{ route('moduledistribute.export') }}"
            data-export-filename="module-distributions.xlsx"
            data-export-sheet="Module Distributions"
        >
            <i class='bx bx-download'></i> Export Excel
        </button>
        <x-import-actions
            :import-url="route('spreadsheets.import', 'module_distributions')"
            import-entity="module_distributions"
            :template-fields="['module_code', 'lecturer_name', 'academic_year']"
            template-filename="module-distributions-template.xlsx"
            hint="module_code, lecturer_name, academic_year"
        />
        <a href="{{ route('moduledistribute.index', ['academic_year' => old('academic_year', $selectedAcademicYear)]) }}"
           class="ent-btn ent-btn-outline ent-btn-sm">
            <i class='bx bx-list-ul'></i> View Saved Distributions
        </a>
    </div>
</div>

{{-- Flash alerts --}}
@if(session('success'))
    <div class="ega-alert ega-alert-success alert alert-dismissible mb-4" role="alert">
        <i class='bx bx-check-circle'></i>
        <span>{{ session('success') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="ega-alert ega-alert-danger alert alert-dismissible mb-4" role="alert">
        <i class='bx bx-error-circle'></i>
        <span>{{ session('error') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('moduledistribute.store') }}" method="POST" id="assignForm">
    @csrf

    {{-- Academic year + actions bar --}}
    <div class="ent-card mb-4">
        <div class="ent-card-header">
            <h2 class="ent-card-title"><i class='bx bx-calendar-alt'></i> Academic Year</h2>
        </div>
        <div class="ent-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-4">
                    <label class="ent-label">Enter Academic Year</label>
                    <input
                        type="text"
                        name="academic_year"
                        id="academicYearInput"
                        class="ent-input @error('academic_year') is-invalid @enderror"
                        value="{{ old('academic_year', $selectedAcademicYear) }}"
                        placeholder="e.g. 2025/2026"
                        required
                    >
                    @error('academic_year')
                        <div class="invalid-feedback d-block" style="font-size:.78rem;color:var(--ent-danger);margin-top:.25rem">{{ $message }}</div>
                    @enderror
                    <div style="font-size:.72rem;color:var(--ent-text-muted);margin-top:.3rem">
                        Showing distribution for: <strong>{{ old('academic_year', $selectedAcademicYear) }}</strong>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-8 d-flex flex-wrap align-items-center gap-2 justify-content-sm-end">
                    <a href="{{ route('moduledistribute.create', ['academic_year' => old('academic_year', $selectedAcademicYear)]) }}"
                       class="ent-btn ent-btn-outline ent-btn-sm">
                        <i class='bx bx-refresh'></i> Reload
                    </a>
                    <button type="submit" class="ent-btn ent-btn-primary ent-btn-sm">
                        <i class='bx bx-save'></i> Save All Distributions
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary bar --}}
    <div class="am-summary-bar mb-3">
        <div class="am-summary-item">
            <i class='bx bx-book'></i>
            <span><strong>{{ $modules->total() }}</strong> total modules</span>
        </div>
        <div class="am-summary-item">
            <i class='bx bx-chalkboard'></i>
            <span><strong>{{ $lecturers->count() }}</strong> available lecturers</span>
        </div>
        <div class="am-summary-divider"></div>
        <div class="am-summary-item">
            <i class='bx bx-info-circle'></i>
            <span>Select a lecturer for each module then click "Save"</span>
        </div>
    </div>

    {{-- Module cards grid --}}
    @if($modules->isEmpty())
        <div class="ent-empty ent-card">
            <i class='bx bx-book-open'></i>
            <p>No modules available for this academic year.</p>
        </div>
    @else
        <div class="am-grid">
            @foreach($modules as $module)
                @php
                    $selectedLecturerId = old(
                        'distributions.' . $module->id,
                        $existingDistributions->get($module->id)?->user_id
                    );
                    $isAssigned = !empty($selectedLecturerId);
                @endphp

                <div class="am-card {{ $isAssigned ? 'am-card--assigned' : '' }}">
                    {{-- Status dot --}}
                    <div class="am-card-status">
                        <span class="ent-status-dot {{ $isAssigned ? 'online' : 'offline' }}"></span>
                        <span class="am-card-status-text">{{ $isAssigned ? 'Assigned' : 'Unassigned' }}</span>
                    </div>

                    {{-- Module name --}}
                    <div class="am-card-title">{{ $module->module_name }}</div>

                    {{-- Badges row --}}
                    <div class="am-card-badges">
                        @if($module->module_code)
                            <span class="ent-badge ent-badge-primary">
                                <i class='bx bx-code-block'></i> {{ $module->module_code }}
                            </span>
                        @endif
                        <span class="ent-badge ent-badge-info">
                            NTA {{ $module->nta_level }}
                        </span>
                        @if($module->semester)
                            <span class="ent-badge ent-badge-default">
                                Sem {{ $module->semester }}
                            </span>
                        @endif
                    </div>

                    {{-- Assign select --}}
                    <div class="am-card-field">
                        <label class="ent-label" for="dist_{{ $module->id }}">
                            <i class='bx bx-user-pin'></i> Lecturer for This Module
                        </label>
                        <select
                            name="distributions[{{ $module->id }}]"
                            id="dist_{{ $module->id }}"
                            class="ent-input am-select"
                            onchange="markChanged(this)"
                        >
                            <option value="">— Select Lecturer —</option>
                            @foreach($lecturers as $lecturer)
                                <option
                                    value="{{ $lecturer->id }}"
                                    {{ (string)$selectedLecturerId === (string)$lecturer->id ? 'selected' : '' }}
                                >
                                    {{ $lecturer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="am-pagination mt-4">
            {{ $modules->appends(['academic_year' => old('academic_year', $selectedAcademicYear)])->links('pagination::bootstrap-5') }}
        </div>

        {{-- Sticky save footer --}}
        <div class="am-save-bar">
            <span class="am-save-bar-info">
                <i class='bx bx-info-circle'></i>
                Changes will not be saved until you click "Save"
            </span>
            <button type="submit" class="ent-btn ent-btn-primary">
                <i class='bx bx-save'></i> Save All Distributions
            </button>
        </div>
    @endif

</form>

@push('styles')
<style>
/* ── Assign Modules page ────────────────────────────────────────────────── */

/* Summary bar */
.am-summary-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .65rem 1.25rem;
    background: #fff;
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    padding: .65rem 1.1rem;
    font-size: .82rem;
    color: var(--ent-text-muted);
}

.am-summary-item {
    display: flex;
    align-items: center;
    gap: .35rem;
    i { color: var(--ent-primary); font-size: .95rem; }
}

.am-summary-divider {
    width: 1px;
    height: 1.1rem;
    background: var(--ent-border);
    margin: 0 .2rem;
}

/* Module grid — auto-fit responsive, min 260px per card */
.am-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}

/* Module card */
.am-card {
    background: #fff;
    border: 1.5px solid var(--ent-border);
    border-radius: var(--ent-radius);
    box-shadow: var(--ent-shadow-sm);
    padding: 1.1rem 1.15rem 1rem;
    display: flex;
    flex-direction: column;
    gap: .65rem;
    transition: var(--ent-transition);

    &:hover {
        box-shadow: var(--ent-shadow);
        border-color: rgba(15,76,129,.2);
        transform: translateY(-1px);
    }

    &.am-card--assigned {
        border-color: rgba(46,125,50,.25);
        background: #fafffe;

        &::before {
            content: '';
            display: block;
            height: 3px;
            background: var(--ent-success);
            border-radius: var(--ent-radius) var(--ent-radius) 0 0;
            margin: -1.1rem -1.15rem .65rem;
        }
    }
}

/* Card status row */
.am-card-status {
    display: flex;
    align-items: center;
    gap: .35rem;
}

.am-card-status-text {
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--ent-text-muted);
}

/* Module name */
.am-card-title {
    font-size: .92rem;
    font-weight: 700;
    color: var(--ent-text);
    line-height: 1.35;
}

/* Badges row */
.am-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
}

/* Select field area */
.am-card-field {
    margin-top: auto;
    padding-top: .5rem;
    border-top: 1px solid var(--ent-border-light);
}

/* Style the select to look like our enterprise input */
.am-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%236B7280' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .65rem center;
    background-size: 1rem;
    padding-right: 2rem !important;
    cursor: pointer;
    width: 100%;
}

/* Sticky save bar */
.am-save-bar {
    position: sticky;
    bottom: 0;
    left: 0; right: 0;
    background: #fff;
    border-top: 1px solid var(--ent-border);
    box-shadow: 0 -4px 16px rgba(0,0,0,.07);
    padding: .75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    z-index: 100;
    margin: 0 -1.75rem;    /* bleed to content padding edges */
}

.am-save-bar-info {
    font-size: .79rem;
    color: var(--ent-text-muted);
    display: flex;
    align-items: center;
    gap: .35rem;
    i { color: var(--ent-info); }
}

/* Pagination centering */
.am-pagination {
    display: flex;
    justify-content: center;
}

/* Breakpoints */
@media (max-width: 575.98px) {
    .am-grid {
        grid-template-columns: 1fr;
    }
    .am-save-bar {
        margin: 0 -1rem;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    .am-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1400px) {
    .am-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}
</style>
@endpush

@push('scripts')
<script>
function markChanged(select) {
    const card = select.closest('.am-card');
    const dot  = card.querySelector('.ent-status-dot');
    const lbl  = card.querySelector('.am-card-status-text');
    if (select.value) {
        card.classList.add('am-card--assigned');
        dot.className  = 'ent-status-dot online';
        lbl.textContent = 'Imegawiwa';
    } else {
        card.classList.remove('am-card--assigned');
        dot.className  = 'ent-status-dot offline';
        lbl.textContent = 'Haijagawiwa';
    }
}
</script>
@endpush

@endsection
