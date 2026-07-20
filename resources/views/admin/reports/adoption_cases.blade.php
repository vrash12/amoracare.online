@extends('layouts.dashboard', ['title' => 'Adoption Case Report'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Adoption Case Report</h2>
                <p>Generate adoption workflow progress and document completion reports.</p>
            </div>

            <a href="{{ route('admin.reports.adoption-cases.export', request()->query()) }}" class="btn">
                Download CSV
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.reports.adoption-cases') }}"
              style="display: grid; grid-template-columns: 200px 200px 220px auto; gap: 12px; align-items: end;">
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
                <label for="status">Status</label>
                <select id="status" name="status" style="width: 100%;">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>
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
                        <th style="text-align: left; padding: 12px;">Case Code</th>
                        <th style="text-align: left; padding: 12px;">Child</th>
                        <th style="text-align: left; padding: 12px;">Parent</th>
                        <th style="text-align: left; padding: 12px;">Assigned Staff</th>
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: left; padding: 12px;">Priority</th>
                        <th style="text-align: left; padding: 12px;">Documents</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($adoptionCases as $case)
                        <tr>
                            <td style="padding: 12px;">{{ $case->case_code }}</td>
                            <td style="padding: 12px;">{{ $case->child?->full_name ?? 'N/A' }}</td>
                            <td style="padding: 12px;">{{ $case->prospectiveParent?->name ?? 'Not assigned' }}</td>
                            <td style="padding: 12px;">{{ $case->assignedSocialWorker?->name ?? 'Not assigned' }}</td>
                            <td style="padding: 12px;">{{ $case->status_label }}</td>
                            <td style="padding: 12px;">{{ $case->priority_label }}</td>
                            <td style="padding: 12px;">{{ $case->document_progress }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 16px; text-align: center;">
                                No adoption cases found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $adoptionCases->links() }}
        </div>
    </div>
@endsection