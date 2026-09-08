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

    public function test_parent_can_replace_a_verified_document_before_final_racco_approval(): void
    {
        [$parent, $adoptionCase] = $this->parentAndCase('under_review');
        $document = $this->verifiedDocument($parent, $adoptionCase);
        $oldPath = $document->file_path;

        Storage::disk('local')->put($oldPath, 'old-file');

        $this->actingAs($parent)
            ->post(route('parent.documents.upload', $document), [
                'file' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('parent.documents.index'))
            ->assertSessionHas('success', 'Document replaced successfully and returned to Submitted status for a new review.');

        $document->refresh();

        $this->assertSame('submitted', $document->status);
        $this->assertSame('replacement.pdf', $document->original_filename);
        $this->assertNull($document->remarks);
        $this->assertNull($document->verified_by);
        $this->assertNull($document->verified_at);
        $this->assertSame($parent->id, $document->uploaded_by);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($document->file_path);
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
