@extends('layouts.dashboard', ['title' => 'Edit Parent'])

@section('content')
    @include('admin.parents._styles')

    <div class="parents-page">
        <section class="parents-hero">
            <div class="parents-header">
                <div class="parents-title">
                    <div class="parents-eyebrow">
                        <i class="bi bi-pencil-square"></i>
                        Edit Parent Profile
                    </div>

                    <h2>{{ $parent->name }}</h2>

                    <p>
                        Update the parent account, preferences, and matching readiness details.
                    </p>
                </div>

                <div class="parents-actions">
                    <a href="{{ route('admin.parents.show', $parent) }}" class="btn light">
                        <i class="bi bi-eye"></i>
                        View
                    </a>

                    <a href="{{ route('admin.parents.index') }}" class="btn light">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.parents.update', $parent) }}">
            @csrf
            @method('PUT')

            @include('admin.parents._form', [
                'buttonLabel' => 'Update Parent'
            ])
        </form>
    </div>
@endsection