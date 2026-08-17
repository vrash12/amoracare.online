@extends('layouts.dashboard', ['title' => 'Child Profile Report'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Child Profile Report</h2>
                <p>Generate filtered reports for child records and adoption eligibility.</p>
            </div>

            <a href="{{ route('admin.reports.children.export', request()->query()) }}" class="btn">
                Download CSV
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.reports.children') }}"
              style="display: grid; grid-template-columns: repeat(2, minmax(150px, 1fr)) repeat(2, minmax(180px, 1fr)) auto; gap: 12px; align-items: end;">
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
                <label for="case_status">Case Status</label>
                <select id="case_status" name="case_status" style="width: 100%;">
                    <option value="">All Statuses</option>
                    @foreach($caseStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($caseStatus === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="adoption_eligibility_status">Eligibility</label>
                <select id="adoption_eligibility_status" name="adoption_eligibility_status" style="width: 100%;">
                    <option value="">All Eligibility</option>
                    @foreach($eligibilityStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($eligibilityStatus === $value)>
                            {{ $label }}
                        </option>
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
                        <th style="text-align: left; padding: 12px;">Child Code</th>
                        <th style="text-align: left; padding: 12px;">Name</th>
                        <th style="text-align: left; padding: 12px;">Sex</th>
                        <th style="text-align: left; padding: 12px;">Case Status</th>
                        <th style="text-align: left; padding: 12px;">Eligibility</th>
                        <th style="text-align: left; padding: 12px;">Special Needs</th>
                        <th style="text-align: left; padding: 12px;">Updated</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($children as $child)
                        <tr>
                            <td style="padding: 12px;">{{ $child->child_code }}</td>
                            <td style="padding: 12px;">{{ $child->full_name }}</td>
                            <td style="padding: 12px;">{{ ucfirst($child->sex) }}</td>
                            <td style="padding: 12px;">{{ $child->case_status_label }}</td>
                            <td style="padding: 12px;">{{ $child->eligibility_status_label }}</td>
                            <td style="padding: 12px;">{{ $child->is_special_needs ? 'Yes' : 'No' }}</td>
                            <td style="padding: 12px;">{{ $child->updated_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 16px; text-align: center;">
                                No child records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $children->links() }}
        </div>
    </div>
@endsection
