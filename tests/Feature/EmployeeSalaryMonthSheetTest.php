<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryMonthSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_salary_report_from_generated_salary_records(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'employee_id' => 'NSYS-EM-010',
            'name' => 'Report Employee',
            'monthly_salary' => 30000,
        ]);

        $this->payroll($employee, $client, [
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-10',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 8,
            'non_working_days' => 2,
            'payable_salary' => 8000,
            'paid_amount' => 3000,
            'status' => 'partial',
            'payment_date' => '2026-06-10',
        ]);

        $response = $this->actingAs($admin)->get('/admin/salary-month-sheet?month=2026-06');

        $response->assertOk();
        $response->assertSee('Salary Report');
        $response->assertSee('NSYS-EM-010');
        $response->assertSee('Report Employee');
        $response->assertSee('Sheet Client');
        $response->assertSee('2026-06-01 to 2026-06-10');
        $response->assertSee('BDT 8,000.00');
        $response->assertSee('BDT 3,000.00');
        $response->assertSee('BDT 5,000.00');
        $response->assertSee('Partially Paid');
        $response->assertSee('Total Salary Records');
        $response->assertSee('Total Payable Salary');
        $response->assertSee('Total Paid Salary');
        $response->assertSee('Total Remaining Due');
    }

    public function test_salary_report_ignores_salary_day_records_without_generated_salary(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Only Salary Day Employee',
        ]);

        $employee->salaryDays()->create([
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'is_counted' => true,
            'reason' => 'active_working',
        ]);

        $response = $this->actingAs($admin)->get('/admin/salary-month-sheet?month=2026-06');

        $response->assertOk();
        $response->assertSee('No generated salary records found for this month.');
        $response->assertDontSee('<br>Only Salary Day Employee', false);
    }

    public function test_employee_and_status_filters_limit_salary_report_rows(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $included = $this->employee(['name' => 'Included Employee']);
        $excluded = $this->employee(['name' => 'Excluded Employee']);

        $this->payroll($included, $client, [
            'paid_amount' => 3000,
            'payable_salary' => 6000,
            'status' => 'partial',
        ]);
        $this->payroll($excluded, $client, [
            'paid_amount' => 6000,
            'payable_salary' => 6000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/salary-month-sheet?month=2026-06&employee_id=' . $included->id . '&status=partial');

        $response->assertOk();
        $response->assertSee('Included Employee');
        $response->assertSee('Partially Paid');
        $response->assertDontSee('<br>Excluded Employee', false);
    }

    public function test_admin_can_export_salary_report_csv(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'employee_id' => 'NSYS-EM-011',
            'name' => 'CSV Employee',
        ]);

        $this->payroll($employee, $client, [
            'working_days' => 10,
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'status' => 'paid',
            'payment_date' => '2026-06-15',
        ]);

        $response = $this->actingAs($admin)->get('/admin/salary-month-sheet/export?month=2026-06&status=paid');

        $response->assertOk();
        $response->assertDownload('employee-salary-report-2026-06.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Employee,Client,"Salary Period","Working Days","Payable Salary","Paid Salary","Remaining Due",Status,"Payment Date"', $csv);
        $this->assertStringContainsString('"NSYS-EM-011 CSV Employee","Sheet Client","2026-06-01 to 2026-06-10",10,10000.00,10000.00,0.00,Paid,2026-06-15', $csv);
    }

    private function payroll(Employee $employee, Client $client, array $overrides = [])
    {
        return $employee->payrolls()->create(array_merge([
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
            'paid_amount' => 0,
            'status' => 'unpaid',
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Report Test Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'monthly_salary' => 3000,
        ], $overrides));
    }

    private function client(): Client
    {
        $clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Sheet Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }
}
