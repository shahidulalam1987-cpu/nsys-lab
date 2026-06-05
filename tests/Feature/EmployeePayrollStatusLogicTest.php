<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeePayrollStatusLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_status_rule_marks_exact_payment_as_paid(): void
    {
        $this->assertSame('unpaid', EmployeePayroll::statusFor(10000, 0));
        $this->assertSame('partial', EmployeePayroll::statusFor(10000, 9999.99));
        $this->assertSame('paid', EmployeePayroll::statusFor(10000, 10000));
        $this->assertSame('paid', EmployeePayroll::statusFor(10000, 12000));
    }

    public function test_stale_existing_payroll_displays_calculated_paid_status(): void
    {
        $admin = $this->admin();
        $employee = $this->employee(['name' => 'Exact Paid Employee']);
        $payrollId = $this->insertPayroll([
            'employee_id' => $employee->id,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'status' => 'partial',
        ]);

        $payroll = EmployeePayroll::findOrFail($payrollId);
        $this->assertSame('paid', $payroll->status);

        $response = $this->actingAs($admin)->get('/admin/payroll/' . $payrollId);

        $response->assertOk();
        $response->assertSee('Paid');
        $response->assertDontSee('Partial');
    }

    public function test_payroll_list_status_filter_uses_calculated_status(): void
    {
        $admin = $this->admin();
        $paidEmployee = $this->employee(['name' => 'Calculated Paid']);
        $partialEmployee = $this->employee(['name' => 'Calculated Partial']);

        $this->insertPayroll([
            'employee_id' => $paidEmployee->id,
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'status' => 'partial',
        ]);
        $this->insertPayroll([
            'employee_id' => $partialEmployee->id,
            'payable_salary' => 10000,
            'paid_amount' => 5000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=paid');

        $response->assertOk();
        $response->assertSee('Calculated Paid');
        $response->assertDontSee('BDT 5,000.00');
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
            'name' => 'Payroll Status Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'monthly_salary' => 10000,
        ], $overrides));
    }

    private function insertPayroll(array $overrides = []): int
    {
        return DB::table('employee_payrolls')->insertGetId(array_merge([
            'employee_id' => $this->employee()->id,
            'client_id' => null,
            'salary_month' => '2026-06-01',
            'payable_salary' => 10000,
            'paid_amount' => 0,
            'payment_method' => null,
            'payment_date' => null,
            'status' => 'unpaid',
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
