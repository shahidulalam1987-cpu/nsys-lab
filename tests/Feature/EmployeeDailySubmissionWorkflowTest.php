<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeeRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDailySubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_sees_only_assigned_pages_and_cannot_submit_spend(): void
    {
        $scope = $this->campaignScope('Assigned Page');
        $other = $this->campaignScope('Hidden Page');
        [$user, $employee] = $this->employeeUser('moderator', 'Moderator');
        $this->assign($employee, $scope);

        $this->actingAs($user)->get('/employee/daily-orders')
            ->assertOk()
            ->assertSee('Assigned Page')
            ->assertDontSee('Hidden Page');
        $this->actingAs($user)->get('/employee/daily-spend')->assertForbidden();

        $this->actingAs($user)->post('/employee/daily-orders', $this->orderPayload($other))
            ->assertSessionHasErrors('page_id');
        $this->assertDatabaseCount('employee_daily_submissions', 0);
    }

    public function test_moderator_can_submit_orders_and_duplicate_is_blocked(): void
    {
        $scope = $this->campaignScope();
        [$user, $employee] = $this->employeeUser('moderator', 'Moderator');
        $this->assign($employee, $scope);
        $payload = $this->orderPayload($scope);

        $this->actingAs($user)->post('/employee/daily-orders', $payload)
            ->assertRedirect('/employee/daily-orders');
        $this->assertDatabaseHas('employee_daily_submissions', [
            'employee_id' => $employee->id,
            'page_id' => $scope['page']->id,
            'submission_type' => 'order',
            'orders' => 12,
            'status' => 'pending',
        ]);
        $this->actingAs($user)->post('/employee/daily-orders', $payload)
            ->assertSessionHasErrors('submission_date');
        $this->assertDatabaseCount('employee_daily_submissions', 1);
    }

    public function test_facebook_manager_can_submit_spend_but_cannot_submit_orders(): void
    {
        $scope = $this->campaignScope();
        [$user, $employee] = $this->employeeUser('facebook_manager', 'Manager');
        $this->assign($employee, $scope);

        $this->actingAs($user)->get('/employee/daily-spend')->assertOk()->assertSee($scope['campaign']->campaign_name);
        $this->actingAs($user)->post('/employee/daily-spend', $this->spendPayload($scope))
            ->assertRedirect('/employee/daily-spend');
        $this->assertDatabaseHas('employee_daily_submissions', [
            'employee_id' => $employee->id,
            'submission_type' => 'spend',
            'dollar_spend' => 120.50,
            'ad_account_id' => $scope['account']->id,
        ]);
        $this->actingAs($user)->get('/employee/daily-orders')->assertForbidden();
    }

    public function test_admin_can_approve_and_reject_submissions(): void
    {
        $scope = $this->campaignScope();
        [$moderator, $employee] = $this->employeeUser('moderator', 'Moderator');
        $this->assign($employee, $scope);
        $this->actingAs($moderator)->post('/employee/daily-orders', $this->orderPayload($scope));
        $submission = EmployeeDailySubmission::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/employee-submissions/'.$submission->id.'/approve')
            ->assertSessionHas('success');
        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertNotNull($submission->fresh()->reviewed_at);

        $rejected = $this->submission($employee, $scope, 'order', ['submission_date' => today()->subDay()]);
        $this->actingAs($admin)->post('/admin/employee-submissions/'.$rejected->id.'/reject', [
            'admin_note' => 'Please correct the count.',
        ])->assertSessionHas('success');
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_approved_order_and_spend_merge_into_daily_performance(): void
    {
        $scope = $this->campaignScope();
        [, $moderator] = $this->employeeUser('moderator', 'Moderator');
        [, $manager] = $this->employeeUser('facebook_manager', 'Manager');
        $order = $this->submission($moderator, $scope, 'order', ['status' => 'approved', 'orders' => 12]);
        $spend = $this->submission($manager, $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 120]);

        $this->actingAs($this->admin())->post('/admin/employee-submissions/'.$order->id.'/merge')
            ->assertRedirect();

        $report = DailyPerformanceReport::firstOrFail();
        $this->assertSame($scope['campaign']->id, $report->campaign_id);
        $this->assertSame(12, $report->orders);
        $this->assertSame('120.00', $report->spend);
        $this->assertSame('10.00', $report->cpp);
        $this->assertSame('admin_approved', $report->status);
        $this->assertSame('merged', $order->fresh()->status);
        $this->assertSame('merged', $spend->fresh()->status);
    }

    public function test_unapproved_submission_cannot_merge(): void
    {
        $scope = $this->campaignScope();
        [, $employee] = $this->employeeUser('moderator', 'Moderator');
        $submission = $this->submission($employee, $scope, 'order');

        $this->actingAs($this->admin())->post('/admin/employee-submissions/'.$submission->id.'/merge')
            ->assertSessionHasErrors('submission');
        $this->assertDatabaseCount('daily_performance_reports', 0);
    }

    public function test_approved_order_alone_does_not_show_on_client_dashboard(): void
    {
        $scope = $this->campaignScope('Order Only');
        $clientUser = $this->clientUser($scope['client']);
        [, $moderator] = $this->employeeUser('moderator', 'Moderator');
        $this->submission($moderator, $scope, 'order', ['status' => 'approved', 'orders' => 12]);

        $this->actingAs($clientUser)->get('/client/dashboard')
            ->assertOk()->assertSee('No report submitted today.')->assertDontSee('Order Only Campaign');
    }

    public function test_approved_spend_alone_does_not_show_on_client_dashboard(): void
    {
        $scope = $this->campaignScope('Spend Only');
        $clientUser = $this->clientUser($scope['client']);
        [, $manager] = $this->employeeUser('facebook_manager', 'Manager');
        $this->submission($manager, $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 120]);

        $this->actingAs($clientUser)->get('/client/dashboard')
            ->assertOk()->assertSee('No report submitted today.')->assertDontSee('Spend Only Campaign');
    }

    public function test_approved_pair_is_hidden_until_merge_then_visible_in_client_dashboard_and_report(): void
    {
        $scope = $this->campaignScope('Merged Client');
        $clientUser = $this->clientUser($scope['client']);
        [, $moderator] = $this->employeeUser('moderator', 'Moderator');
        [, $manager] = $this->employeeUser('facebook_manager', 'Manager');
        $order = $this->submission($moderator, $scope, 'order', ['status' => 'approved', 'orders' => 12]);
        $this->submission($manager, $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 120]);

        $this->actingAs($clientUser)->get('/client/dashboard')->assertDontSee('Merged Client Campaign');
        $this->actingAs($this->admin())->post('/admin/employee-submissions/'.$order->id.'/merge')->assertRedirect();
        $this->actingAs($clientUser)->get('/client/dashboard')
            ->assertOk()->assertSee('Merged Client Campaign')->assertSee('USD 10.00');
        $this->actingAs($clientUser)->get('/client/performance-reports')
            ->assertOk()->assertSee('Merged Client Campaign')->assertSee('USD 120.00');
    }

    public function test_existing_report_requires_explicit_replace_confirmation(): void
    {
        $scope = $this->campaignScope('Replace Guard');
        [, $moderator] = $this->employeeUser('moderator', 'Moderator');
        [, $manager] = $this->employeeUser('facebook_manager', 'Manager');
        $order = $this->submission($moderator, $scope, 'order', ['status' => 'approved', 'orders' => 12]);
        $this->submission($manager, $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 120]);
        $report = DailyPerformanceReport::create([
            'campaign_id' => $scope['campaign']->id, 'report_date' => today(), 'spend' => 25, 'orders' => 5,
        ]);

        $this->actingAs($this->admin())->post('/admin/employee-submissions/'.$order->id.'/merge')
            ->assertSessionHasErrors('submission');
        $this->assertSame('25.00', $report->fresh()->spend);

        $this->actingAs($this->admin())->post('/admin/employee-submissions/'.$order->id.'/merge', ['replace' => 1])
            ->assertRedirect();
        $this->assertSame('120.00', $report->fresh()->spend);
    }

    public function test_client_cannot_see_another_clients_modern_performance(): void
    {
        $own = $this->campaignScope('Own Client');
        $other = $this->campaignScope('Private Client');
        $clientUser = $this->clientUser($own['client']);
        DailyPerformanceReport::create(['campaign_id' => $own['campaign']->id, 'report_date' => today(), 'spend' => 10, 'orders' => 2]);
        DailyPerformanceReport::create(['campaign_id' => $other['campaign']->id, 'report_date' => today(), 'spend' => 999, 'orders' => 99]);

        $this->actingAs($clientUser)->get('/client/performance-reports')
            ->assertOk()->assertSee('Own Client Campaign')->assertDontSee('Private Client Campaign')->assertDontSee('999.00');
    }

    public function test_employee_sees_only_own_submission_status_and_idor_is_blocked(): void
    {
        $scope = $this->campaignScope('Own Page');
        $otherScope = $this->campaignScope('Other Employee Page');
        [$user, $employee] = $this->employeeUser('moderator', 'Moderator');
        [, $otherEmployee] = $this->employeeUser('moderator', 'Moderator');
        $this->assign($employee, $scope);
        $this->assign($otherEmployee, $otherScope);
        $this->submission($employee, $scope, 'order', ['note' => 'My visible note']);
        $this->submission($otherEmployee, $otherScope, 'order', ['note' => 'Private other note']);

        $this->actingAs($user)->get('/employee/daily-orders')
            ->assertOk()
            ->assertSee('My visible note')
            ->assertDontSee('Private other note');
        $this->actingAs($user)->post('/employee/daily-orders', $this->orderPayload($otherScope))
            ->assertSessionHasErrors('page_id');
    }

    private function campaignScope(string $pageName = 'Performance Page'): array
    {
        $client = Client::create([
            'company_name' => $pageName.' Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 125,
            'status' => 'active',
        ]);
        ClientFundLedger::create([
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_FACEBOOK_ADS,
            'direction' => ClientFundLedger::DIRECTION_CREDIT,
            'amount_bdt' => 100000,
            'balance_before' => 0,
            'balance_after' => 100000,
            'reference' => 'TEST-ADS-FUND',
            'description' => 'Test ads fund deposit.',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => $pageName.' BM',
            'bm_id' => 'BM-'.uniqid(),
            'owner_name' => 'NSYS Agency',
            'owner_email' => 'owner-'.uniqid().'@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $account = AdAccount::create([
            'ad_account_name' => $pageName.' Account',
            'ad_account_id' => 'AD-'.uniqid(),
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'currency' => 'USD',
            'status' => 'active',
        ]);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'page_name' => $pageName,
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'campaign_name' => $pageName.' Campaign',
            'campaign_id' => 'CAM-'.uniqid(),
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'sales',
            'status' => 'active',
            'start_date' => today()->subMonth(),
        ]);

        return compact('client', 'bm', 'account', 'page', 'campaign');
    }

    private function employeeUser(string $permissionRole, string $employeeRole): array
    {
        $user = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $user->roles()->sync([Role::where('slug', $permissionRole)->valueOrFail('id')]);
        $role = EmployeeRole::where('name', $employeeRole)->firstOrFail();
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id' => 'SUB-'.uniqid(),
            'name' => $employeeRole.' Employee',
            'department' => 'Facebook Operations',
            'department_id' => Department::where('name', 'Facebook Operations')->valueOrFail('id'),
            'role' => $role->name,
            'role_id' => $role->id,
            'joining_date' => today()->subMonth(),
            'confirmation_date' => today()->subWeeks(3),
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        return [$user, $employee];
    }

    private function assign(Employee $employee, array $scope): void
    {
        $employee->assignments()->create([
            'client_id' => $scope['client']->id,
            'client_page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'assigned_from' => today()->subMonth(),
            'status' => 'active',
        ]);
    }

    private function orderPayload(array $scope): array
    {
        return [
            'submission_date' => today()->toDateString(),
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'orders' => 12,
            'confirmed_orders' => 10,
            'cancelled_orders' => 2,
        ];
    }

    private function spendPayload(array $scope): array
    {
        return [
            'submission_date' => today()->toDateString(),
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'dollar_spend' => 120.50,
            'cpm' => 2.50,
            'cpc' => 0.20,
            'ctr' => 1.5,
        ];
    }

    private function submission(Employee $employee, array $scope, string $type, array $overrides = []): EmployeeDailySubmission
    {
        $date = $overrides['submission_date'] ?? today();
        $dateString = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;

        return EmployeeDailySubmission::create(array_merge([
            'employee_id' => $employee->id,
            'client_id' => $scope['client']->id,
            'page_id' => $scope['page']->id,
            'campaign_id' => $scope['campaign']->id,
            'bm_id' => $scope['bm']->id,
            'ad_account_id' => $scope['account']->id,
            'submission_date' => $dateString,
            'submission_type' => $type,
            'orders' => $type === 'order' ? 5 : null,
            'dollar_spend' => $type === 'spend' ? 50 : null,
            'status' => 'pending',
            'submission_key' => EmployeeDailySubmission::duplicateKey($employee->id, $dateString, $type, $scope['page']->id, $scope['campaign']->id),
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function clientUser(Client $client): User
    {
        $user = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $client->update(['user_id' => $user->id]);

        return $user;
    }
}
