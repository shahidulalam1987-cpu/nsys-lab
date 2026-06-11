<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyPerformancePhase4Test extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_view_update_and_delete_daily_performance(): void
    {
        $admin = $this->user('admin');
        [$campaign] = $this->campaigns();

        $response = $this->actingAs($admin)->post('/admin/daily-reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-12',
            'spend' => 100,
            'messages' => 20,
            'results' => 10,
            'leads' => 5,
            'orders' => 4,
            'reach' => 1000,
            'impressions' => 2000,
            'clicks' => 50,
            'notes' => 'Strong performance',
        ]);

        $report = DailyPerformanceReport::firstOrFail();
        $response->assertRedirect('/admin/daily-reports/' . $report->id);

        $this->assertSame('5.00', $report->cpm);
        $this->assertSame('10.00', $report->cpr);
        $this->assertSame('20.00', $report->cpl);
        $this->assertSame('25.00', $report->cpp);
        $this->assertSame('2.00', $report->cpc);

        $this->actingAs($admin)->get('/admin/daily-reports')
            ->assertOk()
            ->assertSee('Daily Performance Entry')
            ->assertSee('USD 100.00');

        $this->actingAs($admin)->get('/admin/daily-reports/' . $report->id)
            ->assertOk()
            ->assertSee('Performance Details')
            ->assertSee('Calculated Metrics')
            ->assertSee('Campaign Information')
            ->assertSee('Strong performance');

        $duplicate = $this->actingAs($admin)->from('/admin/daily-reports/create')->post('/admin/daily-reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-12',
            'spend' => 120,
            'messages' => 24,
            'results' => 12,
            'leads' => 6,
            'orders' => 3,
            'clicks' => 60,
        ]);
        $duplicate->assertRedirect('/admin/daily-reports/create');
        $duplicate->assertSessionHasErrors('campaign_id');

        $this->actingAs($admin)->post('/admin/daily-reports', [
            'campaign_id' => $campaign->id,
            'report_date' => '2026-06-12',
            'spend' => 120,
            'messages' => 24,
            'results' => 12,
            'leads' => 6,
            'orders' => 3,
            'reach' => 0,
            'impressions' => 0,
            'clicks' => 60,
            'update_existing' => 1,
        ])->assertRedirect('/admin/daily-reports/' . $report->id);

        $this->assertSame('120.00', $report->fresh()->spend);

        $this->actingAs($admin)->post('/admin/daily-reports/' . $report->id . '/delete')
            ->assertRedirect('/admin/daily-reports');
        $this->assertDatabaseMissing('daily_performance_reports', ['id' => $report->id]);
    }

    public function test_bulk_entry_and_integrations_work(): void
    {
        $admin = $this->user('admin');
        [$campaignOne, $campaignTwo, $client, $adAccount] = $this->campaigns();

        $this->actingAs($admin)->post('/admin/daily-reports', [
            'entry_mode' => 'bulk',
            'bulk_report_date' => now()->toDateString(),
            'bulk_rows' => [
                [
                    'enabled' => 1,
                    'campaign_id' => $campaignOne->id,
                    'spend' => 50,
                    'messages' => 10,
                    'results' => 5,
                    'leads' => 2,
                    'orders' => 1,
                    'reach' => 500,
                    'impressions' => 700,
                    'clicks' => 20,
                    'notes' => 'First campaign',
                ],
                [
                    'enabled' => 1,
                    'campaign_id' => $campaignTwo->id,
                    'spend' => 75,
                    'messages' => 15,
                    'results' => 5,
                    'leads' => 3,
                    'orders' => 2,
                    'reach' => 900,
                    'impressions' => 1200,
                    'clicks' => 25,
                    'notes' => 'Second campaign',
                ],
            ],
        ])->assertRedirect('/admin/daily-reports');

        $this->assertDatabaseCount('daily_performance_reports', 2);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeText("Today's Spend")
            ->assertSeeText("Today's Messages")
            ->assertSee('Recent Daily Performance');

        $this->actingAs($admin)->get('/admin/ad-accounts/' . $adAccount->id)
            ->assertOk()
            ->assertSee('Today Spend')
            ->assertSee('Month Spend')
            ->assertSee('Campaign Count');

        $this->actingAs($admin)->get('/admin/campaigns/' . $campaignOne->id)
            ->assertOk()
            ->assertSee('Performance History')
            ->assertSee('Spend History');

        $this->actingAs($admin)->get('/admin/clients/' . $client->id)
            ->assertOk()
            ->assertSee('Boosting Performance Summary')
            ->assertSee('Campaign Count');
    }

    public function test_non_admin_cannot_access_daily_performance(): void
    {
        $this->actingAs($this->user('client'))->get('/admin/daily-reports')->assertForbidden();
        $this->actingAs($this->user('employee'))->get('/admin/daily-reports/create')->assertForbidden();
    }

    private function campaigns(): array
    {
        $client = Client::create([
            'company_name' => 'Performance Client',
            'phone' => '01700000000',
            'client_rate' => 120,
            'buy_rate' => 100,
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Performance BM',
            'bm_id' => 'BM-PERF',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $account = AdAccount::create([
            'ad_account_name' => 'Performance Ad Account',
            'ad_account_id' => 'act_perf',
            'business_manager_id' => $bm->id,
            'client_id' => $client->id,
            'currency' => 'USD',
            'timezone' => 'Asia/Dhaka',
            'threshold_amount' => 1000,
            'current_threshold_usage' => 0,
            'current_balance' => 500,
            'status' => 'active',
        ]);
        $page = ClientPage::create([
            'client_id' => $client->id,
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'page_name' => 'Performance Page',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);
        $campaignOne = Campaign::create([
            'campaign_name' => 'Performance Campaign One',
            'campaign_id' => 'CMP-PERF-1',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'messages',
            'status' => 'active',
        ]);
        $campaignTwo = Campaign::create([
            'campaign_name' => 'Performance Campaign Two',
            'campaign_id' => 'CMP-PERF-2',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'leads',
            'status' => 'active',
        ]);

        return [$campaignOne, $campaignTwo, $client, $account];
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
