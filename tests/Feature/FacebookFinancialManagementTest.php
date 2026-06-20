<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BinancePurchase;
use App\Models\BusinessManager;
use App\Models\CardTransaction;
use App\Models\Client;
use App\Models\FacebookCard;
use App\Models\FinanceAccount;
use App\Models\FinanceAccountLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacebookFinancialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_track_binance_purchase_card_load_transaction_and_profit(): void
    {
        $admin = $this->user('admin');
        $financeAccount = FinanceAccount::create([
            'account_type' => 'bank',
            'account_name' => 'Binance Purchase Bank',
            'currency' => 'BDT',
            'current_balance' => 200000,
            'status' => 'active',
        ]);
        $client = Client::create([
            'company_name' => 'Profit Client',
            'phone' => '01700000000',
            'client_rate' => 145,
            'buy_rate' => 0,
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Main BM',
            'bm_id' => 'BM-FIN-1',
            'owner_name' => 'Finance Owner',
            'owner_email' => 'finance-owner@test.local',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $adAccount = AdAccount::create([
            'ad_account_name' => 'Finance Account',
            'ad_account_id' => 'act_fin_1',
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'currency' => 'USD',
            'timezone' => 'Asia/Dhaka',
            'threshold_amount' => 1000,
            'current_threshold_usage' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);
        $card = FacebookCard::create([
            'card_name' => 'RedotPay Main',
            'provider' => 'RedotPay',
            'card_last_four' => '1234',
            'current_balance' => 0,
            'currency' => 'USD',
            'ad_account_id' => $adAccount->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/facebook-financial/binance-purchases', [
            'finance_account_id' => $financeAccount->id,
            'purchase_date' => '2026-06-12',
            'usd_amount' => 1000,
            'buy_rate' => 121.5,
            'source' => 'Binance',
            'seller_name' => 'Seller One',
            'reference' => 'BN-1',
        ])->assertRedirect('/admin/facebook-financial/binance-purchases');

        $purchase = BinancePurchase::firstOrFail();
        $this->assertSame(121500.0, (float) $purchase->total_bdt_cost);
        $this->assertSame(78500.0, (float) $financeAccount->fresh()->current_balance);
        $this->assertSame(1000.0, (float) $purchase->remaining_usd);

        $this->actingAs($admin)->post('/admin/facebook-financial/card-loads', [
            'load_date' => '2026-06-12',
            'facebook_card_id' => $card->id,
            'binance_purchase_id' => $purchase->id,
            'usd_loaded' => 500,
        ])->assertRedirect('/admin/facebook-financial/card-loads');

        $this->assertSame(500.0, (float) $card->fresh()->current_balance);
        $this->assertSame(500.0, (float) $purchase->fresh()->remaining_usd);

        $this->actingAs($admin)->post('/admin/facebook-financial/card-transactions', [
            'transaction_date' => '2026-06-12',
            'facebook_card_id' => $card->id,
            'binance_purchase_id' => $purchase->id,
            'ad_account_id' => $adAccount->id,
            'client_id' => $client->id,
            'spend_usd' => 100,
            'fee_usd' => 1,
        ])->assertRedirect('/admin/facebook-financial/card-transactions');

        $transaction = CardTransaction::firstOrFail();
        $this->assertSame(101.0, (float) $transaction->total_deducted_usd);
        $this->assertSame(12271.5, (float) $transaction->bdt_cost);
        $this->assertSame(14500.0, (float) $transaction->client_revenue);
        $this->assertSame(2228.5, (float) $transaction->net_profit);
        $this->assertSame(399.0, (float) $card->fresh()->current_balance);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $financeAccount->id,
            'transaction_type' => 'binance_purchase',
            'direction' => 'debit',
            'amount' => 121500,
            'currency' => 'BDT',
        ]);
        $this->assertSame(5, FinanceAccountLedger::count());

        $this->actingAs($admin)
            ->get('/admin/facebook-financial/profit-dashboard?month=2026-06')
            ->assertOk()
            ->assertSee('Profit Dashboard')
            ->assertSee('BDT 2,228.50');
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role . '@financial.test',
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }
}
