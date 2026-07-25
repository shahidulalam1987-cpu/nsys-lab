<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\MetaSpendSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDailyStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_client_daily_whatsapp_statement(): void
    {
        [$client, $campaign] = $this->campaign(['company_name' => 'Rasel Bedsheet']);
        MetaSpendSnapshot::create([
            'campaign_id' => $campaign->id,
            'ad_account_id' => $campaign->ad_account_id,
            'client_id' => $client->id,
            'client_page_id' => $campaign->client_page_id,
            'snapshot_date' => '2026-07-25',
            'spend_usd' => 8312.94,
            'orders' => 0,
            'source' => 'daily_closing',
        ]);
        $this->ledger($client, ClientFundLedger::DIRECTION_DEBIT, 31686.90, '2026-07-25');

        $response = $this->actingAs($this->admin())->get('/admin/client-fund/daily-statement?' . http_build_query([
            'client_id' => $client->id,
            'campaign_id' => $campaign->id,
            'statement_date' => '2026-07-26',
            'current_total_spend_usd' => 8406.33,
            'orders' => 113,
            'rate_bdt' => 145,
        ]));

        $response->assertOk();
        $response->assertSee('Client Daily Statement');
        $response->assertSee('$93.39');
        $response->assertSee('BDT 13,541.55');
        $response->assertSee('BDT 45,228.45');
        $response->assertSee('==8312.94-8406.33=93.39===');
        $response->assertSee('Final Total Due: 45228.45 BDT');
    }

    public function test_saving_daily_statement_creates_snapshot_performance_and_ads_due(): void
    {
        $this->travelTo('2026-07-26 13:00:00');
        [$client, $campaign] = $this->campaign(['company_name' => 'Mehedi Bedsheet']);
        MetaSpendSnapshot::create([
            'campaign_id' => $campaign->id,
            'ad_account_id' => $campaign->ad_account_id,
            'client_id' => $client->id,
            'client_page_id' => $campaign->client_page_id,
            'snapshot_date' => '2026-07-25',
            'spend_usd' => 4009.29,
            'orders' => 0,
            'source' => 'daily_closing',
        ]);
        $this->ledger($client, ClientFundLedger::DIRECTION_DEBIT, 58877.80, '2026-07-25');
        $this->ledger($client, ClientFundLedger::DIRECTION_CREDIT, 50000, '2026-07-26');

        $response = $this->actingAs($this->admin())->post('/admin/client-fund/daily-statement', [
            'client_id' => $client->id,
            'campaign_id' => $campaign->id,
            'statement_date' => '2026-07-26',
            'current_total_spend_usd' => 4169.49,
            'orders' => 223,
            'rate_bdt' => 145,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('meta_spend_snapshots', [
            'campaign_id' => $campaign->id,
            'snapshot_date' => '2026-07-26 00:00:00',
            'source' => 'daily_closing',
            'spend_usd' => 4169.49,
            'orders' => 223,
        ]);
        $this->assertDatabaseHas('daily_performance_reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-07-26 00:00:00',
            'spend' => 160.20,
            'orders' => 223,
        ]);
        $this->assertSame(-32106.8, $client->fresh()->ads_fund_balance());
    }

    public function test_existing_daily_performance_requires_update_confirmation(): void
    {
        [$client, $campaign] = $this->campaign();
        DailyPerformanceReport::create([
            'campaign_id' => $campaign->id,
            'report_date' => '2026-07-26',
            'spend' => 10,
            'orders' => 1,
        ]);

        $this->actingAs($this->admin())->post('/admin/client-fund/daily-statement', [
            'client_id' => $client->id,
            'campaign_id' => $campaign->id,
            'statement_date' => '2026-07-26',
            'previous_total_spend_usd' => 100,
            'current_total_spend_usd' => 120,
            'orders' => 2,
            'rate_bdt' => 145,
        ])->assertSessionHasErrors('campaign_id');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function campaign(array $clientOverrides = []): array
    {
        $client = Client::create(array_merge([
            'company_name' => 'Statement Client',
            'phone' => '123',
            'client_rate' => 145,
            'buy_rate' => 130,
            'status' => 'active',
        ], $clientOverrides));
        $page = ClientPage::create([
            'client_id' => $client->id,
            'page_name' => 'Bedsheet Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Statement BM',
            'bm_id' => 'bm-statement-' . uniqid(),
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $adAccount = AdAccount::create([
            'ad_account_name' => 'Statement Ad Account',
            'ad_account_id' => 'act_' . uniqid(),
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'campaign_name' => 'Bedsheet Campaign',
            'campaign_id' => 'cmp-' . uniqid(),
            'business_manager_id' => $bm->id,
            'ad_account_id' => $adAccount->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'sales',
            'status' => 'active',
        ]);

        return [$client, $campaign];
    }

    private function ledger(Client $client, string $direction, float $amount, string $date): void
    {
        $balanceBefore = (float) ClientFundLedger::where('client_id', $client->id)
            ->where('fund_type', ClientFundLedger::FUND_FACEBOOK_ADS)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance');
        $balanceAfter = $direction === ClientFundLedger::DIRECTION_CREDIT
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        ClientFundLedger::create([
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_FACEBOOK_ADS,
            'direction' => $direction,
            'amount_bdt' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => 'TEST-' . uniqid(),
            'description' => 'Test ads fund movement.',
            'created_at' => $date . ' 13:00:00',
            'updated_at' => $date . ' 13:00:00',
        ]);
    }
}
