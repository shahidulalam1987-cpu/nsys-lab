<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFundDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_client_fund_dashboard_summary_and_client_rows(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Fund Client A']);
        $otherClient = $this->client(['company_name' => 'Fund Client B']);
        $employee = $this->employee();

        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-10',
            'amount' => 20000,
            'payment_method' => 'Bank',
            'transaction_id' => 'APPROVED-1',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-11',
            'amount' => 5000,
            'payment_method' => 'bKash',
            'transaction_id' => 'PENDING-1',
            'status' => 'pending',
        ]);
        SalaryPayment::create([
            'client_id' => $otherClient->id,
            'salary_month' => '2026-06-12',
            'amount' => 3000,
            'payment_method' => 'Nagad',
            'transaction_id' => 'PENDING-2',
            'status' => 'pending',
        ]);

        $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 15000,
            'paid_amount' => 10000,
            'status' => 'partial',
        ]);
        $employee->payrolls()->create([
            'client_id' => $otherClient->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 8000,
            'paid_amount' => 8000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/client-fund');

        $response->assertOk();
        $response->assertSee('Client Fund Dashboard');
        $response->assertSee('Total Fund Received');
        $response->assertSee('BDT 20,000.00');
        $response->assertSee('Total Salary Used');
        $response->assertSee('BDT 18,000.00');
        $response->assertSee('Available Balance');
        $response->assertSee('BDT 2,000.00');
        $response->assertSee('Pending Client Payments');
        $response->assertSee('BDT 8,000.00');
        $response->assertSee('Unpaid Salary Due');
        $response->assertSee('BDT 5,000.00');
        $response->assertSee('Fund Client A');
        $response->assertSee('/admin/clients/' . $client->id, false);
    }

    public function test_client_fund_dashboard_link_is_in_employee_department_sidebar(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/client-fund');

        $response->assertOk();
        $response->assertSee('Client Fund');
        $response->assertSee('href="/admin/client-fund"', false);
        $response->assertSee('Dashboard');
        $response->assertSee('Receive Payment');
        $response->assertSee('Pending Payments');
        $response->assertSee('Payment History');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function client(array $overrides = []): Client
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create(array_merge([
            'user_id' => $clientUser->id,
            'company_name' => 'Fund Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ], $overrides));
    }

    private function employee(): Employee
    {
        return Employee::create([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Fund Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ]);
    }
}
