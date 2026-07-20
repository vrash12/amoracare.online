@extends('layouts.dashboard', ['title' => 'Adoption Cases'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Adoption Cases</h2>
                <p>Manage adoption workflows, document checklists, case progress, and staff notes.</p>
            </div>

            <a href="{{ route('admin.adoption-cases.create') }}" class="btn">
                Create Adoption Case
            </a>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.adoption-cases.index') }}"
              style="display: grid; grid-template-columns: 1fr 220px 180px auto; gap: 12px; align-items: end;">
            <div>
                <label for="search">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search case code, child, or parent"
                    style="width: 100%;"
                >
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
                        <option value="{{ $value }}" @selected($priority === $value)>
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
                        <th style="text-align: left; padding: 12px;">Prospective Parent</th>
                        <th style="text-align: left; padding: 12px;">Status</th>
                        <th style="text-align: left; padding: 12px;">Priority</th>
                        <th style="text-align: left; padding: 12px;">Documents</th>
                        <th style="text-align: right; padding: 12px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($adoptionCases as $case)
                        <tr>
                            <td style="padding: 12px;">
                                <strong>{{ $case->case_code }}</strong>
                                <br>
                                <small>{{ $case->case_type_label }}</small>
                            </td>

                            <td style="padding: 12px;">
                                {{ $case->child?->full_name ?? 'N/A' }}
                                <br>
                                <small>{{ $case->child?->child_code ?? '' }}</small>
                            </td>

                            <td style="padding: 12px;">
                                {{ $case->prospectiveParent?->name ?? 'Not assigned' }}
                            </td>

                            <td style="padding: 12px;">
                                {{ $case->status_label }}
                            </td>

                            <td style="padding: 12px;">
                                {{ $case->priority_label }}
                            </td>

                            <td style="padding: 12px;">
                                {{ $case->document_progress }}
                            </td>

                            <td style="padding: 12px; text-align: right;">
                                <a href="{{ route('admin.adoption-cases.show', $case) }}" class="btn light">View</a>
                                <a href="{{ route('admin.adoption-cases.edit', $case) }}" class="btn secondary">Edit</a>

                                <form
                                    method="POST"
                                    action="{{ route('admin.adoption-cases.destroy', $case) }}"
                                    style="display: inline;"
                                    onsubmit="return confirm('Delete this adoption case? This will soft delete the record.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn light">
                                        Delete
                                    </button>
                                </form>
                            </td>
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