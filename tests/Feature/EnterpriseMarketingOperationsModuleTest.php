<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdManagerReport;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeRole;
use App\Models\ModeratorReport;
use App\Models\PageDailyOperationSummary;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnterpriseMarketingOperationsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_moderator_and_ad_manager_reports_create_daily_summary(): void
    {
        $admin = $this->admin();
        $scope = $this->scope();

        $this->actingAs($admin)->post('/admin/marketing-operations/moderator/operations', [
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'submission_date' => '2026-07-11',
            'orders' => 12,
            'confirmed_orders' => 10,
            'cancelled_orders' => 1,
            'pending_orders' => 1,
            'returned_orders' => 2,
            'status' => 'submitted',
        ])->assertRedirect('/admin/marketing-operations/moderator/operations');

        $this->actingAs($admin)->post('/admin/marketing-operations/ad-manager/operations', [
            'client_id' => $scope['client']->id,
            'campaign_id' => $scope['campaign']->id,
            'report_date' => '2026-07-11',
            'spend_usd' => 50,
            'purchases' => 10,
            'status' => 'submitted',
        ])->assertRedirect('/admin/marketing-operations/ad-manager/operations');

        $this->assertDatabaseHas('moderator_reports', [
            'page_id' => $scope['page']->id,
            'orders' => 12,
            'returned_orders' => 2,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('ad_manager_reports', [
            'campaign_id' => $scope['campaign']->id,
            'spend_usd' => 50,
            'purchases' => 10,
            'cpp' => 5,
        ]);
        $this->assertDatabaseHas('page_daily_operation_summaries', [
            'summary_date' => '2026-07-11 00:00:00',
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'orders' => 12,
            'spend_usd' => 50,
            'cpp' => 5,
        ]);
    }

    public function test_duplicate_moderator_report_is_blocked_for_same_page_and_date(): void
    {
        $admin = $this->admin();
        $scope = $this->scope();
        $payload = [
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'submission_date' => '2026-07-11',
            'orders' => 5,
            'confirmed_orders' => 4,
            'cancelled_orders' => 0,
            'pending_orders' => 1,
            'status' => 'submitted',
        ];

        $this->actingAs($admin)->post('/admin/marketing-operations/moderator/operations', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/marketing-operations/moderator/operations', $payload)->assertSessionHasErrors();

        $this->assertSame(1, ModeratorReport::count());
    }

    public function test_submission_status_uses_centralized_settings_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-11 02:05:00', 'Asia/Dhaka'));
        $admin = $this->admin();
        $scope = $this->scope();

        $this->actingAs($admin)->post('/admin/marketing-operations/moderator/operations', [
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'submission_date' => '2026-07-11',
            'orders' => 7,
            'confirmed_orders' => 6,
            'cancelled_orders' => 0,
            'pending_orders' => 1,
        ])->assertRedirect();

        $this->assertSame('late_submitted', ModeratorReport::firstOrFail()->status);
        Carbon::setTestNow();
    }

    public function test_admin_can_update_marketing_operations_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/marketing-operations/settings', [
            'timezone' => 'Asia/Dhaka',
            'moderator_submission_start' => '01:30',
            'moderator_submission_end' => '02:30',
            'ad_manager_submission_start' => '01:00',
            'ad_manager_submission_end' => '02:00',
            'auditor_review_start' => '02:00',
            'auditor_review_end' => '08:00',
            'monitor_review_start' => '08:00',
            'monitor_review_end' => '11:00',
            'agency_review_start' => '11:00',
            'agency_review_end' => '13:00',
            'late_submission_buffer_minutes' => 1,
            'missing_report_buffer_minutes' => 30,
            'reminder_before_open_minutes' => 10,
            'reminder_before_close_minutes' => '15,5',
        ])->assertRedirect();

        $this->assertDatabaseHas('marketing_operation_settings', [
            'key' => 'moderator_submission_start',
            'value' => '01:30',
        ]);
    }

    public function test_moderator_can_submit_only_for_assigned_page(): void
    {
        $scope = $this->scope();
        $moderator = $this->moderatorUser($scope['employee']);
        EmployeeAssignment::create([
            'employee_id' => $scope['employee']->id,
            'client_id' => $scope['client']->id,
            'client_page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'assigned_from' => '2026-07-01',
            'status' => 'active',
        ]);

        $otherPage = ClientPage::create([
            'client_id' => $scope['client']->id,
            'page_name' => 'Unassigned Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);

        $this->actingAs($moderator)->post('/admin/marketing-operations/moderator/operations', [
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'submission_date' => '2026-07-12',
            'orders' => 3,
            'confirmed_orders' => 3,
            'cancelled_orders' => 0,
            'pending_orders' => 0,
        ])->assertRedirect();

        $this->actingAs($moderator)->post('/admin/marketing-operations/moderator/operations', [
            'client_id' => $scope['client']->id,
            'page_id' => $otherPage->id,
            'submission_date' => '2026-07-12',
            'orders' => 3,
            'confirmed_orders' => 3,
            'cancelled_orders' => 0,
            'pending_orders' => 0,
        ])->assertSessionHasErrors('page_id');
    }

    public function test_report_status_workflow_records_reviewers_and_locks_approved_reports(): void
    {
        $admin = $this->admin();
        $scope = $this->scope();
        $report = ModeratorReport::create([
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'submission_date' => '2026-07-11',
            'orders' => 5,
            'confirmed_orders' => 4,
            'cancelled_orders' => 0,
            'pending_orders' => 1,
            'status' => 'submitted',
        ]);

        $this->actingAs($admin)->post("/admin/marketing-operations/moderator/operations/{$report->id}/status", [
            'status' => 'verified',
        ])->assertRedirect();
        $this->assertSame($admin->id, $report->fresh()->verified_by);

        $this->actingAs($admin)->post("/admin/marketing-operations/moderator/operations/{$report->id}/status", [
            'status' => 'approved',
        ])->assertRedirect();
        $this->assertSame($admin->id, $report->fresh()->approved_by);

        $this->actingAs($admin)->post("/admin/marketing-operations/moderator/operations/{$report->id}/status", [
            'status' => 'rejected',
        ])->assertSessionHasErrors('status');
        $this->assertSame('approved', $report->fresh()->status);
    }

    public function test_agency_operations_dashboard_reads_normalized_reports(): void
    {
        $admin = $this->admin();
        $scope = $this->scope();

        ModeratorReport::create([
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'submission_date' => now()->toDateString(),
            'orders' => 8,
            'confirmed_orders' => 7,
            'cancelled_orders' => 1,
            'pending_orders' => 0,
            'status' => 'approved',
        ]);
        AdManagerReport::create([
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'report_date' => now()->toDateString(),
            'spend_usd' => 40,
            'spend_bdt' => 5800,
            'purchases' => 8,
            'cpp' => 5,
            'status' => 'approved',
        ]);
        PageDailyOperationSummary::create([
            'summary_date' => now()->toDateString(),
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'orders' => 8,
            'spend_usd' => 40,
            'cpp' => 5,
            'final_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get('/admin/marketing-operations/agency')
            ->assertOk()
            ->assertSee('Agency Operations')
            ->assertSee('8')
            ->assertSee('40.00')
            ->assertSee('Marketing Page');
    }

    public function test_marketing_operations_sidebar_uses_enterprise_workspace_labels_and_legacy_routes_still_work(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/marketing-operations')
            ->assertOk()
            ->assertSee('Moderator Operations')
            ->assertSee('Ad Manager Operations')
            ->assertSee('Auditor Operations')
            ->assertSee('Monitor Operations')
            ->assertSee('Agency Operations')
            ->assertSee('Settings')
            ->assertDontSee('Trainer Reports');

        $this->actingAs($admin)->get('/admin/marketing-operations/moderator_order/create')->assertOk();
        $this->actingAs($admin)->get('/admin/marketing-operations/ad_manager_spend/create')->assertOk();
    }

    private function scope(): array
    {
        $client = Client::create([
            'company_name' => 'Enterprise Marketing Client',
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
            'bm_name' => 'Enterprise BM',
            'bm_id' => 'bm_enterprise',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $adAccount = AdAccount::create([
            'ad_account_name' => 'Enterprise Ad Account',
            'ad_account_id' => 'act_enterprise',
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
            'campaign_name' => 'Enterprise Campaign',
            'campaign_id' => 'cmp_enterprise',
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
            'employee_id' => 'NSYS-EM-ENT-MKT',
            'name' => 'Enterprise Marketing Employee',
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

    private function moderatorUser(Employee $employee): User
    {
        $permission = Permission::firstOrCreate(
            ['key' => 'marketing_operations.submit'],
            ['name' => 'Marketing Operations Submit', 'module' => 'marketing_operations']
        );
        $role = Role::firstOrCreate(
            ['slug' => 'moderator'],
            ['name' => 'Moderator']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $user->roles()->syncWithoutDetaching([$role->id]);
        $employee->update(['user_id' => $user->id]);

        return $user;
    }
}
