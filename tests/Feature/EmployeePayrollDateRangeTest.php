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
            'calculation_type' => 'date_to_date',
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
        $this->assertSame('2026-06-01', $payroll->salary_period_from->toDateString());
        $this->assertSame('2026-06-10', $payroll->salary_period_to->toDateString());
        $this->assertSame('2026-06-01', $payroll->salary_month->toDateString());
        $this->assertSame('partial', $payroll->calculated_status);
        $this->assertSame(10000.0, (float) $payroll->payable_salary);
        $this->assertSame(30, $payroll->month_days);
        $this->assertSame(1000.0, (float) $payroll->daily_salary);

        $this->assertDatabaseHas('employee_payrolls', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'working_days' => 10,
            'non_working_days' => 0,
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'status' => 'partial',
        ]);
    }

    public function test_payable_salary_rounds_only_after_final_date_range_calculation(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 10000,
        ]);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'paid_amount' => 10000,
        ]);

        $payroll = $employee->payrolls()->first();

        $this->assertSame(10000.0, (float) $payroll->payable_salary);
        $this->assertSame('paid', $payroll->calculated_status);

        $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-04',
            'working_days' => 4,
            'non_working_days' => 0,
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->orderByDesc('id')->first();

        $this->assertSame(1333.33, (float) $payroll->payable_salary);
    }

    public function test_admin_can_generate_monthly_cycle_salary_from_optional_salary_days(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'monthly_salary' => 30000,
        ]);
        $employee->salaryDays()->create([
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'is_counted' => true,
            'reason' => 'active_working',
        ]);
        $employee->salaryDays()->create([
            'client_id' => $client->id,
            'date' => '2026-06-02',
            'is_counted' => false,
            'reason' => 'client_issue',
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_month' => '2026-06',
            'working_days' => null,
            'non_working_days' => null,
            'paid_amount' => 0,
            'payment_method' => null,
            'payment_date' => null,
            'note' => 'Monthly cycle salary',
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame('monthly_cycle', $payroll->calculation_type);
        $this->assertSame('2026-06-01', $payroll->salary_period_from->toDateString());
        $this->assertSame('2026-06-30', $payroll->salary_period_to->toDateString());
        $this->assertSame(1, $payroll->working_days);
        $this->assertSame(1, $payroll->non_working_days);
        $this->assertSame(1000.0, (float) $payroll->payable_salary);
        $this->assertSame('unpaid', $payroll->calculated_status);
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
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
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
        $listResponse->assertSee('/admin/employees/' . $employee->id, false);
        $listResponse->assertSee('2026-06-01 to 2026-06-10');
        $listResponse->assertSee('Partially Paid');
        $showResponse->assertOk();
        $showResponse->assertSee('Date To Date');
        $showResponse->assertSee('Salary Period');
        $showResponse->assertSee('2026-06-01 to 2026-06-10');
        $showResponse->assertSee('Daily Salary');
        $showResponse->assertSee('BDT 5,000.00');
    }

    public function test_admin_can_delete_salary_record_without_deleting_employee_or_client(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Delete Salary Employee',
        ]);
        $payroll = $employee->payrolls()->create([
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/delete');

        $response->assertRedirect('/admin/payroll');
        $response->assertSessionHas('success', 'Salary record deleted successfully.');
        $this->assertDatabaseMissing('employee_payrolls', [
            'id' => $payroll->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_employee_dashboard_salary_history_shows_date_range(): void
    {
        $employeeUser = $this->user('employee');
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
        ]);
        $employee->payrolls()->create([
            'calculation_type' => 'date_to_date',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
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
