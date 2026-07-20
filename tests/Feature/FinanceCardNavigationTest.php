<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_sidebar_only_shows_compact_card_management_entry(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/finance/accounts');

        $response->assertOk();
        $response->assertSee('Funding Dashboard');
        $response->assertSee('Finance Accounts');
        $response->assertSee('Card Management');
        $response->assertDontSee('Client Funds');
        $response->assertDontSee('Family Expenses');
        $response->assertDontSee('Loan Management');
        $response->assertDontSee('Card Loads');
        $response->assertDontSee('Card Transactions');
        $response->assertDontSee('Binance Purchases');
        $response->assertDontSee('Payment Providers');
        $response->assertDontSee('Provider Transactions');
        $response->assertDontSee('Provider Fee Tracking');
        $response->assertDontSee('Profit Dashboard');
        $response->assertDontSee('Balance Sheet');
    }

    public function test_card_management_tabs_render(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/facebook-cards');

        $response->assertOk();
        $response->assertSee('Overview');
        $response->assertSee('Cards');
        $response->assertSee('Loads');
        $response->assertSee('Transactions');
        $response->assertSee('Binance Purchases');
        $response->assertSee('Providers');
        $response->assertSee('Provider Transactions');
        $response->assertSee('Provider Fees');
        $response->assertSee('Statement');
    }

    public function test_old_card_loads_and_transactions_routes_still_work(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/facebook-financial/card-loads')->assertOk()->assertSee('Card Load History');
        $this->actingAs($admin)->get('/admin/facebook-financial/card-transactions')->assertOk()->assertSee('Card Transactions');
    }

    public function test_card_provider_options_include_redotpay_tevau_and_other(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/facebook-cards/create');

        $response->assertOk();
        $response->assertSee('RedotPay');
        $response->assertSee('Tevau');
        $response->assertSee('Other');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }
}
