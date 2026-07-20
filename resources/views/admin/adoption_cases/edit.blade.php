@extends('layouts.dashboard', ['title' => 'Edit Adoption Case'])

@section('content')
    <div class="panel">
        <h2>Edit Adoption Case</h2>
        <p>Update adoption case information carefully. All changes should be factual and authorized.</p>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('admin.adoption-cases.update', $adoptionCase) }}">
            @csrf
            @method('PUT')

            @include('admin.adoption_cases.partials.form', [
                'adoptionCase' => $adoptionCase,
                'children' => $children,
                'prospectiveParents' => $prospectiveParents,
                'staffUsers' => $staffUsers,
                'caseTypes' => $caseTypes,
                'statuses' => $statuses,
                'priorities' => $priorities,
            ])

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn">
                    Update Adoption Case
                </button>

                <a href="{{ route('admin.adoption-cases.show', $adoptionCase) }}" class="btn light">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection