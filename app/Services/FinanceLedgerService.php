<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\FinanceAccountLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceLedgerService
{
    public function credit(Model $target, float $amount, array $context, string $balanceColumn = 'current_balance'): FinanceAccountLedger
    {
        return DB::transaction(function () use ($target, $amount, $context, $balanceColumn) {
            $lockedTarget = $this->lock($target);
            $this->validateCurrency($lockedTarget, $context);
            $oldBalance = $this->balance($lockedTarget, $balanceColumn);
            $newBalance = round($oldBalance + $this->positiveAmount($amount), 2);
            $lockedTarget->update([$balanceColumn => $newBalance]);

            return $this->record($lockedTarget, 'credit', $amount, $oldBalance, $newBalance, $context);
        });
    }

    public function debit(Model $target, float $amount, array $context, string $balanceColumn = 'current_balance'): FinanceAccountLedger
    {
        return DB::transaction(function () use ($target, $amount, $context, $balanceColumn) {
            $lockedTarget = $this->lock($target);
            $this->validateCurrency($lockedTarget, $context);
            $oldBalance = $this->balance($lockedTarget, $balanceColumn);
            $amount = $this->positiveAmount($amount);

            if (! ($context['allow_negative'] ?? false) && $oldBalance < $amount) {
                throw ValidationException::withMessages([
                    $context['balance_error_field'] ?? 'finance_account_id' => $context['balance_error'] ?? 'Insufficient finance account balance.',
                ]);
            }

            $newBalance = round($oldBalance - $amount, 2);
            $lockedTarget->update([$balanceColumn => $newBalance]);

            return $this->record($lockedTarget, 'debit', $amount, $oldBalance, $newBalance, $context);
        });
    }

    public function transfer(
        Model $source,
        Model $destination,
        float $amount,
        array $context,
        string $sourceBalanceColumn = 'current_balance',
        string $destinationBalanceColumn = 'current_balance'
    ): array {
        return DB::transaction(function () use ($source, $destination, $amount, $context, $sourceBalanceColumn, $destinationBalanceColumn) {
            $lockedSource = $this->lock($source);
            $lockedDestination = $this->lock($destination);
            $this->validateCurrency($lockedSource, $context);
            $amount = $this->positiveAmount($amount);
            $sourceOld = $this->balance($lockedSource, $sourceBalanceColumn);

            if ($sourceOld < $amount) {
                throw ValidationException::withMessages([
                    $context['balance_error_field'] ?? 'amount' => $context['balance_error'] ?? 'Insufficient source balance.',
                ]);
            }

            $destinationOld = $this->balance($lockedDestination, $destinationBalanceColumn);
            $sourceNew = round($sourceOld - $amount, 2);
            $destinationNew = round($destinationOld + $amount, 2);
            $lockedSource->update([$sourceBalanceColumn => $sourceNew]);
            $lockedDestination->update([$destinationBalanceColumn => $destinationNew]);

            return [
                'debit' => $this->record($lockedSource, 'debit', $amount, $sourceOld, $sourceNew, $context),
                'credit' => $this->record($lockedDestination, 'credit', $amount, $destinationOld, $destinationNew, $context),
            ];
        });
    }

    public function reverse(FinanceAccountLedger $ledger, array $context): FinanceAccountLedger
    {
        $account = $ledger->account;

        if (! $account) {
            throw ValidationException::withMessages(['ledger' => 'This ledger entry cannot be reversed automatically.']);
        }

        $context['reference_type'] ??= FinanceAccountLedger::class;
        $context['reference_id'] ??= $ledger->id;
        $context['currency'] ??= $ledger->currency ?: $account->currency;

        return $ledger->direction === 'credit'
            ? $this->debit($account, (float) $ledger->amount, $context)
            : $this->credit($account, (float) $ledger->amount, $context);
    }

    public function hasEntry(string $transactionType, string $referenceType, int $referenceId, ?string $direction = null): bool
    {
        return FinanceAccountLedger::query()
            ->where('transaction_type', $transactionType)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->exists();
    }

    private function record(Model $target, string $direction, float $amount, float $oldBalance, float $newBalance, array $context): FinanceAccountLedger
    {
        $amount = $this->positiveAmount($amount);
        $account = $target instanceof FinanceAccount ? $target : null;
        $currency = $context['currency'] ?? ($account?->currency ?: 'USD');
        $transactionReference = $context['transaction_reference'] ?? null;
        $description = $context['description'] ?? null;

        $ledger = FinanceAccountLedger::create([
            'finance_account_id' => $account?->id,
            'employee_payroll_id' => $context['employee_payroll_id'] ?? null,
            'ledger_date' => $context['ledger_date'] ?? now()->toDateString(),
            'transaction_type' => $context['transaction_type'],
            'amount' => $amount,
            'currency' => $currency,
            'direction' => $direction,
            'previous_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'reference' => $transactionReference,
            'reference_type' => $context['reference_type'] ?? $target::class,
            'reference_id' => $context['reference_id'] ?? $target->getKey(),
            'old_balance' => $oldBalance,
            'new_balance_snapshot' => $newBalance,
            'description' => $description,
            'transaction_reference' => $transactionReference,
            'note' => $context['note'] ?? $description,
            'created_by' => $context['created_by'] ?? auth()->id(),
        ]);

        app(ActivityLogger::class)->log(
            $context['activity_module'] ?? 'Finance',
            $context['activity_action'] ?? ucwords(str_replace('_', ' ', $context['transaction_type'])),
            $context['activity_description'] ?? ($description ?: 'Finance ledger #' . $ledger->id . ' created.')
        );

        return $ledger;
    }

    private function lock(Model $model): Model
    {
        return $model->newQuery()->whereKey($model->getKey())->lockForUpdate()->firstOrFail();
    }

    private function balance(Model $model, string $column): float
    {
        return round((float) $model->getAttribute($column), 2);
    }

    private function positiveAmount(float $amount): float
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Transaction amount must be greater than zero.']);
        }

        return $amount;
    }

    private function validateCurrency(Model $target, array $context): void
    {
        $requiredCurrency = $context['required_currency'] ?? null;

        if ($requiredCurrency && $target instanceof FinanceAccount && $target->currency !== $requiredCurrency) {
            throw ValidationException::withMessages([
                $context['currency_error_field'] ?? 'finance_account_id' => 'Currency mismatch. This payment requires a ' . $requiredCurrency . ' account.',
            ]);
        }
    }
}
