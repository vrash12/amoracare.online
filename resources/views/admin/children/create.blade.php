@extends('layouts.dashboard', ['title' => 'Add Child Profile'])

@section('content')
  

    <div class="panel">
        <form method="POST" action="{{ route('admin.children.store') }}" enctype="multipart/form-data">
            @csrf

            @include('admin.children.partials.form', [
                'child' => null,
                'caseStatuses' => $caseStatuses,
                'eligibilityStatuses' => $eligibilityStatuses,
            ])

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn">
                    Save Child Profile
                </button>

                <a href="{{ route('admin.children.index') }}" class="btn light">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection