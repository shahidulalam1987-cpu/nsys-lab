<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePayroll;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientFundDashboardService
{
    public function __construct(private PayrollEstimateService $payrollEstimator)
    {
    }

    public function dashboard(): array
    {
        $clients = Client::with(['salaryPayments', 'employeePayrolls' => fn ($query) => $query->current(), 'employeePayrolls.employee'])
            ->orderBy('company_name')
            ->get();
        $rows = $clients->map(fn (Client $client) => $this->clientSummary($client));
        $totalFundReceived = (float) $rows->sum('fund_received');
        $totalSalaryUsed = (float) $rows->sum('salary_used');

        return [
            'rows' => $rows,
            'summary' => [
                'total_fund_received' => $totalFundReceived,
                'total_salary_used' => $totalSalaryUsed,
                'available_balance' => $totalFundReceived - $totalSalaryUsed,
                'pending_client_payments' => (float) $rows->sum('pending_payments'),
                'pending_client_payment_count' => (int) $rows->sum('pending_payment_count'),
                'unpaid_salary_due' => (float) $rows->sum('unpaid_salary_due'),
                'unpaid_employee_count' => (int) $rows->sum('unpaid_employee_count'),
                'upcoming_salary' => (float) $rows->sum('upcoming_salary'),
                'upcoming_employee_count' => (int) $rows->sum('upcoming_employee_count'),
            ],
        ];
    }

    public function clientDetails(Client $client, array $filters = []): array
    {
        $client->load(['salaryPayments', 'employeePayrolls' => fn ($query) => $query->current(), 'employeePayrolls.employee']);

        return [
            'row' => $this->clientSummary($client),
            'ledger' => $this->ledger($client, $filters),
        ];
    }

    public function sidebarBadges(): array
    {
        $dashboard = $this->dashboard();

        return [
            'upcoming_salary_count' => $dashboard['summary']['upcoming_employee_count'],
            'unpaid_salary_count' => $dashboard['summary']['unpaid_employee_count'],
            'pending_payment_count' => $dashboard['summary']['pending_client_payment_count'],
        ];
    }

    public function balanceClass(float $balance): string
    {
        if ($balance < 0) {
            return 'balance-critical';
        }

        if ($balance <= 5000) {
            return 'balance-warning';
        }

        return 'balance-positive';
    }

    public function clientAvailableBalance(int $clientId): float
    {
        $fundReceived = (float) SalaryPayment::where('client_id', $clientId)
            ->where('status', 'approved')
            ->sum('amount');
        $salaryUsed = (float) EmployeePayroll::current()
            ->where('client_id', $clientId)
            ->sum('paid_amount');

        return $fundReceived - $salaryUsed;
    }

    public function clientBalanceMap(): array
    {
        return Client::pluck('id')
            ->mapWithKeys(fn ($clientId) => [$clientId => $this->clientAvailableBalance((int) $clientId)])
            ->all();
    }

    public function exportRows(): Collection
    {
        return $this->dashboard()['rows']->map(fn (array $row) => [
            'client' => $row['client']->company_name,
            'fund_received' => $row['fund_received'],
            'salary_used' => $row['salary_used'],
            'balance' => $row['available_balance'],
            'pending_payments' => $row['pending_payments'],
            'upcoming_salary' => $row['upcoming_salary'],
            'unpaid_salary' => $row['unpaid_salary_due'],
        ]);
    }

    private function clientSummary(Client $client): array
    {
        $fundReceived = (float) $client->salaryPayments
            ->where('status', 'approved')
            ->sum('amount');
        $pendingPayments = (float) $client->salaryPayments
            ->where('status', 'pending')
            ->sum('amount');
        $pendingPaymentCount = $client->salaryPayments
            ->where('status', 'pending')
            ->count();
        $salaryUsed = (float) $client->employeePayrolls->sum('paid_amount');
        $availableBalance = $fundReceived - $salaryUsed;
        $unpaid = $this->unpaidSalaryForClient($client);
        $upcoming = $this->upcomingSalaryForClient($client);

        return [
            'client' => $client,
            'fund_received' => $fundReceived,
            'salary_used' => $salaryUsed,
            'available_balance' => $availableBalance,
            'balance_class' => $this->balanceClass($availableBalance),
            'pending_payments' => $pendingPayments,
            'pending_payment_count' => $pendingPaymentCount,
            'unpaid_salary_due' => $unpaid['amount'],
            'unpaid_employee_count' => $unpaid['employee_count'],
            'upcoming_salary' => $upcoming['amount'],
            'upcoming_employee_count' => $upcoming['employee_count'],
            'upcoming_due_text' => $upcoming['due_text'],
        ];
    }

    private function unpaidSalaryForClient(Client $client): array
    {
        $employeeIds = collect();
        $amount = (float) $client->employeePayrolls->sum(function (EmployeePayroll $payroll) use ($employeeIds) {
            if (! in_array($payroll->calculated_status, ['unpaid', 'partial'], true)) {
                return 0;
            }

            if ($payroll->employee_id) {
                $employeeIds->push($payroll->employee_id);
            }

            return max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0);
        });

        foreach ($this->activeAssignmentsForClient($client) as $assignment) {
            $employee = $assignment->employee;
            $dueDate = $employee?->currentSalaryDueDate();

            if (! $employee || $employee->status === 'terminated' || ! $dueDate || ! $dueDate->lt(now()->startOfDay())) {
                continue;
            }

            $hasPayrollForCycle = $client->employeePayrolls->contains(
                fn (EmployeePayroll $payroll) => $payroll->employee_id === $employee->id
                    && $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $dueDate->copy()->startOfMonth()->toDateString()
            );

            if (! $hasPayrollForCycle) {
                $employeeIds->push($employee->id);
                $amount += (float) $this->payrollEstimator
                    ->estimateCycle($employee, $dueDate, $client)['estimated_payable_salary'];
            }
        }

        return [
            'amount' => $amount,
            'employee_count' => $employeeIds->unique()->count(),
        ];
    }

    private function upcomingSalaryForClient(Client $client): array
    {
        $today = now()->startOfDay();
        $until = $today->copy()->addDays(5);
        $employeeIds = collect();
        $amount = 0.0;
        $nearestDueDate = null;

        foreach ($this->activeAssignmentsForClient($client) as $assignment) {
            $employee = $assignment->employee;
            $dueDate = $employee?->nextSalaryDate();

            if (! $employee || $employee->status === 'terminated' || ! $dueDate || ! $dueDate->betweenIncluded($today, $until)) {
                continue;
            }

            $payroll = $client->employeePayrolls
                ->where('employee_id', $employee->id)
                ->filter(fn (EmployeePayroll $payroll) => $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $dueDate->copy()->startOfMonth()->toDateString())
                ->sortByDesc('id')
                ->first();

            if ($payroll && $payroll->calculated_status === 'paid') {
                continue;
            }

            $employeeIds->push($employee->id);
            $amount += $payroll
                ? max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)
                : (float) $this->payrollEstimator
                    ->estimateCycle($employee, $dueDate, $client)['estimated_payable_salary'];
            $nearestDueDate = $nearestDueDate && $nearestDueDate->lte($dueDate)
                ? $nearestDueDate
                : $dueDate->copy();
        }

        return [
            'amount' => $amount,
            'employee_count' => $employeeIds->unique()->count(),
            'due_text' => $nearestDueDate
                ? 'Due in ' . now()->startOfDay()->diffInDays($nearestDueDate) . ' Days'
                : 'No Upcoming Salary',
        ];
    }

    private function activeAssignmentsForClient(Client $client): Collection
    {
        $today = now()->toDateString();

        return EmployeeAssignment::with(['employee.payrolls' => fn ($query) => $query->current()])
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->whereDate('assigned_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $today);
            })
            ->get();
    }

    public function ledgerExportRows(Client $client, array $filters = []): Collection
    {
        return $this->clientDetails($client, $filters)['ledger'];
    }

    private function ledger(Client $client, array $filters = []): Collection
    {
        $entries = collect();

        foreach ($client->salaryPayments->where('status', 'approved') as $payment) {
            $entries->push([
                'date' => $payment->salary_month?->toDateString() ?: $payment->created_at?->toDateString(),
                'type' => 'Client Fund Received',
                'description' => 'Client fund payment ' . ($payment->transaction_id ? '(' . $payment->transaction_id . ')' : ''),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
            ]);
        }

        foreach ($client->employeePayrolls->where('paid_amount', '>', 0) as $payroll) {
            $entries->push([
                'date' => $payroll->payment_date?->toDateString() ?: $payroll->created_at?->toDateString(),
                'type' => 'Employee Salary Paid',
                'description' => trim(($payroll->employee?->name ?: 'Employee') . ' - ' . $payroll->salary_period),
                'debit' => (float) $payroll->paid_amount,
                'credit' => 0.0,
            ]);
        }

        $runningBalance = 0.0;

        return $entries
            ->sortBy([['date', 'asc'], ['type', 'asc']])
            ->values()
            ->filter(function (array $entry) use ($filters) {
                if (($filters['type'] ?? null) && $entry['type'] !== $filters['type']) {
                    return false;
                }

                if (($filters['date_from'] ?? null) && $entry['date'] < $filters['date_from']) {
                    return false;
                }

                if (($filters['date_to'] ?? null) && $entry['date'] > $filters['date_to']) {
                    return false;
                }

                return true;
            })
            ->values()
            ->map(function (array $entry) use (&$runningBalance) {
                $runningBalance += $entry['credit'] - $entry['debit'];
                $entry['running_balance'] = $runningBalance;

                return $entry;
            });
    }
}
