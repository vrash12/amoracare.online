<?php

// app/Http/Controllers/Parent/ParentAiGuidanceController.php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ParentAiGuidanceController extends Controller
{
    private const CHAT_SESSION_KEY_PREFIX = 'parent_ai_messages_user_';

    private const LEGACY_CHAT_SESSION_KEY = 'parent_ai_messages';

    public function index(): View
    {
        $userId = (int) Auth::id();

        $adoptionCase = AdoptionCase::query()
            ->where('prospective_parent_id', $userId)
            ->latest()
            ->first();

        $chatMessages = session()->get($this->chatSessionKey($userId), []);

        // This key was shared by every account before histories were user-scoped.
        // Never render it, and discard it as soon as a parent opens the assistant.
        session()->forget(self::LEGACY_CHAT_SESSION_KEY);

        return view('parent.ai.index', [
            'hasAdoptionCase' => $adoptionCase !== null,
            'faqCategories' => $this->faqCategories(),
            'frequentlyAskedQuestions' => $this->frequentlyAskedQuestions(),
            'chatMessages' => is_array($chatMessages)
                ? array_slice($chatMessages, -12)
                : [],
            'defaultGreeting' => $adoptionCase
                ? 'Welcome back! Select a topic and question for a quick answer, or type your own question. If the FAQ cannot help, the AI assistant will take over.'
                : 'Hello! Select a topic and question for a quick answer, or type your own question. If the FAQ cannot help, the AI assistant will take over.',
        ]);
    }

    public function faq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'faq_id' => ['required', 'string', 'max:80'],
        ]);

        $faq = collect($this->frequentlyAskedQuestions())
            ->firstWhere('id', $validated['faq_id']);

        if (! $faq) {
            return response()->json([
                'message' => 'The selected frequently asked question is unavailable.',
            ], 422);
        }

        return $this->approvedFaqResponse($faq, $faq['question']);
    }

    public function chat(Request $request): JsonResponse
    {
        // Older installations already expose /chat but may cache routes without
        // /faq. Serve button selections here too, without contacting AI.
        if ($request->has('faq_id')) {
            return $this->faq($request);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'use_ai' => ['sometimes', 'boolean'],
        ]);

        $userMessage = $validated['message'];
        $faq = ($validated['use_ai'] ?? false) ? null : $this->matchFrequentlyAskedQuestion($userMessage);

        if ($faq) {
            return $this->approvedFaqResponse($faq, $userMessage);
        }

        $legalGuidanceUrl = config('services.legal_guidance.url');

        $legalGuidanceApiKey = config('services.legal_guidance.api_key');

        if (! $legalGuidanceUrl || ! $legalGuidanceApiKey) {
            return $this->aiUnavailable('missing_configuration');
        }

        $user = Auth::user()->loadMissing([
            'role',
            'matchingProfile',
        ]);

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

        $chatSessionKey = $this->chatSessionKey((int) $user->getKey());
        $history = session()->get($chatSessionKey, []);

        if (! is_array($history)) {
            $history = [];
        }

        try {
            $response = Http::timeout(90)
                ->connectTimeout(10)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Legal-Guidance-Key' => $legalGuidanceApiKey,
                ])
                ->post($legalGuidanceUrl, [
                    'question' => $userMessage,
                    'parent_context' => $parentContext,
                    'conversation_history' => $this->aiConversationHistory($history),
                ]);

            if ($response->failed()) {
                return $this->aiUnavailable('upstream_failure', $response->status());
            }

            $aiReply = $response->json('answer');
            if (! is_string($aiReply) || trim($aiReply) === '') {
                return $this->aiUnavailable('invalid_response', $response->status());
            }
            $sources = $response->json('sources') ?? [];
            $disclaimer = $response->json('disclaimer');

            $history[] = [
                'role' => 'user',
                'content' => $userMessage,
                'origin' => 'ai_guidance',
            ];

            $history[] = [
                'role' => 'assistant',
                'content' => $aiReply,
                'sources' => $sources,
                'disclaimer' => $disclaimer,
                'origin' => 'ai_guidance',
            ];

            session()->put($chatSessionKey, array_slice($history, -12));

            return response()->json([
                'reply' => $aiReply,
                'sources' => $sources,
                'disclaimer' => $disclaimer,
                'answer_type' => 'ai_guidance',
            ]);
        } catch (\Throwable $e) {
            return $this->aiUnavailable('connection_or_response_error');
        }
    }

    private function aiUnavailable(string $reason, ?int $status = null): JsonResponse
    {
        // Log operational metadata only, never keys, questions or parent records.
        Log::warning('Parent AI guidance unavailable', [
            'reason' => $reason,
            'upstream_status' => $status,
        ]);

        return response()->json([
            'code' => 'ai_unavailable',
            'message' => 'AI guidance is temporarily unavailable. You can still click the FAQ questions for saved answers without AI. For help with your specific application, contact AmoraCare staff or your assigned social worker.',
        ], 503);
    }

    public function clear(): JsonResponse
    {
        session()->forget($this->chatSessionKey((int) Auth::id()));
        session()->forget(self::LEGACY_CHAT_SESSION_KEY);

        return response()->json([
            'message' => 'Chat cleared.',
        ]);
    }

    private function chatSessionKey(int $userId): string
    {
        return self::CHAT_SESSION_KEY_PREFIX.$userId;
    }

    private function approvedFaqResponse(array $faq, string $userMessage): JsonResponse
    {
        $chatSessionKey = $this->chatSessionKey((int) Auth::id());
        $history = session()->get($chatSessionKey, []);

        if (! is_array($history)) {
            $history = [];
        }

        $sources = $faq['sources'] ?? [[
            'title' => 'AmoraCare FAQ',
            'source' => 'AmoraCare FAQ',
        ]];
        $disclaimer = 'Predefined FAQ answer. Select Need more help? Ask AI if this does not answer your question.';

        $history[] = [
            'role' => 'user',
            'content' => $userMessage,
            'origin' => 'approved_faq',
        ];

        $history[] = [
            'role' => 'assistant',
            'content' => $faq['answer'],
            'sources' => $sources,
            'disclaimer' => $disclaimer,
            'origin' => 'approved_faq',
            'question' => $userMessage,
        ];

        session()->put($chatSessionKey, array_slice($history, -12));

        return response()->json([
            'question' => $userMessage,
            'reply' => $faq['answer'],
            'sources' => $sources,
            'disclaimer' => $disclaimer,
            'answer_type' => 'approved_faq',
            'matched_faq_id' => $faq['id'],
        ]);
    }

    private function matchFrequentlyAskedQuestion(string $message): ?array
    {
        $normalizedMessage = $this->normalizeFaqText($message);

        if ($normalizedMessage === '') {
            return null;
        }

        // A configured menu question is safe to answer verbatim, including
        // general FAQs about rejected documents or inactive accounts. Keep the
        // broader routing below conservative for case-specific follow-ups.
        foreach ($this->frequentlyAskedQuestions() as $faq) {
            foreach (array_merge([$faq['question']], $faq['exact_questions'] ?? []) as $question) {
                if ($normalizedMessage === $this->normalizeFaqText($question)) {
                    return $faq;
                }
            }
        }

        $globalAiPriorityPhrases = [
            'appeal',
            'denied',
            'denial',
            'rejected',
            'error',
            'failed',
            'not working',
            'why was my',
            'why is my',
            'based on my',
            'which of my',
            'latest update',
        ];

        if (collect($globalAiPriorityPhrases)->contains(
            fn (string $phrase): bool => $this->containsNormalizedPhrase($normalizedMessage, $phrase)
        )) {
            return null;
        }

        $matches = [];

        foreach ($this->frequentlyAskedQuestions() as $faq) {
            $shouldDeferToAi = collect($faq['ai_priority_phrases'] ?? [])
                ->contains(fn (string $phrase): bool => $this->containsNormalizedPhrase(
                    $normalizedMessage,
                    $phrase
                ));

            if ($shouldDeferToAi) {
                continue;
            }

            $exactPhrases = array_merge(
                [$faq['title'], $faq['question']],
                $faq['match_phrases'] ?? []
            );

            foreach ($exactPhrases as $phrase) {
                $normalizedPhrase = $this->normalizeFaqText($phrase);

                if ($normalizedMessage === $normalizedPhrase) {
                    return $faq;
                }
            }

            $matchesApprovedPhrase = collect($faq['match_phrases'] ?? [])
                ->contains(fn (string $phrase): bool => mb_strlen($this->normalizeFaqText($phrase)) >= 12
                    && $this->containsNormalizedPhrase($normalizedMessage, $phrase));

            $matchesRequiredTerms = collect($faq['match_terms'] ?? [])
                ->contains(fn (array $requiredTerms): bool => collect($requiredTerms)
                    ->every(fn (string $term): bool => $this->containsNormalizedPhrase(
                        $normalizedMessage,
                        $term
                    )));

            if ($matchesApprovedPhrase || $matchesRequiredTerms) {
                $matches[$faq['id']] = $faq;
            }
        }

        return count($matches) === 1 ? array_values($matches)[0] : null;
    }

    private function aiConversationHistory(array $history): array
    {
        return collect($history)
            ->filter(fn ($message): bool => is_array($message)
                && ($message['origin'] ?? 'ai_guidance') !== 'approved_faq')
            ->values()
            ->slice(-6)
            ->values()
            ->all();
    }

    private function normalizeFaqText(string $value): string
    {
        $normalized = mb_strtolower($value);
        $normalized = preg_replace('/[^\pL\pN]+/u', ' ', $normalized) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? '');
    }

    private function containsNormalizedPhrase(string $normalizedText, string $phrase): bool
    {
        $normalizedPhrase = $this->normalizeFaqText($phrase);

        return $normalizedPhrase !== ''
            && str_contains(" {$normalizedText} ", " {$normalizedPhrase} ");
    }

    private function faqCategories(): array
    {
        return [
            [
                'id' => 'account-access',
                'title' => 'Account & Access',
                'description' => 'Registration, verification, and account status',
                'icon' => 'bi-person-lock',
                'class' => 'is-status',
            ],
            [
                'id' => 'application-documents',
                'title' => 'Application & Documents',
                'description' => 'Requirements, uploads, and file replacement',
                'icon' => 'bi-folder-check',
                'class' => 'is-documents',
            ],
            [
                'id' => 'process-support',
                'title' => 'Process & Support',
                'description' => 'Home study, timeline, and official roles',
                'icon' => 'bi-signpost-2',
                'class' => 'is-process',
            ],
            [
                'id' => 'privacy-matching',
                'title' => 'Privacy & Matching',
                'description' => 'Protected records and matching decisions',
                'icon' => 'bi-shield-lock',
                'class' => 'is-privacy',
            ],
        ];
    }

    private function frequentlyAskedQuestions(): array
    {
        return [
            [
                'id' => 'create-account',
                'category' => 'account-access',
                'title' => 'Create an Account',
                'description' => 'Use the QR form and verify your email',
                'question' => 'How do I create a prospective adoptive parent account?',
                'answer' => 'Scan the prospective-parent application QR code provided by AmoraCare staff, or open the public application form. Complete the form, create a password, accept the Terms and Conditions and Privacy Notice, then enter the verification code sent to your email. Your account is created only after successful email verification and remains Pending until authorized staff review and activate it.',
                'icon' => 'bi-qr-code-scan',
                'class' => 'is-status',
                'match_phrases' => ['register an account', 'sign up for amoracare', 'scan the application qr code'],
                'match_terms' => [['create', 'account'], ['register', 'account'], ['sign up'], ['signup']],
            ],
            [
                'id' => 'email-verification',
                'category' => 'account-access',
                'title' => 'Email Verification',
                'description' => 'Use or resend your one-time code',
                'question' => 'What should I do if I do not receive the verification code?',
                'answer' => 'Check your registered email address and Spam or Junk folder. Wait until the resend option becomes available, then select Resend code. Use the latest code before it expires and never share it with anyone. AmoraCare sends a verification code during registration and login.',
                'icon' => 'bi-envelope-check',
                'class' => 'is-status',
                'match_phrases' => ['otp not received', 'verification code not received', 'resend verification code', 'email code'],
                'match_terms' => [['verification', 'code'], ['email', 'code'], ['otp'], ['resend', 'code']],
            ],
            [
                'id' => 'account-status',
                'category' => 'account-access',
                'title' => 'Account Status',
                'description' => 'Understand Pending, Active, or Inactive',
                'question' => 'Why is my account inactive?',
                'answer' => 'An account may become inactive after 60 days without use or when authorized staff deactivate it. Contact AmoraCare staff to request reactivation. Pending means your verified application is still waiting for staff review; Active means staff have approved your account for sign-in.',
                'icon' => 'bi-signpost-split',
                'class' => 'is-status',
                'match_phrases' => ['my account is pending', 'my account is inactive', 'activate my account'],
                'match_terms' => [['account', 'status'], ['pending', 'account'], ['inactive', 'account'], ['active', 'account']],
                'ai_priority_phrases' => ['what is my account status', 'why is my account'],
            ],
            [
                'id' => 'required-documents',
                'category' => 'application-documents',
                'title' => 'Required Documents',
                'description' => 'Find your official checklist',
                'question' => 'Where can I see the documents required for my application?',
                'answer' => 'After your account is activated, open My Documents to view the requirements assigned to your application and the status of each file. Requirements may vary by case, so use the checklist shown in your account and follow any instructions from your assigned social worker or authorized AmoraCare staff.',
                'icon' => 'bi-file-earmark-excel',
                'class' => 'is-documents',
                'match_phrases' => ['document checklist', 'application requirements', 'documents do i need'],
                'match_terms' => [['required', 'document'], ['document', 'requirement'], ['document', 'checklist']],
            ],
            [
                'id' => 'replace-document',
                'category' => 'application-documents',
                'title' => 'Replace a Document',
                'description' => 'Upload a corrected or updated file',
                'question' => 'Can I replace a document after submitting it?',
                'answer' => 'Open My Documents and select Replace for the requirement you want to update. The replacement becomes the current submission and must be reviewed again. Replacement is locked for requirements marked Not Required, cases approved by RACCO, and finalized, closed, or cancelled cases. Contact authorized AmoraCare staff if a correction is still necessary.',
                'icon' => 'bi-arrow-repeat',
                'class' => 'is-requirements',
                'match_phrases' => ['replace my submitted file', 'change an uploaded document', 'upload a corrected document'],
                'match_terms' => [['replace', 'document'], ['replace', 'file'], ['change', 'uploaded'], ['correct', 'document']],
            ],
            [
                'id' => 'rejected-document',
                'category' => 'application-documents',
                'title' => 'Rejected Document',
                'description' => 'Read review remarks and correct your file',
                'question' => 'What should I do if my document is rejected?',
                'answer' => 'Read the reviewer\'s remarks, correct the identified problem, and upload the replacement through My Documents. Contact AmoraCare staff if you need clarification about the requested correction or if replacement is unavailable.',
                'icon' => 'bi-file-earmark-check',
                'class' => 'is-documents',
                'match_phrases' => [],
                'match_terms' => [],
            ],
            [
                'id' => 'application-status',
                'category' => 'application-documents',
                'title' => 'Application Status',
                'description' => 'Find progress and parent-visible updates',
                'question' => 'Where can I check my application status?',
                'answer' => 'Open My Application or your parent dashboard to see your current parent-visible stage, document progress, timeline, and authorized updates. If a status or instruction is unclear, contact your assigned social worker or AmoraCare staff for confirmation about your specific case.',
                'icon' => 'bi-clipboard-data',
                'class' => 'is-documents',
                'match_phrases' => ['check my application progress', 'track my application', 'where to see application status'],
                'match_terms' => [['check', 'application', 'status'], ['track', 'application'], ['where', 'application', 'status']],
                'ai_priority_phrases' => ['what is my application status', 'my current application status', 'what is my current stage', 'explain my application status', 'what should i do next'],
            ],
            [
                'id' => 'domestic-requirements',
                'category' => 'application-documents',
                'title' => 'Domestic Adoption Requirements',
                'description' => 'An overview of common documentary requirements',
                'question' => 'What are the common requirements for domestic adoption?',
                'answer' => 'For regular domestic adoption, common documents include case-study reports, PSA birth records, marriage or civil-status documents, clearances, medical and psychological assessments, financial-capacity evidence, character references, photos, and required consents. A Pre-Adoption Forum attendance certificate and child-specific records may also be needed. This is an overview, not a complete checklist: requirements depend on the type and circumstances of the adoption. Check My Documents and confirm the current list with your social worker or RACCO.',
                'sources' => [['title' => 'NACC regular adoption requirements', 'source' => 'National Authority for Child Care', 'url' => 'https://www.nacc.gov.ph/domestic-petition-regular-adoption/']],
                'icon' => 'bi-file-earmark-check',
                'class' => 'is-documents',
                'exact_questions' => ['What documents are required for domestic adoption?', 'What are the requirements for domestic adoption?'],
                'match_phrases' => [],
                'match_terms' => [],
            ],
            [
                'id' => 'home-study',
                'category' => 'process-support',
                'title' => 'Home Study',
                'description' => 'Understand this assessment stage',
                'question' => 'What is a Home Study Report and why is it required?',
                'answer' => 'A Home Study Report is an assessment prepared by an adoption social worker. It considers the prospective parents\' motivation, family circumstances, home environment, and capacity to meet a child\'s needs, supported by documents. It helps the responsible authorities assess the proposed adoption and protect the child\'s welfare. Coordinate with your assigned social worker for the applicable assessment; AmoraCare does not prepare or approve the official report.',
                'sources' => [['title' => 'Implementing rules of Republic Act No. 11642', 'source' => 'Supreme Court E-Library', 'url' => 'https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/2/96120']],
                'exact_questions' => ['What is the home study stage?', 'What is a Home Study Report?', 'Why is a Home Study Report required?'],
                'icon' => 'bi-house-check',
                'class' => 'is-home-study',
                'match_phrases' => ['home study report', 'what happens during home study', 'what is home study', 'explain home study'],
                'match_terms' => [],
                'ai_priority_phrases' => ['my home study status', 'status of my home study', 'has my home study', 'when is my home study'],
            ],
            [
                'id' => 'process-timeline',
                'category' => 'process-support',
                'title' => 'Expected Timeline',
                'description' => 'Learn why completion times vary',
                'question' => 'When will my application be completed?',
                'answer' => 'There is no guaranteed completion date. Timing depends on the completeness and verification of documents, required assessments, case circumstances, matching and placement processes, and decisions by the responsible authorities. Check My Application for your current parent-visible stage and contact your social worker for case-specific guidance.',
                'icon' => 'bi-clock-history',
                'class' => 'is-timeline',
                'match_phrases' => ['adoption process timeline', 'how long will adoption take'],
                'match_terms' => [['how long', 'adoption'], ['adoption', 'timeline']],
            ],
            [
                'id' => 'matching-privacy',
                'category' => 'privacy-matching',
                'title' => 'Privacy and Matching',
                'description' => 'Understand protected case information',
                'question' => 'Can I browse child profiles or matching results?',
                'exact_questions' => ['Why can I not browse child profiles or matching rankings?'],
                'answer' => 'Child profiles, confidential case notes, and matching rankings contain protected information and are available only to authorized personnel. The matching feature provides recommendations for professional review; prospective parents cannot browse protected child records, and the system does not make a final placement or adoption decision.',
                'icon' => 'bi-shield-lock',
                'class' => 'is-privacy',
                'match_phrases' => ['browse child profiles', 'view matching rankings', 'why are child profiles restricted'],
                'match_terms' => [['child', 'profile', 'browse'], ['matching', 'ranking'], ['child', 'profile', 'restricted']],
            ],
            [
                'id' => 'record-privacy',
                'category' => 'privacy-matching',
                'title' => 'Who Can View My Records?',
                'description' => 'Understand role-based record access',
                'question' => 'Who can view my application records?',
                'answer' => 'Only authenticated users with the appropriate role and authorized case access may view relevant records. Prospective parents see only their own parent-visible information. Staff and authorized external reviewers receive access according to their assigned responsibilities. Child profiles, donor records, confidential notes, and other applicants’ records are not available through the parent portal.',
                'icon' => 'bi-person-check',
                'class' => 'is-privacy',
                'match_phrases' => ['who can see my records', 'who can access my information', 'is my application private'],
                'match_terms' => [['who', 'view', 'record'], ['who', 'access', 'information'], ['application', 'private'], ['personal', 'information', 'access']],
            ],
            [
                'id' => 'official-decisions',
                'category' => 'process-support',
                'title' => 'Official Decisions',
                'description' => 'Know what AmoraCare can and cannot decide',
                'question' => 'Does AmoraCare approve an adoption?',
                'answer' => 'No. AmoraCare helps manage records, documents, and application updates. A status shown in the system does not itself constitute a legal adoption approval. Official decisions remain with the authorized authorities.',
                'icon' => 'bi-buildings',
                'class' => 'is-support',
                'match_phrases' => ['who approves an adoption', 'final adoption decision', 'does the system approve adoption'],
                'match_terms' => [['amoracare', 'approve'], ['who', 'approve', 'adoption'], ['system', 'approve']],
            ],
            [
                'id' => 'more-help',
                'category' => 'privacy-matching',
                'title' => 'Need More Help?',
                'description' => 'Use AI guidance only when you need it',
                'question' => 'What if these FAQs do not answer my question?',
                'answer' => 'Select Need more help? Ask AI or type your question. The assistant will provide guidance within its available information and access permissions. For official decisions or unresolved account problems, contact AmoraCare staff.',
                'icon' => 'bi-chat-dots',
                'class' => 'is-support',
                'match_phrases' => ['how do i ask the ai', 'how to ask the ai'],
                'match_terms' => [],
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
