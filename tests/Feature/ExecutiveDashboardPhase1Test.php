<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\FinanceAccount;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveDashboardPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_executive_dashboard_loads_for_super_admin_with_read_only_intelligence(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 7)->setTime(12, 0));

        [$client, $campaign] = $this->campaignScope();
        DailyPerformanceReport::create([
            'campaign_id' => $campaign->id,
            'report_date' => today(),
            'spend' => 10,
            'orders' => 2,
            'status' => 'admin_approved',
        ]);
        $this->fundLedger($client, ClientFundLedger::FUND_EMPLOYEE_SALARY, 'credit', 1000);
        $this->fundLedger($client, ClientFundLedger::FUND_FACEBOOK_ADS, 'credit', 2000);
        FinanceAccount::create([
            'account_type' => 'bank',
            'account_name' => 'Executive Bank',
            'currency' => 'BDT',
            'current_balance' => 5000,
            'status' => 'active',
        ]);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => today(),
            'amount' => 700,
            'payment_method' => 'Bank',
            'transaction_id' => 'EXEC-PAY',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $employee = Employee::create([
            'employee_id' => 'EXEC-EMP',
            'name' => 'Executive Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 5000,
        ]);
        EmployeePayroll::create([
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'salary_month' => today(),
            'payable_salary' => 500,
            'paid_amount' => 500,
            'payroll_status' => 'paid',
            'payment_status' => 'paid',
            'payment_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/executive-performance');

        $response->assertOk();
        $response->assertSee('Executive Dashboard');
        $response->assertSee('Total Orders');
        $response->assertSee('2');
        $response->assertSee('USD 10.00');
        $response->assertSee('BDT 1,450.00');
        $response->assertSee('BDT 150.00');
        $response->assertSee('BDT 500.00');
        $response->assertSee('Employee Salary Fund Balance');
        $response->assertSee('Facebook Ads Fund Balance');
        $response->assertSee('Live Alerts');
        $response->assertSee('Trend Charts');
        $response->assertSee('Quick Actions');
        $response->assertSee('Global Search');
    }

    public function test_only_super_admin_or_agency_owner_can_access_executive_dashboard(): void
    {
        $financeManager = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $financeManager->roles()->sync([Role::where('slug', 'finance_manager')->valueOrFail('id')]);

        $this->actingAs($financeManager)->get('/admin/executive-performance')->assertForbidden();

        $ownerRole = Role::create(['name' => 'Agency Owner', 'slug' => 'agency_owner']);
        $ownerRole->permissions()->sync([Permission::where('key', 'dashboard.view')->valueOrFail('id')]);
        $owner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $owner->roles()->sync([$ownerRole->id]);

        $this->actingAs($owner)->get('/admin/executive-performance')->assertOk();
    }

    public function test_executive_dashboard_exports_are_available(): void
    {
        $admin = $this->admin();

        $csv = $this->actingAs($admin)->get('/admin/executive-performance/export/csv');
        $excel = $this->actingAs($admin)->get('/admin/executive-performance/export/excel');
        $pdf = $this->actingAs($admin)->get('/admin/executive-performance/export/pdf');

        $csv->assertOk();
        $csv->assertDownload('executive-dashboard.csv');
        $this->assertStringContainsString('Today,"Total Orders"', $csv->streamedContent());

        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $excel->assertSee('Executive Dashboard Export');

        $pdf->assertOk();
        $pdf->assertSee('Executive Dashboard');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function campaignScope(): array
    {
        $client = Client::create([
            'company_name' => 'Executive Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Executive BM',
            'bm_id' => 'EXEC-BM-' . uniqid(),
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $account = AdAccount::create([
            'ad_account_name' => 'Executive Ad Account',
            'ad_account_id' => 'EXEC-AD-' . uniqid(),
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'currency' => 'USD',
            'status' => 'active',
        ]);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'page_name' => 'Executive Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'campaign_name' => 'Executive Campaign',
            'campaign_id' => 'EXEC-CAM-' . uniqid(),
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'sales',
            'status' => 'active',
        ]);

        return [$client, $campaign];
    }

    private function fundLedger(Client $client, string $fundType, string $direction, float $amount): ClientFundLedger
    {
        $balanceBefore = (float) ClientFundLedger::where('client_id', $client->id)
            ->where('fund_type', $fundType)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance');

        return ClientFundLedger::create([
            'client_id' => $client->id,
            'fund_type' => $fundType,
            'direction' => $direction,
            'amount_bdt' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $direction === 'credit' ? $balanceBefore + $amount : $balanceBefore - $amount,
            'reference' => 'EXEC-FUND',
            'description' => 'Executive dashboard test fund movement.',
        ]);
    }
}
