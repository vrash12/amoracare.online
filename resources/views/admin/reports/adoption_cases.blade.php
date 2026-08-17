@extends('layouts.dashboard', ['title' => 'Adoption Case Report'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Adoption Case Report</h2>
                <p>
                    Cases opened from {{ $from->format('M d, Y') }} through {{ $to->format('M d, Y') }}.
                    Cases without an opened date use their creation date.
                </p>
            </div>

            <a href="{{ route('admin.reports.adoption-cases.export', request()->except('page')) }}" class="btn">
                Download Filtered CSV
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.reports.adoption-cases') }}"
              style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; align-items: end;">
            <div>
                <label for="search">Search</label>
                <input type="search" id="search" name="search"
                       value="{{ $search }}" placeholder="Case, child, or parent" style="width: 100%;">
            </div>

            <div>
                <label for="from">Opened From</label>
                <input type="date" id="from" name="from"
                       value="{{ $from->format('Y-m-d') }}" style="width: 100%;">
            </div>

            <div>
                <label for="to">Opened To</label>
                <input type="date" id="to" name="to"
                       value="{{ $to->format('Y-m-d') }}" style="width: 100%;">
            </div>

            <div>
                <label for="case_type">Case Type</label>
                <select id="case_type" name="case_type" style="width: 100%;">
                    <option value="">All Case Types</option>
                    @foreach($caseTypes as $value => $label)
                        <option value="{{ $value }}" @selected($caseType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
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

            <div>
                <label for="priority">Priority</label>
                <select id="priority" name="priority" style="width: 100%;">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected($priority === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="submit" class="btn secondary">Apply Filters</button>
                <a href="{{ route('admin.reports.adoption-cases') }}" class="btn light">Clear</a>
            </div>
        </form>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Matching Cases</div>
            <div class="card-value">{{ number_format($summary['total']) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Active Cases</div>
            <div class="card-value">{{ number_format($summary['active']) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Finalized Cases</div>
            <div class="card-value">{{ number_format($summary['finalized']) }}</div>
        </div>

        <div class="card">
            <div class="card-title">Cancelled Cases</div>
            <div class="card-value">{{ number_format($summary['cancelled']) }}</div>
        </div>
    </div>

    <div class="panel">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 12px;">Case Code</th>
                        <th style="text-align: left; padding: 12px;">Case Type</th>
                        <th style="text-align: left; padding: 12px;">Child</th>
                        <th style="text-align: left; padding: 12px;">Parent</th>
                        <th style="text-align: left; padding: 12px;">Assigned Staff</th>
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: left; padding: 12px;">Priority</th>
                        <th style="text-align: left; padding: 12px;">Documents</th>
                        <th style="text-align: left; padding: 12px;">Opened</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($adoptionCases as $case)
                        <tr>
                            <td style="padding: 12px;">
                                <a href="{{ route('admin.adoption-cases.show', $case) }}">{{ $case->case_code }}</a>
                            </td>
                            <td style="padding: 12px;">{{ $case->case_type_label }}</td>
                            <td style="padding: 12px;">{{ $case->child?->full_name ?? 'N/A' }}</td>
                            <td style="padding: 12px;">{{ $case->prospectiveParent?->name ?? 'Not assigned' }}</td>
                            <td style="padding: 12px;">{{ $case->assignedSocialWorker?->name ?? 'Not assigned' }}</td>
                            <td style="padding: 12px;">{{ $case->status_label }}</td>
                            <td style="padding: 12px;">{{ $case->priority_label }}</td>
                            <td style="padding: 12px;">{{ $case->document_progress }} ({{ $case->document_progress_percent }}%)</td>
                            <td style="padding: 12px;">{{ $case->opened_at?->format('M d, Y') ?? $case->created_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 16px; text-align: center;">
                                No adoption cases match the selected filters.
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
