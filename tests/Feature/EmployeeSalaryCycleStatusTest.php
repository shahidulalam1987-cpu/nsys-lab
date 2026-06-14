<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryCycleStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_confirmation_date_day_is_used_when_salary_day_is_missing(): void
    {
        Carbon::setTestNow('2026-06-02');

        $employee = $this->employee([
            'confirmation_date' => '2026-05-07',
            'salary_day' => null,
        ]);

        $this->assertSame(7, $employee->salaryCycleDay());
        $this->assertSame('2026-06-07', $employee->nextSalaryDate()?->toDateString());
        $this->assertSame('upcoming', $employee->salaryCycleStatus());
    }

    public function test_employee_profile_shows_salary_cycle_status(): void
    {
        Carbon::setTestNow('2026-06-08');

        $admin = $this->admin();
        $employee = $this->employee([
            'confirmation_date' => '2026-05-07',
            'salary_day' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);

        $response->assertOk();
        $response->assertSee('Confirmation Date');
        $response->assertSee('Salary Day');
        $response->assertSee('Next Salary Date');
        $response->assertSee('Current Salary Status');
        $response->assertSee('Unpaid');
    }

    public function test_salary_generate_upcoming_filter_uses_next_five_day_salary_window(): void
    {
        Carbon::setTestNow('2026-06-02');

        $admin = $this->admin();
        $client = $this->client();
        $upcomingEmployee = $this->employee([
            'name' => 'Cycle Upcoming Employee',
            'salary_day' => 7,
        ]);
        $futureEmployee = $this->employee([
            'name' => 'Cycle Future Employee',
            'salary_day' => 20,
        ]);

        $this->payroll($upcomingEmployee, $client, [
            'payment_status' => 'upcoming',
        ]);
        $this->payroll($futureEmployee, $client, [
            'payment_status' => 'upcoming',
        ]);
        $this->employee([
            'name' => 'Cycle Upcoming Without Payroll',
            'salary_day' => 7,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=upcoming');

        $response->assertOk();
        $response->assertSee('Cycle Upcoming Employee');
        $response->assertSee('Cycle Upcoming Without Payroll');
        $response->assertSee('Salary Cycle Employees');
        $response->assertDontSee('/admin/employees/' . $futureEmployee->id, false);
    }

    public function test_salary_generate_due_filter_includes_past_due_unpaid_cycle(): void
    {
        Carbon::setTestNow('2026-06-08');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Past Due Employee',
            'salary_day' => 7,
        ]);
        $this->employee([
            'name' => 'Past Due Without Payroll',
            'salary_day' => 7,
        ]);

        $this->payroll($employee, $client, [
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Past Due Employee');
        $response->assertSee('Past Due Without Payroll');
        $response->assertSee('Unpaid');
    }

    public function test_unpaid_salary_page_shows_terminated_final_settlement_records(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $client = $this->client();
        $terminated = $this->employee([
            'name' => 'Final Settlement Employee',
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
        ]);

        $this->payroll($terminated, $client, [
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-20',
            'working_days' => 14,
            'non_working_days' => 6,
            'payable_salary' => 14000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due&employee_scope=terminated');

        $response->assertOk();
        $response->assertSee('Terminated Final Settlement');
        $response->assertSee('Final Settlement Employee');
        $response->assertSee('Final Settlement Unpaid');
        $response->assertSee('Final Settlement Overdue: 4 Days');
        $response->assertSee('Working: 14.00');
        $response->assertSee('Non Working: 6.00');
    }

    public function test_due_filter_shows_final_salary_pending_for_terminated_employee_without_payroll(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $this->employee([
            'name' => 'Final Salary Pending Employee',
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due&employee_scope=terminated');

        $response->assertOk();
        $response->assertSee('Final Salary Pending Employee');
        $response->assertSee('Final salary not generated yet');
        $response->assertSee('Generate Final Salary');
    }

    public function test_terminated_employee_with_unpaid_current_payroll_is_not_also_final_salary_pending(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $client = $this->client();
        $terminated = $this->employee([
            'name' => 'Mashfe Ahmed',
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
        ]);

        $this->payroll($terminated, $client, [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'working_days' => 12,
            'non_working_days' => 0,
            'payable_salary' => 12000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due&employee_scope=terminated');

        $response->assertOk();
        $response->assertSee('Mashfe Ahmed');
        $response->assertSee('Final Settlement Unpaid');
        $response->assertDontSee('Final salary not generated yet');
        $response->assertDontSee('Generate Final Salary');
    }

    public function test_terminated_employee_profile_shows_final_settlement_card(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $client = $this->client();
        $terminated = $this->employee([
            'name' => 'Profile Settlement Employee',
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
        ]);
        $this->payroll($terminated, $client, [
            'payable_salary' => 12000,
            'paid_amount' => 5000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/employees/' . $terminated->id);

        $response->assertOk();
        $response->assertSee('Final Settlement');
        $response->assertSee('Remaining Due');
        $response->assertSee('BDT 7,000.00');
        $response->assertSee('Final Settlement Unpaid');
    }

    public function test_payment_amount_overrides_salary_cycle_status(): void
    {
        Carbon::setTestNow('2026-06-08');

        $client = $this->client();
        $paidEmployee = $this->employee(['salary_day' => 7]);
        $partialEmployee = $this->employee(['salary_day' => 7]);

        $paidPayroll = $this->payroll($paidEmployee, $client, [
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'payment_status' => 'paid',
        ]);
        $partialPayroll = $this->payroll($partialEmployee, $client, [
            'payable_salary' => 10000,
            'paid_amount' => 4000,
            'payment_status' => 'partial',
        ]);

        $this->assertSame('paid', $paidPayroll->fresh()->calculated_status);
        $this->assertSame('partial', $partialPayroll->fresh()->calculated_status);
    }

    public function test_salary_status_modules_can_export_csv_and_excel(): void
    {
        Carbon::setTestNow('2026-06-08');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Paid Export Employee',
            'salary_day' => 7,
        ]);

        $this->payroll($employee, $client, [
            'payable_salary' => 10000,
            'paid_amount' => 10000,
            'payment_status' => 'paid',
            'payment_date' => '2026-06-07',
            'payment_method' => 'Bank',
            'transaction_id' => 'PAID-EXPORT',
        ]);

        $csv = $this->actingAs($admin)->get('/admin/payroll/export/csv?status=paid');
        $excel = $this->actingAs($admin)->get('/admin/payroll/export/excel?status=paid');

        $csv->assertOk();
        $csv->assertDownload('salary-generate-report.csv');
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Paid Export Employee', $csvContent);
        $this->assertStringContainsString('PAID-EXPORT', $csvContent);

        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.ms-excel');
        $excel->assertSee('Paid Export Employee');
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
            'name' => 'Cycle Status Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-05-01',
            'status' => 'active',
            'monthly_salary' => 30000,
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
            'company_name' => 'Cycle Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);
    }

    private function payroll(Employee $employee, Client $client, array $overrides = [])
    {
        return $employee->payrolls()->create(array_merge([
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-30',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'working_days' => 30,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 1000,
            'salary_month' => '2026-06-01',
            'payable_salary' => 30000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ], $overrides));
    }
}
