@extends('layouts.dashboard', ['title' => 'Audit & Activity Logs'])

@section('content')
    <style>
        .audit-page { display: grid; gap: 18px; }
        .audit-hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; flex-wrap: wrap; padding: 22px; border: 1px solid #dbeafe; border-radius: 22px; background: linear-gradient(135deg, #eff6ff, #fff 62%, #f8fafc); }
        .audit-hero h2 { margin: 0; color: #111827; }
        .audit-hero p { margin: 8px 0 0; color: #667085; }
        .audit-filter { display: grid; grid-template-columns: minmax(260px, 1fr) 220px auto auto; gap: 12px; align-items: end; }
        .audit-field label { display: block; margin-bottom: 6px; color: #344054; font-size: 13px; font-weight: 800; }
        .audit-field input, .audit-field select { width: 100%; min-height: 42px; padding: 10px 12px; border: 1px solid #d0d5dd; border-radius: 12px; background: #fff; }
        .audit-list { display: grid; gap: 10px; }
        .audit-event { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 13px; align-items: center; padding: 15px; border: 1px solid #e5e7eb; border-radius: 15px; background: #fff; text-decoration: none; transition: .16s ease; }
        .audit-event:hover { border-color: #bfdbfe; background: #f8fbff; transform: translateY(-1px); }
        .audit-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 13px; background: #eff6ff; color: #1d4ed8; font-size: 18px; }
        .audit-copy strong { display: block; color: #111827; }
        .audit-copy span { display: block; margin-top: 3px; color: #667085; font-size: 13px; }
        .audit-meta { color: #667085; font-size: 12px; text-align: right; white-space: nowrap; }
        .audit-meta strong { display: block; margin-bottom: 3px; color: #344054; }
        .audit-empty { padding: 30px; text-align: center; color: #667085; }
        .audit-pagination { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-top: 18px; }
        .audit-page-button { min-width: 96px; min-height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 8px 12px; border: 1px solid #d0d5dd; border-radius: 11px; background: #fff; color: #344054; font-size: 13px; font-weight: 800; text-decoration: none; }
        .audit-page-button:hover { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
        .audit-page-button.is-disabled { background: #f9fafb; color: #98a2b3; cursor: not-allowed; }
        .audit-page-status { color: #667085; font-size: 12px; font-weight: 700; text-align: center; }
        @media (max-width: 760px) {
            .audit-filter { grid-template-columns: 1fr; }
            .audit-event { grid-template-columns: auto minmax(0, 1fr); }
            .audit-meta { grid-column: 2; text-align: left; white-space: normal; }
        }
    </style>

    <div class="audit-page">
        <section class="audit-hero">
            <div>
                <h2>Audit & Activity Logs</h2>
                <p>Review recent user, child profile, adoption case, donation, and matching activity.</p>
            </div>
            <span class="btn light" style="cursor: default;">
                <i class="bi bi-clock-history"></i>
                Philippine Time
            </span>
        </section>

        <section class="panel">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="audit-filter">
                <div class="audit-field">
                    <label for="search">Search activity</label>
                    <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Action, record, or person">
                </div>

                <div class="audit-field">
                    <label for="type">Activity type</label>
                    <select id="type" name="type">
                        <option value="">All activity</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn secondary"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn light">Reset</a>
            </form>
        </section>

        <section class="panel">
            <div class="audit-list">
                @forelse($events as $event)
                    <a href="{{ $event['url'] }}" class="audit-event">
                        <span class="audit-icon"><i class="bi {{ $event['icon'] }}"></i></span>
                        <span class="audit-copy">
                            <strong>{{ $event['action'] }}</strong>
                            <span>{{ $event['description'] }}</span>
                        </span>
                        <span class="audit-meta">
                            <strong>{{ $event['actor'] }}</strong>
                            {{ $event['occurred_at']->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }} PHT
                        </span>
                    </a>
                @empty
                    <div class="audit-empty">No activity matches the selected filters.</div>
                @endforelse
            </div>

            @if($events->hasPages())
                <nav class="audit-pagination" aria-label="Audit log pagination">
                    @if($events->onFirstPage())
                        <span class="audit-page-button is-disabled" aria-disabled="true">
                            <i class="bi bi-chevron-left"></i> Previous
                        </span>
                    @else
                        <a href="{{ $events->previousPageUrl() }}" class="audit-page-button" rel="prev">
                            <i class="bi bi-chevron-left"></i> Previous
                        </a>
                    @endif

                    <span class="audit-page-status">
                        Page {{ $events->currentPage() }} of {{ $events->lastPage() }}
                    </span>

                    @if($events->hasMorePages())
                        <a href="{{ $events->nextPageUrl() }}" class="audit-page-button" rel="next">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    @else
                        <span class="audit-page-button is-disabled" aria-disabled="true">
                            Next <i class="bi bi-chevron-right"></i>
                        </span>
                    @endif
                </nav>
            @endif
        </section>
    </div>
@endsection
