<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceAccountLedger;
use App\Models\FinanceLoan;
use App\Models\User;
use App\Services\FinanceLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceCoreLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_service_records_credit_debit_and_reversal_snapshots(): void
    {
        $account = $this->account(1000);
        $service = app(FinanceLedgerService::class);

        $credit = $service->credit($account, 250, $this->context('manual_adjustment', 'credit-1'));
        $debit = $service->debit($account, 100, $this->context('manual_adjustment', 'debit-1'));
        $reversal = $service->reverse($debit, $this->context('manual_adjustment', 'reverse-1'));

        $this->assertSame(1250.0, (float) $account->fresh()->current_balance);
        $this->assertSame('credit', $credit->direction);
        $this->assertSame(1000.0, (float) $credit->old_balance);
        $this->assertSame(1250.0, (float) $credit->new_balance_snapshot);
        $this->assertSame('debit', $debit->direction);
        $this->assertSame('credit', $reversal->direction);
        $this->assertSame(FinanceAccountLedger::class, $reversal->reference_type);
        $this->assertSame($debit->id, $reversal->reference_id);
    }

    public function test_failed_second_debit_rolls_back_without_overspending_or_extra_ledger(): void
    {
        $account = $this->account(100);
        $service = app(FinanceLedgerService::class);

        $service->debit($account, 80, $this->context('manual_adjustment', 'first-debit'));

        try {
            $service->debit($account, 30, $this->context('manual_adjustment', 'blocked-debit'));
            $this->fail('Expected insufficient balance validation failure.');
        } catch (ValidationException $exception) {
            $this->assertSame('Insufficient finance account balance.', $exception->errors()['finance_account_id'][0]);
        }

        $this->assertSame(20.0, (float) $account->fresh()->current_balance);
        $this->assertSame(1, $account->ledgers()->count());
        $this->assertDatabaseMissing('finance_account_ledgers', ['transaction_reference' => 'blocked-debit']);
    }

    public function test_loan_taken_and_repayment_create_opposite_ledger_movements(): void
    {
        $admin = $this->admin();
        $account = $this->account(1000);

        $this->actingAs($admin)->post('/admin/finance/loans', [
            'loan_type' => 'taken',
            'finance_account_id' => $account->id,
            'person_company_name' => 'Finance Partner',
            'amount' => 500,
            'loan_date' => '2026-06-20',
            'due_date' => '2026-07-20',
            'note' => 'Working capital.',
        ])->assertRedirect('/admin/finance/loans');

        $loan = FinanceLoan::firstOrFail();
        $this->assertSame(1500.0, (float) $account->fresh()->current_balance);

        $this->actingAs($admin)->post('/admin/finance/loans/' . $loan->id . '/repayments', [
            'finance_account_id' => $account->id,
            'payment_date' => '2026-06-21',
            'amount' => 200,
            'method' => 'Bank Transfer',
            'note' => 'First repayment.',
        ])->assertRedirect('/admin/finance/loans/' . $loan->id);

        $this->assertSame(1300.0, (float) $account->fresh()->current_balance);
        $this->assertSame(200.0, (float) $loan->fresh()->paid_amount);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'loan_taken',
            'direction' => 'credit',
            'amount' => 500,
        ]);
        $this->assertDatabaseHas('finance_account_ledgers', [
            'finance_account_id' => $account->id,
            'transaction_type' => 'loan_repayment',
            'direction' => 'debit',
            'amount' => 200,
        ]);
    }

    public function test_reconciliation_report_flags_balance_mismatch(): void
    {
        $account = $this->account(1000);
        app(FinanceLedgerService::class)->credit($account, 100, $this->context('manual_adjustment', 'reconcile-1'));
        $account->newQuery()->whereKey($account->id)->update(['current_balance' => 900]);

        $this->actingAs($this->admin())
            ->get('/admin/finance/reports/reconciliation')
            ->assertOk()
            ->assertSee('Finance Reconciliation')
            ->assertSee('Mismatch')
            ->assertSee('200.00');
    }

    private function account(float $balance): FinanceAccount
    {
        return FinanceAccount::create([
            'account_type' => 'bank',
            'account_name' => 'Central Ledger Bank',
            'provider_name' => 'NSYS Bank',
            'account_number' => '100200300',
            'currency' => 'BDT',
            'current_balance' => $balance,
            'status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function context(string $type, string $reference): array
    {
        return [
            'transaction_type' => $type,
            'currency' => 'BDT',
            'required_currency' => 'BDT',
            'description' => 'Finance core test transaction.',
            'transaction_reference' => $reference,
        ];
    }
}
