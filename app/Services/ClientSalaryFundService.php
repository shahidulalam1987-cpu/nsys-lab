<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\EmployeePayroll;
use App\Models\SalaryPayment;

class ClientSalaryFundService
{
    public function __construct(private ClientFundLedgerService $ledger) {}

    public function creditDeposit(SalaryPayment $payment): void
    {
        if ($payment->status !== 'approved' || ! $payment->client) {
            return;
        }

        $this->ledger->creditOnce($payment->client, ClientFundLedger::FUND_EMPLOYEE_SALARY, (float) $payment->amount, $payment, [
            'reference' => $payment->transaction_id ?: 'salary-payment:' . $payment->id,
            'description' => 'Client Salary Fund Deposit - ' . ($payment->client?->company_name ?: 'Client') . '.',
            'created_by' => auth()->id(),
        ]);
    }

    public function debitPayroll(EmployeePayroll $payroll): void
    {
        if (! $payroll->client || (float) $payroll->paid_amount <= 0) {
            return;
        }

        $this->ledger->debitOnce($payroll->client, ClientFundLedger::FUND_EMPLOYEE_SALARY, (float) $payroll->paid_amount, $payroll, [
            'reference' => $payroll->transaction_id ?: 'payroll:' . $payroll->id,
            'description' => 'Payroll Confirm Payment - ' . $payroll->snapshotEmployeeName() . '. Salary paid by agency before sufficient client salary fund deposit.',
            'created_by' => auth()->id(),
            'balance_error' => 'Insufficient employee salary fund balance.',
            'allow_negative' => true,
        ]);
    }

    public function summary(Client|int $client): array
    {
        return $this->ledger->totals($client, ClientFundLedger::FUND_EMPLOYEE_SALARY);
    }

    public function balance(Client|int $client): float
    {
        return $this->ledger->balance($client, ClientFundLedger::FUND_EMPLOYEE_SALARY);
    }
}
