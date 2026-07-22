<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdAccountCard;
use App\Models\AdAccountPage;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Dataset;
use App\Models\FacebookCard;
use App\Models\PaymentProvider;
use App\Models\ProviderFeeTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyMigrationGapCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_apps_script_architecture_tables_exist(): void
    {
        foreach ([
            'payment_providers',
            'ad_account_pages',
            'ad_account_cards',
            'datasets',
            'provider_transactions',
            'provider_fee_tracking',
            'ad_account_billing_history',
            'meta_spend_snapshots',
            'whats_app_logs',
            'meta_sync_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table . ' table is missing.');
        }
    }

    public function test_admin_can_open_new_architecture_pages(): void
    {
        $admin = $this->admin();

        foreach ([
            '/admin/payment-providers' => 'Payment Providers',
            '/admin/ad-account-pages' => 'Ad Account Page Mapping',
            '/admin/ad-account-cards' => 'Ad Account Card Mapping',
            '/admin/datasets' => 'Datasets',
            '/admin/provider-transactions' => 'Provider Transactions',
            '/admin/provider-fees' => 'Provider Fee Tracking',
            '/admin/ad-account-billing-history' => 'Billing History',
            '/admin/meta-spend-snapshots' => 'Meta Spend Snapshots',
            '/admin/whatsapp-logs' => 'WhatsApp Logs',
            '/admin/meta-sync-logs' => 'Meta Sync Logs',
        ] as $url => $heading) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee($heading);
        }
    }

    public function test_ad_account_page_and_card_mappings_store_relationships(): void
    {
        $admin = $this->admin();
        [$client, $bm, $account, $page, $card] = $this->facebookStack();

        $this->actingAs($admin)->post('/admin/ad-account-pages', [
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'status' => 'active',
            'mapped_from' => '2026-07-20',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post('/admin/ad-account-cards', [
            'ad_account_id' => $account->id,
            'facebook_card_id' => $card->id,
            'is_primary' => '1',
            'status' => 'active',
            'mapped_from' => '2026-07-20',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(AdAccountPage::whereBelongsTo($account, 'adAccount')->where('client_page_id', $page->id)->exists());
        $this->assertTrue(AdAccountCard::whereBelongsTo($account, 'adAccount')->where('facebook_card_id', $card->id)->where('is_primary', true)->exists());
        $this->assertSame($bm->id, $account->business_manager_id);
    }

    public function test_dataset_and_meta_snapshot_can_bind_to_campaign_stack(): void
    {
        $admin = $this->admin();
        [$client, $bm, $account, $page] = $this->facebookStack();
        $campaign = Campaign::create([
            'campaign_name' => 'Dataset Campaign',
            'campaign_id' => 'cmp-dataset-1',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'sales',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/datasets', [
            'dataset_name' => 'Main Pixel',
            'dataset_id' => 'pixel-1001',
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'platform' => 'Meta',
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post('/admin/meta-spend-snapshots', [
            'campaign_id' => $campaign->id,
            'snapshot_date' => '2026-07-20',
            'spend_usd' => 25.50,
            'orders' => 4,
            'source' => 'manual',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Dataset::where('dataset_id', 'pixel-1001')->where('client_page_id', $page->id)->exists());
        $this->assertDatabaseHas('meta_spend_snapshots', [
            'campaign_id' => $campaign->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
        ]);
    }

    public function test_provider_fee_tracking_calculates_fee_amount_and_percentage(): void
    {
        $admin = $this->admin();
        $provider = PaymentProvider::create([
            'provider_code' => 'redotpay',
            'name' => 'RedotPay',
            'provider_type' => 'card_wallet',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/provider-fees', [
            'payment_provider_id' => $provider->id,
            'sample_date' => '2026-07-20',
            'facebook_charge_usd' => 100,
            'provider_deducted_usd' => 103,
        ])->assertSessionHasNoErrors();

        $fee = ProviderFeeTracking::firstOrFail();
        $this->assertSame('3.00', $fee->fee_amount_usd);
        $this->assertSame('3.0000', $fee->fee_percentage);
    }

    private function facebookStack(): array
    {
        $client = Client::create([
            'company_name' => 'Legacy Gap Client',
            'phone' => '01700000000',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Legacy Gap BM',
            'bm_id' => 'BM-GAP-1',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $account = AdAccount::create([
            'ad_account_name' => 'Legacy Gap Ad Account',
            'ad_account_id' => 'act_gap_1',
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'currency' => 'USD',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
        ]);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'page_name' => 'Legacy Gap Page',
            'page_id' => 'page-gap-1',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $card = FacebookCard::create([
            'card_name' => 'Legacy Gap Card',
            'provider' => 'RedotPay',
            'currency' => 'USD',
            'current_balance' => 100,
            'status' => 'active',
        ]);

        return [$client, $bm, $account, $page, $card];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }
}
