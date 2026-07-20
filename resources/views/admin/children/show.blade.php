{{-- resources/views/admin/children/index.blade.php --}}
@extends('layouts.dashboard', ['title' => 'Child Profile Details'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>{{ $child->full_name }}</h2>
                <p>Child Code: {{ $child->child_code }}</p>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="{{ route('admin.children.edit', $child) }}" class="btn secondary">Edit</a>
                <a href="{{ route('admin.children.index') }}" class="btn light">Back</a>
            </div>
        </div>
    </div>

    <div class="panel">
        @if($child->photo_path)
            <img
                src="{{ asset('storage/' . $child->photo_path) }}"
                alt="Child photo"
                style="width: 120px; height: 120px; object-fit: cover; border-radius: 16px; margin-bottom: 16px;"
            >
        @endif

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
            <p><strong>Full Name:</strong><br>{{ $child->full_name }}</p>
            <p><strong>Nickname:</strong><br>{{ $child->nickname ?? 'N/A' }}</p>
            <p><strong>Sex:</strong><br>{{ ucfirst($child->sex) }}</p>
            <p><strong>Date of Birth:</strong><br>{{ $child->date_of_birth?->format('F d, Y') ?? 'N/A' }}</p>
            <p><strong>Place of Birth:</strong><br>{{ $child->place_of_birth ?? 'N/A' }}</p>
            <p><strong>Current Location:</strong><br>{{ $child->current_location ?? 'N/A' }}</p>
            <p><strong>Admission Date:</strong><br>{{ $child->admission_date?->format('F d, Y') ?? 'N/A' }}</p>
            <p><strong>Admission Reason:</strong><br>{{ $child->admission_reason ?? 'N/A' }}</p>
            <p><strong>Case Status:</strong><br>{{ $child->case_status_label }}</p>
            <p><strong>Adoption Eligibility:</strong><br>{{ $child->eligibility_status_label }}</p>
            <p><strong>Educational Level:</strong><br>{{ $child->educational_level ?? 'N/A' }}</p>
            <p><strong>School Name:</strong><br>{{ $child->school_name ?? 'N/A' }}</p>
            <p><strong>Special Needs:</strong><br>{{ $child->is_special_needs ? 'Yes' : 'No' }}</p>
            <p><strong>Created By:</strong><br>{{ $child->creator?->name ?? 'N/A' }}</p>
            <p><strong>Last Updated By:</strong><br>{{ $child->updater?->name ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="panel">
        <h2>Health Status</h2>
        <p>{{ $child->health_status ?? 'No health status recorded.' }}</p>
    </div>

    <div class="panel">
        <h2>Background Summary</h2>
        <p>{{ $child->background_summary ?? 'No background summary recorded.' }}</p>
    </div>

    <div class="panel">
        <h2>Remarks</h2>
        <p>{{ $child->remarks ?? 'No remarks recorded.' }}</p>
    </div>
@endsection