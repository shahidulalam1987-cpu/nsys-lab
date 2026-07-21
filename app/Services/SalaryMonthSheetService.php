<?php

namespace App\Services;

use App\Models\EmployeePayroll;
use Carbon\Carbon;

class SalaryMonthSheetService
{
    public function build(array $filters = []): array
    {
        $month = ! empty($filters['month']) ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth() : null;
        $paymentMonth = ! empty($filters['payment_month']) ? Carbon::createFromFormat('Y-m', $filters['payment_month'])->startOfMonth() : null;
        $historyScope = $filters['history_scope'] ?? 'current';
        $rows = EmployeePayroll::query()
            ->when($historyScope === 'current', fn ($query) => $query->current())
            ->when($historyScope === 'historical', fn ($query) => $query->where(function ($query) {
                $query->where('is_current', false)->orWhereNotNull('superseded_by_id');
            }))
            ->with(['employee', 'client', 'financeAccount', 'financeLedgers', 'clientFundLedgers'])
            ->when($month, fn ($query) => $query->whereDate('salary_month', $month->toDateString()))
            ->when($paymentMonth, fn ($query) => $query->where(function ($query) use ($paymentMonth) {
                $start = $paymentMonth->copy()->startOfMonth();
                $end = $paymentMonth->copy()->endOfMonth();

                $query->whereBetween('payment_confirmed_at', [$start, $end])
                    ->orWhereBetween('paid_at', [$start, $end])
                    ->orWhereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
            }))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['salary_source'] ?? null, fn ($query, $salarySource) => $query->where('salary_source', $salarySource))
            ->latest('salary_month')
            ->latest()
            ->get()
            ->filter(function (EmployeePayroll $payroll) use ($filters) {
                if (empty($filters['status'])) {
                    return true;
                }

                if ($filters['status'] === 'final_settlement') {
                    return $payroll->isFinalSettlementPayroll();
                }

                return $payroll->reportStatusKey() === $filters['status'];
            })
            ->filter(fn (EmployeePayroll $payroll) => empty($filters['payment_source'])
                || $payroll->paymentSourceStatusKey() === $filters['payment_source'])
            ->values();

        $legacyPaid = EmployeePayroll::query()
            ->current()
            ->with('financeLedgers')
            ->get()
            ->filter(fn (EmployeePayroll $payroll) => $payroll->paymentSourceStatusKey() === 'legacy_manual_paid');

        return [
            'month' => $month,
            'payment_month' => $paymentMonth,
            'rows' => $rows,
            'history_scope' => $historyScope,
            'integrity' => [
                'legacy_paid_without_ledger_count' => $legacyPaid->count(),
                'legacy_paid_without_ledger_amount' => (float) $legacyPaid->sum('paid_amount'),
            ],
            'summary' => [
                'total_salary_records' => $rows->count(),
                'total_payable_salary' => $rows->sum('payable_salary'),
                'total_paid_salary' => $rows->sum('paid_amount'),
                'total_remaining_due' => $rows->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            ],
        ];
    }

    public function employeePayable(int $employeeId, string $month): array
    {
        $sheet = $this->build([
            'employee_id' => $employeeId,
            'month' => $month,
        ]);
        $rows = $sheet['rows'];

        return [
            'month' => $sheet['month'] ?: Carbon::createFromFormat('Y-m', $month)->startOfMonth(),
            'client_id' => $rows->first()?->client_id,
            'payable_salary' => (float) $rows->sum('payable_salary'),
            'counted_days' => (int) $rows->sum('working_days'),
            'non_counted_days' => (int) $rows->sum('non_working_days'),
        ];
    }
}
