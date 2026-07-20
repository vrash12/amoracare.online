@extends('layouts.dashboard', ['title' => 'Create Adoption Case'])

@section('content')
    <div class="panel">
        <h2>Create Adoption Case</h2>
        <p>Create a confidential adoption workflow record for staff review and monitoring.</p>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('admin.adoption-cases.store') }}">
            @csrf

            @include('admin.adoption_cases.partials.form', [
                'adoptionCase' => null,
                'children' => $children,
                'prospectiveParents' => $prospectiveParents,
                'staffUsers' => $staffUsers,
                'caseTypes' => $caseTypes,
                'statuses' => $statuses,
                'priorities' => $priorities,
            ])

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn">
                    Save Adoption Case
                </button>

                <a href="{{ route('admin.adoption-cases.index') }}" class="btn light">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection