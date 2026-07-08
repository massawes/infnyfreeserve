@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class='bx bx-info-circle me-2'></i>Module Distribution Details
                    </h5>
                    <a href="{{ route('hodreport') }}" class="btn btn-light btn-sm rounded-pill px-3">
                        <i class='bx bx-arrow-back me-1'></i> Back to Report
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Module Name</label>
                            <h5 class="fw-bold text-dark">{{ $distribution->module->module_name }}</h5>
                            <small class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">
                                NTA Level {{ $distribution->module->nta_level }}
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Program</label>
                            <h5 class="fw-bold text-dark">{{ $distribution->module->program->program_name }}</h5>
                        </div>
                        <hr class="my-4 bg-light">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Assigned Lecturer</label>
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-light rounded-circle me-3 text-primary">
                                    <i class='bx bxs-user-detail fs-3'></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $distribution->lecturer->name }}</h6>
                                    <small class="text-muted">{{ $distribution->lecturer->email }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1">Academic Year</label>
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-light rounded-circle me-3 text-success">
                                    <i class='bx bx-calendar fs-3'></i>
                                </div>
                                <h6 class="mb-0 fw-bold">{{ $distribution->academic_year }}</h6>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 d-flex gap-2">
                        <a href="{{ route('moduledistribute.edit', $distribution->id) }}" class="btn btn-info text-white rounded-3 px-4 shadow-sm">
                            <i class='bx bx-edit-alt me-1'></i> Edit Assignment
                        </a>
                        <form action="{{ route('moduledistribute.destroy', $distribution->id) }}" method="POST" onsubmit="return confirm('Are you sure? This will permanently delete this assignment.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger rounded-3 px-4 shadow-sm">
                                <i class='bx bx-trash me-1'></i> Delete Assignment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
