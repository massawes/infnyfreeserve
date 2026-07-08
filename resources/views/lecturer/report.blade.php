@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 lecturer-shell">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold mb-1">Lecturer Attendance Analysis</div>
            <h4 class="fw-bold mb-0 text-dark">Report for your subjects only</h4>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 no-print">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white border shadow-sm">
                <i class='bx bx-user-pin text-primary'></i>
                <span class="fw-semibold">{{ $lecturerName }}</span>
            </div>
            <x-report-actions
                :export-url="request()->fullUrlWithQuery(['export' => 1])"
                export-filename="lecturer-analysis.xlsx"
                export-sheet="Lecturer Analysis"
            />
        </div>
    </div>

    <div class="lecturer-card card mb-3">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('lecturerireport') }}" id="lecturer-report-filter">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="week_id" class="form-label filter-label mb-1">Week</label>
                        <select name="week_id" id="week_id" class="form-select form-select-lg" {{ !$hasWeekColumn ? 'disabled' : '' }}>
                            <option value="">Select Week</option>
                            @foreach ($weeks as $week)
                                <option value="{{ $week->id }}" @selected((string) $selectedWeek === (string) $week->id)>{{ $week->week_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="module_id" class="form-label filter-label mb-1">Subject</label>
                        <select name="module_id" id="module_id" class="form-select form-select-lg">
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
                        <label class="form-label filter-label mb-1">NTA</label>
                        <div class="form-control form-control-lg bg-light d-flex align-items-center justify-content-between">
                            <span id="nta_level_display" class="text-muted">{{ filled($selectedNtaLevel) ? 'NTA ' . $selectedNtaLevel : 'Auto from subject' }}</span>
                            <span class="badge rounded-pill text-bg-primary">Auto</span>
                        </div>
                        <input type="hidden" name="nta_level" id="nta_level" value="{{ $selectedNtaLevel }}">
                    </div>

                    <div class="col-md-2 d-grid">
                        <a href="{{ route('lecturerireport') }}" class="btn btn-outline-secondary btn-lg">
                            Reset
                        </a>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Select a week or subject. NTA will be filled in automatically based on the subject you choose.</small>
            </form>
        </div>
    </div>

    @if ($showResults)
        <div class="printable-area">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="lecturer-stat card">
                        <div class="card-body p-3">
                            <div class="text-muted small text-uppercase fw-semibold">Total Records</div>
                            <div class="fs-3 fw-bold mb-0">{{ (int) ($summary->total_records ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="lecturer-stat card">
                        <div class="card-body p-3">
                            <div class="text-muted small text-uppercase fw-semibold">Present</div>
                            <div class="fs-3 fw-bold text-success mb-0">{{ (int) ($summary->present_records ?? 0) }}</div>
                        </div>
                    </div>
                </div>
              
            </div>

            <div class="lecturer-card card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Student</th>
                                    <th>Program</th>
                                    <th>Subject</th>
                                    <th>NTA</th>
                                    <th>Week</th>
                                    <th>Present</th>
                                    <th class="pe-3">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr>
                                        <td class="ps-3 fw-semibold">{{ $records->firstItem() + $loop->index }}</td>
                                        <td class="fw-semibold">{{ $record->student_name ?: $record->student_record_name }}</td>
                                        <td>{{ $record->program_name }}</td>
                                        <td>{{ $record->module_name }}</td>
                                        <td><span class="badge bg-info text-dark">NTA {{ $record->nta_level }}</span></td>
                                        <td>{{ $record->week_name ?? 'N/A' }}</td>
                                        <td>{{ $record->present_count }}</td>
                                        <td class="pe-3">
                                            <span class="badge rounded-pill bg-{{ $record->percentage >= 75 ? 'success' : ($record->percentage >= 50 ? 'warning text-dark' : 'danger') }} px-3 py-2">
                                                {{ number_format((float) $record->percentage, 2) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="lecturer-empty py-4 mx-3">
                                                <h5 class="fw-bold mb-2">No matching data found</h5>
                                                <p class="text-muted mb-0">Change the week or subject to see results.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if (method_exists($records, 'links'))
                    <div class="card-footer bg-white border-0 px-3 pb-3 pt-2 no-print">
                        {{ $records->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="lecturer-empty text-center py-5 px-4">
            <div class="display-6 text-primary mb-2">
                <i class='bx bx-calendar-week'></i>
            </div>
            <h5 class="fw-bold mb-2">Select a week or subject</h5>
            <p class="text-muted mb-0">Results will appear automatically after you choose a subject from the database.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('lecturer-report-filter');
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
