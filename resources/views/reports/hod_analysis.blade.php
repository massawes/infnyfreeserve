@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold mb-1">HOD Analysis</div>
            <h4 class="fw-bold mb-0 text-dark">Attendance Analysis</h4>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 no-print">
            <a href="{{ route('hodreport') }}" class="btn btn-dark btn-sm rounded-pill px-3">Module Report</a>
            <x-report-actions
                :export-url="request()->fullUrlWithQuery(['export' => 1])"
                export-filename="hod-analysis.xlsx"
                export-sheet="HOD Analysis"
            />
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('hod.analysis') }}" id="hod-analysis-filter">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="week_id" class="form-label small text-uppercase text-muted fw-semibold mb-1">Week</label>
                        <select name="week_id" id="week_id" class="form-select form-select-sm" {{ !$hasWeekColumn ? 'disabled' : '' }}>
                            <option value="">Select Week</option>
                            @foreach ($weeks as $week)
                                <option value="{{ $week->id }}" @selected((string) $selectedWeek === (string) $week->id)>{{ $week->week_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="module_id" class="form-label small text-uppercase text-muted fw-semibold mb-1">Subject</label>
                        <select name="module_id" id="module_id" class="form-select form-select-sm">
                            <option value="">Select Subject</option>
                            @foreach ($modules as $module)
                                <option
                                    value="{{ $module->id }}"
                                    data-nta-level="{{ $module->nta_level }}"
                                    data-weeks="{{ implode(',', $moduleWeekMap[$module->id] ?? []) }}"
                                    @selected((string) $selectedModule === (string) $module->id)
                                >
                                    {{ $module->module_name }} ({{ $module->module_code }}) - {{ $module->program_name }} - NTA {{ $module->nta_level }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-uppercase text-muted fw-semibold mb-1">NTA</label>
                        <div class="form-control form-control-sm bg-light d-flex align-items-center justify-content-between">
                            <span id="nta_level_display" class="text-muted">{{ filled($selectedNtaLevel) ? 'NTA ' . $selectedNtaLevel : 'Auto from subject' }}</span>
                            <span class="badge rounded-pill text-bg-primary">Auto</span>
                        </div>
                        <input type="hidden" name="nta_level" id="nta_level" value="{{ $selectedNtaLevel }}">
                    </div>

                    <div class="col-md-2 d-grid">
                        <a href="{{ route('hod.analysis') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Reset</a>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Select a week or subject. NTA will be filled in automatically based on the subject you choose.</small>
            </form>
        </div>
    </div>

    @if ($hasAnyFilter)
        <div class="printable-area">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-3">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Records</div>
                            <div class="fs-3 fw-bold text-dark">{{ (int) ($summary->total_records ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-3">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Present</div>
                            <div class="fs-3 fw-bold text-dark">{{ (int) ($summary->present_records ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Subject</th>
                                <th>Week</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $record)
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark">{{ $record->student_name }}</td>
                                    <td class="text-muted">{{ $record->module_name }}</td>
                                    <td class="text-muted">{{ $record->week_name ?? 'N/A' }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge rounded-pill {{ $record->percentage >= 75 ? 'text-bg-success' : 'text-bg-danger' }} px-3 py-2">
                                            {{ $record->percentage >= 75 ? 'Clear' : 'Review' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No matching data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white border-0 px-4 py-3 d-flex justify-content-center no-print">
                    {{ $records->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center text-muted">
                Select a week or subject to view results from the database.
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('hod-analysis-filter');
        const weekSelect = document.getElementById('week_id');
        const moduleSelect = document.getElementById('module_id');
        const ntaInput = document.getElementById('nta_level');
        const ntaDisplay = document.getElementById('nta_level_display');
        let submitTimer = null;

        if (!form || !weekSelect || !moduleSelect || !ntaInput || !ntaDisplay) {
            return;
        }

        const scheduleSubmit = () => {
            window.clearTimeout(submitTimer);
            submitTimer = window.setTimeout(() => {
                form.requestSubmit();
            }, 120);
        };

        const syncNta = () => {
            const selectedOption = moduleSelect.options[moduleSelect.selectedIndex];
            const ntaLevel = selectedOption?.dataset?.ntaLevel || '';

            ntaInput.value = ntaLevel;
            ntaDisplay.textContent = ntaLevel ? `NTA ${ntaLevel}` : 'Auto from subject';
        };

        const syncWeekWithModule = () => {
            const selectedOption = moduleSelect.options[moduleSelect.selectedIndex];
            const weeks = String(selectedOption?.dataset?.weeks || '')
                .split(',')
                .map((week) => week.trim())
                .filter(Boolean);

            if (!weeks.length) {
                return false;
            }

            const currentWeek = String(weekSelect.value || '').trim();
            const nextWeek = !currentWeek || !weeks.includes(currentWeek) ? weeks[0] : currentWeek;

            if (nextWeek !== currentWeek) {
                weekSelect.value = nextWeek;
                return true;
            }

            return false;
        };

        weekSelect.addEventListener('change', scheduleSubmit);
        moduleSelect.addEventListener('change', () => {
            const weekChanged = syncWeekWithModule();
            syncNta();
            scheduleSubmit();
            if (weekChanged) {
                scheduleSubmit();
            }
        });

        if (syncWeekWithModule()) {
            scheduleSubmit();
        }
        syncNta();
    });
</script>
@endsection
