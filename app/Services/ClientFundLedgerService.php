<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientFundLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientFundLedgerService
{
    public function credit(Client $client, string $fundType, float $amount, array $context): ClientFundLedger
    {
        return $this->move($client, $fundType, ClientFundLedger::DIRECTION_CREDIT, $amount, $context);
    }

    public function debit(Client $client, string $fundType, float $amount, array $context): ClientFundLedger
    {
        return $this->move($client, $fundType, ClientFundLedger::DIRECTION_DEBIT, $amount, $context);
    }

    public function debitOnce(Client $client, string $fundType, float $amount, Model $source, array $context): ?ClientFundLedger
    {
        if ($this->hasSourceEntry($source, $fundType, ClientFundLedger::DIRECTION_DEBIT)) {
            return null;
        }

        return $this->debit($client, $fundType, $amount, $this->sourceContext($source, $context));
    }

    public function creditOnce(Client $client, string $fundType, float $amount, Model $source, array $context): ?ClientFundLedger
    {
        if ($this->hasSourceEntry($source, $fundType, ClientFundLedger::DIRECTION_CREDIT)) {
            return null;
        }

        return $this->credit($client, $fundType, $amount, $this->sourceContext($source, $context));
    }

    public function syncDebitForSource(Client $client, string $fundType, float $targetAmount, Model $source, array $context): ?ClientFundLedger
    {
        $targetAmount = $this->positiveOrZero($targetAmount);
        $currentDebit = $this->sourceNetDebit($source, $fundType);
        $difference = round($targetAmount - $currentDebit, 2);

        if (abs($difference) < 0.01) {
            return null;
        }

        $sourceContext = $this->sourceContext($source, $context);

        return $difference > 0
            ? $this->debit($client, $fundType, $difference, $sourceContext)
            : $this->credit($client, $fundType, abs($difference), array_merge($sourceContext, [
                'description' => $context['adjustment_description'] ?? $context['description'] ?? 'Client fund debit adjusted.',
            ]));
    }

    public function balance(Client|int $client, string $fundType): float
    {
        $clientId = $client instanceof Client ? $client->id : $client;

        return round((float) ClientFundLedger::query()
            ->where('client_id', $clientId)
            ->where('fund_type', $fundType)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance'), 2);
    }

    public function totals(Client|int $client, string $fundType): array
    {
        $clientId = $client instanceof Client ? $client->id : $client;
        $credit = (float) ClientFundLedger::where('client_id', $clientId)
            ->where('fund_type', $fundType)
            ->where('direction', ClientFundLedger::DIRECTION_CREDIT)
            ->sum('amount_bdt');
        $debit = (float) ClientFundLedger::where('client_id', $clientId)
            ->where('fund_type', $fundType)
            ->where('direction', ClientFundLedger::DIRECTION_DEBIT)
            ->sum('amount_bdt');

        return [
            'received' => $credit,
            'used' => $debit,
            'balance' => round($credit - $debit, 2),
        ];
    }

    public function hasSourceEntry(Model $source, string $fundType, ?string $direction = null): bool
    {
        return ClientFundLedger::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->where('fund_type', $fundType)
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->exists();
    }

    private function move(Client $client, string $fundType, string $direction, float $amount, array $context): ClientFundLedger
    {
        $amount = $this->positiveAmount($amount);

        return DB::transaction(function () use ($client, $fundType, $direction, $amount, $context) {
            $lockedClient = Client::whereKey($client->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = $this->balance($lockedClient, $fundType);
            $balanceAfter = $direction === ClientFundLedger::DIRECTION_CREDIT
                ? round($balanceBefore + $amount, 2)
                : round($balanceBefore - $amount, 2);

            if ($direction === ClientFundLedger::DIRECTION_DEBIT
                && $balanceAfter < 0
                && ! ($context['allow_negative'] ?? false)) {
                throw ValidationException::withMessages([
                    $context['balance_error_field'] ?? 'amount' => $context['balance_error'] ?? 'Insufficient client fund balance.',
                ]);
            }

            return ClientFundLedger::create([
                'client_id' => $lockedClient->id,
                'fund_type' => $fundType,
                'direction' => $direction,
                'amount_bdt' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => $context['source_type'] ?? null,
                'source_id' => $context['source_id'] ?? null,
                'reference' => $context['reference'] ?? null,
                'description' => $context['description'] ?? null,
                'created_by' => $context['created_by'] ?? auth()->id(),
            ]);
        });
    }

    private function sourceContext(Model $source, array $context): array
    {
        return array_merge($context, [
            'source_type' => $source::class,
            'source_id' => $source->getKey(),
        ]);
    }

    private function sourceNetDebit(Model $source, string $fundType): float
    {
        $credit = (float) ClientFundLedger::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->where('fund_type', $fundType)
            ->where('direction', ClientFundLedger::DIRECTION_CREDIT)
            ->sum('amount_bdt');
        $debit = (float) ClientFundLedger::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->where('fund_type', $fundType)
            ->where('direction', ClientFundLedger::DIRECTION_DEBIT)
            ->sum('amount_bdt');

        return round($debit - $credit, 2);
    }

    private function positiveAmount(float $amount): float
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Client fund amount must be greater than zero.']);
        }

        return $amount;
    }

    private function positiveOrZero(float $amount): float
    {
        return max(round($amount, 2), 0);
    }
}
