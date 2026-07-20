@extends('layouts.dashboard', ['title' => 'Donation Details'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>{{ $donation->donation_code }}</h2>
                <p>{{ $donation->donation_type_label }} • {{ $donation->purpose_label }}</p>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="{{ route('admin.donations.edit', $donation) }}" class="btn secondary">Edit</a>
                <a href="{{ route('admin.donations.index') }}" class="btn light">Back</a>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Donor Information</h2>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
            <p><strong>Donor Code:</strong><br>{{ $donation->donor?->donor_code ?? 'N/A' }}</p>
            <p><strong>Name:</strong><br>{{ $donation->donor?->name ?? 'N/A' }}</p>
            <p><strong>Type:</strong><br>{{ $donation->donor?->donor_type_label ?? 'N/A' }}</p>
            <p><strong>Email:</strong><br>{{ $donation->donor?->email ?? 'N/A' }}</p>
            <p><strong>Phone:</strong><br>{{ $donation->donor?->phone_number ?? 'N/A' }}</p>
            <p><strong>Address:</strong><br>{{ $donation->donor?->address ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="panel">
        <h2>Donation Summary</h2>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
            <p><strong>Donation Type:</strong><br>{{ $donation->donation_type_label }}</p>
            <p><strong>Date:</strong><br>{{ $donation->donation_date?->format('F d, Y') }}</p>
            <p><strong>Purpose:</strong><br>{{ $donation->purpose_label }}</p>
            <p><strong>Status:</strong><br>{{ $donation->status_label }}</p>
            <p><strong>Cash Amount:</strong><br>{{ $donation->cash_amount ? '₱' . number_format($donation->cash_amount, 2) : 'N/A' }}</p>
            <p><strong>Payment Method:</strong><br>{{ $donation->payment_method ?? 'N/A' }}</p>
            <p><strong>Reference Number:</strong><br>{{ $donation->reference_number ?? 'N/A' }}</p>
            <p><strong>Receipt Number:</strong><br>{{ $donation->receipt_number ?? 'N/A' }}</p>
            <p><strong>Acknowledgment:</strong><br>{{ $donation->acknowledgment_status_label }}</p>
            <p><strong>Acknowledgment Date:</strong><br>{{ $donation->acknowledgment_date?->format('F d, Y') ?? 'N/A' }}</p>
            <p><strong>Encoded By:</strong><br>{{ $donation->encoder?->name ?? 'N/A' }}</p>
            <p><strong>Last Updated By:</strong><br>{{ $donation->updater?->name ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="panel">
        <h2>Material / In-Kind Items</h2>

        @if($donation->items->count())
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 12px;">Item</th>
                            <th style="text-align: left; padding: 12px;">Category</th>
                            <th style="text-align: left; padding: 12px;">Quantity</th>
                            <th style="text-align: left; padding: 12px;">Estimated Value</th>
                            <th style="text-align: left; padding: 12px;">Condition</th>
                            <th style="text-align: left; padding: 12px;">Storage</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($donation->items as $item)
                            <tr>
                                <td style="padding: 12px;">
                                    <strong>{{ $item->item_name }}</strong>
                                    <br>
                                    <small>{{ $item->description ?? '' }}</small>
                                </td>
                                <td style="padding: 12px;">{{ $item->item_category_label }}</td>
                                <td style="padding: 12px;">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td style="padding: 12px;">
                                    {{ $item->estimated_total_value ? '₱' . number_format($item->estimated_total_value, 2) : 'N/A' }}
                                </td>
                                <td style="padding: 12px;">{{ $item->condition_status_label }}</td>
                                <td style="padding: 12px;">{{ $item->storage_location ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>No material items recorded for this donation.</p>
        @endif
    </div>

    <div class="panel">
        <h2>Allocation Notes</h2>
        <p>{{ $donation->allocation_notes ?? 'No allocation notes recorded.' }}</p>
    </div>

    <div class="panel">
        <h2>Remarks</h2>
        <p>{{ $donation->remarks ?? 'No remarks recorded.' }}</p>
    </div>
@endsection