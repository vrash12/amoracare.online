<?php
//app/Http/Controllers/Admin/AdoptionMatchingController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionMatchingResult;
use App\Models\AdoptionMatchingRun;
use App\Models\Child;
use App\Models\User;
use App\Services\AdoptionMatching\GaleShapleyMatcher;
use App\Services\ExternalReviewerAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\AdoptionMatching\MatchAiExplanationService;
use App\Jobs\GenerateMatchAiExplanation;

class AdoptionMatchingController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can access adoption matching.');
        }
    }

    public function index(): View
    {
        $this->authorizeAdmin();

        $runs = AdoptionMatchingRun::with(['generator'])
            ->withCount('results')
            ->latest()
            ->paginate(10);

        $eligibleChildrenCount = Child::query()
            ->where('adoption_eligibility_status', 'eligible')
            ->where('case_status', 'available_for_adoption')
            ->whereDoesntHave('adoptionCases', function ($query) {
                $query->whereNotIn('status', ['finalized', 'closed', 'cancelled']);
            })
            ->count();

        $activeParentsWithProfilesCount = User::query()
            ->whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->where('status', 'active')
            ->whereHas('matchingProfile')
            ->whereDoesntHave('adoptionCasesAsProspectiveParent', function ($query) {
                $query->whereNotIn('status', ['finalized', 'closed', 'cancelled']);
            })
            ->count();

        $totalRunsCount = AdoptionMatchingRun::count();

        $completedRunsCount = AdoptionMatchingRun::where('status', 'completed')->count();

        $totalRecommendationsCount = AdoptionMatchingResult::count();

        $pendingRecommendationsCount = AdoptionMatchingResult::where('status', 'recommended')->count();

        $convertedRecommendationsCount = AdoptionMatchingResult::where('status', 'converted_to_case')->count();

        $latestRun = AdoptionMatchingRun::with(['generator'])
            ->withCount('results')
            ->latest()
            ->first();

        return view('admin.matching.index', compact(
            'runs',
            'eligibleChildrenCount',
            'activeParentsWithProfilesCount',
            'totalRunsCount',
            'completedRunsCount',
            'totalRecommendationsCount',
            'pendingRecommendationsCount',
            'convertedRecommendationsCount',
            'latestRun'
        ));
    }

