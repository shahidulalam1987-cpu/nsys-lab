<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeWorkStatus;
use App\Models\User;
use App\Services\PayrollCategoryService;
use App\Services\PayrollEstimateService;
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
        $upcomingEmployee = $this->employee([
            'name' => 'Cycle Upcoming Employee',
            'salary_day' => 7,
        ]);
        $futureEmployee = $this->employee([
            'name' => 'Cycle Future Employee',
            'salary_day' => 20,
        ]);

        $this->employee([
            'name' => 'Cycle Upcoming Without Payroll',
            'salary_day' => 7,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=upcoming');

        $response->assertOk();
        $response->assertSee('Cycle Upcoming Employee');
        $response->assertSee('Cycle Upcoming Without Payroll');
        $response->assertSee('Upcoming Salary');
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
        $response->assertSee('Add Work Status');
        $response->assertDontSee('BDT 7,000.00');
    }

    public function test_pending_work_status_action_prefills_monthly_cycle_and_returns_to_salary_generate(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'Work Status Shortcut Employee',
            'employee_type' => 'agency_internal',
            'salary_day' => 12,
        ]);

        $payrollPage = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $payrollPage->assertOk();
        $payrollPage->assertSee('Add Work Status');
        $payrollPage->assertSee('entry_mode=monthly');
        $payrollPage->assertSee('employee_id=' . $employee->id);
        $payrollPage->assertSee('salary_month=2026-06');
        $payrollPage->assertDontSee('client_id=', false);

        $createPage = $this->actingAs($admin)->get('/admin/work-status/create?' . http_build_query([
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-06',
            'status' => 'working',
            'note' => 'Salary cycle work status entry',
            'return_to' => '/admin/payroll?status=due',
        ]));

        $createPage->assertOk();
        $createPage->assertSee('value="monthly" selected', false);
        $createPage->assertSee('value="' . $employee->id . '" selected', false);
        $createPage->assertSee('value="2026-06"', false);
        $createPage->assertSee('Salary cycle work status entry');

        $rows = collect(range(0, 30))->map(function (int $offset) {
            return [
                'date' => Carbon::parse('2026-05-13')->addDays($offset)->toDateString(),
                'day_type' => 'working',
                'status' => 'working',
            ];
        })->all();

        $save = $this->actingAs($admin)->post('/admin/work-status', [
            'entry_mode' => 'monthly',
            'employee_id' => $employee->id,
            'salary_month' => '2026-06',
            'duplicate_action' => 'skip',
            'monthly_rows' => $rows,
            'status' => 'working',
            'note' => 'Salary cycle work status entry',
            'return_to' => '/admin/payroll?status=due',
        ]);

        $save->assertRedirect('/admin/payroll?status=due');
        $this->assertDatabaseCount('employee_work_statuses', 31);
        $this->assertDatabaseMissing('employee_work_statuses', [
            'employee_id' => $employee->id,
            'client_id' => 1,
        ]);
        $this->assertDatabaseCount('employee_payrolls', 0);
    }

    public function test_pending_work_status_action_prefills_active_assignment_client(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Assigned Work Status Shortcut Employee',
            'employee_type' => 'client_assigned',
            'salary_day' => 12,
        ]);
        $this->assignment($employee, $client);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('employee_id=' . $employee->id);
        $response->assertSee('client_id=' . $client->id);
        $response->assertSee('Add Work Status');
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
        $response->assertSee('Add Work Status');
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
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
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

    public function test_each_employee_renders_in_exactly_one_priority_payroll_stage(): void
    {
        Carbon::setTestNow('2026-06-02');

        $admin = $this->admin();
        $client = $this->client();
        $upcoming = $this->employee(['name' => 'Exclusive Upcoming', 'salary_day' => 7]);
        $pending = $this->employee(['name' => 'Exclusive Pending', 'salary_day' => 1]);
        $ready = $this->employee(['name' => 'Exclusive Ready', 'salary_day' => 1]);
        $this->workStatus($ready, null, '2026-06-01', 'working');

        $unpaid = $this->employee(['name' => 'Exclusive Unpaid', 'salary_day' => 1]);
        $this->payroll($unpaid, $client, [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $paid = $this->employee(['name' => 'Exclusive Paid', 'salary_day' => 1]);
        $this->payroll($paid, $client, ['paid_amount' => 30000, 'payment_status' => 'paid']);

        $finalPending = $this->employee([
            'name' => 'Exclusive Final Pending',
            'status' => 'terminated',
            'last_working_date' => '2026-06-01',
            'salary_day' => 1,
        ]);
        $finalUnpaid = $this->employee([
            'name' => 'Exclusive Final Unpaid',
            'status' => 'terminated',
            'last_working_date' => '2026-06-01',
            'salary_day' => 1,
        ]);
        $this->payroll($finalUnpaid, $client, [
            'salary_period_to' => '2026-06-01',
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);
        $finalPaid = $this->employee([
            'name' => 'Exclusive Final Paid',
            'status' => 'terminated',
            'last_working_date' => '2026-06-01',
            'salary_day' => 1,
        ]);
        $this->payroll($finalPaid, $client, [
            'salary_period_to' => '2026-06-01',
            'paid_amount' => 30000,
            'payment_status' => 'paid',
        ]);

        $expected = [
            $upcoming->id => PayrollCategoryService::UPCOMING,
            $pending->id => PayrollCategoryService::PENDING_WORK_STATUS,
            $ready->id => PayrollCategoryService::SALARY_READY,
            $unpaid->id => PayrollCategoryService::UNPAID,
            $paid->id => PayrollCategoryService::PAID,
            $finalPending->id => PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            $finalUnpaid->id => PayrollCategoryService::FINAL_SETTLEMENT_UNPAID,
            $finalPaid->id => PayrollCategoryService::FINAL_SETTLEMENT_PAID,
        ];

        foreach ($expected as $employeeId => $category) {
            $employee = Employee::findOrFail($employeeId);
            $this->assertSame($category, app(PayrollCategoryService::class)->resolveEmployee($employee)['category']);
        }

        $upcomingPage = $this->actingAs($admin)->get('/admin/payroll?status=upcoming');
        $duePage = $this->actingAs($admin)->get('/admin/payroll?status=due');
        $paidPage = $this->actingAs($admin)->get('/admin/payroll?status=paid');

        $upcomingPage->assertSee('/admin/employees/' . $upcoming->id, false);
        foreach ([$pending, $ready, $unpaid, $paid, $finalPending, $finalUnpaid, $finalPaid] as $employee) {
            $upcomingPage->assertDontSee('/admin/employees/' . $employee->id, false);
        }

        foreach ([$pending, $ready, $unpaid, $finalPending, $finalUnpaid] as $employee) {
            $duePage->assertSee('/admin/employees/' . $employee->id, false);
        }
        foreach ([$upcoming, $paid, $finalPaid] as $employee) {
            $duePage->assertDontSee('/admin/employees/' . $employee->id, false);
        }

        foreach ([$paid, $finalPaid] as $employee) {
            $paidPage->assertSee('/admin/employees/' . $employee->id, false);
        }
        foreach ([$upcoming, $pending, $ready, $unpaid, $finalPending, $finalUnpaid] as $employee) {
            $paidPage->assertDontSee('/admin/employees/' . $employee->id, false);
        }
    }

    public function test_employee_without_confirmation_is_excluded_from_salary_cycle_lists(): void
    {
        Carbon::setTestNow('2026-06-19');

        $admin = $this->admin();
        $this->employee([
            'name' => 'Unconfirmed Employee',
            'confirmation_date' => null,
            'salary_day' => 12,
        ]);

        $upcoming = $this->actingAs($admin)->get('/admin/payroll?status=upcoming');
        $due = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $upcoming->assertOk()->assertDontSee('Upcoming Salary This Week');
        $due->assertOk()->assertDontSee('Pending Work Status');
        $due->assertDontSee('Salary Ready / Pending Generation');
    }

    public function test_salary_day_before_confirmation_moves_first_cycle_to_next_month(): void
    {
        Carbon::setTestNow('2026-06-19');

        $employee = $this->employee([
            'confirmation_date' => '2026-06-18',
            'salary_day' => 12,
        ]);

        $this->assertSame('2026-07-12', $employee->currentSalaryDueDate()?->toDateString());
        $this->assertSame('2026-07-12', $employee->nextSalaryDate()?->toDateString());
    }

    public function test_work_status_before_confirmation_is_ignored_and_after_confirmation_is_counted(): void
    {
        Carbon::setTestNow('2026-06-20');

        $employee = $this->employee([
            'confirmation_date' => '2026-06-18',
            'salary_day' => 20,
            'monthly_salary' => 9000,
        ]);
        $this->workStatus($employee, null, '2026-06-17', 'working');
        $this->workStatus($employee, null, '2026-06-18', 'working');
        $this->workStatus($employee, null, '2026-06-19', 'half_day');

        $estimate = app(PayrollEstimateService::class)->estimateCycle($employee, Carbon::parse('2026-06-20'));

        $this->assertSame('2026-06-18', $estimate['salary_period_start']->toDateString());
        $this->assertSame(1.5, $estimate['working_salary_count']);
        $this->assertSame(450.0, $estimate['estimated_payable_salary']);
    }

    public function test_payroll_estimate_caps_thirty_two_work_status_days_at_thirty(): void
    {
        Carbon::setTestNow('2026-06-19');

        $admin = $this->admin();
        $employee = $this->employee([
            'name' => 'Thirty Two Day Employee',
            'employee_type' => 'agency_internal',
            'confirmation_date' => '2026-05-16',
            'salary_day' => 16,
            'monthly_salary' => 5000,
        ]);

        for ($date = Carbon::parse('2026-05-16'); $date->lte(Carbon::parse('2026-06-16')); $date->addDay()) {
            $this->workStatus($employee, null, $date->toDateString(), 'working');
        }

        $estimate = app(PayrollEstimateService::class)->estimateCycle($employee, Carbon::parse('2026-06-16'));

        $this->assertSame(32.0, $estimate['actual_work_status_count']);
        $this->assertSame(32.0, $estimate['working_salary_count']);
        $this->assertSame(30.0, $estimate['effective_salary_count']);
        $this->assertTrue($estimate['cap_applied']);
        $this->assertSame(5000.0, $estimate['estimated_payable_salary']);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');
        $response->assertOk();
        $response->assertSee('Working: 32.00');
        $response->assertSee('Payable Count: 30.00');
        $response->assertSee('BDT 5,000.00');
    }

    public function test_payroll_estimate_preserves_twenty_nine_and_half_salary_count(): void
    {
        $employee = $this->employee([
            'employee_type' => 'agency_internal',
            'confirmation_date' => '2026-06-01',
            'salary_day' => 30,
            'monthly_salary' => 6000,
        ]);

        foreach (range(1, 29) as $day) {
            $this->workStatus($employee, null, '2026-06-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT), 'working');
        }
        $this->workStatus($employee, null, '2026-06-30', 'half_day');

        $estimate = app(PayrollEstimateService::class)->estimateCycle($employee, Carbon::parse('2026-06-30'));

        $this->assertSame(29.5, $estimate['actual_work_status_count']);
        $this->assertSame(29.5, $estimate['effective_salary_count']);
        $this->assertFalse($estimate['cap_applied']);
        $this->assertSame(5900.0, $estimate['estimated_payable_salary']);
    }

    public function test_payroll_estimate_caps_thirty_and_half_salary_count_at_thirty(): void
    {
        $employee = $this->employee([
            'employee_type' => 'agency_internal',
            'confirmation_date' => '2026-05-31',
            'salary_day' => 30,
            'monthly_salary' => 6000,
        ]);

        for ($date = Carbon::parse('2026-05-31'); $date->lt(Carbon::parse('2026-06-30')); $date->addDay()) {
            $this->workStatus($employee, null, $date->toDateString(), 'working');
        }
        $this->workStatus($employee, null, '2026-06-30', 'half_day');

        $estimate = app(PayrollEstimateService::class)->estimateCycle($employee, Carbon::parse('2026-06-30'));

        $this->assertSame(30.5, $estimate['actual_work_status_count']);
        $this->assertSame(30.0, $estimate['effective_salary_count']);
        $this->assertTrue($estimate['cap_applied']);
        $this->assertSame(6000.0, $estimate['estimated_payable_salary']);
    }

    public function test_work_status_salary_preview_counts_only_records_after_confirmation(): void
    {
        Carbon::setTestNow('2026-06-20');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'confirmation_date' => '2026-06-18',
            'salary_day' => 20,
            'monthly_salary' => 9000,
        ]);
        $this->workStatus($employee, $client, '2026-06-17', 'working');
        $this->workStatus($employee, $client, '2026-06-18', 'working');
        $this->workStatus($employee, $client, '2026-06-19', 'half_day');

        $response = $this->actingAs($admin)->post('/admin/payroll', [
            'generation_mode' => 'work_status',
            'work_status_action' => 'preview',
            'salary_month' => '2026-06',
            'employee_id' => $employee->id,
            'client_id' => $client->id,
        ]);

        $response->assertOk();
        $response->assertSee('1.50');
        $response->assertSee('BDT 450.00');
        $response->assertDontSee('BDT 600.00');
    }

    public function test_confirmed_employee_with_salary_date_in_next_five_days_appears_upcoming(): void
    {
        Carbon::setTestNow('2026-06-18');

        $admin = $this->admin();
        $this->employee([
            'name' => 'Confirmed Upcoming Employee',
            'confirmation_date' => '2026-06-01',
            'salary_day' => 22,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=upcoming');

        $response->assertOk();
        $response->assertSee('Upcoming Salary This Week');
        $response->assertSee('Confirmed Upcoming Employee');
    }

    public function test_confirmed_past_due_employee_without_work_status_is_pending_not_unpaid(): void
    {
        Carbon::setTestNow('2026-06-19');

        $admin = $this->admin();
        $this->employee([
            'name' => 'Confirmed Pending Work Status Employee',
            'confirmation_date' => '2026-06-01',
            'salary_day' => 12,
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Pending Work Status');
        $response->assertSee('Confirmed Pending Work Status Employee');
        $response->assertDontSee('Unpaid Salary Due');
    }

    public function test_generated_unpaid_payroll_appears_only_in_unpaid_section(): void
    {
        Carbon::setTestNow('2026-06-19');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Generated Unpaid Employee',
            'confirmation_date' => '2026-06-01',
            'salary_day' => 12,
        ]);
        $this->payroll($employee, $client, [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Unpaid Salary Due');
        $response->assertSee('Generated Unpaid Employee');
        $response->assertDontSee('Salary Ready / Pending Generation');
        $response->assertDontSee('Work Status Required');
    }

    public function test_cross_month_payroll_overdue_uses_cycle_end_salary_date(): void
    {
        Carbon::setTestNow('2026-06-19');

        $admin = $this->admin();
        $client = $this->client();
        $employee = $this->employee([
            'name' => 'Farzana Overdue Regression',
            'confirmation_date' => '2026-05-16',
            'salary_day' => 16,
        ]);
        $payroll = $this->payroll($employee, $client, [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-16',
            'salary_period_to' => '2026-06-16',
            'from_date' => '2026-05-16',
            'to_date' => '2026-06-16',
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->assertSame('2026-06-16', $payroll->salaryDueDate()?->toDateString());
        $this->assertSame(3, $payroll->overdueDays());
        $this->assertSame(-3, $payroll->daysUntilDue());
        $this->assertSame('3 Days Overdue', $payroll->overdueLabel());

        $response = $this->actingAs($admin)->get('/admin/payroll?status=due');

        $response->assertOk();
        $response->assertSee('Farzana Overdue Regression');
        $response->assertSee('2026-06-16');
        $response->assertSee('3 Days Overdue');
        $response->assertDontSee('34 Days Overdue');
    }

    public function test_calendar_month_payroll_due_date_advances_past_completed_period(): void
    {
        Carbon::setTestNow('2026-06-19');

        $employee = $this->employee([
            'confirmation_date' => '2026-05-16',
            'salary_day' => 16,
        ]);
        $payroll = $this->payroll($employee, $this->client(), [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
        ]);

        $this->assertSame('2026-06-16', $payroll->salaryDueDate()?->toDateString());
        $this->assertSame(3, $payroll->overdueDays());
        $this->assertSame('3 Days Overdue', $payroll->overdueLabel());
    }

    public function test_payroll_period_ending_before_salary_day_uses_same_month_due_date(): void
    {
        Carbon::setTestNow('2026-06-19');

        $employee = $this->employee([
            'confirmation_date' => '2026-05-16',
            'salary_day' => 16,
        ]);
        $payroll = $this->payroll($employee, $this->client(), [
            'salary_month' => '2026-06-01',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-15',
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-15',
        ]);

        $this->assertSame('2026-06-16', $payroll->salaryDueDate()?->toDateString());
        $this->assertSame(3, $payroll->overdueDays());
    }

    public function test_terminated_final_estimate_starts_from_confirmation_date(): void
    {
        Carbon::setTestNow('2026-06-24');

        $employee = $this->employee([
            'status' => 'terminated',
            'confirmation_date' => '2026-06-18',
            'last_working_date' => '2026-06-20',
            'salary_day' => 20,
            'monthly_salary' => 12000,
        ]);
        $this->workStatus($employee, null, '2026-06-17', 'working');
        $this->workStatus($employee, null, '2026-06-18', 'working');
        $this->workStatus($employee, null, '2026-06-19', 'working');

        $estimate = app(PayrollEstimateService::class)->estimateCycle($employee, $employee->last_working_date);

        $this->assertSame('2026-06-18', $estimate['salary_period_start']->toDateString());
        $this->assertSame('2026-06-20', $estimate['salary_period_end']->toDateString());
        $this->assertSame(2.0, $estimate['working_salary_count']);
        $this->assertSame(800.0, $estimate['estimated_payable_salary']);
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
            'confirmation_date' => '2026-05-01',
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
