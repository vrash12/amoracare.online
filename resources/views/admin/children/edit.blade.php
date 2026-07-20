@extends('layouts.dashboard', ['title' => 'Edit Child Profile'])

@section('content')
    <div class="panel">
        <h2>Edit Child Profile</h2>
        <p>Update child information carefully. All sensitive data must remain confidential.</p>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('admin.children.update', $child) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.children.partials.form', [
                'child' => $child,
                'caseStatuses' => $caseStatuses,
                'eligibilityStatuses' => $eligibilityStatuses,
            ])

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn">
                    Update Child Profile
                </button>

                <a href="{{ route('admin.children.index') }}" class="btn light">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection