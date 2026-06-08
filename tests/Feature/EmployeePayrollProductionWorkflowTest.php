<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePayrollProductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_salary_generation_requires_regenerate_confirmation(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();

        $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));
        $existing = $employee->payrolls()->first();

        $response = $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));

        $response->assertOk();
        $response->assertSee('Salary already generated for this period.');
        $response->assertSee('View Existing Salary');
        $response->assertSee('Regenerate');
        $this->assertSame(1, $employee->payrolls()->count());

        $regenerate = $this->actingAs($admin)->post('/admin/payroll', array_merge(
            $this->salaryPayload($employee, $client),
            ['confirm_regenerate' => 1]
        ));

        $latest = $employee->payrolls()->orderByDesc('id')->first();

        $regenerate->assertRedirect('/admin/payroll/' . $latest->id);
        $this->assertSame(2, $employee->payrolls()->count());
        $this->assertSame('generated', $existing->fresh()->generation_status);
        $this->assertSame('regenerated', $latest->generation_status);
        $this->assertSame($existing->id, $latest->regenerated_from_id);
        $this->assertDatabaseHas('employee_payroll_audits', [
            'employee_payroll_id' => $latest->id,
            'action' => 'salary_regenerated',
        ]);
    }

    public function test_payroll_can_be_approved_and_marked_paid_with_audit_log(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();

        $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));
        $payroll = $employee->payrolls()->first();

        $this->assertSame('generated', $payroll->payroll_status);
        $this->assertDatabaseHas('employee_payroll_audits', [
            'employee_payroll_id' => $payroll->id,
            'action' => 'salary_generated',
        ]);

        $approve = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/approve');
        $approve->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame('approved', $payroll->fresh()->payroll_status);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/update', [
            'paid_amount' => 10000,
            'payment_method' => 'Bank Transfer',
            'payment_date' => '2026-06-30',
            'transaction_id' => 'SAL-PAID-1',
        ]);

        $paid = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/mark-paid');
        $paid->assertRedirect('/admin/payroll/' . $payroll->id);

        $payroll->refresh();
        $this->assertSame('paid', $payroll->payroll_status);
        $this->assertSame('paid', $payroll->calculated_status);
        $this->assertNotNull($payroll->approved_at);
        $this->assertNotNull($payroll->paid_at);
        $this->assertDatabaseHas('employee_payroll_audits', [
            'employee_payroll_id' => $payroll->id,
            'action' => 'salary_approved',
        ]);
        $this->assertDatabaseHas('employee_payroll_audits', [
            'employee_payroll_id' => $payroll->id,
            'action' => 'salary_paid',
        ]);

        $show = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id);
        $show->assertOk();
        $show->assertSee('Approval History');
        $show->assertSee('Payment History');
        $show->assertSee('Audit Log');
        $show->assertSee('Salary Paid');
    }

    public function test_employee_profile_salary_ledger_and_exports_are_available(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $employee->payrolls()->create([
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
            'paid_amount' => 6000,
            'payment_method' => 'Bank',
            'payment_date' => '2026-06-10',
        ]);

        $profile = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);
        $profile->assertOk();
        $profile->assertSee('Salary Ledger');
        $profile->assertSee('Total Generated Salary');
        $profile->assertSee('BDT 10,000.00');

        $csv = $this->actingAs($admin)->get('/admin/employees/' . $employee->id . '/salary-ledger/export/csv');
        $excel = $this->actingAs($admin)->get('/admin/employees/' . $employee->id . '/salary-ledger/export/excel');

        $csv->assertOk();
        $csv->assertDownload('employee-salary-ledger-' . $employee->id . '.csv');
        $this->assertStringContainsString('2026-06', $csv->streamedContent());
        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $excel->assertSee('Salary Ledger');
    }

    private function salaryPayload(Employee $employee, Client $client): array
    {
        return [
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'working_days' => 10,
            'non_working_days' => 0,
            'paid_amount' => 0,
        ];
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
            'name' => 'Production Payroll Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'confirmation_date' => '2026-06-10',
            'status' => 'active',
            'monthly_salary' => 30000,
        ], $overrides));
    }

    private function client(): Client
    {
        $clientUser = $this->user('client');

        return Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Production Payroll Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }
}
