<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\FinanceAccount;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\ClientFundLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClientDualFundArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_fund_deposit_creates_employee_salary_ledger_only(): void
    {
        $client = $this->client();
        $account = $this->financeAccount();

        $this->actingAs($this->admin())->post('/admin/salary-payments', [
            'client_id' => $client->id,
            'fund_type' => 'employee_salary',
            'amount' => 15000,
            'payment_method' => 'Bank',
            'transaction_id' => 'SALARY-FUND-1',
            'payment_date' => '2026-06-20',
            'status' => 'approved',
            'finance_account_id' => $account->id,
        ])->assertRedirect('/admin/salary-payments');

        $this->assertSame(15000.0, $client->fresh()->salary_fund_balance());
        $this->assertSame(0.0, $client->fresh()->ads_fund_balance());
        $this->assertDatabaseHas('client_fund_ledgers', [
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'direction' => ClientFundLedger::DIRECTION_CREDIT,
            'amount_bdt' => 15000,
            'balance_before' => 0,
            'balance_after' => 15000,
            'reference' => 'SALARY-FUND-1',
        ]);
    }

    public function test_ads_fund_deposit_creates_facebook_ads_ledger_only(): void
    {
        $client = $this->client();
        $account = $this->financeAccount();

        $this->actingAs($this->admin())->post('/admin/salary-payments', [
            'client_id' => $client->id,
            'fund_type' => 'facebook_ads',
            'amount' => 25000,
            'payment_method' => 'Bank',
            'transaction_id' => 'ADS-FUND-1',
            'payment_date' => '2026-06-20',
            'status' => 'approved',
            'finance_account_id' => $account->id,
        ])->assertRedirect('/admin/salary-payments');

        $this->assertSame(0.0, $client->fresh()->salary_fund_balance());
        $this->assertSame(25000.0, $client->fresh()->ads_fund_balance());
        $this->assertDatabaseHas('client_fund_ledgers', [
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_FACEBOOK_ADS,
            'direction' => ClientFundLedger::DIRECTION_CREDIT,
            'amount_bdt' => 25000,
        ]);
    }

    public function test_payroll_confirm_payment_debits_salary_fund_once(): void
    {
        $client = $this->client();
        $this->ledger($client, ClientFundLedger::FUND_EMPLOYEE_SALARY, 'credit', 20000);
        $account = $this->financeAccount(['current_balance' => 50000]);
        $payroll = $this->payroll($client, ['payroll_status' => 'approved', 'payable_salary' => 10000]);

        $payload = [
            'payment_date' => '2026-06-20',
            'finance_account_id' => $account->id,
            'transaction_id' => 'PAYROLL-PAID-1',
            'payment_note' => 'Salary paid.',
        ];

        $this->actingAs($this->admin())->post('/admin/payroll/'.$payroll->id.'/confirm-payment', $payload)
            ->assertRedirect('/admin/payroll/'.$payroll->id);
        $this->actingAs($this->admin())->post('/admin/payroll/'.$payroll->id.'/confirm-payment', $payload)
            ->assertSessionHas('success', 'This salary payment is already confirmed.');

        $this->assertSame(10000.0, $client->fresh()->salary_fund_balance());
        $this->assertSame(1, ClientFundLedger::where('source_type', EmployeePayroll::class)
            ->where('source_id', $payroll->id)
            ->where('fund_type', ClientFundLedger::FUND_EMPLOYEE_SALARY)
            ->where('direction', ClientFundLedger::DIRECTION_DEBIT)
            ->count());
    }

    public function test_payroll_confirm_payment_allows_negative_salary_fund_and_records_due_from_client(): void
    {
        $client = $this->client();
        $this->ledger($client, ClientFundLedger::FUND_EMPLOYEE_SALARY, ClientFundLedger::DIRECTION_DEBIT, 7083.48);
        $account = $this->financeAccount(['current_balance' => 20000]);
        $payroll = $this->payroll($client, ['payroll_status' => 'approved', 'payable_salary' => 5000]);

        $payload = [
            'payment_date' => '2026-06-20',
            'finance_account_id' => $account->id,
            'transaction_id' => 'PAYROLL-NEGATIVE-FUND-1',
            'payment_note' => 'Salary paid before client fund deposit.',
        ];

        $this->actingAs($this->admin())->post('/admin/payroll/'.$payroll->id.'/confirm-payment', $payload)
            ->assertRedirect('/admin/payroll/'.$payroll->id);

        $this->assertSame(-12083.48, $client->fresh()->salary_fund_balance());
        $this->assertSame(15000.0, (float) $account->fresh()->current_balance);
        $this->assertSame('paid', $payroll->fresh()->payment_status);
        $this->assertStringStartsWith('NSYS-SP-2026-', (string) $payroll->fresh()->salary_receipt_number);

        $this->assertDatabaseHas('client_fund_ledgers', [
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'direction' => ClientFundLedger::DIRECTION_DEBIT,
            'amount_bdt' => 5000,
            'balance_before' => -7083.48,
            'balance_after' => -12083.48,
            'source_type' => EmployeePayroll::class,
            'source_id' => $payroll->id,
            'reference' => 'PAYROLL-NEGATIVE-FUND-1',
        ]);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'employee_payroll_id' => $payroll->id,
            'transaction_type' => 'salary_payment',
            'amount' => 5000,
            'reference' => 'PAYROLL-NEGATIVE-FUND-1',
        ]);

        $this->actingAs($this->admin())->post('/admin/payroll/'.$payroll->id.'/confirm-payment', $payload)
            ->assertSessionHas('success', 'This salary payment is already confirmed.');

        $this->assertSame(-12083.48, $client->fresh()->salary_fund_balance());
        $this->assertSame(15000.0, (float) $account->fresh()->current_balance);
        $this->assertSame(1, ClientFundLedger::where('source_type', EmployeePayroll::class)
            ->where('source_id', $payroll->id)
            ->where('fund_type', ClientFundLedger::FUND_EMPLOYEE_SALARY)
            ->where('direction', ClientFundLedger::DIRECTION_DEBIT)
            ->count());
    }

    public function test_unrelated_salary_fund_debit_still_blocks_negative_balance(): void
    {
        $client = $this->client();

        $this->expectException(ValidationException::class);

        app(ClientFundLedgerService::class)->debit($client, ClientFundLedger::FUND_EMPLOYEE_SALARY, 100, [
            'reference' => 'MANUAL-DEBIT-1',
            'description' => 'Manual salary fund debit.',
        ]);
    }

    public function test_confirm_payment_page_shows_projected_negative_salary_fund_warning(): void
    {
        $client = $this->client();
        $this->ledger($client, ClientFundLedger::FUND_EMPLOYEE_SALARY, ClientFundLedger::DIRECTION_DEBIT, 7083.48);
        $payroll = $this->payroll($client, ['payroll_status' => 'approved', 'payable_salary' => 5000]);

        $this->actingAs($this->admin())->get('/admin/payroll/'.$payroll->id)
            ->assertOk()
            ->assertSee('Client Employee Salary Fund is insufficient.')
            ->assertSee('Due From Client')
            ->assertSee('BDT -7,083.48')
            ->assertSee('BDT 5,000.00')
            ->assertSee('BDT -12,083.48');
    }

    public function test_confirm_payment_validation_errors_are_visible_after_redirect(): void
    {
        $client = $this->client();
        $payroll = $this->payroll($client, ['payroll_status' => 'approved', 'payable_salary' => 5000]);

        $this->followingRedirects()
            ->actingAs($this->admin())
            ->from('/admin/payroll/'.$payroll->id)
            ->post('/admin/payroll/'.$payroll->id.'/confirm-payment', [])
            ->assertOk()
            ->assertSee('Please fix the following:')
            ->assertSee('payment date field is required');
    }

    public function test_performance_report_debits_ads_fund_and_update_is_idempotent(): void
    {
        [$client, $campaign] = $this->campaign();
        $this->ledger($client, ClientFundLedger::FUND_FACEBOOK_ADS, 'credit', 50000);

        $this->actingAs($this->admin())->post('/admin/daily-reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-20',
            'spend' => 100,
            'orders' => 10,
        ])->assertRedirect();

        $this->assertSame(35000.0, $client->fresh()->ads_fund_balance());
        $this->assertSame(2, ClientFundLedger::where('client_id', $client->id)->count());

        $this->actingAs($this->admin())->post('/admin/daily-reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-20',
            'spend' => 100,
            'orders' => 10,
            'update_existing' => 1,
        ])->assertRedirect();

        $this->assertSame(35000.0, $client->fresh()->ads_fund_balance());
        $this->assertSame(2, ClientFundLedger::where('client_id', $client->id)->count());
    }

    public function test_performance_report_is_blocked_when_ads_fund_is_insufficient(): void
    {
        [, $campaign] = $this->campaign();

        $this->actingAs($this->admin())->post('/admin/daily-reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-20',
            'spend' => 100,
            'orders' => 10,
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('daily_performance_reports', 0);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function client(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'company_name' => 'Dual Fund Client',
            'phone' => '123',
            'client_rate' => 150,
            'buy_rate' => 120,
            'status' => 'active',
        ], $overrides));
    }

    private function financeAccount(array $overrides = []): FinanceAccount
    {
        return FinanceAccount::create(array_merge([
            'account_type' => 'bank',
            'account_name' => 'Dual Fund Bank',
            'provider_name' => 'Bank',
            'account_number' => '123',
            'currency' => 'BDT',
            'current_balance' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function payroll(Client $client, array $overrides = []): EmployeePayroll
    {
        $employee = Employee::create([
            'employee_id' => 'DUAL-'.uniqid(),
            'name' => 'Dual Fund Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        return $employee->payrolls()->create(array_merge([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 0,
            'payroll_status' => 'generated',
            'payment_status' => 'unpaid',
        ], $overrides));
    }

    private function campaign(): array
    {
        $client = $this->client();
        $bm = BusinessManager::create([
            'bm_name' => 'Dual BM',
            'bm_id' => 'BM-'.uniqid(),
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $account = AdAccount::create([
            'ad_account_name' => 'Dual Ad Account',
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
            'page_name' => 'Dual Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'campaign_name' => 'Dual Campaign',
            'campaign_id' => 'CMP-'.uniqid(),
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'sales',
            'status' => 'active',
        ]);

        return [$client, $campaign];
    }

    private function ledger(Client $client, string $fundType, string $direction, float $amount): ClientFundLedger
    {
        $before = $client->fundLedgers()
            ->where('fund_type', $fundType)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance');
        $after = $direction === 'credit' ? $before + $amount : $before - $amount;

        return ClientFundLedger::create([
            'client_id' => $client->id,
            'fund_type' => $fundType,
            'direction' => $direction,
            'amount_bdt' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'reference' => 'TEST-FUND',
            'description' => 'Test fund movement.',
        ]);
    }
}
