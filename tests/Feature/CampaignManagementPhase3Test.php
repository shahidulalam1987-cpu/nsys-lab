<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignManagementPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_campaign_hierarchy(): void
    {
        $admin = $this->user('admin');
        [$bm, $account, $client, $page] = $this->boostingHierarchy();

        $response = $this->actingAs($admin)->post('/admin/campaigns', [
            'campaign_name' => 'NSYS Lead Campaign',
            'campaign_id' => 'CMP-1001',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'leads',
            'status' => 'active',
            'start_date' => '2026-06-01',
            'end_date' => now()->copy()->addDays(4)->toDateString(),
            'daily_budget' => 25,
            'lifetime_budget' => 500,
            'notes' => 'Campaign foundation test.',
        ]);

        $campaign = Campaign::firstOrFail();

        $response->assertRedirect('/admin/campaigns/' . $campaign->id);
        $this->assertDatabaseHas('campaigns', [
            'campaign_id' => 'CMP-1001',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
        ]);

        $this->actingAs($admin)->get('/admin/campaigns')
            ->assertOk()
            ->assertSee('Campaign Management')
            ->assertSee('NSYS Lead Campaign')
            ->assertSee('Ending Soon');

        $this->actingAs($admin)->get('/admin/campaigns/' . $campaign->id)
            ->assertOk()
            ->assertSee('Campaign Information')
            ->assertSee('BM Information')
            ->assertSee('Ad Account Information')
            ->assertSee('Performance Summary');

        $this->actingAs($admin)->post('/admin/campaigns/' . $campaign->id . '/update', [
            'campaign_name' => 'NSYS Lead Campaign Updated',
            'campaign_id' => 'CMP-1001',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'messages',
            'status' => 'paused',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'daily_budget' => 30,
            'lifetime_budget' => 600,
        ])->assertRedirect('/admin/campaigns/' . $campaign->id);

        $this->assertSame('paused', $campaign->fresh()->status);
    }

    public function test_assignment_and_work_status_can_link_to_campaign(): void
    {
        $admin = $this->user('admin');
        [$bm, $account, $client, $page] = $this->boostingHierarchy();
        $campaign = Campaign::create([
            'campaign_name' => 'Assignment Campaign',
            'campaign_id' => 'CMP-ASSIGN',
            'business_manager_id' => $bm->id,
            'ad_account_id' => $account->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'objective' => 'messages',
            'status' => 'active',
        ]);
        $employee = $this->employee();
        $shift = Shift::where('name', 'Morning Shift')->firstOrFail();

        $this->actingAs($admin)->post('/admin/assignments', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'campaign_id' => $campaign->id,
            'shift_id' => $shift->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ])->assertRedirect('/admin/assignments');

        $this->assertDatabaseHas('employee_assignments', [
            'employee_id' => $employee->id,
            'campaign_id' => $campaign->id,
        ]);

        $this->actingAs($admin)->post('/admin/work-status', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'client_page_id' => $page->id,
            'campaign_id' => $campaign->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-06-10',
            'status' => 'working',
            'note' => 'Campaign work',
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_work_statuses', [
            'employee_id' => $employee->id,
            'campaign_id' => $campaign->id,
            'salary_count_value' => 1,
        ]);

        $this->actingAs($admin)->get('/admin/work-status?campaign_id=' . $campaign->id)
            ->assertOk()
            ->assertSee('Assignment Campaign')
            ->assertSee('Campaign work');
    }

    public function test_non_admin_cannot_access_campaign_management(): void
    {
        $this->actingAs($this->user('client'))
            ->get('/admin/campaigns')
            ->assertForbidden();

        $this->actingAs($this->user('employee'))
            ->get('/admin/campaigns/create')
            ->assertForbidden();
    }

    private function boostingHierarchy(): array
    {
        $client = Client::create([
            'company_name' => 'Campaign Client',
            'phone' => '01700000000',
            'client_rate' => 120,
            'buy_rate' => 100,
            'status' => 'active',
        ]);
        $bm = BusinessManager::create([
            'bm_name' => 'Campaign BM',
            'bm_id' => 'BM-CAMPAIGN',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@example.com',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);
        $account = AdAccount::create([
            'ad_account_name' => 'Campaign Ad Account',
            'ad_account_id' => 'act_campaign',
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
            'page_name' => 'Campaign Page',
            'page_id' => 'PAGE-CAMPAIGN',
            'platform' => 'Facebook',
            'status' => 'active',
        ]);

        return [$bm, $account, $client, $page];
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function employee(): Employee
    {
        return Employee::create([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Campaign Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ]);
    }
}
