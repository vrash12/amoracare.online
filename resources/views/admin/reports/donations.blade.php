@extends('layouts.dashboard', ['title' => 'Donation Report'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Donation Report</h2>
                <p>Donations received from {{ $from->format('M d, Y') }} through {{ $to->format('M d, Y') }}.</p>
            </div>

            <a href="{{ route('admin.reports.donations.export', request()->except('page')) }}" class="btn">
                Download Filtered CSV
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.reports.donations') }}"
              style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; align-items: end;">
            <div>
                <label for="search">Search</label>
                <input type="search" id="search" name="search"
                       value="{{ $search }}" placeholder="Donation or donor" style="width: 100%;">
            </div>

            <div>
                <label for="from">Date From</label>
                <input type="date" id="from" name="from"
                       value="{{ $from->format('Y-m-d') }}" style="width: 100%;">
            </div>

            <div>
                <label for="to">Date To</label>
                <input type="date" id="to" name="to"
                       value="{{ $to->format('Y-m-d') }}" style="width: 100%;">
            </div>

            <div>
                <label for="donation_type">Donation Type</label>
                <select id="donation_type" name="donation_type" style="width: 100%;">
                    <option value="">All Types</option>
                    @foreach($donationTypes as $value => $label)
                        <option value="{{ $value }}" @selected($donationType === $value)>
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

            <div>
                <label for="status">Status</label>
                <select id="status" name="status" style="width: 100%;">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="submit" class="btn secondary">Apply Filters</button>
                <a href="{{ route('admin.reports.donations') }}" class="btn light">Clear</a>
            </div>
        </form>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Total Donation Records</div>
            <div class="card-value">{{ $summary['total'] }}</div>
        </div>

        <div class="card">
            <div class="card-title">Recorded Donations</div>
            <div class="card-value">{{ number_format($summary['recorded']) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Verified Donations</div>
            <div class="card-value">{{ number_format($summary['verified']) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Cancelled Donations</div>
            <div class="card-value">{{ number_format($summary['cancelled']) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Cash Total</div>
            <div class="card-value">₱{{ number_format($summary['cash_total'], 2) }}</div>
        </div>

        <div class="card">
            <div class="card-title">In-Kind Estimated Value</div>
            <div class="card-value">₱{{ number_format($summary['in_kind_estimated_total'], 2) }}</div>
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
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: left; padding: 12px;">Date</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($donations as $donation)
                        <tr>
                            <td style="padding: 12px;">
                                <a href="{{ route('admin.donations.show', $donation) }}">{{ $donation->donation_code }}</a>
                            </td>
                            <td style="padding: 12px;">{{ $donation->donor?->name ?? 'N/A' }}</td>
                            <td style="padding: 12px;">{{ $donation->donation_type_label }}</td>
                            <td style="padding: 12px;">{{ $donation->purpose_label }}</td>
                            <td style="padding: 12px;">
                                {{ $donation->cash_amount !== null ? '₱' . number_format((float) $donation->cash_amount, 2) : 'N/A' }}
                            </td>
                            <td style="padding: 12px;">
                                ₱{{ number_format($donation->estimated_in_kind_total, 2) }}
                            </td>
                            <td style="padding: 12px;">{{ $donation->items->count() }}</td>
                            <td style="padding: 12px;">{{ $donation->status_label }}</td>
                            <td style="padding: 12px;">{{ $donation->donation_date?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 16px; text-align: center;">
                                No donations match the selected filters.
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
