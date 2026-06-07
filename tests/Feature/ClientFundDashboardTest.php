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
        $response->assertSee('Upcoming Salary This Week');
        $response->assertSee('Fund Client A');
        $response->assertSee('/admin/client-fund/' . $client->id, false);
    }

    public function test_admin_can_view_client_fund_detail_ledger(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Ledger Client']);
        $employee = $this->employee();

        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 10000,
            'payment_method' => 'Bank',
            'transaction_id' => 'LEDGER-CREDIT',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payment_date' => '2026-06-06',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-06',
            'payable_salary' => 2000,
            'paid_amount' => 2000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/client-fund/' . $client->id);

        $response->assertOk();
        $response->assertSee('Client Fund Details');
        $response->assertSee('Ledger Client');
        $response->assertSee('Transaction Timeline');
        $response->assertSee('Receive Payment');
        $response->assertSee('Salary Payment');
        $response->assertSee('BDT 10,000.00');
        $response->assertSee('BDT 2,000.00');
        $response->assertSee('BDT 8,000.00');
    }

    public function test_admin_can_export_client_fund_dashboard_csv_and_excel(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Export Fund Client']);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 7000,
            'payment_method' => 'Bank',
            'transaction_id' => 'EXPORT-1',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $csv = $this->actingAs($admin)->get('/admin/client-fund/export/csv');
        $excel = $this->actingAs($admin)->get('/admin/client-fund/export/excel');

        $csv->assertOk();
        $csv->assertDownload('client-fund-dashboard.csv');
        $this->assertStringContainsString('Export Fund Client', $csv->streamedContent());

        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $excel->assertSee('Export Fund Client');
    }

    public function test_salary_edit_page_warns_when_client_fund_is_insufficient(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee();
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id . '/edit');

        $response->assertOk();
        $response->assertSee('Insufficient Client Fund');
        $response->assertSee('Need: BDT 10,000.00');
        $response->assertSee('Available: BDT 0.00');
    }

    public function test_employee_profile_shows_client_fund_salary_context(): void
    {
        $admin = $this->admin();
        $client = $this->client(['company_name' => 'Profile Fund Client']);
        $employee = $this->employee([
            'confirmation_date' => '2026-06-10',
            'salary_day' => 10,
        ]);
        $employee->assignments()->create([
            'client_id' => $client->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);
        SalaryPayment::create([
            'client_id' => $client->id,
            'salary_month' => '2026-06-01',
            'amount' => 8000,
            'payment_method' => 'Bank',
            'transaction_id' => 'PROFILE-FUND',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);

        $response->assertOk();
        $response->assertSee('Assigned Client');
        $response->assertSee('Profile Fund Client');
        $response->assertSee('Client Fund Balance');
        $response->assertSee('BDT 8,000.00');
        $response->assertSee('Upcoming Salary Date');
        $response->assertSee('Salary Status');
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
