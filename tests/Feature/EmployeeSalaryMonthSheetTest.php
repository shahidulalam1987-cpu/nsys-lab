<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\FinanceAccountLedger;
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
        $response->assertSee('No generated salary records found for the selected month.');
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
        $this->assertStringContainsString('Employee,Client,"Salary Month","Salary Period","Working Days","Payable Salary","Paid Salary","Remaining Due",Status,"Payment Source Status","Payment Date"', $csv);
        $this->assertStringContainsString('"NSYS-EM-011 CSV Employee","Sheet Client",2026-06,"2026-06-01 to 2026-06-10",10.00,10000.00,10000.00,0.00,Paid,"Legacy Manual Paid",2026-06-15', $csv);
    }

    public function test_salary_report_classifies_payment_sources_and_exposes_superseded_history(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $legacyEmployee = $this->employee(['name' => 'Legacy Paid Employee']);
        $linkedEmployee = $this->employee(['name' => 'Ledger Linked Employee']);
        $historicalEmployee = $this->employee(['name' => 'Superseded Paid Employee']);

        $legacy = $this->payroll($legacyEmployee, $client, [
            'payable_salary' => 5000,
            'paid_amount' => 5000,
            'payment_status' => 'paid',
        ]);
        $linked = $this->payroll($linkedEmployee, $client, [
            'payable_salary' => 6000,
            'paid_amount' => 6000,
            'payment_status' => 'paid',
        ]);
        $historical = $this->payroll($historicalEmployee, $client, [
            'payable_salary' => 7000,
            'paid_amount' => 7000,
            'payment_status' => 'paid',
            'is_current' => false,
        ]);

        FinanceAccountLedger::create([
            'employee_payroll_id' => $linked->id,
            'ledger_date' => '2026-06-15',
            'transaction_type' => 'salary_payment',
            'amount' => 6000,
            'currency' => 'BDT',
        ]);

        $default = $this->actingAs($admin)->get('/admin/salary-month-sheet');
        $default->assertOk();
        $default->assertSee('Legacy Paid Employee');
        $default->assertSee('Legacy Manual Paid');
        $default->assertSee('Ledger Linked Employee');
        $default->assertSee('Finance Ledger Linked');
        $default->assertDontSee('<br>Superseded Paid Employee', false);
        $default->assertSee('Legacy Paid Without Ledger: 1');
        $default->assertSee('Amount: BDT 5,000.00');
        $this->assertSame(2, app(\App\Services\SalaryMonthSheetService::class)->build([])['summary']['total_salary_records']);

        $historicalResponse = $this->actingAs($admin)->get('/admin/salary-month-sheet?history_scope=historical');
        $historicalResponse->assertOk();
        $historicalResponse->assertSee('Superseded Paid Employee');
        $historicalResponse->assertSee('Superseded History');
        $historicalResponse->assertDontSee('<br>Legacy Paid Employee', false);

        $all = $this->actingAs($admin)->get('/admin/salary-month-sheet?history_scope=all');
        $all->assertOk();
        $all->assertSee('Historical payrolls may include regenerated/superseded records');
        $all->assertSee('Legacy Paid Employee');
        $all->assertSee('Ledger Linked Employee');
        $all->assertSee('Superseded Paid Employee');

        $legacyFilter = $this->actingAs($admin)->get('/admin/salary-month-sheet?payment_source=legacy_manual_paid');
        $legacyFilter->assertOk();
        $legacyFilter->assertSee('Legacy Paid Employee');
        $legacyFilter->assertDontSee('<br>Ledger Linked Employee', false);
        $legacyFilter->assertDontSee('<br>Superseded Paid Employee', false);

        $this->assertSame('legacy_manual_paid', $legacy->fresh()->paymentSourceStatusKey());
        $this->assertSame('finance_ledger_linked', $linked->fresh()->paymentSourceStatusKey());
        $this->assertSame('superseded', $historical->fresh()->paymentSourceStatusKey());
    }

    public function test_salary_month_and_payment_month_filters_are_independent(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $mayEmployee = $this->employee(['name' => 'May Salary Paid June']);
        $juneEmployee = $this->employee(['name' => 'June Salary Paid July']);

        $this->payroll($mayEmployee, $client, [
            'salary_month' => '2026-05-01',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'payable_salary' => 5000,
            'paid_amount' => 5000,
            'payment_status' => 'paid',
            'payment_date' => '2026-06-20',
            'payment_confirmed_at' => '2026-06-20 10:00:00',
            'paid_at' => '2026-06-20 10:00:00',
        ]);
        $this->payroll($juneEmployee, $client, [
            'salary_month' => '2026-06-01',
            'salary_period_from' => '2026-06-01',
            'salary_period_to' => '2026-06-30',
            'payable_salary' => 6000,
            'paid_amount' => 6000,
            'payment_status' => 'paid',
            'payment_date' => '2026-07-20',
            'payment_confirmed_at' => '2026-07-20 10:00:00',
            'paid_at' => '2026-07-20 10:00:00',
        ]);

        $salaryMay = $this->actingAs($admin)->get('/admin/salary-month-sheet?month=2026-05');
        $salaryMay->assertOk()->assertSee('<br>May Salary Paid June', false);
        $salaryMay->assertDontSee('<br>June Salary Paid July', false);

        $paymentJune = $this->actingAs($admin)->get('/admin/salary-month-sheet?payment_month=2026-06');
        $paymentJune->assertOk()->assertSee('<br>May Salary Paid June', false);
        $paymentJune->assertDontSee('<br>June Salary Paid July', false);

        $salaryJune = $this->actingAs($admin)->get('/admin/salary-month-sheet?month=2026-06');
        $salaryJune->assertOk()->assertDontSee('<br>May Salary Paid June', false);
        $salaryJune->assertSee('<br>June Salary Paid July', false);

        $reset = $this->actingAs($admin)->get('/admin/salary-month-sheet');
        $reset->assertOk();
        $reset->assertSee('<br>May Salary Paid June', false);
        $reset->assertSee('<br>June Salary Paid July', false);
        $reset->assertSee('Salary Month = the month salary belongs to. Payment Month = the month salary was actually paid.');
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
