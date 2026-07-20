@extends('layouts.dashboard', ['title' => 'Add Parent'])

@section('content')
    @include('admin.parents._styles')

    <div class="parents-page">
        <section class="parents-hero">
            <div class="parents-header">
                <div class="parents-title">
                    <div class="parents-eyebrow">
                        <i class="bi bi-person-plus"></i>
                        New Parent Profile
                    </div>

                    <h2>Add Parent</h2>

                    <p>
                        Create a prospective parent account and matching profile.
                    </p>
                </div>

                <div class="parents-actions">
                    <a href="{{ route('admin.parents.index') }}" class="btn light">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.parents.store') }}">
            @csrf

            @include('admin.parents._form', [
                'buttonLabel' => 'Create Parent'
            ])
        </form>
    </div>
@endsection