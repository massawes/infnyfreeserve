@extends('layouts.app')
@section('page-title', 'Edit Module Distribution')

@section('content')

<div class="ent-page-header">
    <div>
        <h1 class="ent-page-title">Edit Module Distribution</h1>
        <p class="ent-page-sub">Change the lecturer, module, or academic year for this record</p>
    </div>
    <div class="ent-page-actions">
        <a href="{{ route('hodreport') }}" class="ent-btn ent-btn-outline ent-btn-sm">
            <i class='bx bx-arrow-back'></i> Back to Report
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
        <div class="ent-card">
            <div class="ent-card-header">
                <h2 class="ent-card-title"><i class='bx bx-edit-alt'></i> Edit Form</h2>
            </div>
            <div class="ent-card-body">
                <form action="{{ route('moduledistribute.update', $distribution->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="ent-form-group">
                        <label class="ent-label">Module / Course</label>
                        <select name="module_id" class="ent-input @error('module_id') is-invalid @enderror">
                            <option value="">— Select Module —</option>
                            @foreach($modules as $module)
                                <option value="{{ $module->id }}"
                                    {{ $distribution->module_id == $module->id ? 'selected' : '' }}>
                                    {{ $module->module_name }} (NTA {{ $module->nta_level }})
                                </option>
                            @endforeach
                        </select>
                        @error('module_id')
                            <div style="font-size:.78rem;color:var(--ent-danger);margin-top:.25rem">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="ent-form-group">
                        <label class="ent-label">Assigned Lecturer</label>
                        <select name="user_id" class="ent-input @error('user_id') is-invalid @enderror">
                            <option value="">— Select Lecturer —</option>
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}"
                                    {{ $distribution->user_id == $lecturer->id ? 'selected' : '' }}>
                                    {{ $lecturer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div style="font-size:.78rem;color:var(--ent-danger);margin-top:.25rem">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="ent-form-group">
                        <label class="ent-label">Academic Year</label>
                        <input type="text" name="academic_year"
                            class="ent-input @error('academic_year') is-invalid @enderror"
                            value="{{ old('academic_year', $distribution->academic_year) }}"
                            placeholder="e.g. 2025/2026">
                        @error('academic_year')
                            <div style="font-size:.78rem;color:var(--ent-danger);margin-top:.25rem">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--ent-border)">
                        <a href="{{ route('hodreport') }}" class="ent-btn ent-btn-outline">
                            Cancel
                        </a>
                        <button type="submit" class="ent-btn ent-btn-primary">
                            <i class='bx bx-check-circle'></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
