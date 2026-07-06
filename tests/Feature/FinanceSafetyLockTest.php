<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\FamilyExpense;
use App\Models\FinanceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSafetyLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_account_with_ledger_cannot_be_deleted(): void
    {
        $account = $this->account();
        $account->ledgers()->create($this->ledgerData('manual_adjustment', 1000, 900));

        $this->actingAs($this->admin())
            ->post('/admin/finance/accounts/' . $account->id . '/delete')
            ->assertRedirect('/admin/finance/accounts')
            ->assertSessionHasErrors(['account' => 'This finance account has transaction history and cannot be deleted.']);

        $this->assertDatabaseHas('finance_accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('finance_account_ledgers', ['finance_account_id' => $account->id]);
    }

    public function test_finance_account_without_ledger_can_be_deleted(): void
    {
        $account = $this->account(['current_balance' => 0]);

        $this->actingAs($this->admin())
            ->post('/admin/finance/accounts/' . $account->id . '/delete')
            ->assertRedirect('/admin/finance/accounts');

        $this->assertDatabaseMissing('finance_accounts', ['id' => $account->id]);
    }

    public function test_paid_payroll_cannot_be_deleted(): void
    {
        $payroll = $this->payroll([
            'paid_amount' => 10000,
            'payment_status' => 'paid',
            'payroll_status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/payroll/' . $payroll->id . '/delete')
            ->assertRedirect('/admin/payroll/' . $payroll->id)
            ->assertSessionHasErrors(['payroll' => 'Paid payroll cannot be deleted. Use reverse payment or void payroll.']);

        $this->assertDatabaseHas('employee_payrolls', ['id' => $payroll->id]);
    }

    public function test_unpaid_generated_payroll_without_ledger_can_be_deleted(): void
    {
        $payroll = $this->payroll();

        $this->actingAs($this->admin())
            ->post('/admin/payroll/' . $payroll->id . '/delete')
            ->assertRedirect('/admin/payroll');

        $this->assertDatabaseMissing('employee_payrolls', ['id' => $payroll->id]);
    }

    public function test_salary_payment_cannot_use_usd_account(): void
    {
        $account = $this->account(['currency' => 'USD', 'current_balance' => 50000]);
        $payroll = $this->payroll(['payroll_status' => 'approved']);

        $this->actingAs($this->admin())
            ->post('/admin/payroll/' . $payroll->id . '/confirm-payment', [
                'payment_date' => '2026-06-20',
                'finance_account_id' => $account->id,
                'transaction_id' => 'USD-BLOCKED',
                'payment_note' => 'Must not deduct USD.',
            ])
            ->assertSessionHas('success', 'Currency mismatch. This payment requires a BDT account.');

        $this->assertSame(50000.0, (float) $account->fresh()->current_balance);
        $this->assertSame(0, $account->ledgers()->count());
        $this->assertSame('approved', $payroll->fresh()->payroll_status);
    }

    public function test_family_expense_cannot_use_usd_account(): void
    {
        $account = $this->account(['currency' => 'USD', 'current_balance' => 1000]);

        $this->actingAs($this->admin())
            ->from('/admin/finance/family-expenses')
            ->post('/admin/finance/family-expenses', $this->expenseData($account))
            ->assertSessionHasErrors(['finance_account_id' => 'Currency mismatch. This payment requires a BDT account.']);

        $this->assertSame(1000.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseCount('family_expenses', 0);
        $this->assertDatabaseCount('finance_account_ledgers', 0);
    }

    public function test_opening_balance_creates_ledger(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/finance/accounts', $this->accountData(['current_balance' => 2500]));

        $account = FinanceAccount::firstOrFail();
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'opening_balance',
            'amount' => 2500,
            'previous_balance' => 0,
            'new_balance' => 2500,
            'created_by' => $admin->id,
        ]);
    }

    public function test_manual_balance_adjustment_creates_ledger(): void
    {
        $admin = $this->admin();
        $account = $this->account(['current_balance' => 1000]);

        $this->actingAs($admin)->post('/admin/finance/accounts/' . $account->id . '/update', $this->accountData([
            'adjustment_type' => 'credit',
            'adjustment_amount' => 400,
            'adjustment_reason' => 'Bank statement reconciliation.',
        ]));

        $this->assertSame(1400.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'manual_adjustment',
            'direction' => 'credit',
            'amount' => 400,
            'previous_balance' => 1000,
            'new_balance' => 1400,
            'note' => 'Bank statement reconciliation.',
            'created_by' => $admin->id,
        ]);
    }

    public function test_debit_adjustment_updates_balance_and_snapshot(): void
    {
        $admin = $this->admin();
        $account = $this->account(['current_balance' => 1000]);

        $this->actingAs($admin)->post('/admin/finance/accounts/' . $account->id . '/update', $this->accountData([
            'adjustment_type' => 'debit',
            'adjustment_amount' => 250,
            'adjustment_reason' => 'Bank correction entry.',
        ]))->assertSessionHas('success');

        $this->assertSame(750.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'manual_adjustment',
            'direction' => 'debit',
            'amount' => 250,
            'previous_balance' => 1000,
            'new_balance' => 750,
            'note' => 'Bank correction entry.',
        ]);
    }

    public function test_zero_adjustment_is_rejected(): void
    {
        $account = $this->account(['current_balance' => 1000]);

        $this->actingAs($this->admin())->post('/admin/finance/accounts/' . $account->id . '/update', $this->accountData([
            'adjustment_type' => 'credit',
            'adjustment_amount' => 0,
            'adjustment_reason' => 'Zero correction.',
        ]))->assertSessionHasErrors('adjustment_amount');

        $this->assertSame(1000.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseCount('finance_account_ledgers', 0);
    }

    public function test_negative_adjustment_is_rejected(): void
    {
        $account = $this->account(['current_balance' => 1000]);

        $this->actingAs($this->admin())->post('/admin/finance/accounts/' . $account->id . '/update', $this->accountData([
            'adjustment_type' => 'debit',
            'adjustment_amount' => -50,
            'adjustment_reason' => 'Invalid correction.',
        ]))->assertSessionHasErrors('adjustment_amount');

        $this->assertSame(1000.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseCount('finance_account_ledgers', 0);
    }

    public function test_adjustment_reason_is_required_and_has_minimum_length(): void
    {
        $account = $this->account(['current_balance' => 1000]);
        $payload = $this->accountData(['adjustment_type' => 'credit', 'adjustment_amount' => 100]);

        $this->actingAs($this->admin())->post('/admin/finance/accounts/' . $account->id . '/update', $payload)
            ->assertSessionHasErrors('adjustment_reason');
        $this->actingAs($this->admin())->post('/admin/finance/accounts/' . $account->id . '/update', array_merge($payload, ['adjustment_reason' => 'Four']))
            ->assertSessionHasErrors('adjustment_reason');

        $this->assertSame(1000.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseCount('finance_account_ledgers', 0);
    }

    public function test_edit_form_shows_read_only_balance_and_adjustment_controls(): void
    {
        $account = $this->account(['current_balance' => 4916.56]);

        $this->actingAs($this->admin())->get('/admin/finance/accounts/' . $account->id . '/edit')
            ->assertOk()
            ->assertSee('Current ledger balance. This value cannot be edited directly.')
            ->assertSee('BDT 4,916.56')
            ->assertSee('Credit (Increase Balance)')
            ->assertSee('Debit (Decrease Balance)')
            ->assertDontSee('name="current_balance"', false);
    }

    public function test_controller_cannot_overwrite_current_balance_directly(): void
    {
        $account = $this->account(['current_balance' => 1000]);

        $this->actingAs($this->admin())->post('/admin/finance/accounts/' . $account->id . '/update', $this->accountData([
            'current_balance' => 999999,
            'adjustment_type' => 'credit',
            'adjustment_amount' => 100,
            'adjustment_reason' => 'Controlled ledger adjustment.',
        ]))->assertSessionHas('success');

        $this->assertSame(1100.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'manual_adjustment',
            'amount' => 100,
            'previous_balance' => 1000,
            'new_balance' => 1100,
        ]);
    }

    public function test_family_expense_creates_ledger_and_deducts_balance(): void
    {
        $admin = $this->admin();
        $account = $this->account(['current_balance' => 1000]);

        $this->actingAs($admin)->post('/admin/finance/family-expenses', $this->expenseData($account, 250));

        $expenseId = (int) FamilyExpense::value('id');
        $this->assertSame(750.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'family_expense',
            'amount' => 250,
            'previous_balance' => 1000,
            'new_balance' => 750,
            'reference' => 'family-expense:' . $expenseId,
            'created_by' => $admin->id,
        ]);
    }

    public function test_family_expense_delete_restores_balance_through_reversal_ledger(): void
    {
        $admin = $this->admin();
        $account = $this->account(['current_balance' => 1000]);
        $this->actingAs($admin)->post('/admin/finance/family-expenses', $this->expenseData($account, 250));
        $expense = FamilyExpense::firstOrFail();

        $this->actingAs($admin)->post('/admin/finance/family-expenses/' . $expense->id . '/delete');

        $this->assertSame(1000.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseMissing('family_expenses', ['id' => $expense->id]);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'family_expense_reversal',
            'amount' => 250,
            'previous_balance' => 750,
            'new_balance' => 1000,
            'reference' => 'family-expense:' . $expense->id,
            'created_by' => $admin->id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function account(array $overrides = []): FinanceAccount
    {
        return FinanceAccount::create(array_merge($this->accountData(), $overrides));
    }

    private function accountData(array $overrides = []): array
    {
        return array_merge([
            'account_type' => 'bank',
            'account_name' => 'NSYS Safety Bank',
            'provider_name' => 'Safety Bank',
            'account_number' => '123456789',
            'currency' => 'BDT',
            'current_balance' => 1000,
            'status' => 'active',
            'note' => null,
        ], $overrides);
    }

    private function ledgerData(string $type, float $previous, float $new): array
    {
        return [
            'ledger_date' => '2026-06-20',
            'transaction_type' => $type,
            'amount' => abs($new - $previous),
            'previous_balance' => $previous,
            'new_balance' => $new,
            'reference' => 'TEST-LEDGER',
        ];
    }

    private function expenseData(FinanceAccount $account, float $amount = 100): array
    {
        return [
            'expense_date' => '2026-06-20',
            'person_name' => 'Safety Test Person',
            'relation' => 'Family',
            'expense_category' => 'family_support',
            'amount' => $amount,
            'payment_method' => 'Bank Transfer',
            'finance_account_id' => $account->id,
            'note' => 'Safety test expense.',
        ];
    }

    private function payroll(array $overrides = []): EmployeePayroll
    {
        $employee = Employee::create([
            'employee_id' => 'SAFE-' . uniqid(),
            'name' => 'Finance Safety Employee',
            'department' => 'Management',
            'role' => 'Manager',
            'joining_date' => '2026-05-01',
            'confirmation_date' => '2026-05-01',
            'status' => 'active',
            'monthly_salary' => 10000,
        ]);
        $clientUser = User::factory()->create(['role' => 'client', 'status' => 'active']);
        $client = Client::create([
            'user_id' => $clientUser->id,
            'company_name' => 'Finance Safety Client',
            'phone' => '123',
            'client_rate' => 100,
            'buy_rate' => 80,
            'status' => 'active',
        ]);

        return $employee->payrolls()->create(array_merge([
            'client_id' => $client->id,
            'calculation_type' => 'monthly_cycle',
            'salary_period_from' => '2026-05-01',
            'salary_period_to' => '2026-05-31',
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'working_days' => 30,
            'non_working_days' => 0,
            'month_days' => 30,
            'daily_salary' => 333.33,
            'salary_month' => '2026-05-01',
            'payable_salary' => 10000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'payroll_status' => 'generated',
        ], $overrides));
    }
}
