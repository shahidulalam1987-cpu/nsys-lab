<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\FinanceAccount;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\SalaryFundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientFundPaymentAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_receive_approved_client_fund_payment(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $client = $this->client();
        $account = FinanceAccount::create([
            'account_type' => 'bank',
            'account_name' => 'Client Fund Bank',
            'currency' => 'BDT',
            'current_balance' => 1000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/admin/salary-payments', [
            'client_id' => $client->id,
            'finance_account_id' => $account->id,
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'amount' => 12000,
            'payment_method' => 'Bank',
            'transaction_id' => 'FUND-123',
            'payment_date' => '2026-06-15',
            'screenshot' => UploadedFile::fake()->image('fund-proof.jpg'),
            'note' => 'Client fund received',
            'status' => 'approved',
        ]);

        $payment = SalaryPayment::first();

        $response->assertRedirect('/admin/salary-payments');
        $this->assertSame($client->id, $payment->client_id);
        $this->assertSame('2026-06-15', $payment->salary_month->toDateString());
        $this->assertSame('approved', $payment->status);
        $this->assertSame(ClientFundLedger::FUND_EMPLOYEE_SALARY, $payment->fund_type);
        $this->assertNotNull($payment->approved_at);
        $this->assertSame(13000.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'client_payment',
            'direction' => 'credit',
            'reference_type' => SalaryPayment::class,
            'reference_id' => $payment->id,
            'amount' => 12000,
            'old_balance' => 1000,
            'new_balance_snapshot' => 13000,
            'currency' => 'BDT',
        ]);
        $this->assertDatabaseHas('client_fund_ledgers', [
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'direction' => ClientFundLedger::DIRECTION_CREDIT,
            'source_type' => SalaryPayment::class,
            'source_id' => $payment->id,
            'amount_bdt' => 12000,
        ]);
        Storage::disk('public')->assertExists($payment->screenshot);
    }

    public function test_admin_client_payment_pages_use_client_fund_wording(): void
    {
        $admin = $this->admin();

        $history = $this->actingAs($admin)->get('/admin/salary-payments');
        $pending = $this->actingAs($admin)->get('/admin/salary-payments/pending');
        $create = $this->actingAs($admin)->get('/admin/salary-payments/create');

        $history->assertOk();
        $history->assertSee('Client Payment History');
        $history->assertSee('Payment Date');
        $history->assertSee('No client payment history found.');
        $history->assertDontSee('Salary Month');
        $history->assertDontSee('No salary payments found.');

        $pending->assertOk();
        $pending->assertSee('Pending Client Payments');
        $pending->assertSee('Submitted Date');
        $pending->assertSee('No pending client payments found.');
        $pending->assertDontSee('Salary Month');

        $create->assertOk();
        $create->assertSee('Receive Client Payment');
        $create->assertSee('Save Payment');
        $create->assertSee('Transaction ID / Reference');
        $create->assertSee('Payment Date');
    }

    public function test_client_fund_summary_counts_payments_across_selected_month(): void
    {
        $client = $this->client();
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-15',
            'amount' => 5000,
            'payment_method' => 'bKash',
            'transaction_id' => 'MID-MONTH',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $fund = app(SalaryFundService::class)->build($client, '2026-06');

        $this->assertSame(5000.0, $fund['summary']['paid_to_nsys']);
    }

    public function test_client_payment_approval_stores_audit_metadata_receipt_and_references(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $account = FinanceAccount::create([
            'account_type' => 'bank',
            'account_name' => 'Audit Bank',
            'currency' => 'BDT',
            'current_balance' => 1000,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/salary-payments', [
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'amount' => 60000,
            'payment_method' => 'Bank',
            'transaction_id' => 'AUDIT-CLIENT-1',
            'payment_date' => '2026-07-09',
            'status' => 'pending',
        ]);

        $payment = SalaryPayment::firstOrFail();
        $this->assertStringStartsWith('NSYS-CP-2026-', $payment->receipt_number);

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->withHeader('User-Agent', 'NSYS-Test-Agent')
            ->actingAs($admin)
            ->post('/admin/salary-payments/' . $payment->id . '/approve', [
                'finance_account_id' => $account->id,
            ])
            ->assertRedirect();

        $payment->refresh();
        $financeLedger = $payment->financeLedger();
        $clientFundLedger = $payment->clientFundLedger();

        $this->assertSame($admin->id, $payment->approved_by);
        $this->assertSame('10.10.10.10', $payment->approved_ip);
        $this->assertSame('NSYS-Test-Agent', $payment->approved_user_agent);
        $this->assertNotNull($financeLedger);
        $this->assertNotNull($clientFundLedger);

        $details = $this->actingAs($admin)->get('/admin/salary-payments/' . $payment->id);
        $details->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertSee('Approval Timeline')
            ->assertSee('Finance Ledger ID')
            ->assertSee((string) $financeLedger->id)
            ->assertSee('Client Fund Ledger ID')
            ->assertSee((string) $clientFundLedger->id)
            ->assertSee('10.10.10.10')
            ->assertSee('QR Code placeholder');

        $search = $this->actingAs($admin)->get('/admin/salary-payments?search=' . urlencode($payment->receipt_number));
        $search->assertOk()->assertSee($payment->receipt_number);
    }

    public function test_client_payment_receipt_and_approval_metadata_are_immutable(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $payment = SalaryPayment::create([
            'client_id' => $client->id,
            'receipt_number' => 'NSYS-CP-2026-000777',
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'salary_month' => '2026-07-09',
            'amount' => 1000,
            'payment_method' => 'Bank',
            'transaction_id' => 'IMMUTABLE-1',
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'approved_ip' => '1.1.1.1',
            'approved_user_agent' => 'Original Agent',
        ]);

        $payment->update([
            'receipt_number' => 'NSYS-CP-2026-999999',
            'approved_by' => User::factory()->create(['role' => 'admin'])->id,
            'approved_ip' => '2.2.2.2',
            'approved_user_agent' => 'Changed Agent',
        ]);

        $payment->refresh();
        $this->assertSame('NSYS-CP-2026-000777', $payment->receipt_number);
        $this->assertSame($admin->id, $payment->approved_by);
        $this->assertSame('1.1.1.1', $payment->approved_ip);
        $this->assertSame('Original Agent', $payment->approved_user_agent);
    }

    public function test_client_payment_pdf_uses_receipt_number(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $payment = SalaryPayment::create([
            'client_id' => $client->id,
            'receipt_number' => 'NSYS-CP-2026-000888',
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'salary_month' => '2026-07-09',
            'amount' => 1000,
            'payment_method' => 'Bank',
            'transaction_id' => 'PDF-CLIENT-1',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/salary-payments/' . $payment->id . '/receipt-pdf');

        $response->assertOk();
        $this->assertStringContainsString(
            'client-payment-NSYS-CP-2026-000888.pdf',
            $response->headers->get('content-disposition')
        );
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function client(): Client
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Client Fund Test',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }
}
