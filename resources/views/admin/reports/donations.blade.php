@extends('layouts.dashboard', ['title' => 'Donation Report'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Donation Report</h2>
                <p>Generate reports for cash, material, and mixed donations.</p>
            </div>

            <a href="{{ route('admin.reports.donations.export', request()->query()) }}" class="btn">
                Download CSV
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.reports.donations') }}"
              style="display: grid; grid-template-columns: 180px 180px 200px 200px auto; gap: 12px; align-items: end;">
            <div>
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from"
                       value="{{ $dateFrom->format('Y-m-d') }}" style="width: 100%;">
            </div>

            <div>
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to"
                       value="{{ $dateTo->format('Y-m-d') }}" style="width: 100%;">
            </div>

            <div>
                <label for="donation_type">Donation Type</label>
                <select id="donation_type" name="donation_type" style="width: 100%;">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="purpose">Purpose</label>
                <select id="purpose" name="purpose" style="width: 100%;">
                    <option value="">All Purposes</option>
                    @foreach($purposes as $value => $label)
                        <option value="{{ $value }}" @selected($purpose === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn secondary">Filter</button>
        </form>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Total Donation Records</div>
            <div class="card-value">{{ $summary['total_donations'] }}</div>
        </div>

        <div class="card">
            <div class="card-title">Cash Total</div>
            <div class="card-value">₱{{ number_format($summary['total_cash'], 2) }}</div>
        </div>

        <div class="card">
            <div class="card-title">In-Kind Estimated Value</div>
            <div class="card-value">₱{{ number_format($summary['total_in_kind_value'], 2) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Material Items</div>
            <div class="card-value">{{ $summary['total_items'] }}</div>
        </div>
    </div>

    <div class="panel">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 12px;">Donation Code</th>
                        <th style="text-align: left; padding: 12px;">Donor</th>
                        <th style="text-align: left; padding: 12px;">Type</th>
                        <th style="text-align: left; padding: 12px;">Purpose</th>
                        <th style="text-align: left; padding: 12px;">Cash</th>
                        <th style="text-align: left; padding: 12px;">In-Kind Value</th>
                        <th style="text-align: left; padding: 12px;">Items</th>
                        <th style="text-align: left; padding: 12px;">Date</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($donations as $donation)
                        <tr>
                            <td style="padding: 12px;">{{ $donation->donation_code }}</td>
                            <td style="padding: 12px;">{{ $donation->donor?->name ?? 'N/A' }}</td>
                            <td style="padding: 12px;">{{ $donation->donation_type_label }}</td>
                            <td style="padding: 12px;">{{ $donation->purpose_label }}</td>
                            <td style="padding: 12px;">
                                {{ $donation->cash_amount ? '₱' . number_format($donation->cash_amount, 2) : 'N/A' }}
                            </td>
                            <td style="padding: 12px;">
                                ₱{{ number_format($donation->estimated_in_kind_total, 2) }}
                            </td>
                            <td style="padding: 12px;">{{ $donation->items->count() }}</td>
                            <td style="padding: 12px;">{{ $donation->donation_date?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 16px; text-align: center;">
                                No donation records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $donations->links() }}
        </div>
    </div>
@endsection