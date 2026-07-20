@extends('layouts.dashboard', ['title' => 'Record Donation'])

@section('content')
    <div class="panel">
        <h2>Record Donation</h2>
        <p>Enter donation information carefully for accurate recordkeeping.</p>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('admin.donations.store') }}">
            @csrf

            @include('admin.donations.partials.form', [
                'donation' => null,
            ])

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn">Save Donation</button>
                <a href="{{ route('admin.donations.index') }}" class="btn light">Cancel</a>
            </div>
        </form>
    </div>
@endsection