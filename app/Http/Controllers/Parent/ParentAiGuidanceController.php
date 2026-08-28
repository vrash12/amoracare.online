<?php

// app/Http/Controllers/Parent/ParentAiGuidanceController.php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ParentAiGuidanceController extends Controller
{
    public function index(): View
    {
        $adoptionCase = AdoptionCase::query()
            ->where('prospective_parent_id', Auth::id())
            ->latest()
            ->first();

        return view('parent.ai.index', [
            'hasAdoptionCase' => $adoptionCase !== null,
            'initialChatRequests' => $this->initialChatRequests($adoptionCase),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $legalGuidanceUrl = config('services.legal_guidance.url');

        if (! $legalGuidanceUrl) {
            return response()->json([
                'message' => 'The AI legal guidance URL is not configured.',
                'details' => 'Please set LEGAL_GUIDANCE_URL in your .env file.',
            ], 500);
        }

        $user = Auth::user()->loadMissing([
            'role',
            'matchingProfile',
        ]);

        $userMessage = $validated['message'];

        $adoptionCase = AdoptionCase::with([
            'prospectiveParent.matchingProfile',
            'assignedSocialWorker',
            'documents' => function ($query) {
                $query->where('requirement_scope', 'parent')
                    ->orderBy('document_name');
            },
            'notes' => function ($query) {
                $query->where('visibility', 'parent_update')
                    ->latest()
                    ->limit(5);
            },
        ])
            ->where('prospective_parent_id', $user->id)
            ->latest()
            ->first();

        $parentContext = $this->buildParentContext($user, $adoptionCase);

        $history = session()->get('parent_ai_messages', []);

        try {
            $legalGuidanceApiKey = config('services.legal_guidance.api_key');

            if (! $legalGuidanceApiKey) {
                return response()->json([
                    'message' => 'The AI legal guidance API key is not configured.',
                    'details' => 'Please set LEGAL_GUIDANCE_INTERNAL_API_KEY in the Laravel .env file.',
                ], 500);
            }

            $response = Http::timeout(180)
                ->connectTimeout(10)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Legal-Guidance-Key' => $legalGuidanceApiKey,
                ])
                ->post($legalGuidanceUrl, [
                    'question' => $userMessage,
                    'parent_context' => $parentContext,
                    'conversation_history' => array_slice($history, -6),
                ]);

            if ($response->failed()) {
                return response()->json([
                    'message' => 'The AI legal guidance service could not process the request.',
                    'details' => $response->json('message')
                        ?? $response->json('error')
                        ?? $response->body(),

                    // This helps you debug locally.
                    // It will not show in production.
                    'sent_parent_context' => app()->environment('local') ? $parentContext : null,
                ], 500);
            }

            $aiReply = $response->json('answer') ?? 'No response received.';
            $sources = $response->json('sources') ?? [];
            $disclaimer = $response->json('disclaimer');

            $history[] = [
                'role' => 'user',
                'content' => $userMessage,
            ];

            $history[] = [
                'role' => 'assistant',
                'content' => $aiReply,
                'sources' => $sources,
                'disclaimer' => $disclaimer,
            ];

            session()->put('parent_ai_messages', array_slice($history, -12));

            return response()->json([
                'reply' => $aiReply,
                'sources' => $sources,
                'disclaimer' => $disclaimer,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to connect to the AI legal guidance service.',
                'details' => $e->getMessage(),

                // This helps you debug locally.
                // It will not show in production.
                'sent_parent_context' => app()->environment('local') ? $parentContext : null,
            ], 500);
        }
    }

    public function clear(): JsonResponse
    {
        session()->forget('parent_ai_messages');

        return response()->json([
            'message' => 'Chat cleared.',
        ]);
    }

    private function initialChatRequests(?AdoptionCase $adoptionCase): array
    {
        if (! $adoptionCase) {
            return [
                [
                    'title' => 'Begin an Application',
                    'description' => 'Learn how the adoption process starts',
                    'question' => 'How do I begin an adoption application, and what should I prepare first?',
                    'icon' => 'bi-play-circle',
                    'class' => 'is-status',
                ],
                [
                    'title' => 'Initial Documents',
                    'description' => 'Review common starting requirements',
                    'question' => 'What documents should a prospective adoptive parent prepare before starting an application?',
                    'icon' => 'bi-file-earmark-check',
                    'class' => 'is-documents',
                ],
                [
                    'title' => 'Basic Qualifications',
                    'description' => 'Understand general eligibility rules',
                    'question' => 'What are the basic qualifications for prospective adoptive parents in the Philippines?',
                    'icon' => 'bi-person-check',
                    'class' => 'is-requirements',
                ],
                [
                    'title' => 'Home Study',
                    'description' => 'Learn its purpose and process',
                    'question' => 'What is a Home Study Report, and what happens during the home study process?',
                    'icon' => 'bi-house-check',
                    'class' => 'is-home-study',
                ],
                [
                    'title' => 'Pre-Adoption Forum',
                    'description' => 'Know what to expect and prepare',
                    'question' => 'What is the purpose of the pre-adoption forum, and how should I prepare for it?',
                    'icon' => 'bi-people',
                    'class' => 'is-forum',
                ],
                [
                    'title' => 'Process Timeline',
                    'description' => 'Understand the usual adoption stages',
                    'question' => 'What are the usual stages of the adoption process, and what may affect the timeline?',
                    'icon' => 'bi-clock-history',
                    'class' => 'is-timeline',
                ],
                [
                    'title' => 'Responsible Agencies',
                    'description' => 'Understand the roles of NACC and RACCO',
                    'question' => 'What are the roles of NACC, RACCO, DSWD, and the social worker in the adoption process?',
                    'icon' => 'bi-buildings',
                    'class' => 'is-support',
                ],
                [
                    'title' => 'Privacy and Matching',
                    'description' => 'Learn why protected records are restricted',
                    'question' => 'Why can prospective adoptive parents not browse child profiles or matching rankings?',
                    'icon' => 'bi-shield-lock',
                    'class' => 'is-privacy',
                ],
            ];
        }

        $statusLabel = $adoptionCase->status_label;

        return [
            [
                'title' => 'My Current Status',
                'description' => "Understand the {$statusLabel} stage",
                'question' => "My application status is {$statusLabel}. What does this mean?",
                'icon' => 'bi-signpost-split',
                'class' => 'is-status',
            ],
            [
                'title' => 'My Next Steps',
                'description' => 'Know what you should do next',
                'question' => 'Based on my current application, what should I do next?',
                'icon' => 'bi-arrow-right-circle',
                'class' => 'is-process',
            ],
            [
                'title' => 'Documents Needing Action',
                'description' => 'Check pending or returned requirements',
                'question' => 'Which of my documents are pending, rejected, expired, or still need my attention?',
                'icon' => 'bi-file-earmark-excel',
                'class' => 'is-documents',
            ],
            [
                'title' => 'Document Progress',
                'description' => 'Review submitted and verified records',
                'question' => 'Can you summarize the progress of my submitted and verified documents?',
                'icon' => 'bi-clipboard2-check',
                'class' => 'is-requirements',
            ],
            [
                'title' => 'Home Study Status',
                'description' => 'Understand home-study requirements',
                'question' => 'What should I know about the home study stage of my application?',
                'icon' => 'bi-house-check',
                'class' => 'is-home-study',
            ],
            [
                'title' => 'Recent Case Updates',
                'description' => 'Explain parent-visible staff updates',
                'question' => 'Please explain the latest parent-visible updates on my adoption case.',
                'icon' => 'bi-chat-left-text',
                'class' => 'is-forum',
            ],
            [
                'title' => 'Expected Timeline',
                'description' => 'Learn what may happen after this stage',
                'question' => 'What usually happens after my current stage, and what may affect the timeline?',
                'icon' => 'bi-clock-history',
                'class' => 'is-timeline',
            ],
            [
                'title' => 'Privacy and Matching',
                'description' => 'Understand protected case information',
                'question' => 'What application and matching information can I access, and what information must remain confidential?',
                'icon' => 'bi-shield-lock',
                'class' => 'is-privacy',
            ],
        ];
    }

    private function buildParentContext($user, ?AdoptionCase $adoptionCase): array
    {
        $user->loadMissing([
            'role',
            'matchingProfile',
        ]);

        $profile = $user->matchingProfile;

        $parentInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'account_status' => $user->status,
            'role' => $user->role?->name ?? 'Prospective Parent',
            'email_verified' => $user->email_verified_at ? true : false,
            'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
        ];

        $matchingProfile = [
            'preferred_child_sex' => $profile?->preferred_child_sex,
            'min_child_age' => $profile?->min_child_age,
            'max_child_age' => $profile?->max_child_age,
            'open_to_special_needs' => $profile ? (bool) $profile->open_to_special_needs : null,
            'home_study_verified' => $profile ? (bool) $profile->home_study_verified : null,
            'financial_capacity_score' => $profile?->financial_capacity_score,
            'housing_score' => $profile?->housing_score,
            'parenting_capacity_score' => $profile?->parenting_capacity_score,
            'matching_notes' => $profile?->matching_notes,
        ];

        if (! $adoptionCase) {
            return [
                'has_application' => false,
                'parent_name' => $user->name,

                'parent' => $parentInfo,

                'matching_profile' => $matchingProfile,

                'message' => 'This parent does not currently have an adoption application record in the system.',

                'privacy_rules' => [
                    'Do not reveal child profiles.',
                    'Do not reveal confidential case notes.',
                    'Do not reveal donor data.',
                    'Do not provide child matching recommendations.',
                    'Do not reveal matching rankings.',
                    'Do not reveal other parent records.',
                    'Only guide the parent about their own account, matching profile, application status, documents, and next steps.',
                ],
            ];
        }

        $documents = $adoptionCase->documents->map(function ($document) {
            return [
                'id' => $document->id,
                'document_name' => $document->document_name,
                'document_type' => $document->document_type,
                'requirement_scope' => $document->requirement_scope,
                'status' => $document->status,
                'status_label' => $document->status_label,
                'expiry_date' => $document->expiry_date?->format('Y-m-d'),
                'remarks' => $document->remarks,
            ];
        })->values()->toArray();

        $updates = $adoptionCase->notes->map(function ($note) {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'note_type' => $note->note_type,
                'body' => $note->body,
                'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
            ];
        })->values()->toArray();

        $requiredDocumentsCount = count($documents);

        $submittedDocumentsCount = collect($documents)
            ->whereIn('status', ['submitted', 'under_review', 'verified'])
            ->count();

        $verifiedDocumentsCount = collect($documents)
            ->where('status', 'verified')
            ->count();

        $pendingDocumentsCount = collect($documents)
            ->where('status', 'pending')
            ->count();

        $rejectedDocumentsCount = collect($documents)
            ->where('status', 'rejected')
            ->count();

        $expiredDocumentsCount = collect($documents)
            ->where('status', 'expired')
            ->count();

        $documentProgressPercent = $requiredDocumentsCount > 0
            ? round(($verifiedDocumentsCount / $requiredDocumentsCount) * 100)
            : 0;

        return [
            'has_application' => true,
            'parent_name' => $user->name,

            'parent' => $parentInfo,

            'matching_profile' => $matchingProfile,

            'case' => [
                'id' => $adoptionCase->id,
                'case_code' => $adoptionCase->case_code,
                'case_type' => $adoptionCase->case_type,
                'case_type_label' => $adoptionCase->case_type_label,
                'status' => $adoptionCase->status,
                'status_label' => $adoptionCase->status_label,
                'priority' => $adoptionCase->priority,
                'priority_label' => $adoptionCase->priority_label,
                'opened_at' => $adoptionCase->opened_at?->format('Y-m-d'),
                'target_completion_date' => $adoptionCase->target_completion_date?->format('Y-m-d'),
                'assigned_social_worker' => $adoptionCase->assignedSocialWorker?->name,
            ],

            'document_progress' => [
                'required_documents_count' => $requiredDocumentsCount,
                'submitted_documents_count' => $submittedDocumentsCount,
                'verified_documents_count' => $verifiedDocumentsCount,
                'pending_documents_count' => $pendingDocumentsCount,
                'rejected_documents_count' => $rejectedDocumentsCount,
                'expired_documents_count' => $expiredDocumentsCount,
                'progress_percent' => $documentProgressPercent,
            ],

            'parent_documents' => $documents,

            'parent_visible_updates' => $updates,

            'privacy_rules' => [
                'Do not reveal child profiles.',
                'Do not reveal confidential case notes.',
                'Do not reveal donor data.',
                'Do not provide child matching recommendations.',
                'Do not reveal matching rankings.',
                'Do not reveal other parent records.',
                'Only use this context to guide the parent about their own application status, parent profile, parent documents, parent-visible updates, and next steps.',
            ],
        ];
    }
}
