<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeePayroll;
use App\Models\FinanceAccount;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseNavigationPermissionPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_ad_manager_auditor_and_monitor_are_separated_by_direct_routes(): void
    {
        $moderator = $this->staff('moderator', 'employee');
        $this->actingAs($moderator)->get('/admin/marketing-operations/moderator/operations')->assertOk();
        $this->actingAs($moderator)->get('/admin/marketing-operations/ad-manager/operations')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/marketing-operations/auditor/operations')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/marketing-operations/agency')->assertForbidden();

        $adManager = $this->staff('ad_manager', 'employee');
        $this->actingAs($adManager)->get('/admin/marketing-operations/ad-manager/operations')->assertOk();
        $this->actingAs($adManager)->get('/admin/marketing-operations/moderator/operations')->assertForbidden();

        $auditor = $this->staff('auditor', 'employee');
        $this->actingAs($auditor)->get('/admin/marketing-operations/auditor/operations')->assertOk();
        $this->actingAs($auditor)->post('/admin/marketing-operations/moderator/operations')->assertForbidden();

        $monitor = $this->staff('monitor', 'employee');
        $submission = $this->submission();
        $this->actingAs($monitor)->get('/admin/marketing-operations/monitor/operations')->assertOk();
        $this->actingAs($monitor)->post('/admin/employee-submissions/'.$submission->id.'/merge')->assertForbidden();
    }

    public function test_hr_can_manage_employee_workflow_but_cannot_confirm_salary_payment_without_pay_permission(): void
    {
        $hr = $this->staff('hr_manager');
        $payroll = $this->approvedPayroll();

        $this->actingAs($hr)->get('/admin/employees')->assertOk();
        $this->actingAs($hr)->get('/admin/work-status')->assertOk();
        $this->actingAs($hr)->get('/admin/payroll')->assertOk();
        $this->actingAs($hr)->post('/admin/payroll/'.$payroll->id.'/approve')->assertRedirect('/admin/payroll/'.$payroll->id);
        $this->actingAs($hr)->post('/admin/finance/accounts')->assertForbidden();
        $this->actingAs($hr)->post('/admin/payroll/'.$payroll->id.'/confirm-payment', $this->paymentPayload())->assertForbidden();
    }

    public function test_finance_manager_can_manage_finance_and_client_payments_but_not_employee_hr(): void
    {
        $finance = $this->staff('finance_manager');
        $payroll = $this->approvedPayroll();

        $this->actingAs($finance)->get('/admin/financial-management')->assertOk();
        $this->actingAs($finance)->get('/admin/finance/accounts')->assertOk();
        $this->actingAs($finance)->get('/admin/salary-payments')->assertOk();
        $this->actingAs($finance)->get('/admin/employees')->assertForbidden();
        $this->actingAs($finance)->post('/admin/payroll/'.$payroll->id.'/confirm-payment', $this->paymentPayload())->assertForbidden();

        $this->grant($finance, 'payroll.pay');
        $this->assertTrue($finance->fresh()->hasPermission('payroll.pay'));
    }

    public function test_business_manager_and_page_manager_have_separate_workspaces(): void
    {
        $business = $this->staff('business_manager');
        $this->actingAs($business)->get('/admin/business-managers')->assertOk();
        $this->actingAs($business)->get('/admin/ad-accounts')->assertOk();
        $this->actingAs($business)->get('/admin/client-pages')->assertForbidden();
        $this->actingAs($business)->get('/admin/financial-management')->assertForbidden();

        $page = $this->staff('page_manager');
        $this->actingAs($page)->get('/admin/client-pages')->assertOk();
        $this->actingAs($page)->get('/admin/campaigns')->assertOk();
        $this->actingAs($page)->get('/admin/business-managers')->assertForbidden();
        $this->actingAs($page)->get('/admin/finance/accounts')->assertForbidden();
    }

    public function test_menu_visibility_matches_direct_route_access(): void
    {
        $page = $this->staff('page_manager');
        $request = request()->create('/admin/client-pages', 'GET');
        $request->setUserResolver(fn () => $page);

        $sections = collect(app(NavigationService::class)->forRequest($request)['sections']);
        $labels = $sections->pluck('label')->all();

        $this->assertContains('Page Management', $labels);
        $this->assertNotContains('Business Management', $labels);
        $this->assertNotContains('Finance', $labels);

        $this->actingAs($page)->get('/admin/client-pages')->assertOk();
        $this->actingAs($page)->get('/admin/business-managers')->assertForbidden();
        $this->actingAs($page)->get('/admin/finance/accounts')->assertForbidden();
    }

    public function test_clients_and_employees_remain_blocked_from_admin_routes(): void
    {
        $client = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $employee = User::factory()->create(['role' => 'employee', 'status' => 'active']);

        $this->actingAs($client)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($employee)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_legacy_daily_reports_permission_does_not_grant_business_management(): void
    {
        $role = Role::create(['name' => 'Legacy Daily Reports', 'slug' => 'legacy_daily_reports']);
        $permission = Permission::firstOrCreate(
            ['key' => 'daily_reports.view'],
            ['name' => 'Daily Reports View', 'module' => 'legacy']
        );
        $role->permissions()->sync([$permission->id]);
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user)->get('/admin/daily-reports')->assertForbidden();
        $this->actingAs($user)->get('/admin/business-managers')->assertForbidden();
    }

    private function staff(string $role, string $userRole = 'admin'): User
    {
        $user = User::factory()->create(['role' => $userRole, 'status' => 'active']);
        $user->roles()->sync([Role::where('slug', $role)->valueOrFail('id')]);

        if ($userRole === 'employee') {
            Employee::create([
                'user_id' => $user->id,
                'employee_id' => 'PH2-'.uniqid(),
                'name' => 'Phase 2 Staff',
                'department' => 'Operations',
                'role' => str($role)->replace('_', ' ')->title()->toString(),
                'joining_date' => '2026-06-01',
                'confirmation_date' => '2026-06-01',
                'status' => 'active',
                'monthly_salary' => 10000,
            ]);
        }

        return $user->fresh();
    }

    private function grant(User $user, string $permissionKey): void
    {
        $permission = Permission::where('key', $permissionKey)->firstOrFail();
        $role = $user->roles()->firstOrFail();
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    private function approvedPayroll(): EmployeePayroll
    {
        $client = Client::create([
            'company_name' => 'Phase 2 Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'employee_id' => 'PH2-PAY-'.uniqid(),
            'name' => 'Payroll Employee',
            'department' => 'Employees',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);
        FinanceAccount::create([
            'id' => 500,
            'account_type' => 'bank',
            'account_name' => 'Phase 2 Bank',
            'provider_name' => 'Bank',
            'account_number' => '500',
            'currency' => 'BDT',
            'current_balance' => 50000,
            'status' => 'active',
        ]);

        return $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 5000,
            'paid_amount' => 0,
            'payroll_status' => 'approved',
            'payment_status' => 'unpaid',
        ]);
    }

    private function submission(): EmployeeDailySubmission
    {
        $employee = Employee::create([
            'employee_id' => 'PH2-SUB-'.uniqid(),
            'name' => 'Submission Employee',
            'department' => 'Operations',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        return EmployeeDailySubmission::create([
            'employee_id' => $employee->id,
            'submission_date' => '2026-07-12',
            'submission_type' => 'order',
            'orders' => 10,
            'confirmed_orders' => 8,
            'cancelled_orders' => 2,
            'status' => 'approved',
            'submission_key' => 'PH2-SUB-'.uniqid(),
        ]);
    }

    private function paymentPayload(): array
    {
        return [
            'payment_date' => '2026-07-12',
            'finance_account_id' => 500,
            'transaction_id' => 'PH2-PAY',
            'payment_note' => 'Permission phase payment test.',
        ];
    }
}
