<?php

namespace Tests\Feature;

use App\Models\Client;
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

        $response = $this->actingAs($admin)->post('/admin/salary-payments', [
            'client_id' => $client->id,
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
        $this->assertNotNull($payment->approved_at);
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
