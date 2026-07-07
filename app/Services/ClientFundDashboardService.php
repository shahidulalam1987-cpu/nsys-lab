<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\EmployeePayroll;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientFundDashboardService
{
    private ?Collection $payrollStages = null;

    public function __construct(
        private PayrollCategoryService $payrollCategory,
        private AssignmentResolver $assignmentResolver,
        private ClientFundSummaryService $fundSummary
    ) {}

    public function dashboard(): array
    {
        $clients = Client::with(['salaryPayments', 'employeePayrolls' => fn ($query) => $query->current(), 'employeePayrolls.employee'])
            ->orderBy('company_name')
            ->get();
        $rows = $clients->map(fn (Client $client) => $this->clientSummary($client));
        $totalFundReceived = (float) $rows->sum('fund_received');
        $totalSalaryUsed = (float) $rows->sum('salary_used');
        $totalAdsReceived = (float) $rows->sum('ads_received');
        $totalAdsSpent = (float) $rows->sum('ads_spent');

        return [
            'rows' => $rows,
            'summary' => [
                'total_fund_received' => $totalFundReceived,
                'total_salary_used' => $totalSalaryUsed,
                'available_balance' => (float) $rows->sum('available_balance'),
                'ads_fund_received' => $totalAdsReceived,
                'ads_fund_spent' => $totalAdsSpent,
                'ads_fund_balance' => (float) $rows->sum('ads_balance'),
                'combined_client_balance' => (float) $rows->sum('combined_balance'),
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
        $payrollCounts = $this->payrollCategory->queueCounts();

        return [
            'upcoming_salary_count' => $payrollCounts['upcoming'],
            'unpaid_salary_count' => $payrollCounts['unpaid'],
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
        return app(ClientSalaryFundService::class)->balance($clientId);
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
            'ads_received' => $row['ads_received'],
            'ads_spent' => $row['ads_spent'],
            'ads_balance' => $row['ads_balance'],
            'combined_balance' => $row['combined_balance'],
            'pending_payments' => $row['pending_payments'],
            'upcoming_salary' => $row['upcoming_salary'],
            'unpaid_salary' => $row['unpaid_salary_due'],
        ]);
    }

    private function clientSummary(Client $client): array
    {
        $funds = $this->fundSummary->forClient($client);
        $fundReceived = (float) $funds['salary']['received'];
        $pendingPayments = (float) $client->salaryPayments
            ->where('status', 'pending')
            ->sum('amount');
        $pendingPaymentCount = $client->salaryPayments
            ->where('status', 'pending')
            ->count();
        $salaryUsed = (float) $funds['salary']['used'];
        $availableBalance = (float) $funds['salary']['balance'];
        $unpaid = $this->unpaidSalaryForClient($client);
        $upcoming = $this->upcomingSalaryForClient($client);

        return [
            'client' => $client,
            'fund_received' => $fundReceived,
            'salary_used' => $salaryUsed,
            'available_balance' => $availableBalance,
            'ads_received' => (float) $funds['ads']['received'],
            'ads_spent' => (float) $funds['ads']['used'],
            'ads_balance' => (float) $funds['ads']['balance'],
            'combined_balance' => (float) $funds['combined_balance'],
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
        $categories = [
            PayrollCategoryService::PENDING_WORK_STATUS,
            PayrollCategoryService::SALARY_READY,
            PayrollCategoryService::GENERATED,
            PayrollCategoryService::UNPAID,
            PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            PayrollCategoryService::FINAL_SETTLEMENT_UNPAID,
        ];
        $rows = $this->stagesForClient($client)
            ->filter(fn (array $row) => in_array(data_get($row, 'stage.category'), $categories, true));

        return [
            'amount' => (float) $rows->sum(fn (array $row) => $this->stageAmount($row['stage'])),
            'employee_count' => $rows->pluck('employee.id')->unique()->count(),
        ];
    }

    private function upcomingSalaryForClient(Client $client): array
    {
        $rows = $this->payrollCategory->upcomingCycles()
            ->filter(fn (array $row) => $this->stageClientId($row['employee'], $row['salary_date'], data_get($row, 'payroll')) === $client->id);
        $nearestDueDate = $rows->min('salary_date');

        return [
            'amount' => (float) $rows->sum(fn (array $row) => $this->stageAmount($row)),
            'employee_count' => $rows->pluck('employee.id')->unique()->count(),
            'due_text' => $nearestDueDate
                ? 'Due in ' . now()->startOfDay()->diffInDays($nearestDueDate) . ' Days'
                : 'No Upcoming Salary',
        ];
    }

    private function stagesForClient(Client $client): Collection
    {
        $this->payrollStages ??= $this->payrollCategory->employeeStages();

        return $this->payrollStages->filter(function (array $row) use ($client) {
            $stage = $row['stage'];
            $payroll = data_get($stage, 'payroll');
            $date = data_get($stage, 'salary_date') ?: $payroll?->salaryDueDate();

            return $this->stageClientId($row['employee'], $date, $payroll) === $client->id;
        });
    }

    private function stageClientId($employee, $date, ?EmployeePayroll $payroll): ?int
    {
        if ($payroll?->client_id) {
            return (int) $payroll->client_id;
        }

        $assignmentDate = $date ? Carbon::parse($date) : now();

        return $this->assignmentResolver->current($employee, $assignmentDate)?->client_id;
    }

    private function stageAmount(array $stage): float
    {
        $payroll = data_get($stage, 'payroll');

        return $payroll
            ? max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)
            : (float) data_get($stage, 'estimate.estimated_payable_salary', 0);
    }

    public function ledgerExportRows(Client $client, array $filters = []): Collection
    {
        return $this->clientDetails($client, $filters)['ledger'];
    }

    private function ledger(Client $client, array $filters = []): Collection
    {
        return ClientFundLedger::where('client_id', $client->id)
            ->latest()
            ->get()
            ->map(fn (ClientFundLedger $ledger) => [
                'date' => $ledger->created_at?->toDateString(),
                'type' => $ledger->fundTypeLabel() . ' ' . $ledger->directionLabel(),
                'fund_type' => $ledger->fund_type,
                'reference' => $ledger->reference,
                'description' => $ledger->description ?: $ledger->reference,
                'debit' => $ledger->direction === ClientFundLedger::DIRECTION_DEBIT ? (float) $ledger->amount_bdt : 0.0,
                'credit' => $ledger->direction === ClientFundLedger::DIRECTION_CREDIT ? (float) $ledger->amount_bdt : 0.0,
                'running_balance' => (float) $ledger->balance_after,
            ])
            ->sortBy([['date', 'asc'], ['type', 'asc']])
            ->values()
            ->filter(function (array $entry) use ($filters) {
                if (($filters['type'] ?? null) && $entry['type'] !== $filters['type']) {
                    return false;
                }

                if (($filters['fund_type'] ?? null) && $entry['fund_type'] !== $filters['fund_type']) {
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
            ->values();
    }
}
