<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\Employee;
use App\Models\EmployeeWorkStatus;
use App\Models\FinanceAccount;
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
        $this->assertFalse((bool) $existing->fresh()->is_current);
        $this->assertSame('regenerated', $latest->generation_status);
        $this->assertTrue((bool) $latest->is_current);
        $this->assertSame($existing->id, $latest->regenerated_from_id);
        $this->assertSame($latest->id, $existing->fresh()->superseded_by_id);
        $this->assertDatabaseHas('employee_payroll_audits', [
            'employee_payroll_id' => $latest->id,
            'action' => 'salary_regenerated',
        ]);
    }

    public function test_regenerated_payroll_excludes_old_record_from_due_totals_and_reports(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();

        $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));
        $oldPayroll = $employee->payrolls()->firstOrFail();

        $this->actingAs($admin)->post('/admin/payroll', array_merge(
            $this->salaryPayload($employee, $client),
            [
                'working_days' => 5,
                'confirm_regenerate' => 1,
            ]
        ));

        $newPayroll = $employee->payrolls()->orderByDesc('id')->firstOrFail();

        $this->assertFalse((bool) $oldPayroll->fresh()->is_current);
        $this->assertTrue((bool) $newPayroll->is_current);
        $this->assertSame($newPayroll->id, $oldPayroll->fresh()->superseded_by_id);
        $this->assertSame(5000.0, (float) $newPayroll->payable_salary);

        $payrollPage = $this->actingAs($admin)->get('/admin/payroll?status=due');
        $payrollPage->assertOk();
        $payrollPage->assertSee('BDT 5,000.00');
        $payrollPage->assertDontSee('BDT 15,000.00');

        $sheet = app(\App\Services\SalaryMonthSheetService::class)->build(['month' => '2026-06']);
        $this->assertSame(1, $sheet['summary']['total_salary_records']);
        $this->assertSame(5000.0, (float) $sheet['summary']['total_remaining_due']);

        $profile = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);
        $profile->assertOk();
        $profile->assertSee('Current Payroll');
        $profile->assertSee('Historical Payroll');
        $profile->assertSee('Superseded by #' . $newPayroll->id);
        $profile->assertSee('Regenerated from #' . $oldPayroll->id);
    }

    public function test_payroll_can_be_approved_and_confirmed_paid_with_finance_ledger(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $financeAccount = FinanceAccount::create([
            'account_type' => 'bank',
            'account_name' => 'NSYS Salary Bank',
            'provider_name' => 'Test Bank',
            'account_number' => '123456789',
            'currency' => 'BDT',
            'current_balance' => 50000,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));
        $payroll = $employee->payrolls()->first();
        $this->seedClientSalaryFund($client, 20000);

        $this->assertSame('generated', $payroll->payroll_status);
        $this->assertDatabaseHas('employee_payroll_audits', [
            'employee_payroll_id' => $payroll->id,
            'action' => 'salary_generated',
        ]);

        $approve = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/approve');
        $approve->assertRedirect('/admin/payroll/' . $payroll->id);
        $this->assertSame('approved', $payroll->fresh()->payroll_status);

        $paid = $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', [
            'payment_date' => '2026-06-30',
            'finance_account_id' => $financeAccount->id,
            'transaction_id' => 'SAL-PAID-1',
            'payment_note' => 'Salary transfer completed.',
        ]);
        $paid->assertRedirect('/admin/payroll/' . $payroll->id);

        $payroll->refresh();
        $financeLedger = $payroll->financeLedgers()->where('transaction_type', 'salary_payment')->firstOrFail();
        $clientFundLedger = $payroll->clientFundLedgers()->where('direction', ClientFundLedger::DIRECTION_DEBIT)->firstOrFail();

        $this->assertSame('paid', $payroll->payroll_status);
        $this->assertSame('paid', $payroll->calculated_status);
        $this->assertSame(10000.0, (float) $payroll->paid_amount);
        $this->assertStringStartsWith('NSYS-SP-2026-', $payroll->salary_receipt_number);
        $this->assertSame('NSYS Salary Bank', $payroll->finance_account_name);
        $this->assertSame(40000.0, (float) $financeAccount->fresh()->current_balance);
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
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $financeAccount->id,
            'employee_payroll_id' => $payroll->id,
            'transaction_type' => 'salary_payment',
            'reference' => 'SAL-PAID-1',
        ]);

        $show = $this->actingAs($admin)->get('/admin/payroll/' . $payroll->id);
        $show->assertOk();
        $show->assertSee('Approval History');
        $show->assertSee('Payment History');
        $show->assertSee('Finance Ledger');
        $show->assertSee('Audit Log');
        $show->assertSee('Salary Paid');
        $show->assertSee($payroll->salary_receipt_number);
        $show->assertSee('Finance Ledger ID');
        $show->assertSee((string) $financeLedger->id);
        $show->assertSee('Client Fund Ledger ID');
        $show->assertSee((string) $clientFundLedger->id);
        $show->assertSee('Salary Timeline');

        $report = $this->actingAs($admin)->get('/admin/payroll/payment-report?search=' . urlencode($payroll->salary_receipt_number));
        $report->assertOk()
            ->assertSee($payroll->salary_receipt_number)
            ->assertSee((string) $financeLedger->id)
            ->assertSee((string) $clientFundLedger->id);

        $statementHtml = view('employee.pdf.salary-statement', app(\App\Services\SalaryStatementService::class)->data($payroll))->render();
        $this->assertStringContainsString($payroll->salary_receipt_number, $statementHtml);
        $this->assertStringContainsString('Payroll Ledger', $statementHtml);
    }

    public function test_second_confirm_payment_post_does_not_deduct_again(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $financeAccount = $this->financeAccount();
        $payroll = $this->approvedPayroll($admin, $employee, $client);
        $this->seedClientSalaryFund($client, 20000);

        $payload = [
            'payment_date' => '2026-06-30',
            'finance_account_id' => $financeAccount->id,
            'transaction_id' => 'SAFE-PAY-1',
            'payment_note' => 'Salary transfer completed.',
        ];

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', $payload)
            ->assertRedirect('/admin/payroll/' . $payroll->id);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', $payload)
            ->assertRedirect('/admin/payroll/' . $payroll->id)
            ->assertSessionHas('success', 'This salary payment is already confirmed.');

        $this->assertSame(40000.0, (float) $financeAccount->fresh()->current_balance);
        $this->assertSame(1, $payroll->financeLedgers()->where('transaction_type', 'salary_payment')->count());
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Payroll',
            'action' => 'Duplicate Payment Blocked',
        ]);
    }

    public function test_already_paid_payroll_returns_safe_message_without_required_fields(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $financeAccount = $this->financeAccount();
        $payroll = $this->approvedPayroll($admin, $employee, $client);
        $this->seedClientSalaryFund($client, 20000);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', [
            'payment_date' => '2026-06-30',
            'finance_account_id' => $financeAccount->id,
            'transaction_id' => 'SAFE-PAY-2',
            'payment_note' => 'Salary transfer completed.',
        ])->assertRedirect('/admin/payroll/' . $payroll->id);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', [])
            ->assertRedirect('/admin/payroll/' . $payroll->id)
            ->assertSessionHas('success', 'This salary payment is already confirmed.');

        $this->assertSame(40000.0, (float) $financeAccount->fresh()->current_balance);
        $this->assertSame(1, $payroll->financeLedgers()->where('transaction_type', 'salary_payment')->count());
    }

    public function test_insufficient_finance_account_balance_blocks_salary_payment(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $financeAccount = $this->financeAccount(['current_balance' => 9999]);
        $payroll = $this->approvedPayroll($admin, $employee, $client);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', [
            'payment_date' => '2026-06-30',
            'finance_account_id' => $financeAccount->id,
            'transaction_id' => 'LOW-BALANCE-1',
            'payment_note' => 'Salary transfer completed.',
        ])->assertRedirect('/admin/payroll/' . $payroll->id)
            ->assertSessionHas('success', 'Insufficient finance account balance.');

        $this->assertSame(9999.0, (float) $financeAccount->fresh()->current_balance);
        $this->assertSame('approved', $payroll->fresh()->payroll_status);
        $this->assertSame(0, $payroll->financeLedgers()->where('transaction_type', 'salary_payment')->count());
    }

    public function test_reverse_payment_restores_balance_once_only(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $financeAccount = $this->financeAccount();
        $payroll = $this->approvedPayroll($admin, $employee, $client);
        $this->seedClientSalaryFund($client, 20000);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/confirm-payment', [
            'payment_date' => '2026-06-30',
            'finance_account_id' => $financeAccount->id,
            'transaction_id' => 'REV-PAY-1',
            'payment_note' => 'Salary transfer completed.',
        ])->assertRedirect('/admin/payroll/' . $payroll->id);

        $this->assertSame(40000.0, (float) $financeAccount->fresh()->current_balance);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/reverse-payment', [
            'reversal_note' => 'Wrong account selected.',
        ])->assertRedirect('/admin/payroll/' . $payroll->id);

        $this->assertSame(50000.0, (float) $financeAccount->fresh()->current_balance);
        $this->assertSame(1, $payroll->financeLedgers()->where('transaction_type', 'salary_payment_reversal')->count());
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Payroll',
            'action' => 'Salary Reversed',
        ]);

        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/reverse-payment', [
            'reversal_note' => 'Try duplicate reversal.',
        ])->assertRedirect('/admin/payroll/' . $payroll->id)
            ->assertSessionHas('success', 'This salary payment cannot be reversed.');

        $this->assertSame(50000.0, (float) $financeAccount->fresh()->current_balance);
        $this->assertSame(1, $payroll->financeLedgers()->where('transaction_type', 'salary_payment_reversal')->count());
    }

    public function test_work_status_inside_current_payroll_period_cannot_be_deleted(): void
    {
        $admin = $this->user('admin');
        $client = $this->client();
        $employee = $this->employee();
        $workStatus = EmployeeWorkStatus::create([
            'employee_id' => $employee->id,
            'client_id' => $client->id,
            'work_date' => '2026-06-05',
            'status' => 'working',
            'salary_count_value' => 1,
        ]);

        $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));

        $this->actingAs($admin)->post('/admin/work-status/' . $workStatus->id . '/delete')
            ->assertRedirect('/admin/work-status')
            ->assertSessionHasErrors('work_status');

        $this->assertDatabaseHas('employee_work_statuses', ['id' => $workStatus->id]);
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

    private function approvedPayroll(User $admin, Employee $employee, Client $client)
    {
        $this->actingAs($admin)->post('/admin/payroll', $this->salaryPayload($employee, $client));
        $payroll = $employee->payrolls()->firstOrFail();
        $this->actingAs($admin)->post('/admin/payroll/' . $payroll->id . '/approve');

        return $payroll->fresh();
    }

    private function financeAccount(array $overrides = []): FinanceAccount
    {
        return FinanceAccount::create(array_merge([
            'account_type' => 'bank',
            'account_name' => 'NSYS Salary Bank',
            'provider_name' => 'Test Bank',
            'account_number' => '123456789',
            'currency' => 'BDT',
            'current_balance' => 50000,
            'status' => 'active',
        ], $overrides));
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

    private function seedClientSalaryFund(Client $client, float $amount): ClientFundLedger
    {
        $balanceBefore = (float) ClientFundLedger::where('client_id', $client->id)
            ->where('fund_type', ClientFundLedger::FUND_EMPLOYEE_SALARY)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance');

        return ClientFundLedger::create([
            'client_id' => $client->id,
            'fund_type' => ClientFundLedger::FUND_EMPLOYEE_SALARY,
            'direction' => ClientFundLedger::DIRECTION_CREDIT,
            'amount_bdt' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'reference' => 'TEST-SALARY-FUND',
            'description' => 'Test salary fund deposit.',
        ]);
    }
}
