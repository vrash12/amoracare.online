<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Models\Child;
use App\Models\Donation;
use App\Models\DonationItem;
use App\Models\Donor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || Auth::user()?->role?->slug !== 'admin') {
            abort(403, 'Only administrators can access reports.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);

        $childrenQuery = Child::query()
            ->whereBetween('created_at', [$from, $to]);

        $casesQuery = AdoptionCase::query()
            ->whereBetween('created_at', [$from, $to]);

        $documentsQuery = AdoptionCaseDocument::query()
            ->whereBetween('created_at', [$from, $to]);

        $donationsQuery = Donation::query()
            ->whereBetween('donation_date', [
                $from->toDateString(),
                $to->toDateString(),
            ]);

        $summary = [
            'total_children' => (clone $childrenQuery)->count(),
            'eligible_children' => (clone $childrenQuery)
                ->where('adoption_eligibility_status', 'eligible')
                ->count(),
            'available_children' => (clone $childrenQuery)
                ->where('case_status', 'available_for_adoption')
                ->count(),
            'special_needs_children' => (clone $childrenQuery)
                ->where('is_special_needs', true)
                ->count(),

            'total_adoption_cases' => (clone $casesQuery)->count(),
            'active_adoption_cases' => (clone $casesQuery)
                ->whereNotIn('status', ['finalized', 'closed', 'cancelled'])
                ->count(),
            'finalized_adoption_cases' => (clone $casesQuery)
                ->where('status', 'finalized')
                ->count(),
            'cancelled_adoption_cases' => (clone $casesQuery)
                ->where('status', 'cancelled')
                ->count(),

            'total_documents' => (clone $documentsQuery)->count(),
            'verified_documents' => (clone $documentsQuery)
                ->where('status', 'verified')
                ->count(),
            'pending_documents' => (clone $documentsQuery)
                ->where('status', 'pending')
                ->count(),
            'rejected_documents' => (clone $documentsQuery)
                ->where('status', 'rejected')
                ->count(),
            'expired_documents' => (clone $documentsQuery)
                ->where('status', 'expired')
                ->count(),

            'total_donations' => (clone $donationsQuery)->count(),
            'verified_donations' => (clone $donationsQuery)
                ->where('status', 'verified')
                ->count(),
            'cash_donation_total' => (float) (clone $donationsQuery)
                ->whereIn('donation_type', ['cash', 'mixed'])
                ->sum('cash_amount'),

            'total_donors' => Donor::count(),

            'prospective_parents' => User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })->count(),

            'external_reviewers' => User::whereHas('role', function ($query) {
                $query->where('slug', 'external_reviewer');
            })->count(),
        ];

        $summary['document_progress_percent'] = $summary['total_documents'] > 0
            ? round(($summary['verified_documents'] / $summary['total_documents']) * 100)
            : 0;

        $childrenByCaseStatus = (clone $childrenQuery)
            ->select('case_status', DB::raw('COUNT(*) as total'))
            ->groupBy('case_status')
            ->orderBy('case_status')
            ->get();

        $childrenByEligibility = (clone $childrenQuery)
            ->select('adoption_eligibility_status', DB::raw('COUNT(*) as total'))
            ->groupBy('adoption_eligibility_status')
            ->orderBy('adoption_eligibility_status')
            ->get();

        $casesByStatus = (clone $casesQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $casesByType = (clone $casesQuery)
            ->select('case_type', DB::raw('COUNT(*) as total'))
            ->groupBy('case_type')
            ->orderBy('case_type')
            ->get();

        $documentsByStatus = (clone $documentsQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $donationsByPurpose = (clone $donationsQuery)
            ->select('purpose', DB::raw('COUNT(*) as total'), DB::raw('SUM(cash_amount) as cash_total'))
            ->groupBy('purpose')
            ->orderBy('purpose')
            ->get();

        $dailyCases = AdoptionCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as report_date, COUNT(*) as total')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get();

        $dailyDonations = Donation::query()
            ->whereBetween('donation_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->selectRaw('DATE(donation_date) as report_date, COUNT(*) as total, SUM(cash_amount) as cash_total')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get();

        $chartData = [
            'dailyCases' => $this->buildDailySeries(
                $from,
                $to,
                $dailyCases,
                'report_date',
                'total'
            ),

            'dailyDonations' => $this->buildDailySeries(
                $from,
                $to,
                $dailyDonations,
                'report_date',
                'total'
            ),

            'dailyCashDonations' => $this->buildDailySeries(
                $from,
                $to,
                $dailyDonations,
                'report_date',
                'cash_total'
            ),

            'childrenByCaseStatus' => [
                'labels' => $childrenByCaseStatus
                    ->pluck('case_status')
                    ->map(fn ($status) => Child::CASE_STATUSES[$status] ?? ucwords(str_replace('_', ' ', (string) $status)))
                    ->values(),
                'values' => $childrenByCaseStatus
                    ->pluck('total')
                    ->map(fn ($value) => (int) $value)
                    ->values(),
            ],

            'childrenByEligibility' => [
                'labels' => $childrenByEligibility
                    ->pluck('adoption_eligibility_status')
                    ->map(fn ($status) => Child::ELIGIBILITY_STATUSES[$status] ?? ucwords(str_replace('_', ' ', (string) $status)))
                    ->values(),
                'values' => $childrenByEligibility
                    ->pluck('total')
                    ->map(fn ($value) => (int) $value)
                    ->values(),
            ],

            'casesByStatus' => [
                'labels' => $casesByStatus
                    ->pluck('status')
                    ->map(fn ($status) => AdoptionCase::STATUSES[$status] ?? ucwords(str_replace('_', ' ', (string) $status)))
                    ->values(),
                'values' => $casesByStatus
                    ->pluck('total')
                    ->map(fn ($value) => (int) $value)
                    ->values(),
            ],

            'casesByType' => [
                'labels' => $casesByType
                    ->pluck('case_type')
                    ->map(fn ($type) => AdoptionCase::CASE_TYPES[$type] ?? ucwords(str_replace('_', ' ', (string) $type)))
                    ->values(),
                'values' => $casesByType
                    ->pluck('total')
                    ->map(fn ($value) => (int) $value)
                    ->values(),
            ],

            'documentsByStatus' => [
                'labels' => $documentsByStatus
                    ->pluck('status')
                    ->map(fn ($status) => AdoptionCaseDocument::STATUSES[$status] ?? ucwords(str_replace('_', ' ', (string) $status)))
                    ->values(),
                'values' => $documentsByStatus
                    ->pluck('total')
                    ->map(fn ($value) => (int) $value)
                    ->values(),
            ],

            'donationsByPurpose' => [
                'labels' => $donationsByPurpose
                    ->pluck('purpose')
                    ->map(fn ($purpose) => Donation::PURPOSES[$purpose] ?? ucwords(str_replace('_', ' ', (string) $purpose)))
                    ->values(),
                'values' => $donationsByPurpose
                    ->pluck('total')
                    ->map(fn ($value) => (int) $value)
                    ->values(),
                'cashTotals' => $donationsByPurpose
                    ->pluck('cash_total')
                    ->map(fn ($value) => (float) $value)
                    ->values(),
            ],
        ];

        $recentChildren = Child::with(['creator', 'updater'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->take(10)
            ->get();

        $recentAdoptionCases = AdoptionCase::with([
                'child',
                'prospectiveParent',
                'assignedSocialWorker',
                'documents',
            ])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->take(10)
            ->get();

        $recentDonations = Donation::with(['donor', 'items', 'encoder'])
            ->whereBetween('donation_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.reports.index', compact(
            'from',
            'to',
            'summary',
            'childrenByCaseStatus',
            'childrenByEligibility',
            'casesByStatus',
            'casesByType',
            'documentsByStatus',
            'donationsByPurpose',
            'recentChildren',
            'recentAdoptionCases',
            'recentDonations',
            'chartData'
        ));
    }

    public function children(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);

        $search = $request->input('search');
        $caseStatus = $request->input('case_status');
        $eligibilityStatus = $request->input('adoption_eligibility_status');
        $sex = $request->input('sex');
        $specialNeeds = $request->input('special_needs');

        $childrenQuery = Child::with(['creator', 'updater'])
            ->whereBetween('created_at', [$from, $to]);

        if ($search) {
            $childrenQuery->where(function ($query) use ($search) {
                $query->where('child_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        if ($caseStatus) {
            $childrenQuery->where('case_status', $caseStatus);
        }

        if ($eligibilityStatus) {
            $childrenQuery->where('adoption_eligibility_status', $eligibilityStatus);
        }

        if ($sex) {
            $childrenQuery->where('sex', $sex);
        }

        if ($specialNeeds !== null && $specialNeeds !== '') {
            $childrenQuery->where('is_special_needs', (bool) $specialNeeds);
        }

        $children = $childrenQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $childrenQuery)->count(),
            'eligible' => (clone $childrenQuery)->where('adoption_eligibility_status', 'eligible')->count(),
            'available_for_adoption' => (clone $childrenQuery)->where('case_status', 'available_for_adoption')->count(),
            'special_needs' => (clone $childrenQuery)->where('is_special_needs', true)->count(),
        ];

        $caseStatuses = Child::CASE_STATUSES;
        $eligibilityStatuses = Child::ELIGIBILITY_STATUSES;

        return view('admin.reports.children', compact(
            'from',
            'to',
            'children',
            'summary',
            'search',
            'caseStatus',
            'eligibilityStatus',
            'sex',
            'specialNeeds',
            'caseStatuses',
            'eligibilityStatuses'
        ));
    }

    public function adoptionCases(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);

        $search = $request->input('search');
        $status = $request->input('status');
        $caseType = $request->input('case_type');
        $priority = $request->input('priority');

        $casesQuery = $this->adoptionCasesReportQuery($request, $from, $to);

        $adoptionCases = (clone $casesQuery)
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => (clone $casesQuery)->count(),
            'active' => (clone $casesQuery)->whereNotIn('status', ['finalized', 'closed', 'cancelled'])->count(),
            'finalized' => (clone $casesQuery)->where('status', 'finalized')->count(),
            'cancelled' => (clone $casesQuery)->where('status', 'cancelled')->count(),
        ];

        $caseTypes = AdoptionCase::CASE_TYPES;
        $statuses = AdoptionCase::STATUSES;
        $priorities = AdoptionCase::PRIORITIES;

        return view('admin.reports.adoption_cases', compact(
            'from',
            'to',
            'adoptionCases',
            'summary',
            'search',
            'status',
            'caseType',
            'priority',
            'caseTypes',
            'statuses',
            'priorities'
        ));
    }

    public function donations(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);

        $search = $request->input('search');
        $donationType = $request->input('donation_type');
        $purpose = $request->input('purpose');
        $status = $request->input('status');

        $donationsQuery = $this->donationsReportQuery($request, $from, $to);

        $donations = (clone $donationsQuery)
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $donationIds = (clone $donationsQuery)->pluck('id');

        $inKindEstimatedTotal = DonationItem::whereIn('donation_id', $donationIds)
            ->sum('estimated_total_value');

        $summary = [
            'total' => (clone $donationsQuery)->count(),
            'verified' => (clone $donationsQuery)->where('status', 'verified')->count(),
            'recorded' => (clone $donationsQuery)->where('status', 'recorded')->count(),
            'cancelled' => (clone $donationsQuery)->where('status', 'cancelled')->count(),
            'cash_total' => (float) (clone $donationsQuery)->sum('cash_amount'),
            'in_kind_estimated_total' => (float) $inKindEstimatedTotal,
        ];

        $donationTypes = Donation::TYPES;
        $purposes = Donation::PURPOSES;
        $statuses = Donation::STATUSES;

        return view('admin.reports.donations', compact(
            'from',
            'to',
            'donations',
            'summary',
            'search',
            'donationType',
            'purpose',
            'status',
            'donationTypes',
            'purposes',
            'statuses'
        ));
    }

    public function exportChildren(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);

        $filename = 'amoracare_children_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Child Code',
                'Full Name',
                'Nickname',
                'Sex',
                'Date of Birth',
                'Admission Date',
                'Current Location',
                'Case Status',
                'Eligibility Status',
                'Special Needs',
                'Health Status',
                'Educational Level',
                'Created At',
            ], ',', '"', '');

            Child::query()
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->chunk(200, function ($children) use ($handle) {
                    foreach ($children as $child) {
                        fputcsv($handle, [
                            $child->child_code,
                            $child->full_name,
                            $child->nickname,
                            $child->sex,
                            optional($child->date_of_birth)->format('Y-m-d'),
                            optional($child->admission_date)->format('Y-m-d'),
                            $child->current_location,
                            $child->case_status_label,
                            $child->eligibility_status_label,
                            $child->is_special_needs ? 'Yes' : 'No',
                            $child->health_status,
                            $child->educational_level,
                            optional($child->created_at)->format('Y-m-d H:i:s'),
                        ], ',', '"', '');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportAdoptionCases(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);
        $casesQuery = $this->adoptionCasesReportQuery($request, $from, $to);

        $filename = 'amoracare_adoption_cases_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($casesQuery) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Case Code',
                'Case Type',
                'Status',
                'Priority',
                'Child Code',
                'Child Name',
                'Prospective Parent',
                'Assigned Social Worker',
                'Opened At',
                'Target Completion',
                'Closed At',
                'Document Progress',
                'Created At',
            ], ',', '"', '');

            (clone $casesQuery)
                ->chunkById(200, function ($cases) use ($handle) {
                    foreach ($cases as $case) {
                        fputcsv($handle, [
                            $case->case_code,
                            $case->case_type_label,
                            $case->status_label,
                            $case->priority_label,
                            $case->child?->child_code,
                            $case->child?->full_name,
                            $case->prospectiveParent?->name,
                            $case->assignedSocialWorker?->name,
                            optional($case->opened_at)->format('Y-m-d'),
                            optional($case->target_completion_date)->format('Y-m-d'),
                            optional($case->closed_at)->format('Y-m-d'),
                            $case->document_progress,
                            optional($case->created_at)->format('Y-m-d H:i:s'),
                        ], ',', '"', '');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportDonations(Request $request)
    {
        $this->authorizeAdmin();

        [$from, $to] = $this->resolveDateRange($request);
        $donationsQuery = $this->donationsReportQuery($request, $from, $to);

        $filename = 'amoracare_donations_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($donationsQuery) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Donation Code',
                'Donor',
                'Donor Type',
                'Donation Type',
                'Donation Date',
                'Purpose',
                'Cash Amount',
                'Payment Method',
                'Reference Number',
                'Receipt Number',
                'Acknowledgment Status',
                'Status',
                'In-Kind Items',
                'Estimated In-Kind Total',
                'Encoded By',
                'Created At',
            ], ',', '"', '');

            (clone $donationsQuery)
                ->chunkById(200, function ($donations) use ($handle) {
                    foreach ($donations as $donation) {
                        $items = $donation->items->map(function ($item) {
                            return $item->item_name . ' - ' . $item->quantity . ' ' . $item->unit;
                        })->implode(' | ');

                        fputcsv($handle, [
                            $donation->donation_code,
                            $donation->donor?->name,
                            $donation->donor?->donor_type_label,
                            $donation->donation_type_label,
                            optional($donation->donation_date)->format('Y-m-d'),
                            $donation->purpose_label,
                            $donation->cash_amount,
                            $donation->payment_method,
                            $donation->reference_number,
                            $donation->receipt_number,
                            $donation->acknowledgment_status_label,
                            $donation->status_label,
                            $items,
                            $donation->estimated_in_kind_total,
                            $donation->encoder?->name,
                            optional($donation->created_at)->format('Y-m-d H:i:s'),
                        ], ',', '"', '');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function adoptionCasesReportQuery(Request $request, Carbon $from, Carbon $to): Builder
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $caseType = $request->input('case_type');
        $priority = $request->input('priority');

        return AdoptionCase::with([
                'child',
                'prospectiveParent',
                'assignedSocialWorker',
                'documents',
                'creator',
                'updater',
            ])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('opened_at', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($fallbackQuery) use ($from, $to) {
                        $fallbackQuery->whereNull('opened_at')
                            ->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('case_code', 'like', "%{$search}%")
                        ->orWhereHas('child', function ($childQuery) use ($search) {
                            $childQuery->where('child_code', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('prospectiveParent', function ($parentQuery) use ($search) {
                            $parentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(array_key_exists((string) $status, AdoptionCase::STATUSES), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists((string) $caseType, AdoptionCase::CASE_TYPES), fn ($query) => $query->where('case_type', $caseType))
            ->when(array_key_exists((string) $priority, AdoptionCase::PRIORITIES), fn ($query) => $query->where('priority', $priority));
    }

    private function donationsReportQuery(Request $request, Carbon $from, Carbon $to): Builder
    {
        $search = trim((string) $request->input('search', ''));
        $donationType = $request->input('donation_type');
        $purpose = $request->input('purpose');
        $status = $request->input('status');

        return Donation::with(['donor', 'items', 'encoder', 'updater'])
            ->whereBetween('donation_date', [$from->toDateString(), $to->toDateString()])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('donation_code', 'like', "%{$search}%")
                        ->orWhere('receipt_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('donor', function ($donorQuery) use ($search) {
                            $donorQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when(array_key_exists((string) $donationType, Donation::TYPES), fn ($query) => $query->where('donation_type', $donationType))
            ->when(array_key_exists((string) $purpose, Donation::PURPOSES), fn ($query) => $query->where('purpose', $purpose))
            ->when(array_key_exists((string) $status, Donation::STATUSES), fn ($query) => $query->where('status', $status));
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfYear();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [
                $to->copy()->startOfDay(),
                $from->copy()->endOfDay(),
            ];
        }

        return [$from, $to];
    }

    private function buildDailySeries(Carbon $from, Carbon $to, $rows, string $dateKey, string $valueKey): array
    {
        $labels = [];
        $values = [];

        $rowsByDate = $rows->keyBy($dateKey);

        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->format('Y-m-d');

            $labels[] = $cursor->format('M d');
            $values[] = isset($rowsByDate[$date])
                ? (float) $rowsByDate[$date]->{$valueKey}
                : 0;

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
