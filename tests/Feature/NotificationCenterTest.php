<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Client;
use App\Models\Employee;
use App\Models\FundingBalance;
use App\Models\SalaryPayment;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_agency_dashboard_shows_central_notification_center_alerts(): void
    {
        Carbon::setTestNow('2026-06-13');
        $admin = $this->admin();
        $client = $this->client();
        $bm = BusinessManager::create([
            'bm_name' => 'Alert BM',
            'bm_id' => 'BM-' . uniqid(),
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        AdAccount::create([
            'ad_account_name' => 'Payment Issue Account',
            'ad_account_id' => 'act_' . uniqid(),
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'threshold_amount' => 1000,
            'current_threshold_usage' => 1000,
            'current_balance' => 20,
            'monthly_billing_date' => 13,
            'status' => 'payment_issue',
        ]);
        FundingBalance::create([
            'source' => 'binance',
            'current_balance' => 50,
            'balance_date' => '2026-06-13',
        ]);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 1000,
            'payment_method' => 'Bank',
            'transaction_id' => 'PENDING-1',
            'status' => 'pending',
        ]);
        Employee::create([
            'employee_id' => 'NSYS-EM-900',
            'name' => 'Missing Bank Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Notification Center');
        $response->assertSee('Critical Alerts');
        $response->assertSee('Pending Client Payments');
        $response->assertSee('Binance Balance Below 200 USD');
        $response->assertSee('Payment Issue Ad Accounts');
        $this->assertDatabaseHas('system_notifications', [
            'notification_key' => 'finance.low_binance',
            'status' => 'unread',
        ]);
    }

    public function test_notification_center_can_filter_and_auto_resolve_fixed_alerts(): void
    {
        Carbon::setTestNow('2026-06-13');
        $admin = $this->admin();
        $balance = FundingBalance::create([
            'source' => 'redotpay',
            'current_balance' => 25,
            'balance_date' => '2026-06-13',
        ]);

        $this->actingAs($admin)->get('/admin/notifications?priority=warning')
            ->assertOk()
            ->assertSee('RedotPay Balance Below 100 USD');

        $notification = SystemNotification::where('notification_key', 'finance.low_redotpay')->firstOrFail();

        $this->actingAs($admin)->post('/admin/notifications/' . $notification->id . '/status', [
            'status' => 'read',
        ])->assertRedirect();

        $this->assertSame('read', $notification->fresh()->status);

        $balance->update(['current_balance' => 500]);

        $this->actingAs($admin)->get('/admin/notifications?status=resolved')
            ->assertOk()
            ->assertSee('RedotPay Balance Below 100 USD');

        $this->assertSame('resolved', $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->resolved_at);
    }

    public function test_terminated_employee_final_settlement_uses_separate_notification(): void
    {
        Carbon::setTestNow('2026-06-24');
        $admin = $this->admin();
        $client = $this->client();
        $employee = Employee::create([
            'employee_id' => 'NSYS-EM-901',
            'name' => 'Terminated Settlement Alert',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-01',
            'salary_day' => 20,
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'monthly_salary' => 30000,
            'bank_name' => 'Bank',
            'account_name' => 'Terminated Settlement Alert',
            'account_number' => '123',
        ]);
        $employee->payrolls()->create([
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-20',
            'working_days' => 12,
            'non_working_days' => 8,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 12000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'status' => 'unpaid',
            'is_final_settlement' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/notifications');

        $response->assertOk();
        $response->assertSee('Final Settlements Due');
        $response->assertSee('Terminated Settlement Alert');
        $response->assertSee('Final Settlement Due In: 36 Days');
        $this->assertDatabaseHas('system_notifications', [
            'notification_key' => 'employee.final_settlement_due',
            'priority' => 'critical',
        ]);
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
        $user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create([
            'user_id' => $user->id,
            'company_name' => 'Notification Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
    }
}
