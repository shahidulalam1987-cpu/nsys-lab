<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeWorkStatus;
use App\Models\User;
use App\Services\PayrollCategoryService;
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

    public function test_recently_joined_employee_without_work_status_shows_zero_estimate(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $this->employee([
            'employee_id' => 'NSYS-EM-017',
            'name' => 'Recently Joined Employee',
            'joining_date' => '2026-06-12',
            'salary_day' => 12,
            'monthly_salary' => 7000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Recently Joined Employee');
        $response->assertSee('Estimated Amount Due');
        $response->assertSee('BDT 0.00');
        $response->assertSee('Pending Work Status');
        $response->assertSee('Work Status Required');
        $response->assertDontSee('BDT 7,000.00');
    }

    public function test_cycle_employee_estimate_uses_work_status_salary_count(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'Three Day Employee',
            'joining_date' => '2026-06-01',
            'salary_day' => 12,
            'monthly_salary' => 7000,
        ]);

        foreach (['2026-06-10', '2026-06-11', '2026-06-12'] as $date) {
            $this->workStatus($employee, null, $date, 'working');
        }

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Three Day Employee');
        $response->assertSee('BDT 700.00');
        $response->assertSee('Based on Work Status');
        $response->assertSee('Working: 3.00');
    }

    public function test_agency_internal_employee_estimate_includes_null_client_work_status(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'Internal Employee',
            'employee_type' => 'agency_internal',
            'joining_date' => '2026-06-01',
            'salary_day' => 12,
            'monthly_salary' => 9000,
        ]);

        $this->workStatus($employee, null, '2026-06-11', 'working');
        $this->workStatus($employee, null, '2026-06-12', 'half_day');

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Internal Employee');
        $response->assertSee('BDT 450.00');
        $response->assertSee('Working: 1.50');
    }

    public function test_client_assigned_employee_estimate_uses_client_specific_work_status(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $client = $this->client();
        $otherClient = $this->client();
        $employee = $this->employee([
            'name' => 'Client Specific Employee',
            'joining_date' => '2026-06-01',
            'salary_day' => 12,
            'monthly_salary' => 6000,
        ]);
        $this->assignment($employee, $client);

        $this->workStatus($employee, $client, '2026-06-11', 'working');
        $this->workStatus($employee, $client, '2026-06-12', 'working');
        $this->workStatus($employee, $otherClient, '2026-06-10', 'working');

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Client Specific Employee');
        $response->assertSee('BDT 400.00');
        $response->assertSee('Working: 2.00');
    }

    public function test_terminated_final_salary_pending_uses_work_status_estimate(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'Final Estimate Employee',
            'status' => 'terminated',
            'joining_date' => '2026-06-01',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
            'monthly_salary' => 12000,
        ]);

        $this->workStatus($employee, null, '2026-06-18', 'working');
        $this->workStatus($employee, null, '2026-06-19', 'working');
        $this->workStatus($employee, null, '2026-06-20', 'on_leave');

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due&employee_scope=terminated');

        $response->assertOk();
        $response->assertSee('Final Estimate Employee');
        $response->assertSee('BDT 800.00');
        $response->assertSee('Working: 2.00');
        $response->assertSee('Non Working: 1.00');
        $response->assertDontSee('BDT 12,000.00');
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
        $response->assertSee('Work Status Required');
        $response->assertDontSee('Generate Final Salary');
    }

    public function test_salary_generation_is_blocked_when_no_work_status_exists_for_zero_payable_salary(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'No Work Status Employee',
            'employee_type' => 'agency_internal',
            'joining_date' => '2026-06-12',
            'salary_day' => 12,
            'monthly_salary' => 7000,
        ]);

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'manual',
            'employee_id' => $employee->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-12',
            'to_date' => '2026-06-12',
            'working_days' => 0,
            'non_working_days' => 1,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $response->assertSessionHasErrors(['work_status' => 'Work Status records are required before salary generation.']);
        $this->assertSame(0, $employee->payrolls()->count());
    }

    public function test_terminated_final_settlement_can_generate_when_work_status_exists(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'Final Settlement Ready Employee',
            'employee_type' => 'agency_internal',
            'status' => 'terminated',
            'joining_date' => '2026-06-01',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
            'monthly_salary' => 12000,
        ]);
        $this->workStatus($employee, null, '2026-06-18', 'working');

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'manual',
            'employee_id' => $employee->id,
            'calculation_type' => 'date_to_date',
            'from_date' => '2026-06-18',
            'to_date' => '2026-06-20',
            'use_work_status_records' => 1,
            'payment_status' => 'upcoming',
            'paid_amount' => 0,
        ]);

        $payroll = $employee->payrolls()->first();

        $response->assertRedirect('/admin/payroll/' . $payroll?->id);
        $this->assertNotNull($payroll);
        $this->assertSame('1.00', $payroll->working_days);
        $this->assertSame('400.00', $payroll->payable_salary);
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

    public function test_payroll_category_resolver_returns_pending_work_status_for_active_employee_without_work_status(): void
    {
        Carbon::setTestNow('2026-06-15');

        $employee = $this->employee([
            'salary_day' => 12,
            'monthly_salary' => 7000,
        ]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertSame(PayrollCategoryService::PENDING_WORK_STATUS, $category['category']);
        $this->assertSame('Pending Work Status', $category['label']);
    }

    public function test_payroll_category_resolver_returns_salary_ready_for_active_employee_with_work_status(): void
    {
        Carbon::setTestNow('2026-06-15');

        $employee = $this->employee([
            'salary_day' => 12,
            'monthly_salary' => 7000,
        ]);
        $this->workStatus($employee, null, '2026-06-12', 'working');

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertSame(PayrollCategoryService::SALARY_READY, $category['category']);
    }

    public function test_payroll_category_resolver_returns_unpaid_for_active_employee_with_unpaid_payroll(): void
    {
        Carbon::setTestNow('2026-06-15');

        $client = $this->client();
        $employee = $this->employee(['salary_day' => 12]);
        $this->payroll($employee, $client, [
            'payable_salary' => 30000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertSame(PayrollCategoryService::UNPAID, $category['category']);
    }

    public function test_payroll_category_resolver_returns_paid_for_active_employee_with_paid_payroll(): void
    {
        Carbon::setTestNow('2026-06-15');

        $client = $this->client();
        $employee = $this->employee(['salary_day' => 12]);
        $this->payroll($employee, $client, [
            'payable_salary' => 30000,
            'paid_amount' => 30000,
            'payment_status' => 'paid',
        ]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertSame(PayrollCategoryService::PAID, $category['category']);
    }

    public function test_payroll_category_resolver_returns_final_settlement_pending_without_final_payroll(): void
    {
        Carbon::setTestNow('2026-06-24');

        $employee = $this->employee([
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
        ]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertSame(PayrollCategoryService::FINAL_SETTLEMENT_PENDING, $category['category']);
    }

    public function test_payroll_category_resolver_returns_final_settlement_unpaid_for_unpaid_final_payroll(): void
    {
        Carbon::setTestNow('2026-06-24');

        $client = $this->client();
        $employee = $this->employee([
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
        ]);
        $payroll = $this->payroll($employee, $client, [
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-20',
            'payable_salary' => 20000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertTrue($payroll->fresh()->isFinalSettlementPayroll());
        $this->assertTrue($payroll->fresh()->isFinalSettlementDue());
        $this->assertSame(PayrollCategoryService::FINAL_SETTLEMENT_UNPAID, $category['category']);
    }

    public function test_payroll_category_resolver_returns_final_settlement_paid_for_paid_final_payroll(): void
    {
        Carbon::setTestNow('2026-06-24');

        $client = $this->client();
        $employee = $this->employee([
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
        ]);
        $payroll = $this->payroll($employee, $client, [
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-20',
            'payable_salary' => 20000,
            'paid_amount' => 20000,
            'payment_status' => 'paid',
        ]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertTrue($payroll->fresh()->isFinalSettlementPayroll());
        $this->assertTrue($payroll->fresh()->isFinalSettlementPaid());
        $this->assertSame('Final Settlement Paid', $payroll->fresh()->settlementStatusLabel());
        $this->assertSame(PayrollCategoryService::FINAL_SETTLEMENT_PAID, $category['category']);
    }

    public function test_paid_terminated_employee_does_not_show_as_final_salary_pending(): void
    {
        Carbon::setTestNow('2026-06-24');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Mashfe Ahmed',
            'status' => 'terminated',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
            'monthly_salary' => 30000,
        ]);
        $payroll = $this->payroll($employee, $client, [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'working_days' => 1,
            'payable_salary' => 1000.02,
            'paid_amount' => 1000.02,
            'payment_status' => 'paid',
            'payroll_status' => 'paid',
        ]);
        $payroll->update(['is_current' => null]);

        $dueResponse = $this->actingAs($admin)->get('/admin/payroll?status=due&employee_scope=terminated');
        $paidResponse = $this->actingAs($admin)->get('/admin/payroll?status=paid');

        $this->assertTrue($employee->fresh()->load(['payrolls' => fn ($query) => $query->current()])->hasFinalSalaryPayroll());
        $this->assertTrue($payroll->fresh()->isFinalSettlementPaid());
        $dueResponse->assertOk();
        $dueResponse->assertSee('No salary records found.');
        $dueResponse->assertDontSee('Final salary not generated yet');
        $dueResponse->assertDontSee('Generate Final Salary');
        $paidResponse->assertOk();
        $paidResponse->assertSee('Mashfe Ahmed');
        $paidResponse->assertSee('BDT 1,000.02');
    }

    public function test_superseded_payrolls_do_not_affect_current_category(): void
    {
        Carbon::setTestNow('2026-06-15');

        $client = $this->client();
        $employee = $this->employee(['salary_day' => 12]);
        $old = $this->payroll($employee, $client, [
            'payable_salary' => 30000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'is_current' => false,
        ]);
        $current = $this->payroll($employee, $client, [
            'payable_salary' => 30000,
            'paid_amount' => 30000,
            'payment_status' => 'paid',
            'regenerated_from_id' => $old->id,
        ]);
        $old->update(['superseded_by_id' => $current->id]);

        $category = app(PayrollCategoryService::class)->resolveEmployee($employee);

        $this->assertSame(PayrollCategoryService::PAID, $category['category']);
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

    private function assignment(Employee $employee, Client $client): EmployeeAssignment
    {
        return EmployeeAssignment::create([
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'assigned_from' => '2026-06-01',
            'status' => 'active',
        ]);
    }

    private function workStatus(Employee $employee, ?Client $client, string $date, string $status): EmployeeWorkStatus
    {
        return EmployeeWorkStatus::create([
            'employee_id' => $employee->id,
            'client_id' => $client?->id,
            'work_date' => $date,
            'status' => $status,
            'salary_count_value' => EmployeeWorkStatus::salaryCountFor($status),
        ]);
    }
}
