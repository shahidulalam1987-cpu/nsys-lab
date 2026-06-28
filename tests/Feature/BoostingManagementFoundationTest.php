<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdAccountLedger;
use App\Models\BusinessManager;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoostingManagementFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_manage_bm_ad_account_and_page_hierarchy(): void
    {
        $admin = $this->user('admin');
        $client = Client::create([
            'company_name' => 'NSYS Test Client',
            'phone' => '01700000000',
            'client_rate' => 120,
            'buy_rate' => 100,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/business-managers', [
                'bm_name' => 'NSYS Main BM',
                'bm_id' => 'BM-1001',
                'owner_name' => 'NSYS Owner',
                'owner_email' => 'owner@nsys.test',
                'verification_status' => 'verified',
                'status' => 'active',
                'notes' => 'Primary testing BM',
            ])
            ->assertRedirect('/admin/business-managers');

        $bm = BusinessManager::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/business-managers')
            ->assertOk()
            ->assertSee('BM Management')
            ->assertSee('NSYS Main BM')
            ->assertSee('Verified');

        $this->actingAs($admin)
            ->post('/admin/ad-accounts', [
                'ad_account_name' => 'NSYS Ad Account 1',
                'ad_account_id' => 'act_1001',
                'business_manager_id' => $bm->id,
                'client_id' => $client->id,
                'timezone' => 'Asia/Dhaka',
                'threshold_amount' => 10000,
                'current_threshold_usage' => 3500,
                'current_balance' => 2500,
                'monthly_billing_date' => 15,
                'last_payment_date' => '2026-06-01',
                'payment_method' => 'Card',
                'card_last_four' => '1234',
                'status' => 'active',
                'notes' => 'Primary testing ad account',
            ])
            ->assertRedirect();

        $account = AdAccount::firstOrFail();

        $this->assertSame(6500.0, $account->remaining_threshold);
        $this->assertSame('USD', $account->currency);
        $this->assertDatabaseHas('ad_account_ledgers', [
            'ad_account_id' => $account->id,
            'transaction_type' => 'threshold_update',
        ]);

        ClientPage::create([
            'client_id' => $client->id,
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'page_name' => 'NSYS Test Page',
            'page_id' => 'PAGE-1001',
            'page_url' => 'https://facebook.com/nsys-test-page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/ad-accounts')
            ->assertOk()
            ->assertSee('Ad Account Management')
            ->assertSee('NSYS Ad Account 1')
            ->assertSee('6,500.00');

        $this->actingAs($admin)
            ->get('/admin/client-pages')
            ->assertOk()
            ->assertSee('NSYS Test Page')
            ->assertDontSee('PAGE-1001')
            ->assertSee('NSYS Main BM')
            ->assertSee('NSYS Ad Account 1');

        $this->actingAs($admin)
            ->get('/admin/facebook-dashboard')
            ->assertOk()
            ->assertSee('Total BM')
            ->assertSee('Total Ad Accounts')
            ->assertSee('Active Campaigns')
            ->assertSee('Remaining Threshold')
            ->assertSee('Critical Accounts')
            ->assertSee('Add BM')
            ->assertSee('Add Ad Account');
    }

    public function test_ad_account_alert_filters_and_ledger_updates_work(): void
    {
        Carbon::setTestNow('2026-06-10');

        $admin = $this->user('admin');
        $bm = BusinessManager::create([
            'bm_name' => 'Alert BM',
            'bm_id' => 'BM-ALERT',
            'owner_name' => 'Alert Owner',
            'owner_email' => 'alert@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);

        $account = AdAccount::create([
            'ad_account_name' => 'Risky Account',
            'ad_account_id' => 'act_risky',
            'business_manager_id' => $bm->id,
            'currency' => 'USD',
            'timezone' => 'Asia/Dhaka',
            'threshold_amount' => 1000,
            'current_threshold_usage' => 960,
            'current_balance' => 50,
            'monthly_billing_date' => now()->copy()->addDays(3)->day,
            'status' => 'payment_issue',
        ]);

        $this->assertSame('critical', $account->thresholdStatus());
        $this->assertSame('upcoming', $account->billingStatus());
        $this->assertSame('low', $account->balanceStatus());

        $this->actingAs($admin)
            ->get('/admin/ad-accounts?threshold_status=critical&billing_status=upcoming&balance_status=low')
            ->assertOk()
            ->assertSee('Risky Account')
            ->assertSee('Critical')
            ->assertSee('Upcoming Billing')
            ->assertSee('Low Balance');

        $this->actingAs($admin)
            ->post('/admin/ad-accounts/' . $account->id . '/update', [
                'ad_account_name' => 'Risky Account',
                'ad_account_id' => 'act_risky',
                'business_manager_id' => $bm->id,
                'client_id' => null,
                'timezone' => 'Asia/Dhaka',
                'threshold_amount' => 1000,
                'current_threshold_usage' => 1000,
                'current_balance' => 0,
                'monthly_billing_date' => now()->copy()->subDay()->day,
                'last_payment_date' => now()->toDateString(),
                'payment_method' => 'Card',
                'card_last_four' => '5555',
                'status' => 'limit_reached',
                'notes' => 'Limit reached during testing.',
            ])
            ->assertRedirect('/admin/ad-accounts/' . $account->id);

        $account->refresh();

        $this->assertSame('limit_reached', $account->thresholdStatus());
        $this->assertSame('negative', $account->balanceStatus());
        $this->assertDatabaseHas('ad_account_ledgers', [
            'ad_account_id' => $account->id,
            'transaction_type' => 'status_change',
        ]);

        $ledger = AdAccountLedger::where('ad_account_id', $account->id)->latest()->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/ad-account-ledger')
            ->assertOk()
            ->assertSee('Ad Account Financial Ledger')
            ->assertSee('Risky Account');

        $this->actingAs($admin)
            ->get('/admin/ad-account-ledger/' . $ledger->id)
            ->assertOk()
            ->assertSee('Ledger Details')
            ->assertSee($ledger->typeLabel());
    }

    public function test_non_admin_cannot_access_boosting_management_foundation(): void
    {
        $this->actingAs($this->user('client'))
            ->get('/admin/business-managers')
            ->assertForbidden();

        $this->actingAs($this->user('employee'))
            ->get('/admin/ad-accounts')
            ->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
