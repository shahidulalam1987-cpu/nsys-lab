<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BonusRule;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBonusEarning;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeeRole;
use App\Models\EmployeeTarget;
use App\Models\Role;
use App\Models\User;
use App\Services\PerformanceOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceOperationsPhaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_grouped_ready_to_merge_performance(): void
    {
        $scope = $this->scope();
        $orderEmployee = $this->employee('Moderator');
        $spendEmployee = $this->employee('Manager');
        $this->submission($orderEmployee, $scope, 'order', ['status' => 'approved', 'orders' => 10, 'confirmed_orders' => 8]);
        $this->submission($spendEmployee, $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 100]);

        $this->actingAs($this->admin())->get('/admin/performance-verification')
            ->assertOk()->assertSee('Ready To Merge')->assertSee('USD 10.00')->assertSee('BDT 2,000.00');
    }

    public function test_merge_stores_provenance_and_duplicate_merge_is_blocked(): void
    {
        $scope = $this->scope();
        $order = $this->submission($this->employee('Moderator'), $scope, 'order', ['status' => 'approved', 'orders' => 10]);
        $spend = $this->submission($this->employee('Manager'), $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 100]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/employee-submissions/'.$order->id.'/merge')->assertRedirect();
        $report = DailyPerformanceReport::firstOrFail();
        $this->assertEqualsCanonicalizing([$order->id, $spend->id], $report->source_submission_ids);
        $this->assertSame($admin->id, $report->merged_by);
        $this->assertNotNull($report->merged_at);

        $this->actingAs($admin)->post('/admin/employee-submissions/'.$order->id.'/merge')->assertSessionHasErrors('submission');
        $this->assertDatabaseCount('daily_performance_reports', 1);
    }

    public function test_moderator_and_ad_manager_kpis_use_only_own_approved_data(): void
    {
        $scope = $this->scope();
        $moderator = $this->employee('Moderator');
        $manager = $this->employee('Manager');
        $this->submission($moderator, $scope, 'order', ['status' => 'approved', 'orders' => 12, 'confirmed_orders' => 10]);
        $this->submission($manager, $scope, 'spend', ['status' => 'approved', 'dollar_spend' => 120]);
        $service = app(PerformanceOperationsService::class);

        $moderatorKpi = $service->employeeKpi($moderator, today(), today());
        $managerKpi = $service->employeeKpi($manager, today(), today());
        $this->assertSame(12, $moderatorKpi['total_orders']);
        $this->assertSame(0.0, $moderatorKpi['approved_spend']);
        $this->assertSame(120.0, $managerKpi['approved_spend']);
        $this->assertSame(0, $managerKpi['total_orders']);
    }

    public function test_employee_performance_page_does_not_expose_other_employee_data(): void
    {
        $scope = $this->scope();
        [$user, $employee] = $this->employeeWithUser('Moderator', 'moderator');
        $other = $this->employee('Moderator', 'PRIVATE OTHER EMPLOYEE');
        $this->submission($employee, $scope, 'order', ['status' => 'approved', 'orders' => 5]);
        $this->submission($other, $scope, 'order', ['status' => 'approved', 'orders' => 99]);

        $this->actingAs($user)->get('/employee/performance')
            ->assertOk()->assertSee('My Performance')->assertDontSee('PRIVATE OTHER EMPLOYEE');
    }

    public function test_leaderboard_ranks_highest_confirmed_orders_first(): void
    {
        $scope = $this->scope();
        $top = $this->employee('Moderator', 'Top Moderator');
        $second = $this->employee('Moderator', 'Second Moderator');
        $this->submission($top, $scope, 'order', ['status' => 'approved', 'orders' => 20, 'confirmed_orders' => 18]);
        $this->submission($second, $scope, 'order', ['status' => 'approved', 'orders' => 10, 'confirmed_orders' => 9]);

        $this->actingAs($this->admin())->get('/admin/leaderboard?type=orders')
            ->assertOk()->assertSeeInOrder(['Top Moderator', 'Second Moderator']);
    }

    public function test_employee_target_has_priority_and_achievement_is_calculated(): void
    {
        $scope = $this->scope();
        $employee = $this->employee('Moderator');
        EmployeeTarget::create(['department_id' => $employee->department_id, 'target_type' => 'orders', 'target_value' => 20, 'period_type' => 'daily', 'start_date' => today(), 'status' => 'active']);
        EmployeeTarget::create(['employee_id' => $employee->id, 'target_type' => 'orders', 'target_value' => 10, 'period_type' => 'daily', 'start_date' => today(), 'status' => 'active']);
        $this->submission($employee, $scope, 'order', ['status' => 'approved', 'orders' => 10, 'confirmed_orders' => 10]);

        $kpi = app(PerformanceOperationsService::class)->employeeKpi($employee, today(), today());
        $this->assertSame($employee->id, $kpi['target']->employee_id);
        $this->assertSame(100.0, $kpi['target_achievement']);
    }

    public function test_bonus_evaluation_creates_pending_earning_without_payment(): void
    {
        $scope = $this->scope();
        $employee = $this->employee('Moderator');
        $this->submission($employee, $scope, 'order', ['status' => 'approved', 'confirmed_orders' => 15]);
        $rule = BonusRule::create([
            'name' => 'Daily Order Bonus', 'applies_to_type' => 'employee', 'employee_id' => $employee->id,
            'metric' => 'confirmed_orders', 'comparison' => 'gte', 'threshold' => 10, 'bonus_amount' => 500,
            'bonus_type' => 'fixed', 'period_type' => 'daily', 'status' => 'active',
        ]);

        $this->actingAs($this->hrManager())->post('/admin/bonuses/rules/'.$rule->id.'/evaluate')->assertSessionHas('success');
        $earning = EmployeeBonusEarning::firstOrFail();
        $this->assertSame('pending', $earning->status);
        $this->assertNull($earning->paid_payroll_id);
        $this->assertSame('500.00', $earning->bonus_amount);
    }

    public function test_bonus_approval_and_performance_permissions_are_enforced(): void
    {
        $facebook = $this->staff('facebook_manager');
        $this->actingAs($facebook)->get('/admin/performance-verification')->assertOk();
        $this->actingAs($facebook)->get('/admin/employee-kpi')->assertForbidden();

        $finance = $this->staff('finance_manager');
        $this->actingAs($finance)->get('/admin/bonuses')->assertOk();

        $rule = BonusRule::create(['name' => 'Permission Rule', 'applies_to_type' => 'employee', 'metric' => 'confirmed_orders', 'comparison' => 'gte', 'threshold' => 1, 'bonus_amount' => 100, 'bonus_type' => 'fixed', 'period_type' => 'monthly', 'status' => 'active']);
        $earning = EmployeeBonusEarning::create(['employee_id' => $this->employee('Moderator')->id, 'bonus_rule_id' => $rule->id, 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(), 'metric_value' => 1, 'bonus_amount' => 100, 'status' => 'pending']);
        $this->actingAs($finance)->post('/admin/bonuses/'.$earning->id.'/approve')->assertForbidden();
        $this->actingAs($this->hrManager())->post('/admin/bonuses/'.$earning->id.'/approve')->assertSessionHas('success');
        $this->assertSame('approved', $earning->fresh()->status);
    }

    public function test_executive_dashboard_matches_merged_report_totals(): void
    {
        $scope = $this->scope();
        DailyPerformanceReport::create(['campaign_id' => $scope['campaign']->id, 'report_date' => today(), 'status' => 'admin_approved', 'spend' => 100, 'orders' => 10]);

        $this->actingAs($this->admin())->get('/admin/executive-performance')
            ->assertOk()->assertSee('USD 100.00')->assertSee('BDT 2,000.00');
    }

    private function scope(): array
    {
        $client = Client::create(['company_name' => 'Ops Client '.uniqid(), 'phone' => '1', 'client_rate' => 145, 'buy_rate' => 125, 'status' => 'active']);
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
        $bm = BusinessManager::create(['bm_name' => 'Ops BM', 'bm_id' => 'BM-'.uniqid(), 'owner_name' => 'Owner', 'owner_email' => uniqid().'@test.com', 'verification_status' => 'verified', 'status' => 'active']);
        $account = AdAccount::create(['ad_account_name' => 'Ops Account', 'ad_account_id' => 'AD-'.uniqid(), 'business_manager_id' => $bm->id, 'client_id' => $client->id, 'currency' => 'USD', 'status' => 'active']);
        $page = ClientPage::create(['client_id' => $client->id, 'business_manager_id' => $bm->id, 'ad_account_id' => $account->id, 'page_name' => 'Ops Page', 'platform' => 'Facebook', 'status' => 'active']);
        $campaign = Campaign::create(['campaign_name' => 'Ops Campaign', 'campaign_id' => 'CAM-'.uniqid(), 'business_manager_id' => $bm->id, 'ad_account_id' => $account->id, 'client_id' => $client->id, 'client_page_id' => $page->id, 'objective' => 'sales', 'status' => 'active', 'start_date' => today()->subMonth()]);

        return compact('client', 'bm', 'account', 'page', 'campaign');
    }

    private function employee(string $roleName, ?string $name = null): Employee
    {
        $role = EmployeeRole::where('name', $roleName)->firstOrFail();

        return Employee::create(['employee_id' => 'KPI-'.uniqid(), 'name' => $name ?: $roleName.' Employee', 'department' => 'Facebook Operations', 'department_id' => Department::where('name', 'Facebook Operations')->valueOrFail('id'), 'role' => $role->name, 'role_id' => $role->id, 'joining_date' => today()->subMonth(), 'confirmation_date' => today()->subWeeks(3), 'status' => 'active', 'monthly_salary' => 10000]);
    }

    private function employeeWithUser(string $roleName, string $permissionRole): array
    {
        $user = User::factory()->create(['role' => 'employee', 'status' => 'active']);
        $user->roles()->sync([Role::where('slug', $permissionRole)->valueOrFail('id')]);
        $employee = $this->employee($roleName);
        $employee->update(['user_id' => $user->id]);

        return [$user, $employee];
    }

    private function submission(Employee $employee, array $scope, string $type, array $overrides = []): EmployeeDailySubmission
    {
        return EmployeeDailySubmission::create(array_merge(['employee_id' => $employee->id, 'client_id' => $scope['client']->id, 'page_id' => $scope['page']->id, 'campaign_id' => $scope['campaign']->id, 'bm_id' => $scope['bm']->id, 'ad_account_id' => $scope['account']->id, 'submission_date' => today(), 'submission_type' => $type, 'orders' => $type === 'order' ? 0 : null, 'dollar_spend' => $type === 'spend' ? 0 : null, 'status' => 'pending', 'submission_key' => EmployeeDailySubmission::duplicateKey($employee->id, today()->toDateString(), $type, $scope['page']->id, $scope['campaign']->id)], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function hrManager(): User
    {
        return $this->staff('hr_manager');
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user->roles()->sync([Role::where('slug', $role)->valueOrFail('id')]);

        return $user->fresh();
    }
}
