<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\MarketingOperationsReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketingOperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_marketing_operations_report_types_can_be_submitted(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $scope = $this->scope();

        $this->actingAs($admin)->post('/admin/marketing-operations/moderator_order', [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'page_id' => $scope['page']->id,
            'confirmed_orders' => 10,
            'cancelled_orders' => 1,
            'pending_orders' => 2,
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/marketing-operations/ad_manager_spend', [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'campaign_id' => $scope['campaign']->id,
            'dollar_spend' => 50,
            'currency' => 'USD',
            'cost_per_purchase' => 5,
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/marketing-operations/auditor_audit', [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'page_id' => $scope['page']->id,
            'target_employee_id' => $scope['employee']->id,
            'average_response_time' => 3,
            'maximum_delay' => 8,
            'missed_messages' => 1,
            'delayed_conversations' => 2,
            'wrong_replies' => 1,
            'follow_up_quality' => 80,
            'response_quality' => 85,
            'customer_handling' => 90,
            'severity' => 'Medium',
            'screenshot' => UploadedFile::fake()->image('audit.png'),
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/marketing-operations/monitor_issue', [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'target_employee_id' => $scope['employee']->id,
            'mistake_category' => 'Wrong Reply',
            'correction_suggestion' => 'Follow SOP checklist.',
            'severity' => 'High',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/marketing-operations/trainer_training', [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'target_employee_id' => $scope['employee']->id,
            'training_type' => 'SOP Training',
            'score' => 88,
            'pass_fail' => 'Pass',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/marketing-operations/management_review', [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'department_id' => $scope['department']->id,
            'operations_status' => 'Good',
            'daily_summary' => 'Operations stable.',
        ])->assertRedirect();

        $this->assertDatabaseCount('marketing_operations_reports', 6);
    }

    public function test_duplicate_prevention_and_admin_status_workflow(): void
    {
        $admin = $this->admin();
        $scope = $this->scope();
        $payload = [
            'report_date' => '2026-07-08',
            'platform' => 'Meta',
            'page_id' => $scope['page']->id,
            'confirmed_orders' => 10,
            'cancelled_orders' => 1,
            'pending_orders' => 2,
        ];

        $this->actingAs($admin)->post('/admin/marketing-operations/moderator_order', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/marketing-operations/moderator_order', $payload)->assertSessionHasErrors();

        $report = MarketingOperationsReport::firstOrFail();
        $this->actingAs($admin)->post('/admin/marketing-operations/reports/' . $report->id . '/status', [
            'status' => 'needs_correction',
            'admin_note' => 'Fix order proof.',
        ])->assertRedirect();
        $this->assertSame('needs_correction', $report->fresh()->status);

        $this->actingAs($admin)->post('/admin/marketing-operations/reports/' . $report->id . '/status', [
            'status' => 'approved',
        ])->assertRedirect();
        $this->assertSame('approved', $report->fresh()->status);
    }

    public function test_monitor_fix_workflow_and_training_record_appears_on_employee_profile(): void
    {
        $admin = $this->admin();
        $scope = $this->scope();

        $this->actingAs($admin)->post('/admin/marketing-operations/monitor_issue', [
            'report_date' => '2026-07-08',
            'target_employee_id' => $scope['employee']->id,
            'mistake_category' => 'Late Reply',
            'correction_suggestion' => 'Reply within SLA.',
            'severity' => 'Low',
        ])->assertRedirect();
        $monitor = MarketingOperationsReport::firstOrFail();
        $this->actingAs($admin)->post('/admin/marketing-operations/reports/' . $monitor->id . '/status', ['status' => 'fixed'])->assertRedirect();
        $this->assertSame('fixed', $monitor->fresh()->status);

        $this->actingAs($admin)->post('/admin/marketing-operations/trainer_training', [
            'report_date' => '2026-07-09',
            'target_employee_id' => $scope['employee']->id,
            'training_type' => 'Message Handling',
        ])->assertRedirect();

        $this->actingAs($admin)
            ->get('/admin/employees/' . $scope['employee']->id)
            ->assertOk()
            ->assertSee('Marketing Operations History')
            ->assertSee('Trainer Report');
    }

    public function test_marketing_operations_navigation_replaces_facebook_and_tiktok_wording_and_old_routes_work(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/marketing-operations')
            ->assertOk()
            ->assertSee('Marketing Operations')
            ->assertSee('Moderator Reports')
            ->assertSee('Ad Manager Reports')
            ->assertDontSee('>Facebook<', false)
            ->assertDontSee('>TikTok<', false);

        $this->actingAs($admin)->get('/admin/daily-reports')->assertOk();
        $this->actingAs($admin)->get('/admin/tiktok')->assertOk();
    }

    public function test_executive_dashboard_marketing_widgets_render(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/executive-performance')
            ->assertOk()
            ->assertSee('Marketing Operations Insights')
            ->assertSee('Top Moderator')
            ->assertSee('Average CPP');
    }

    private function scope(): array
    {
        $client = Client::create([
            'company_name' => 'Marketing Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'page_name' => 'Marketing Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Marketing BM',
            'bm_id' => 'bm_mkt',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $adAccount = AdAccount::create([
            'ad_account_name' => 'Marketing Ad Account',
            'ad_account_id' => 'act_mkt',
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'currency' => 'USD',
            'timezone' => 'Asia/Dhaka',
            'threshold_amount' => 100,
            'current_threshold_usage' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'campaign_name' => 'Marketing Campaign',
            'campaign_id' => 'cmp_mkt',
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'ad_account_id' => $adAccount->id,
            'objective' => 'sales',
            'status' => 'active',
            'start_date' => '2026-07-01',
            'daily_budget' => 10,
        ]);
        $department = Department::create(['name' => 'Marketing Ops', 'slug' => 'marketing-ops', 'status' => 'active']);
        $role = EmployeeRole::firstOrCreate(
            ['slug' => 'moderator'],
            ['name' => 'Moderator', 'status' => 'active']
        );
        $employee = Employee::create([
            'employee_id' => 'NSYS-EM-MKT',
            'name' => 'Marketing Employee',
            'mobile' => '01700000111',
            'department' => 'Marketing',
            'role' => 'Moderator',
            'department_id' => $department->id,
            'role_id' => $role->id,
            'status' => 'active',
            'monthly_salary' => 10000,
            'joining_date' => '2026-07-01',
        ]);

        return compact('client', 'page', 'bm', 'adAccount', 'campaign', 'department', 'role', 'employee');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }
}
