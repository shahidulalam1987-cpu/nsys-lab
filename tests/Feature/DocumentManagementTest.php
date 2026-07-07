<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\ManagedDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_search_and_bind_document_to_employee(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->admin();
        $employee = Employee::create([
            'employee_id' => 'NSYS-EM-DMS',
            'name' => 'DMS Employee',
            'mobile' => '01700000000',
            'department' => 'HR',
            'role' => 'Manager',
            'status' => 'active',
            'monthly_salary' => 10000,
            'joining_date' => '2026-07-01',
        ]);

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Employee Contract',
            'description' => 'Signed employee contract',
            'category' => 'Contracts',
            'tags' => 'contract, employee',
            'owner_module' => 'employee',
            'owner_record_id' => $employee->id,
            'document' => UploadedFile::fake()->create('contract.pdf', 128, 'application/pdf'),
        ])->assertRedirect('/admin/documents');

        $document = ManagedDocument::firstOrFail();
        Storage::disk('local')->assertExists($document->current_file_path);
        Storage::disk('public')->assertMissing($document->current_file_path);
        $this->assertSame(Employee::class, $document->owner_record_type);
        $this->assertSame($employee->id, $document->owner_record_id);
        $this->assertSame(1, $document->versions()->count());
        $this->assertDatabaseHas('managed_document_audits', [
            'managed_document_id' => $document->id,
            'action' => 'uploaded',
        ]);

        $this->actingAs($admin)
            ->get('/admin/documents?search=Contract&category=Contracts')
            ->assertOk()
            ->assertSee('Employee Contract');
    }

    public function test_admin_can_archive_restore_and_upload_new_version(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $document = $this->documentFor($admin);

        $this->actingAs($admin)
            ->post('/admin/documents/' . $document->id . '/archive')
            ->assertRedirect();
        $this->assertSame('archived', $document->fresh()->status);

        $this->actingAs($admin)
            ->post('/admin/documents/' . $document->id . '/restore')
            ->assertRedirect();
        $this->assertSame('active', $document->fresh()->status);

        $this->actingAs($admin)->post('/admin/documents/' . $document->id . '/version', [
            'document' => UploadedFile::fake()->create('updated.pdf', 64, 'application/pdf'),
            'change_note' => 'Updated signed copy',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame(2, (int) $document->version);
        $this->assertSame(2, $document->versions()->count());
        $this->assertDatabaseHas('managed_document_audits', [
            'managed_document_id' => $document->id,
            'action' => 'updated',
        ]);
    }

    public function test_document_download_is_audited(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $document = $this->documentFor($admin);

        $this->actingAs($admin)
            ->get('/documents/' . $document->id . '/download')
            ->assertOk();

        $this->assertDatabaseHas('managed_document_audits', [
            'managed_document_id' => $document->id,
            'action' => 'downloaded',
        ]);
    }

    public function test_client_can_download_only_client_owned_document(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $clientUser = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $otherUser = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $client = Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'DMS Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
        Client::create([
            'user_id' => $otherUser->id,
            'company_name' => 'Other Client',
            'phone' => '456',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Client Receipt',
            'category' => 'Receipts',
            'owner_module' => 'client',
            'owner_record_id' => $client->id,
            'document' => UploadedFile::fake()->create('receipt.pdf', 64, 'application/pdf'),
        ]);
        $document = ManagedDocument::firstOrFail();

        $this->actingAs($clientUser)->get('/documents/' . $document->id . '/download')->assertOk();
        $this->actingAs($otherUser)->get('/documents/' . $document->id . '/download')->assertForbidden();
    }

    public function test_employee_can_download_only_own_employee_document(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $employeeUser = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $otherUser = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_id' => 'NSYS-EM-OWN',
            'name' => 'Own Employee',
            'mobile' => '01700000001',
            'department' => 'HR',
            'role' => 'Manager',
            'status' => 'active',
            'monthly_salary' => 10000,
            'joining_date' => '2026-07-01',
        ]);
        Employee::create([
            'user_id' => $otherUser->id,
            'employee_id' => 'NSYS-EM-OTHER',
            'name' => 'Other Employee',
            'mobile' => '01700000002',
            'department' => 'HR',
            'role' => 'Manager',
            'status' => 'active',
            'monthly_salary' => 10000,
            'joining_date' => '2026-07-01',
        ]);

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Employee NID',
            'category' => 'Employee',
            'owner_module' => 'employee',
            'owner_record_id' => $employee->id,
            'document' => UploadedFile::fake()->image('nid.jpg'),
        ]);
        $document = ManagedDocument::firstOrFail();

        $this->actingAs($employeeUser)->get('/documents/' . $document->id . '/download')->assertOk();
        $this->actingAs($otherUser)->get('/documents/' . $document->id . '/download')->assertForbidden();
    }

    public function test_preview_requires_permission_and_missing_file_returns_clean_not_found(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $other = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $document = $this->documentFor($admin);

        $this->actingAs($other)
            ->get('/documents/' . $document->id . '/preview')
            ->assertForbidden();

        Storage::disk('local')->delete($document->current_file_path);

        $this->actingAs($admin)
            ->get('/documents/' . $document->id . '/download')
            ->assertNotFound()
            ->assertSee('Document file not found.');

        $this->actingAs($admin)
            ->get('/documents/' . $document->id . '/preview')
            ->assertNotFound()
            ->assertSee('Document file not found.');
    }

    public function test_historical_version_download_and_preview_are_secured(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $other = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $document = $this->documentFor($admin);

        $this->actingAs($admin)->post('/admin/documents/' . $document->id . '/version', [
            'document' => UploadedFile::fake()->create('updated.pdf', 64, 'application/pdf'),
            'change_note' => 'Updated copy',
        ])->assertRedirect();

        $version = $document->versions()->where('version', 1)->firstOrFail();

        $this->actingAs($other)
            ->get('/admin/documents/' . $document->id . '/versions/' . $version->id . '/download')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/documents/' . $document->id . '/versions/' . $version->id . '/download')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/documents/' . $document->id . '/versions/' . $version->id . '/preview')
            ->assertOk();
    }

    public function test_related_widget_upload_button_hidden_without_manage_permission(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $viewer = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $employee = Employee::create([
            'user_id' => $viewer->id,
            'employee_id' => 'NSYS-EM-WIDGET',
            'name' => 'Widget Employee',
            'mobile' => '01700000003',
            'department' => 'HR',
            'role' => 'Manager',
            'status' => 'active',
            'monthly_salary' => 10000,
            'joining_date' => '2026-07-01',
        ]);

        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'Widget Document',
            'category' => 'Employee',
            'owner_module' => 'employee',
            'owner_record_id' => $employee->id,
            'document' => UploadedFile::fake()->create('widget.pdf', 64, 'application/pdf'),
        ]);

        $this->actingAs($viewer);
        $html = view('admin.documents.partials.related-widget', [
            'ownerModule' => 'employee',
            'ownerId' => $employee->id,
            'category' => 'Employee',
        ])->render();

        $this->assertStringNotContainsString('/admin/documents/create', $html);
        $this->assertStringContainsString('Widget Document', $html);
    }

    private function documentFor(User $admin): ManagedDocument
    {
        $this->actingAs($admin)->post('/admin/documents', [
            'title' => 'General Policy',
            'category' => 'General',
            'document' => UploadedFile::fake()->create('policy.pdf', 64, 'application/pdf'),
        ]);

        return ManagedDocument::firstOrFail();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }
}
