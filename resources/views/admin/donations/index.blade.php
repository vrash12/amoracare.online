
@extends('layouts.dashboard', ['title' => 'Donation Management'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
            
                <p>Record cash, material, and mixed donations for transparency and accountability.</p>
            </div>

            <a href="{{ route('admin.donations.create') }}" class="btn">
                Add Donation
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.donations.index') }}"
              style="display: grid; grid-template-columns: 1fr 180px 180px 180px auto; gap: 12px; align-items: end;">
            <div>
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="{{ $search }}"
                       placeholder="Search donor, donation code, receipt" style="width: 100%;">
            </div>

            <div>
                <label for="donation_type">Type</label>
                <select id="donation_type" name="donation_type" style="width: 100%;">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="purpose">Purpose</label>
                <select id="purpose" name="purpose" style="width: 100%;">
                    <option value="">All Purposes</option>
                    @foreach($purposes as $value => $label)
                        <option value="{{ $value }}" @selected($purpose === $value)>{{ $label }}</option>
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

            <button type="submit" class="btn secondary">Filter</button>
        </form>
    </div>

    <div class="panel">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 12px;">Code</th>
                        <th style="text-align: left; padding: 12px;">Donor</th>
                        <th style="text-align: left; padding: 12px;">Type</th>
                        <th style="text-align: left; padding: 12px;">Date</th>
                        <th style="text-align: left; padding: 12px;">Cash</th>
                        <th style="text-align: left; padding: 12px;">Items</th>
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: right; padding: 12px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($donations as $donation)
                        <tr>
                            <td style="padding: 12px;">
                                <strong>{{ $donation->donation_code }}</strong>
                                <br>
                                <small>{{ $donation->purpose_label }}</small>
                            </td>

                            <td style="padding: 12px;">
                                {{ $donation->donor?->name ?? 'N/A' }}
                                <br>
                                <small>{{ $donation->donor?->donor_code ?? '' }}</small>
                            </td>

                            <td style="padding: 12px;">{{ $donation->donation_type_label }}</td>
                            <td style="padding: 12px;">{{ $donation->donation_date?->format('M d, Y') }}</td>
                            <td style="padding: 12px;">
                                {{ $donation->cash_amount ? '₱' . number_format($donation->cash_amount, 2) : 'N/A' }}
                            </td>
                            <td style="padding: 12px;">{{ $donation->items->count() }}</td>
                            <td style="padding: 12px;">{{ $donation->status_label }}</td>

                            <td style="padding: 12px; text-align: right;">
                                <a href="{{ route('admin.donations.show', $donation) }}" class="btn light">View</a>
                                <a href="{{ route('admin.donations.edit', $donation) }}" class="btn secondary">Edit</a>

                                <form method="POST"
                                      action="{{ route('admin.donations.destroy', $donation) }}"
                                      style="display: inline;"
                                      onsubmit="return confirm('Delete this donation record?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn light">Delete</button>
                                </form>
                            </td>
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