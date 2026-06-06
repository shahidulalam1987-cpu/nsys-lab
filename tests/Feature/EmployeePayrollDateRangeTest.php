<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePayrollDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_salary_by_date_range_without_salary_days(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'paid_amount' => 5000,
            'payment_method' => 'bKash',
            'payment_date' => '2026-06-10',
            'note' => 'Date range salary',
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $payroll->refresh();

        $this->assertSame('2026-06-01', $payroll->from_date->toDateString());
        $this->assertSame('2026-06-10', $payroll->to_date->toDateString());
        $this->assertSame('2026-06-01', $payroll->salary_month->toDateString());
        $this->assertSame('partial', $payroll->calculated_status);
        $this->assertSame(10000.0, (float) $payroll->payable_salary);

        $this->assertDatabaseHas('employee_payrolls', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'working_days' => 10,
            'non_working_days' => 0,
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'status' => 'partial',
        ]);
    }

    public function test_salary_generate_pages_show_date_range_and_due_status(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Range Salary Employee',
            'monthly_salary' => 30000,
        ]);
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'payment_method' => 'bKash',
            'payment_date' => '2026-06-10',
            'status' => 'partial',
        ]);

        $listResponse = $this->actingAs($admin)->get('/admin/payroll');
        $showResponse = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id);

        $listResponse->assertOk();
        $listResponse->assertSee('2026-06-01 to 2026-06-10');
        $listResponse->assertSee('Partially Paid');
        $showResponse->assertOk();
        $showResponse->assertSee('Salary Period');
        $showResponse->assertSee('2026-06-01 to 2026-06-10');
        $showResponse->assertSee('BDT 5,000.00');
    }

    public function test_employee_dashboard_salary_history_shows_date_range(): void
    {
        $employeeUser = $this->user('employee');
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
        ]);
        $employee->payrolls()->create([
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($employeeUser)->get('/employee/dashboard');

        $response->assertOk();
        $response->assertSee('Salary Period');
        $response->assertSee('2026-06-01 to 2026-06-10');
        $response->assertSee('Paid');
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Date Range Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'active',
            'monthly_salary' => 30000,
        ], $overrides));
    }

    private function client(): Client
    {
        $clientUser = $this->user('client');

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Date Range Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }
}
