<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentAiGuidancePrivacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.legal_guidance.url' => 'https://legal-guidance.test/api/ask',
            'services.legal_guidance.api_key' => 'test-internal-key',
        ]);

        Schema::dropIfExists('adoption_cases');
        Schema::dropIfExists('parent_matching_profiles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('status')->default(User::STATUS_PENDING);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parent_matching_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->timestamps();
        });

        Schema::create('adoption_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospective_parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('adoption_cases');
        Schema::dropIfExists('parent_matching_profiles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_each_parent_page_receives_only_that_parents_history(): void
    {
        $firstParent = $this->createParent('first.parent@example.com');
        $secondParent = $this->createParent('second.parent@example.com');
        $newParent = $this->createParent('new.parent@example.com');

        $firstHistory = [
            ['role' => 'user', 'content' => 'FIRST-PARENT-PRIVATE-QUESTION'],
            ['role' => 'assistant', 'content' => 'FIRST-PARENT-PRIVATE-ANSWER'],
        ];
        $secondHistory = [
            ['role' => 'user', 'content' => 'SECOND-PARENT-PRIVATE-QUESTION'],
            ['role' => 'assistant', 'content' => 'SECOND-PARENT-PRIVATE-ANSWER'],
        ];
        $legacyHistory = [
            ['role' => 'user', 'content' => 'LEGACY-SHARED-PRIVATE-QUESTION'],
        ];

        $response = $this->actingAs($firstParent)
            ->withSession([
                $this->historyKey($firstParent) => $firstHistory,
                $this->historyKey($secondParent) => $secondHistory,
                'parent_ai_messages' => $legacyHistory,
            ])
            ->get(route('parent.ai.index'));

        $response->assertOk()
            ->assertViewHas('chatMessages', $firstHistory)
            ->assertSee('FIRST-PARENT-PRIVATE-QUESTION')
            ->assertDontSee('SECOND-PARENT-PRIVATE-QUESTION')
            ->assertDontSee('LEGACY-SHARED-PRIVATE-QUESTION')
            ->assertDontSee('const savedConversation = localStorage.getItem', false)
            ->assertSessionMissing('parent_ai_messages');

        $this->actingAs($secondParent)
            ->get(route('parent.ai.index'))
            ->assertOk()
            ->assertViewHas('chatMessages', $secondHistory)
            ->assertSee('SECOND-PARENT-PRIVATE-QUESTION')
            ->assertDontSee('FIRST-PARENT-PRIVATE-QUESTION');

        $this->actingAs($newParent)
            ->get(route('parent.ai.index'))
            ->assertOk()
            ->assertViewHas('chatMessages', [])
            ->assertDontSee('FIRST-PARENT-PRIVATE-QUESTION')
            ->assertDontSee('SECOND-PARENT-PRIVATE-QUESTION');
    }

    public function test_chat_uses_and_updates_only_the_authenticated_parents_history(): void
    {
        $firstParent = $this->createParent('first.parent@example.com');
        $secondParent = $this->createParent('second.parent@example.com');

        $firstHistory = [
            ['role' => 'user', 'content' => 'My earlier private question'],
            ['role' => 'assistant', 'content' => 'My earlier private answer'],
        ];
        $secondHistory = [
            ['role' => 'user', 'content' => 'Another parent private question'],
        ];
        $sentConversationHistory = null;

        Http::fake(function (ClientRequest $request) use (&$sentConversationHistory) {
            $sentConversationHistory = $request['conversation_history'];

            return Http::response([
                'answer' => 'A private answer for the first parent',
                'sources' => [],
                'disclaimer' => 'Informational guidance only.',
            ]);
        });

        $response = $this->actingAs($firstParent)
            ->withSession([
                $this->historyKey($firstParent) => $firstHistory,
                $this->historyKey($secondParent) => $secondHistory,
            ])
            ->postJson(route('parent.ai.chat'), [
                'message' => 'My new private question',
            ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'A private answer for the first parent');

        $this->assertSame($firstHistory, $sentConversationHistory);
        $this->assertSame(
            $secondHistory,
            $this->app['session.store']->get($this->historyKey($secondParent))
        );

        $updatedFirstHistory = $this->app['session.store']->get($this->historyKey($firstParent));

        $this->assertCount(4, $updatedFirstHistory);
        $this->assertSame('My new private question', $updatedFirstHistory[2]['content']);
        $this->assertSame('A private answer for the first parent', $updatedFirstHistory[3]['content']);

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://legal-guidance.test/api/ask'
            && $request->hasHeader('X-Legal-Guidance-Key', 'test-internal-key')
            && $request['conversation_history'] === $firstHistory
        );
    }

    public function test_approved_faq_answer_does_not_call_the_external_ai_service(): void
    {
        $parent = $this->createParent('faq.parent@example.com');

        Http::fake();

        $response = $this->actingAs($parent)
            ->postJson(route('parent.ai.faq'), [
                'faq_id' => 'create-account',
            ]);

        $response->assertOk()
            ->assertJsonPath('answer_type', 'approved_faq')
            ->assertJsonPath('question', 'How do I create a prospective adoptive parent account?')
            ->assertJsonPath('sources.0.title', 'AmoraCare FAQ');

        Http::assertNothingSent();

        $history = $this->app['session.store']->get($this->historyKey($parent));

        $this->assertCount(2, $history);
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertStringContainsString('verification code', $history[1]['content']);
    }

    public function test_typed_known_question_uses_an_approved_faq_before_ai(): void
    {
        $parent = $this->createParent('typed.faq@example.com');

        config([
            'services.legal_guidance.url' => null,
            'services.legal_guidance.api_key' => null,
        ]);
        Http::fake();

        $response = $this->actingAs($parent)
            ->postJson(route('parent.ai.chat'), [
                'message' => 'Can I replace my submitted document with a corrected file?',
            ]);

        $response->assertOk()
            ->assertJsonPath('answer_type', 'approved_faq')
            ->assertJsonPath('matched_faq_id', 'replace-document')
            ->assertJsonPath('question', 'Can I replace my submitted document with a corrected file?');

        Http::assertNothingSent();

        $history = $this->app['session.store']->get($this->historyKey($parent));

        $this->assertSame('Can I replace my submitted document with a corrected file?', $history[0]['content']);
        $this->assertStringContainsString('select Replace', $history[1]['content']);
    }

    public function test_personal_application_status_question_is_deferred_to_context_aware_ai(): void
    {
        $parent = $this->createParent('personal.status@example.com');

        Http::fake([
            'https://legal-guidance.test/api/ask' => Http::response([
                'answer' => 'This answer uses the authenticated parent context.',
                'sources' => [],
                'disclaimer' => 'Informational guidance only.',
            ]),
        ]);

        $this->actingAs($parent)
            ->postJson(route('parent.ai.faq'), [
                'faq_id' => 'required-documents',
            ])
            ->assertOk()
            ->assertJsonPath('answer_type', 'approved_faq');

        $response = $this->actingAs($parent)
            ->postJson(route('parent.ai.chat'), [
                'message' => 'What is my application status?',
            ]);

        $response->assertOk()
            ->assertJsonPath('answer_type', 'ai_guidance')
            ->assertJsonPath('reply', 'This answer uses the authenticated parent context.');

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://legal-guidance.test/api/ask'
            && $request['question'] === 'What is my application status?'
            && $request['conversation_history'] === []
        );
        Http::assertSentCount(1);
    }

    public function test_unknown_faq_is_rejected_without_calling_the_external_ai_service(): void
    {
        $parent = $this->createParent('unknown.faq@example.com');

        Http::fake();

        $this->actingAs($parent)
            ->postJson(route('parent.ai.faq'), [
                'faq_id' => 'not-an-approved-faq',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The selected frequently asked question is unavailable.');

        Http::assertNothingSent();
        $this->assertNull($this->app['session.store']->get($this->historyKey($parent)));
    }

    public function test_parent_can_request_ai_when_a_predefined_answer_does_not_help(): void
    {
        $parent = $this->createParent('faq.followup@example.com');
        Http::fake([
            'https://legal-guidance.test/api/ask' => Http::response([
                'answer' => 'Additional help for this question.',
            ]),
        ]);

        $this->actingAs($parent)->postJson(route('parent.ai.chat'), [
            'message' => 'Can I replace my document?',
            'use_ai' => true,
        ])->assertOk()->assertJsonPath('answer_type', 'ai_guidance');

        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request) => $request['question'] === 'Can I replace my document?');
    }

    public function test_reported_production_questions_use_faqs_even_without_ai_configuration(): void
    {
        $this->actingAs($this->createParent('production.faq@example.com'));
        config(['services.legal_guidance.url' => null, 'services.legal_guidance.api_key' => null]);
        Http::fake();

        foreach ([
            'What are the common requirements for domestic adoption?' => 'domestic-requirements',
            'What is a Home Study Report and why is it required?' => 'home-study',
            'Why can I not browse child profiles or matching rankings?' => 'matching-privacy',
            'What should I do if my document is rejected?' => 'rejected-document',
        ] as $question => $id) {
            $this->postJson(route('parent.ai.chat'), ['message' => $question])
                ->assertOk()->assertJsonPath('answer_type', 'approved_faq')
                ->assertJsonPath('matched_faq_id', $id);
        }
        Http::assertNothingSent();
    }

    public function test_every_menu_button_and_exact_question_returns_a_saved_answer(): void
    {
        $this->actingAs($this->createParent('all.faqs@example.com'));
        config(['services.legal_guidance.url' => null, 'services.legal_guidance.api_key' => null]);
        Http::fake();
        $response = $this->get(route('parent.ai.index'))->assertOk();
        $faqs = $response->viewData('frequentlyAskedQuestions');
        $this->assertCount(14, $faqs);
        $response->assertSee('Choose a question for a quick answer')
            ->assertSee('These saved answers do not use AI.');

        foreach ($faqs as $faq) {
            $response->assertSee($faq['question']);
            foreach (['parent.ai.faq', 'parent.ai.chat'] as $routeName) {
                $this->postJson(route($routeName), ['faq_id' => $faq['id']])
                    ->assertOk()->assertJsonPath('reply', $faq['answer'])
                    ->assertJsonPath('answer_type', 'approved_faq');
            }
            $this->postJson(route('parent.ai.chat'), ['message' => $faq['question']])
                ->assertOk()->assertJsonPath('reply', $faq['answer'])
                ->assertJsonPath('answer_type', 'approved_faq');
        }
        Http::assertNothingSent();
    }

    public function test_ai_outages_are_actionable_and_do_not_expose_upstream_details(): void
    {
        $parent = $this->createParent('unavailable.ai@example.com');
        $this->actingAs($parent);
        foreach ([
            Http::response(['error' => 'PRIVATE-UPSTREAM-DETAIL'], 503),
            Http::response('<html>upstream error</html>', 200),
            Http::response(['answer' => ''], 200),
        ] as $upstreamResponse) {
            Http::fake(['*' => $upstreamResponse]);
            $this->postJson(route('parent.ai.chat'), ['message' => 'Explain my next case-specific step'])
                ->assertStatus(503)->assertJsonPath('code', 'ai_unavailable')
                ->assertJsonMissingPath('details')->assertJsonMissingPath('sent_parent_context')
                ->assertDontSee('PRIVATE-UPSTREAM-DETAIL')->assertDontSee('test-internal-key');
            $this->assertNull($this->app['session.store']->get($this->historyKey($parent)));
        }
    }

    public function test_missing_ai_configuration_does_not_break_faq_buttons(): void
    {
        $this->actingAs($this->createParent('missing.ai@example.com'));
        config(['services.legal_guidance.api_key' => null]);
        Http::fake();
        $this->postJson(route('parent.ai.chat'), ['message' => 'Explain my next case-specific step'])
            ->assertStatus(503)->assertJsonPath('code', 'ai_unavailable')
            ->assertJsonMissingPath('details');
        $this->postJson(route('parent.ai.chat'), ['faq_id' => 'home-study'])
            ->assertOk()->assertJsonPath('answer_type', 'approved_faq');
        Http::assertNothingSent();
    }

    public function test_clear_removes_only_the_authenticated_parents_history(): void
    {
        $firstParent = $this->createParent('first.parent@example.com');
        $secondParent = $this->createParent('second.parent@example.com');
        $secondHistory = [
            ['role' => 'user', 'content' => 'Keep this second-parent message'],
        ];

        $response = $this->actingAs($firstParent)
            ->withSession([
                $this->historyKey($firstParent) => [
                    ['role' => 'user', 'content' => 'Clear this first-parent message'],
                ],
                $this->historyKey($secondParent) => $secondHistory,
                'parent_ai_messages' => [
                    ['role' => 'user', 'content' => 'Remove the unsafe legacy message'],
                ],
            ])
            ->postJson(route('parent.ai.clear'));

        $response->assertOk()
            ->assertJsonPath('message', 'Chat cleared.')
            ->assertSessionMissing($this->historyKey($firstParent))
            ->assertSessionMissing('parent_ai_messages')
            ->assertSessionHas($this->historyKey($secondParent), $secondHistory);
    }

    private function createParent(string $email): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'prospective_parent'],
            ['name' => 'Prospective Adoptive Parent']
        );

        $parent = User::create([
            'role_id' => $role->id,
            'name' => strstr($email, '@', true),
            'email' => $email,
            'password' => 'SecurePass123',
            'status' => User::STATUS_ACTIVE,
        ]);

        $parent->forceFill([
            'email_verified_at' => now(),
            'activated_at' => now(),
        ])->save();

        return $parent;
    }

    private function historyKey(User $user): string
    {
        return 'parent_ai_messages_user_'.$user->getKey();
    }
}
