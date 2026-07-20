@extends('layouts.dashboard', ['title' => 'Reports and Analytics'])

@section('content')
    <div class="panel">
        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div>
                <h2>Reports and Analytics</h2>
                <p>View adoption, child profile, donation, and document analytics.</p>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="{{ route('admin.reports.children') }}" class="btn light">Child Report</a>
                <a href="{{ route('admin.reports.adoption-cases') }}" class="btn light">Adoption Case Report</a>
                <a href="{{ route('admin.reports.donations') }}" class="btn light">Donation Report</a>
            </div>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('admin.reports.index') }}"
              style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; align-items: end;">
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

            <button type="submit" class="btn secondary">Apply Filter</button>
        </form>
    </div>

    @php
        $totalChildren = max((int) $childStats['total_children'], 1);
        $totalCases = max((int) $adoptionStats['total_cases'], 1);
        $totalDocuments = max((int) $documentStats['total_documents'], 1);
        $totalDonations = max((int) $donationStats['total_donations'], 1);

        $eligibleRate = round(($childStats['eligible_children'] / $totalChildren) * 100);
        $finalizedRate = round(($adoptionStats['finalized_cases'] / $totalCases) * 100);
        $documentVerifiedRate = round(($documentStats['verified_documents'] / $totalDocuments) * 100);
        $cashAverage = $donationStats['total_donations'] > 0
            ? $donationStats['cash_total'] / $donationStats['total_donations']
            : 0;
    @endphp

    <div class="cards">
        <div class="card">
            <div class="card-title">Total Children</div>
            <div class="card-value">{{ $childStats['total_children'] }}</div>
            <small>{{ $eligibleRate }}% eligible for adoption</small>
        </div>

        <div class="card">
            <div class="card-title">Eligible Children</div>
            <div class="card-value">{{ $childStats['eligible_children'] }}</div>
            <small>{{ $childStats['under_matching'] }} under matching</small>
        </div>

        <div class="card">
            <div class="card-title">Active Adoption Cases</div>
            <div class="card-value">{{ $adoptionStats['active_cases'] }}</div>
            <small>{{ $adoptionStats['total_cases'] }} total cases in selected range</small>
        </div>

        <div class="card">
            <div class="card-title">Finalized Cases</div>
            <div class="card-value">{{ $adoptionStats['finalized_cases'] }}</div>
            <small>{{ $finalizedRate }}% finalized rate</small>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Total Donations</div>
            <div class="card-value">{{ $donationStats['total_donations'] }}</div>
            <small>{{ $donationStats['total_donors'] }} recorded donors</small>
        </div>

        <div class="card">
            <div class="card-title">Cash Donations</div>
            <div class="card-value">₱{{ number_format($donationStats['cash_total'], 2) }}</div>
            <small>Average ₱{{ number_format($cashAverage, 2) }} per donation</small>
        </div>

        <div class="card">
            <div class="card-title">In-Kind Estimated Value</div>
            <div class="card-value">₱{{ number_format($donationStats['in_kind_total_value'], 2) }}</div>
            <small>{{ $donationStats['in_kind_item_count'] }} material items</small>
        </div>

        <div class="card">
            <div class="card-title">Verified Documents</div>
            <div class="card-value">{{ $documentStats['verified_documents'] }}</div>
            <small>{{ $documentVerifiedRate }}% of documents verified</small>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
        <div class="panel">
            <h2>Monthly Cash Donations</h2>
            <p style="margin-top: 0;">Cash donation trend based on the selected date range.</p>
            <div style="height: 320px;">
                <canvas id="monthlyDonationChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <h2>Monthly Adoption Cases</h2>
            <p style="margin-top: 0;">Number of adoption cases created per month.</p>
            <div style="height: 320px;">
                <canvas id="monthlyCaseChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px;">
        <div class="panel">
            <h2>Adoption Case Status</h2>
            <div style="height: 320px;">
                <canvas id="adoptionStatusChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <h2>Child Eligibility</h2>
            <div style="height: 320px;">
                <canvas id="childEligibilityChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px;">
        <div class="panel">
            <h2>Document Review Status</h2>
            <div style="height: 320px;">
                <canvas id="documentStatusChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <h2>Donation Types</h2>
            <div style="height: 320px;">
                <canvas id="donationTypeChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px;">
        <div class="panel">
            <h2>Adoption Case Status Summary</h2>

            <div style="display: grid; gap: 10px;">
                @foreach(\App\Models\AdoptionCase::STATUSES as $status => $label)
                    @php
                        $count = $adoptionStats['by_status'][$status] ?? 0;
                        $percent = $adoptionStats['total_cases'] > 0
                            ? round(($count / $adoptionStats['total_cases']) * 100)
                            : 0;
                    @endphp

                    <div>
                        <div style="display: flex; justify-content: space-between; gap: 12px;">
                            <span>{{ $label }}</span>
                            <strong>{{ $count }} <small>({{ $percent }}%)</small></strong>
                        </div>

                        <div style="height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin-top: 6px;">
                            <div style="height: 100%; width: {{ $percent }}%; background: #2563eb;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2>Child Eligibility Summary</h2>

            <div style="display: grid; gap: 10px;">
                @foreach(\App\Models\Child::ELIGIBILITY_STATUSES as $status => $label)
                    @php
                        $count = $childStats['by_eligibility'][$status] ?? 0;
                        $percent = $childStats['total_children'] > 0
                            ? round(($count / $childStats['total_children']) * 100)
                            : 0;
                    @endphp

                    <div>
                        <div style="display: flex; justify-content: space-between; gap: 12px;">
                            <span>{{ $label }}</span>
                            <strong>{{ $count }} <small>({{ $percent }}%)</small></strong>
                        </div>

                        <div style="height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin-top: 6px;">
                            <div style="height: 100%; width: {{ $percent }}%; background: #16a34a;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 16px;">
        <div class="panel">
            <h2>Document Review Summary</h2>

            <div class="cards">
                <div class="card">
                    <div class="card-title">Total Documents</div>
                    <div class="card-value">{{ $documentStats['total_documents'] }}</div>
                </div>

                <div class="card">
                    <div class="card-title">Pending</div>
                    <div class="card-value">{{ $documentStats['pending_documents'] }}</div>
                </div>

                <div class="card">
                    <div class="card-title">Submitted</div>
                    <div class="card-value">{{ $documentStats['submitted_documents'] }}</div>
                </div>

                <div class="card">
                    <div class="card-title">Verified</div>
                    <div class="card-value">{{ $documentStats['verified_documents'] }}</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Donation Purpose Summary</h2>

            <div style="display: grid; gap: 10px;">
                @foreach(\App\Models\Donation::PURPOSES as $purpose => $label)
                    @php
                        $count = $donationStats['by_purpose'][$purpose] ?? 0;
                        $percent = $donationStats['total_donations'] > 0
                            ? round(($count / $donationStats['total_donations']) * 100)
                            : 0;
                    @endphp

                    <div>
                        <div style="display: flex; justify-content: space-between; gap: 12px;">
                            <span>{{ $label }}</span>
                            <strong>{{ $count }} <small>({{ $percent }}%)</small></strong>
                        </div>

                        <div style="height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin-top: 6px;">
                            <div style="height: 100%; width: {{ $percent }}%; background: #f59e0b;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const chartColors = [
            '#2563eb',
            '#16a34a',
            '#f59e0b',
            '#dc2626',
            '#7c3aed',
            '#0891b2',
            '#db2777',
            '#65a30d',
            '#9333ea',
            '#475569'
        ];

        const monthlyDonationData = @json($monthlyDonationChart);
        const monthlyCaseData = @json($monthlyCaseChart);

        const adoptionStatusLabels = @json(collect(\App\Models\AdoptionCase::STATUSES)->values());
        const adoptionStatusData = @json(
            collect(\App\Models\AdoptionCase::STATUSES)
                ->keys()
                ->map(fn ($status) => $adoptionStats['by_status'][$status] ?? 0)
                ->values()
        );

        const childEligibilityLabels = @json(collect(\App\Models\Child::ELIGIBILITY_STATUSES)->values());
        const childEligibilityData = @json(
            collect(\App\Models\Child::ELIGIBILITY_STATUSES)
                ->keys()
                ->map(fn ($status) => $childStats['by_eligibility'][$status] ?? 0)
                ->values()
        );

        const documentStatusLabels = @json(collect(\App\Models\AdoptionCaseDocument::STATUSES)->values());
        const documentStatusData = @json(
            collect(\App\Models\AdoptionCaseDocument::STATUSES)
                ->keys()
                ->map(fn ($status) => $documentStats['by_status'][$status] ?? 0)
                ->values()
        );

        const donationTypeLabels = @json(collect(\App\Models\Donation::TYPES)->values());
        const donationTypeData = @json(
            collect(\App\Models\Donation::TYPES)
                ->keys()
                ->map(fn ($type) => $donationStats['by_type'][$type] ?? 0)
                ->values()
        );

        function makeChart(canvasId, config) {
            const element = document.getElementById(canvasId);

            if (!element) {
                return;
            }

            new Chart(element, config);
        }

        makeChart('monthlyDonationChart', {
            type: 'bar',
            data: {
                labels: monthlyDonationData.labels ?? [],
                datasets: [
                    {
                        label: 'Cash Donations',
                        data: monthlyDonationData.cash_totals ?? [],
                        backgroundColor: '#2563eb',
                        borderColor: '#1d4ed8',
                        borderWidth: 1
                    },
                    {
                        label: 'Donation Count',
                        data: monthlyDonationData.donation_counts ?? [],
                        backgroundColor: '#16a34a',
                        borderColor: '#15803d',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'countAxis'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cash Amount'
                        }
                    },
                    countAxis: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Donation Count'
                        }
                    }
                }
            }
        });

        makeChart('monthlyCaseChart', {
            type: 'line',
            data: {
                labels: monthlyCaseData.labels ?? [],
                datasets: [
                    {
                        label: 'Adoption Cases',
                        data: monthlyCaseData.case_counts ?? [],
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.15)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        makeChart('adoptionStatusChart', {
            type: 'bar',
            data: {
                labels: adoptionStatusLabels,
                datasets: [
                    {
                        label: 'Cases',
                        data: adoptionStatusData,
                        backgroundColor: chartColors
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        makeChart('childEligibilityChart', {
            type: 'doughnut',
            data: {
                labels: childEligibilityLabels,
                datasets: [
                    {
                        data: childEligibilityData,
                        backgroundColor: chartColors
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        makeChart('documentStatusChart', {
            type: 'doughnut',
            data: {
                labels: documentStatusLabels,
                datasets: [
                    {
                        data: documentStatusData,
                        backgroundColor: chartColors
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        makeChart('donationTypeChart', {
            type: 'pie',
            data: {
                labels: donationTypeLabels,
                datasets: [
                    {
                        data: donationTypeData,
                        backgroundColor: chartColors
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endsection