public function run(
    GaleShapleyMatcher $matcher
): RedirectResponse {
    $this->authorizeAdmin();

    $children = Child::query()
        ->where('adoption_eligibility_status', 'eligible')
        ->where('case_status', 'available_for_adoption')
        ->whereDoesntHave('adoptionCases', function ($query) {
            $query->whereNotIn(
                'status',
                ['finalized', 'closed', 'cancelled']
            );
        })
        ->orderBy('created_at')
        ->get();

    $parents = User::query()
        ->with('matchingProfile')
        ->whereHas('role', function ($query) {
            $query->where('slug', 'prospective_parent');
        })
        ->where('status', 'active')
        ->whereHas('matchingProfile')
        ->whereDoesntHave(
            'adoptionCasesAsProspectiveParent',
            function ($query) {
                $query->whereNotIn(
                    'status',
                    ['finalized', 'closed', 'cancelled']
                );
            }
        )
        ->orderBy('name')
        ->get();

    if ($children->isEmpty()) {
        return back()->with(
            'error',
            'No eligible children are available for matching.'
        );
    }

    if ($parents->isEmpty()) {
        return back()->with(
            'error',
            'No active prospective parents with matching profiles are available.'
        );
    }

    /*
     * Only the Gale-Shapley computation happens during the web request.
     * OpenAI is not called here.
     */
    $matchingOutput = $matcher->match($children, $parents);

    if (empty($matchingOutput['matches'])) {
        return back()->with(
            'error',
            'The matching process completed, but no compatible recommendations were found.'
        );
    }

    [$run, $resultIds] = DB::transaction(function () use (
        $matchingOutput
    ) {
        $run = AdoptionMatchingRun::create([
            'run_code' => $this->generateRunCode(),
            'status' => 'completed',
            'generated_by' => Auth::id(),
            'generated_at' => now(),
        ]);

        $resultIds = [];

        foreach ($matchingOutput['matches'] as $match) {
            $explanation = $match['explanation'] ?? [];

            /*
             * The actual AI summary will be generated by a queue worker.
             */
            $explanation['ai_summary'] = null;
            $explanation['ai_status'] = 'queued';

            $matchingResult = AdoptionMatchingResult::create([
                'adoption_matching_run_id' => $run->id,
                'child_id' => $match['child_id'],
                'prospective_parent_id' =>
                    $match['prospective_parent_id'],
                'child_score' => $match['child_score'],
                'parent_score' => $match['parent_score'],
                'rank_for_child' => $match['rank_for_child'],
                'rank_for_parent' => $match['rank_for_parent'],
                'status' => 'recommended',
                'explanation' => $explanation,
                'created_by' => Auth::id(),
            ]);

            $resultIds[] = $matchingResult->id;
        }

        return [$run, $resultIds];
    });

    /*
     * Dispatch only after the database transaction has committed.
     */
    foreach ($resultIds as $resultId) {
        GenerateMatchAiExplanation::dispatch($resultId)
            ->onQueue('matching-ai');
    }

    return redirect()
        ->route('admin.matching.show', $run)
        ->with(
            'success',
            'Matching recommendations were generated. AI explanations are being prepared in the background.'
        );
}



    public function show(AdoptionMatchingRun $matching): View
    {
        $this->authorizeAdmin();

        $matching->load([
            'generator',
            'results' => function ($query) {
                $query->with([
                        'child',
                        'prospectiveParent.matchingProfile',
                    ])
                    ->orderByRaw('(child_score + parent_score) DESC')
                    ->orderBy('rank_for_child')
                    ->orderBy('rank_for_parent');
            },
        ]);

        return view('admin.matching.show', [
            'run' => $matching,
        ]);
    }

    public function createCase(
        AdoptionMatchingResult $result,
        ExternalReviewerAccessService $reviewerAccessService
    ): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($result->status !== 'recommended') {
            return back()->with('error', 'Only recommended matches can be converted into adoption cases.');
        }

        DB::transaction(function () use ($result, $reviewerAccessService) {
            $case = AdoptionCase::create([
                'case_code' => $this->generateCaseCode(),
                'child_id' => $result->child_id,
                'prospective_parent_id' => $result->prospective_parent_id,
                'case_type' => 'domestic_adoption',
                'status' => 'matching_review',
                'priority' => 'normal',
                'opened_at' => now()->toDateString(),
                'summary' => 'Created from stable matching recommendation. Final review and approval are still required.',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach (AdoptionCase::DEFAULT_DOCUMENTS as $document) {
                $case->documents()->create([
                    'document_name' => $document['document_name'],
                    'document_type' => $document['document_type'],
                    'requirement_scope' => $document['requirement_scope'],
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $case->notes()->create([
                'note_type' => 'matching_review',
                'visibility' => 'internal',
                'title' => 'Matching Recommendation Converted',
                'body' => 'This adoption case was created from a stable matching recommendation. Final review and approval must still be completed by authorized staff.',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $reviewerAccessService->authorizeCaseForActiveReviewers(
                $case,
                Auth::id()
            );

            $result->update([
                'status' => 'converted_to_case',
            ]);

            $result->child->update([
                'case_status' => 'under_matching',
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('admin.adoption-cases.index')
            ->with('success', 'Matching recommendation converted into an adoption case.');
    }

    private function generateRunCode(): string
    {
        $nextId = (AdoptionMatchingRun::max('id') ?? 0) + 1;

        return 'MR-' . now()->format('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    private function generateCaseCode(): string
    {
        $nextId = (AdoptionCase::withTrashed()->max('id') ?? 0) + 1;

        return 'AC-' . now()->format('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
