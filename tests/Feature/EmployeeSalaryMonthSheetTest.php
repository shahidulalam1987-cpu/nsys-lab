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

    public function test_admin_can_view_employee_salary_month_sheet_summary(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'employee_id' => 'NSYS-EM-010',
            'name' => 'Sheet Employee',
            'monthly_salary' => 3000,
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

        $response = $this->actingAs($admin)->get('/admin/salary-month-sheet?month=2026-06');

        $response->assertOk();
        $response->assertSee('NSYS-EM-010');
        $response->assertSee('Sheet Employee');
        $response->assertSee('2026-06');
        $response->assertSee('BDT 3,000.00');
        $response->assertSee('BDT 100.00');
        $response->assertSee('Total Employees');
        $response->assertSee('Total Working Days');
        $response->assertSee('Total Payable Salary');
        $response->assertDontSee('Client</th>', false);
    }

    public function test_salary_month_sheet_rounds_only_after_final_payable_calculation(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'employee_id' => 'NSYS-EM-012',
            'name' => 'Rounded Sheet Employee',
            'monthly_salary' => 10000,
        ]);

        for ($day = 1; $day <= 30; $day++) {
            $employee->salaryDays()->create([
                'client_id' => $client->id,
                'date' => '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'is_counted' => true,
                'reason' => 'active_working',
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/salary-month-sheet?month=2026-06');

        $response->assertOk();
        $response->assertSee('BDT 10,000.00');
        $response->assertDontSee('BDT 9,999.90');
    }

    public function test_employee_filter_limits_salary_month_sheet_rows(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $included = $this->employee(['name' => 'Included Employee']);
        $excluded = $this->employee(['name' => 'Excluded Employee']);

        foreach ([$included, $excluded] as $employee) {
            $employee->salaryDays()->create([
                'client_id' => $client->id,
                'date' => '2026-06-01',
                'is_counted' => true,
                'reason' => 'active_working',
            ]);
        }

        $response = $this->actingAs($admin)
            ->get('/admin/salary-month-sheet?month=2026-06&employee_id=' . $included->id);

        $response->assertOk();
        $response->assertSee('Included Employee');
        $response->assertDontSee('Excluded Employee</td>', false);
    }

    public function test_admin_can_export_employee_salary_month_sheet_csv(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'employee_id' => 'NSYS-EM-011',
            'name' => 'CSV Employee',
            'monthly_salary' => 3000,
        ]);

        $employee->salaryDays()->create([
            'client_id' => $client->id,
            'date' => '2026-06-01',
            'is_counted' => true,
            'reason' => 'active_working',
        ]);

        $response = $this->actingAs($admin)->get('/admin/salary-month-sheet/export?month=2026-06');

        $response->assertOk();
        $response->assertDownload('employee-salary-month-sheet-2026-06.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Employee ID","Employee Name",Month,"Counted Days","Non Counted Days","Monthly Salary","Payable Salary"', $csv);
        $this->assertStringContainsString('NSYS-EM-011,"CSV Employee",2026-06,1,0,3000.00,100.00', $csv);
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
            'name' => 'Sheet Test Employee',
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
