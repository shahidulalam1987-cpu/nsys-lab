<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFundDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_client_fund_dashboard_summary_and_client_rows(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Fund Client A']);
        $otherClient = $this->client(['company_name' => 'Fund Client B']);
        $employee = $this->employee();

        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-10',
            'amount' => 20000,
            'payment_method' => 'Bank',
            'transaction_id' => 'APPROVED-1',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->ledger($client, 'employee_salary', 'credit', 8000, 'PROFILE-FUND');
        $this->ledger($client, 'employee_salary', 'credit', 20000, 'APPROVED-1');
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-11',
            'amount' => 5000,
            'payment_method' => 'bKash',
            'transaction_id' => 'PENDING-1',
            'status' => 'pending',
        ]);
        SalaryPayment::create([
            'client_id' => $otherClient->id,
            'salary_month' => '2026-06-12',
            'amount' => 3000,
            'payment_method' => 'Nagad',
            'transaction_id' => 'PENDING-2',
            'status' => 'pending',
        ]);

        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 15000,
            'paid_amount' => 10000,
            'status' => 'partial',
        ]);
        $this->ledger($client, 'employee_salary', 'debit', 10000, 'PAYROLL-' . $payroll->id);
        $otherPayroll = $employee->payrolls()->create([
            'client_id' => $otherClient->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 8000,
            'paid_amount' => 8000,
            'status' => 'paid',
        ]);
        $this->ledger($otherClient, 'employee_salary', 'debit', 8000, 'PAYROLL-' . $otherPayroll->id, true);

        $response = $this->actingAs($admin)->get('/admin/client-fund');

        $response->assertOk();
        $response->assertSee('Client Dual Fund Dashboard');
        $response->assertSee('Salary Fund Received');
        $response->assertSee('BDT 28,000.00');
        $response->assertSee('Salary Fund Used');
        $response->assertSee('BDT 18,000.00');
        $response->assertSee('Salary Fund Balance');
        $response->assertSee('BDT 10,000.00');
        $response->assertSee('Ads Fund Received');
        $response->assertSee('Fund Client A');
        $response->assertSee('/admin/client-fund/' . $client->id . '/details', false);
    }

    public function test_admin_can_view_client_fund_detail_ledger(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Ledger Client']);
        $employee = $this->employee();

        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 10000,
            'payment_method' => 'Bank',
            'transaction_id' => 'LEDGER-CREDIT',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->ledger($client, 'employee_salary', 'credit', 10000, 'LEDGER-CREDIT');
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payment_date' => '2026-06-06',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-06',
            'payable_salary' => 2000,
            'paid_amount' => 2000,
            'status' => 'paid',
        ]);
        $this->ledger($client, 'employee_salary', 'debit', 2000, 'PAYROLL-' . $payroll->id);

        $response = $this->actingAs($admin)->get('/admin/client-fund/' . $client->id . '/details');

        $response->assertOk();
        $response->assertSee('Client Fund Details');
        $response->assertSee('Ledger Client');
        $response->assertSee('Transaction Ledger');
        $response->assertSee('Employee Salary Fund Credit');
        $response->assertSee('Employee Salary Fund Debit');
        $response->assertSee('BDT 10,000.00');
        $response->assertSee('BDT 2,000.00');
        $response->assertSee('BDT 8,000.00');
    }

    public function test_admin_can_filter_and_export_client_fund_detail_ledger(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Filtered Ledger Client']);
        $employee = $this->employee();

        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 12000,
            'payment_method' => 'Bank',
            'transaction_id' => 'FILTER-CREDIT',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->ledger($client, 'employee_salary', 'credit', 12000, 'FILTER-CREDIT');
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payment_date' => '2026-06-05',
            'payable_salary' => 3000,
            'paid_amount' => 3000,
            'status' => 'paid',
        ]);
        $this->ledger($client, 'employee_salary', 'debit', 3000, 'PAYROLL-' . $payroll->id);

        $response = $this->actingAs($admin)->get('/admin/client-fund/' . $client->id . '/details?fund_type=employee_salary');

        $response->assertOk();
        $response->assertSee('Employee Salary Fund Debit');
        $response->assertSee('FILTER-CREDIT');

        $csv = $this->actingAs($admin)->get('/admin/client-fund/' . $client->id . '/details/export/csv?fund_type=employee_salary');
        $excel = $this->actingAs($admin)->get('/admin/client-fund/' . $client->id . '/details/export/excel?fund_type=employee_salary');

        $csv->assertOk();
        $csv->assertDownload('client-fund-ledger-' . $client->id . '.csv');
        $this->assertStringContainsString('Employee Salary Fund Debit', $csv->streamedContent());

        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $excel->assertSee('Employee Salary Fund Debit');
    }

    public function test_admin_can_export_client_fund_dashboard_csv_and_excel(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Export Fund Client']);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 7000,
            'payment_method' => 'Bank',
            'transaction_id' => 'EXPORT-1',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->ledger($client, 'employee_salary', 'credit', 7000, 'EXPORT-1');

        $csv = $this->actingAs($admin)->get('/admin/client-fund/export/csv');
        $excel = $this->actingAs($admin)->get('/admin/client-fund/export/excel');

        $csv->assertOk();
        $csv->assertDownload('client-fund-dashboard.csv');
        $this->assertStringContainsString('Export Fund Client', $csv->streamedContent());

        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $excel->assertSee('Export Fund Client');
    }

    public function test_salary_edit_page_warns_when_client_fund_is_insufficient(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee();
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id . '/edit');

        $response->assertOk();
        $response->assertSee('Insufficient Client Fund');
        $response->assertSee('Need: BDT 10,000.00');
        $response->assertSee('Available: BDT 0.00');
    }

    public function test_employee_profile_shows_client_fund_salary_context(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Profile Fund Client']);
        $employee = $this->employee([
            'confirmation_date' => '2026-06-10',
            'salary_day' => 10,
        ]);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 8000,
            'payment_method' => 'Bank',
            'transaction_id' => 'PROFILE-FUND',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->ledger($client, 'employee_salary', 'credit', 8000, 'PROFILE-FUND');

        $response = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);

        $response->assertOk();
        $response->assertSee('Assigned Client');
        $response->assertSee('Profile Fund Client');
        $response->assertSee('Client Fund Balance');
        $response->assertSee('BDT 8,000.00');
        $response->assertSee('Upcoming Salary Date');
        $response->assertSee('Salary Status');
    }

    public function test_client_fund_dashboard_link_is_in_clients_navigation(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/client-fund');

        $response->assertOk();
        $response->assertSee('Client Funds');
        $response->assertSee('href="/admin/client-fund"', false);
        $response->assertSee('Clients');
        $response->assertSee('Client Dashboard');
        $response->assertSee('Receive Client Payment');
        $response->assertSee('Pending Payments');
        $response->assertSee('Payment History');
    }

    public function test_client_list_uses_dual_fund_columns_instead_of_legacy_ledger_columns(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Dual Fund List Client']);

        $this->ledger($client, ClientFundLedger::FUND_EMPLOYEE_SALARY, ClientFundLedger::DIRECTION_CREDIT, 10000, 'SALARY-CREDIT');
        $this->ledger($client, ClientFundLedger::FUND_FACEBOOK_ADS, ClientFundLedger::DIRECTION_CREDIT, 7000, 'ADS-CREDIT');
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-20',
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'amount' => 1500,
            'payment_method' => 'Bank',
            'transaction_id' => 'PENDING-LIST',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin/clients');

        $response->assertOk();
        $response->assertSee('Salary Fund');
        $response->assertSee('Ads Fund');
        $response->assertSee('Combined Balance');
        $response->assertSee('Pending Payments');
        $response->assertSee('Dual Fund List Client');
        $response->assertSee('BDT 17,000.00');
        $response->assertDontSee('Spend USD');
        $response->assertDontSee('Profit');
    }

    public function test_client_details_uses_modern_funds_payments_and_daily_performance(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Modern Client Details']);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'page_name' => 'Modern Client Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Modern BM',
            'bm_id' => 'bm-modern-client',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $adAccount = AdAccount::create([
            'ad_account_name' => 'Modern Ad Account',
            'ad_account_id' => 'act_modern_client',
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'campaign_name' => 'Modern Campaign',
            'campaign_id' => 'cmp-modern-client',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $adAccount->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'sales',
            'status' => 'active',
        ]);
        DailyPerformanceReport::create([
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-20',
            'spend' => 50,
            'orders' => 10,
            'status' => 'merged',
        ]);
        $payment = SalaryPayment::create([
            'client_id' => $client->id,
            'receipt_number' => 'NSYS-CP-2026-000123',
            'salary_month' => '2026-06-20',
            'fund_type' => ClientFundLedger::FUND_FACEBOOK_ADS,
            'amount' => 7000,
            'payment_method' => 'Bank',
            'transaction_id' => 'MODERN-PAYMENT',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->ledger($client, ClientFundLedger::FUND_FACEBOOK_ADS, ClientFundLedger::DIRECTION_CREDIT, 7000, $payment->transaction_id);

        $response = $this->actingAs($admin)->get('/admin/clients/' . $client->id);

        $response->assertOk();
        $response->assertSee('Client Fund Summary');
        $response->assertSee('Client Fund Ledger');
        $response->assertSee('Recent Daily Performance');
        $response->assertSee('Modern Campaign');
        $response->assertSee('USD 50.00');
        $response->assertSee('NSYS-CP-2026-000123');
        $response->assertDontSee('Financial Summary');
        $response->assertDontSee('Recent Daily Reports');
        $response->assertDontSee('Recent Payments');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function client(array $overrides = []): Client
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create(array_merge([
            'user_id' => $clientUser->id,
            'company_name' => 'Fund Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ], $overrides));
    }

    private function employee(): Employee
    {
        return Employee::create([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Fund Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ]);
    }

    private function ledger(Client $client, string $fundType, string $direction, float $amount, string $reference, bool $allowNegative = false): ClientFundLedger
    {
        $balanceBefore = (float) ClientFundLedger::where('client_id', $client->id)
            ->where('fund_type', $fundType)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance');
        $balanceAfter = $direction === 'credit'
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        if (! $allowNegative) {
            $this->assertGreaterThanOrEqual(0, $balanceAfter);
        }

        return ClientFundLedger::create([
            'client_id' => $client->id,
            'fund_type' => $fundType,
            'direction' => $direction,
            'amount_bdt' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $reference,
            'description' => $direction === 'credit' ? 'Test fund credit.' : 'Test fund debit.',
        ]);
    }
}
