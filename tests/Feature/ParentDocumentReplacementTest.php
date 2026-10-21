<?php

namespace Tests\Feature;

use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParentDocumentReplacementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Schema::dropIfExists('adoption_case_documents');
        Schema::dropIfExists('adoption_cases');
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

        Schema::create('adoption_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_code')->nullable();
            $table->foreignId('prospective_parent_id');
            $table->string('status')->default('document_collection');
            $table->string('racco_review_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('adoption_case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adoption_case_id');
            $table->string('document_name');
            $table->string('document_type');
            $table->string('requirement_scope');
            $table->string('status');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('adoption_case_documents');
        Schema::dropIfExists('adoption_cases');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_parent_can_upload_a_pending_document_only_once(): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('under_review');
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $document->update([
            'status' => 'pending',
            'file_path' => null,
            'original_filename' => null,
            'uploaded_by' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('success', 'Document uploaded successfully. Please wait for staff verification.');

        $document->refresh();

        $this->assertSame('submitted', $document->status);
        $this->assertSame('first.pdf', $document->original_filename);
        $this->assertNull($document->remarks);
        $this->assertNull($document->verified_by);
        $this->assertNull($document->verified_at);
        $this->assertSame($parent->id, $document->uploaded_by);
        Storage::disk('local')->assertExists($document->file_path);

        $submittedAttributes = $document->getAttributes();

        $this->post(route('parent.documents.upload', $document), [
            'file' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'),
        ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('error');

        $this->assertSame($submittedAttributes, $document->fresh()->getAttributes());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    #[DataProvider('documentStatuses')]
    public function test_parent_cannot_replace_an_existing_file_in_any_status(string $status): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('under_review');
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $document->update(['status' => $status]);
        Storage::disk('local')->put($document->file_path, 'original-file');
        $originalAttributes = $document->refresh()->getAttributes();

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('error');

        $this->assertSame($originalAttributes, $document->fresh()->getAttributes());
        $this->assertSame('original-file', Storage::disk('local')->get($document->file_path));
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public static function documentStatuses(): array
    {
        return array_map(fn (string $status) => [$status], array_keys(AdoptionCaseDocument::STATUSES));
    }

    public function test_a_missing_submitted_file_does_not_allow_a_new_parent_upload(): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('under_review');
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $document->update(['status' => 'submitted', 'file_path' => null]);
        $originalAttributes = $document->refresh()->getAttributes();

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('error');

        $this->assertSame($originalAttributes, $document->fresh()->getAttributes());
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_document_page_keeps_details_and_download_but_only_pending_items_can_be_uploaded(): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('under_review');
        $submitted = $this->verifiedDocument($parent, $adoptionCase);
        $pending = $this->verifiedDocument($parent, $adoptionCase);
        $pending->update(['status' => 'pending', 'file_path' => null, 'original_filename' => null]);

        $this->actingAs($parent)
            ->get(route('parent.documents.index'))
            ->assertOk()
            ->assertSee('Details')
            ->assertSee(route('parent.documents.download', $submitted), false)
            ->assertSee('data-upload-action="'.route('parent.documents.upload', $pending).'"', false)
            ->assertDontSee('data-upload-action="'.route('parent.documents.upload', $submitted).'"', false)
            ->assertDontSee('Replace document')
            ->assertDontSee('Replace file')
            ->assertSee('Submitted files cannot be replaced from your account.');
    }

    public function test_parent_cannot_upload_to_someone_elses_document_or_a_child_requirement(): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('under_review');
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $document->update(['status' => 'pending', 'file_path' => null, 'requirement_scope' => 'child']);

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $document->update(['requirement_scope' => 'parent']);
        $adoptionCase->update(['prospective_parent_id' => $parent->id + 1]);

        $this->post(route('parent.documents.upload', $document), [
            'file' => UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    #[DataProvider('closedCaseStatuses')]
    public function test_pending_uploads_are_blocked_for_approved_or_closed_cases(string $status, string $raccoStatus): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase($raccoStatus);
        $adoptionCase->update(['status' => $status]);
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $document->update(['status' => 'pending', 'file_path' => null]);

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('error');

        $this->assertNull($document->fresh()->file_path);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public static function closedCaseStatuses(): array
    {
        return [
            ['document_collection', 'approved'],
            ['finalized', 'under_review'],
            ['closed', 'under_review'],
            ['cancelled', 'under_review'],
        ];
    }

    public function test_parent_cannot_replace_a_document_after_final_racco_case_approval(): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('approved');
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $oldPath = $document->file_path;

        Storage::disk('local')->put($oldPath, 'approved-file');

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('error');

        $document->refresh();

        $this->assertSame('verified', $document->status);
        $this->assertSame($oldPath, $document->file_path);
        Storage::disk('local')->assertExists($oldPath);
    }

    /**
     * @return array{User, AdoptionCase}
     */
    private function parentAndCase(string $raccoReviewStatus): array
    {
        $role = Role::create([
            'name' => 'Prospective Adoptive Parent',
            'slug' => 'prospective_parent',
        ]);

        $parent = User::create([
            'role_id' => $role->id,
            'name' => 'Test Parent',
            'email' => 'parent@example.com',
            'phone_number' => '09170000000',
            'password' => 'SecurePass123',
            'status' => User::STATUS_ACTIVE,
        ]);
        $parent->forceFill(['email_verified_at' => now()])->save();

        $adoptionCase = AdoptionCase::create([
            'case_code' => 'CASE-001',
            'prospective_parent_id' => $parent->id,
            'status' => 'document_collection',
            'racco_review_status' => $raccoReviewStatus,
        ]);

        return [$parent, $adoptionCase];
    }

    private function verifiedDocument(User $parent, AdoptionCase $adoptionCase): AdoptionCaseDocument
    {
        return AdoptionCaseDocument::create([
            'adoption_case_id' => $adoptionCase->id,
            'document_name' => 'PSA Birth Certificate',
            'document_type' => 'applicant_birth_certificate',
            'requirement_scope' => 'parent',
            'status' => 'verified',
            'file_path' => 'adoption/parent-documents/original.pdf',
            'original_filename' => 'original.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'remarks' => 'Accepted by RACCO reviewer.',
            'verified_by' => $parent->id,
            'verified_at' => now(),
            'uploaded_by' => $parent->id,
            'created_by' => $parent->id,
            'updated_by' => $parent->id,
        ]);
    }
}
