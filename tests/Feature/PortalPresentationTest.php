<?php

namespace Tests\Feature;

use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Models\AdoptionCaseNote;
use App\Models\Role;
use App\Models\User;
use App\Support\AdoptionTimeline;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PortalPresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_completed_and_cancelled_application_pages_show_the_correct_outcome(): void
    {
        $user = $this->user();

        foreach (['finalized', 'cancelled'] as $status) {
            $case = $this->adoptionCase($status);
            $view = $this->view('parent.application.index', [
                'user' => $user,
                'parentMatchingProfile' => null,
                'adoptionCase' => $case,
                'parentDocuments' => collect(),
                'parentUpdates' => collect(),
                'requiredDocumentsCount' => 0,
                'submittedDocumentsCount' => 0,
                'verifiedDocumentsCount' => 0,
                'documentProgressPercent' => 0,
                'applicationTimeline' => AdoptionTimeline::forStatus($status),
            ]);

            $view->assertSee('Workflow stage progress');
            if ($status === 'finalized') {
                $view->assertSee('Your application workflow is completed.');
                $view->assertSee('aria-valuenow="100"', false);
                $view->assertDontSee('Upcoming workflow stage');
                $view->assertDontSee('95%');
            } else {
                $view->assertSee('Not applicable');
                $view->assertSee('This case is no longer progressing.');
                $dom = new \DOMDocument;
                $dom->loadHTML((string) $view, LIBXML_NOERROR | LIBXML_NOWARNING);
                $this->assertSame(0, (new \DOMXPath($dom))->query('//*[@id="appPanelTimeline"]//*[@role="progressbar"]')->count());
                $view->assertDontSee('95%');
            }
        }
    }

    public function test_admin_user_timestamps_show_philippine_time_without_changing_storage_values(): void
    {
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Asia/Manila']);
        $user = $this->user();
        $user->created_at = '2026-10-20 18:30:00';
        $user->last_login_at = '2026-10-21 01:15:00';
        $user->email_verified_at = '2026-10-20 18:45:00';
        $original = $user->getAttributes();

        $this->view('admin.users.show', compact('user'))
            ->assertSee('Oct 21, 2026 02:30 AM PHT')
            ->assertSee('Oct 21, 2026 09:15 AM PHT')
            ->assertSee('Oct 21, 2026 02:45 AM PHT');

        $this->assertSame($original, $user->getAttributes());
        $this->assertSame('UTC', config('app.timezone'));

        $user->created_at = null;
        $user->last_login_at = null;
        $user->email_verified_at = null;
        $this->view('admin.users.show', compact('user'))
            ->assertSee('N/A')->assertSee('Never')->assertSee('Verification required');
    }

    public function test_admin_document_verification_and_notes_show_philippine_time(): void
    {
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Asia/Manila']);
        $case = $this->adoptionCase('assessment');
        $document = new AdoptionCaseDocument([
            'document_name' => 'Test requirement',
            'requirement_scope' => 'parent',
            'status' => 'verified',
            'verified_at' => '2026-10-20 18:30:00',
        ]);
        $document->id = 901;
        $document->setRelation('verifier', null)->setRelation('uploader', null);
        $note = new AdoptionCaseNote([
            'note_type' => 'general',
            'visibility' => 'internal',
            'body' => 'Test note',
        ]);
        $note->created_at = '2026-10-20 19:00:00';
        $note->setRelation('creator', null);
        $case->setRelation('documents', collect([$document]));
        $case->setRelation('notes', collect([$note]));

        $this->view('admin.adoption_cases.show', [
            'adoptionCase' => $case,
            'documentStatuses' => AdoptionCaseDocument::STATUSES,
            'noteTypes' => AdoptionCaseNote::NOTE_TYPES,
            'noteVisibilities' => AdoptionCaseNote::VISIBILITIES,
            'errors' => new ViewErrorBag,
        ])->assertSee('Verified on Oct 21, 2026 02:30 AM PHT')
            ->assertSee('Oct 21, 2026 03:00 AM PHT');

        $this->assertSame('2026-10-20 18:30:00', $document->getAttributes()['verified_at']);
        $this->assertSame('2026-10-20 19:00:00', $note->getAttributes()['created_at']);
    }

    private function user(): User
    {
        $user = new User(['name' => 'Presentation test', 'email' => 'test@example.invalid', 'status' => 'pending']);
        $user->id = 900;
        $user->setRelation('role', new Role(['name' => 'Administrator', 'slug' => 'admin']));
        $user->setRelation('matchingProfile', null);

        return $user;
    }

    private function adoptionCase(string $status): AdoptionCase
    {
        $case = new AdoptionCase(['case_code' => 'PRESENTATION-TEST', 'status' => $status, 'case_type' => 'domestic', 'priority' => 'normal']);
        $case->id = 900;
        foreach (['child', 'prospectiveParent', 'assignedSocialWorker', 'creator', 'updater'] as $relation) {
            $case->setRelation($relation, null);
        }

        return $case;
    }
}
