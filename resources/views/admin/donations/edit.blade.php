@extends('layouts.dashboard', ['title' => 'Edit Donation'])

@section('content')
    <div class="panel">
        <h2>Edit Donation</h2>
        <p>Update donation information carefully for accurate recordkeeping.</p>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('admin.donations.update', $donation) }}">
            @csrf
            @method('PUT')

            @include('admin.donations.partials.form', [
                'donation' => $donation,
            ])

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" class="btn">Update Donation</button>
                <a href="{{ route('admin.donations.show', $donation) }}" class="btn light">Cancel</a>
            </div>
        </form>
    </div>
@endsection