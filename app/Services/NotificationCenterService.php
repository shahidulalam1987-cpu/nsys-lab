<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\CardTransaction;
use App\Models\Client;
use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeeBonusEarning;
use App\Models\EmployeeTarget;
use App\Models\PerformanceVerification;
use App\Models\EmployeeWorkStatus;
use App\Models\FinanceAccount;
use App\Models\FundingBalance;
use App\Models\SalaryPayment;
use App\Models\SystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationCenterService
{
    public function __construct(private PayrollCategoryService $payrollCategory)
    {
    }

    public function sync(): Collection
    {
        $alerts = collect()
            ->merge($this->employeeAlerts())
            ->merge($this->clientAlerts())
            ->merge($this->facebookAlerts())
            ->merge($this->financeAlerts())
            ->merge($this->profitAlerts());

        $activeKeys = $alerts->pluck('notification_key')->all();

        $alerts->each(function (array $alert) {
            $notification = SystemNotification::firstOrNew([
                'notification_key' => $alert['notification_key'],
            ]);

            $status = $this->syncedStatus($notification, $alert);

            $notification->fill(array_merge($alert, [
                'type' => $alert['type'] ?? 'alert',
                'status' => $status,
                'resolved_at' => null,
                'resolved_by' => null,
            ]));
            $notification->save();
        });

        SystemNotification::query()
            ->whereNotIn('notification_key', $activeKeys)
            ->where('type', 'alert')
            ->whereNotIn('status', ['resolved'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => null,
                'updated_at' => now(),
            ]);

        return $this->openNotifications();
    }

    public function openNotifications()
    {
        return SystemNotification::query()
            ->whereNotIn('status', ['resolved', 'dismissed'])
            ->latest()
            ->get();
    }

    public function summary(): array
    {
        $open = $this->sync();

        return $this->summaryFor($open);
    }

    public function readSummary(): array
    {
        return $this->summaryFor($this->openNotifications());
    }

    public function summaryFor(Collection $open): array
    {
        return [
            'critical' => $open->where('priority', 'critical')->count(),
            'warning' => $open->where('priority', 'warning')->count(),
            'information' => $open->where('priority', 'information')->count(),
            'unread' => $open->where('status', 'unread')->count(),
            'upcoming_salaries' => $open->where('notification_key', 'employee.upcoming_salary')->count()
                ? $this->upcomingSalaryCount()
                : 0,
            'pending_client_payments' => SalaryPayment::where('status', 'pending')->count(),
            'low_funding_balance' => FundingBalance::all()->filter(fn (FundingBalance $balance) => $balance->isLowBalance())->count(),
            'ad_account_billing_due' => AdAccount::all()->filter(fn (AdAccount $account) => $account->billingStatus() === 'upcoming')->count(),
            'today_profit' => (float) CardTransaction::whereDate('transaction_date', today())->sum('net_profit'),
            'monthly_profit' => (float) CardTransaction::whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('net_profit'),
        ];
    }

    public function groupedOpenNotifications(int $limitPerPriority = 8): array
    {
        $open = $this->sync();

        return $this->groupedFrom($open, $limitPerPriority);
    }

    public function readGroupedOpenNotifications(int $limitPerPriority = 8): array
    {
        return $this->groupedFrom($this->openNotifications(), $limitPerPriority);
    }

    private function groupedFrom(Collection $open, int $limitPerPriority = 8): array
    {
        return [
            'critical' => $open->where('priority', 'critical')->take($limitPerPriority)->values(),
            'warning' => $open->where('priority', 'warning')->take($limitPerPriority)->values(),
            'information' => $open->where('priority', 'information')->take($limitPerPriority)->values(),
        ];
    }

    private function syncedStatus(SystemNotification $notification, array $alert): string
    {
        if (! $notification->exists || $notification->status === 'resolved') {
            return 'unread';
        }

        if ($notification->status === 'dismissed' && ($alert['priority'] ?? null) === 'critical') {
            return 'unread';
        }

        return $notification->status;
    }

    private function employeeAlerts(): array
    {
        $upcoming = $this->payrollCategory->upcomingCycles();
        $stages = $this->payrollCategory->employeeStages();
        $unpaidCategories = [
            PayrollCategoryService::PENDING_WORK_STATUS,
            PayrollCategoryService::SALARY_READY,
            PayrollCategoryService::GENERATED,
            PayrollCategoryService::UNPAID,
            PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            PayrollCategoryService::FINAL_SETTLEMENT_UNPAID,
        ];
        $unpaid = $stages->filter(fn (array $row) => in_array($row['stage']['category'], $unpaidCategories, true));
        $finalSettlements = $stages->filter(fn (array $row) => in_array($row['stage']['category'], [PayrollCategoryService::FINAL_SETTLEMENT_PENDING, PayrollCategoryService::FINAL_SETTLEMENT_UNPAID], true));
        $overdue = $unpaid->filter(function (array $row) {
            $payroll = data_get($row, 'stage.payroll');
            $employee = data_get($row, 'employee') ?: $payroll?->employee;
            $salaryDate = in_array(data_get($row, 'stage.category'), [PayrollCategoryService::FINAL_SETTLEMENT_PENDING, PayrollCategoryService::FINAL_SETTLEMENT_UNPAID], true)
                ? (data_get($row, 'stage.payment_deadline') ?: $employee?->finalSettlementPaymentDeadline())
                : (data_get($row, 'stage.salary_date') ?: $payroll?->salaryDueDate());

            return $salaryDate && $salaryDate->lt(today());
        });
        $dueInFive = $upcoming->filter(fn (array $row) => today()->diffInDays($row['salary_date']) === 5)->count();
        $dueInThree = $upcoming->filter(fn (array $row) => today()->diffInDays($row['salary_date']) === 3)->count();
        $dueTomorrow = $upcoming->filter(fn (array $row) => today()->diffInDays($row['salary_date']) === 1)->count();

        $employees = Employee::with(['activeAssignments', 'workStatuses' => fn ($query) => $query->whereDate('work_date', today())])
            ->whereIn('status', ['active', 'probation', 'on_leave'])
            ->get();
        $missingBank = $employees->filter(fn (Employee $employee) => ! $employee->bank_name || ! $employee->account_name || ! $employee->account_number);
        $onLeave = $employees->where('status', 'on_leave');
        $missingAssignment = $employees->filter(fn (Employee $employee) => $employee->status !== 'on_leave' && $employee->activeAssignments->isEmpty());
        $missingWorkStatus = $employees->filter(fn (Employee $employee) => $employee->status !== 'on_leave' && $employee->workStatuses->isEmpty());
        $finalSalaryPending = $this->terminatedEmployeesMissingFinalPayroll();
        $missedTargets = EmployeeTarget::with('employee')
            ->where('status', 'active')
            ->whereNotNull('employee_id')
            ->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->get()
            ->filter(function (EmployeeTarget $target) {
                if (! $target->employee) return false;
                [$from, $to] = match ($target->period_type) {
                    'daily' => [today(), today()], 'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
                    default => [now()->startOfMonth(), now()->endOfMonth()],
                };
                $kpi = app(PerformanceOperationsService::class)->employeeKpi($target->employee, $from, $to);
                $value = match ($target->target_type) {
                    'orders' => $kpi['confirmed_orders'], 'spend' => $kpi['approved_spend'],
                    'max_cpo' => $kpi['average_cpo'], 'approval_rate' => $kpi['approval_rate'], default => 0,
                };
                return $target->target_type === 'max_cpo' ? $value > (float) $target->target_value : $value < (float) $target->target_value;
            });

        return array_values(array_filter([
            $this->alertIf($upcoming->count(), 'employee.upcoming_salary', 'Employee', 'warning', $upcoming->count() . ' Upcoming Salaries', '/admin/payroll?status=upcoming', 'HR Team'),
            $this->alertIf($dueInFive, 'employee.salary_due_5_days', 'Employee', 'information', $dueInFive . ' Salaries Due in 5 Days', '/admin/payroll?status=upcoming', 'HR Team'),
            $this->alertIf($dueInThree, 'employee.salary_due_3_days', 'Employee', 'warning', $dueInThree . ' Salaries Due in 3 Days', '/admin/payroll?status=upcoming', 'HR Team'),
            $this->alertIf($dueTomorrow, 'employee.salary_due_tomorrow', 'Employee', 'critical', $dueTomorrow . ' Salaries Due Tomorrow', '/admin/payroll?status=upcoming', 'HR Team'),
            $this->alertIf($unpaid->count(), 'employee.unpaid_salary', 'Employee', 'critical', $unpaid->count() . ' Unpaid Salary Actions', '/admin/payroll?status=due', 'HR Team'),
            $this->alertIf($overdue->count(), 'employee.salary_overdue', 'Employee', 'critical', $overdue->count() . ' Salary Payments Overdue', '/admin/payroll?status=due', 'HR Team'),
            $this->alertIf($finalSettlements->count(), 'employee.final_settlement_due', 'Employee', 'critical', $this->finalSettlementStageMessage($finalSettlements), '/admin/payroll?status=due&employee_scope=terminated', 'HR Team'),
            $this->alertIf($finalSalaryPending->count(), 'employee.final_salary_pending', 'Employee', 'warning', $this->finalSalaryPendingMessage($finalSalaryPending), '/admin/payroll?status=due&employee_scope=terminated', 'HR Team'),
            $this->alertIf($missingBank->count(), 'employee.missing_bank', 'Employee', 'warning', $missingBank->count() . ' Employees Missing Bank Information', '/admin/employees', 'HR Team'),
            $this->alertIf($onLeave->count(), 'employee.on_leave', 'Employee', 'information', $onLeave->count() . ' Employees On Leave', '/admin/employees?status=on_leave', 'HR Team'),
            $this->alertIf($missingAssignment->count(), 'employee.missing_assignment', 'Employee', 'warning', $missingAssignment->count() . ' Employees Missing Assignment', '/admin/assignments', 'HR Team'),
            $this->alertIf($missingWorkStatus->count(), 'employee.missing_work_status', 'Employee', 'warning', $missingWorkStatus->count() . ' Employees Missing Today Work Status', '/admin/work-status', 'HR Team'),
            $this->alertIf($missedTargets->count(), 'employee.performance_target_missed', 'Employee', 'warning', $missedTargets->count() . ' Employee Performance Targets Missed', '/admin/performance-targets', 'HR Team'),
        ]));
    }

    private function clientAlerts(): array
    {
        $dashboard = app(ClientFundDashboardService::class)->dashboard();
        $rows = $dashboard['rows'];
        $negative = $rows->filter(fn (array $row) => (float) $row['available_balance'] < 0);
        $low = $rows->filter(fn (array $row) => (float) $row['available_balance'] >= 0 && (float) $row['available_balance'] < 1000);
        $pending = SalaryPayment::where('status', 'pending')->count();
        $rejected = SalaryPayment::where('status', 'rejected')->count();
        $noActivity = Client::doesntHave('dailyPerformanceReports')->doesntHave('employeePayrolls')->count();

        return array_values(array_filter([
            $this->alertIf($negative->count(), 'client.negative_balance', 'Client', 'critical', $negative->count() . ' Clients Negative Balance', '/admin/client-fund', 'Management'),
            $this->alertIf($low->count(), 'client.low_balance', 'Client', 'warning', $low->count() . ' Clients Low Balance', '/admin/client-fund', 'Management'),
            $this->alertIf($pending, 'client.pending_payments', 'Client', 'warning', $pending . ' Pending Client Payments', '/admin/salary-payments/pending', 'Finance Team'),
            $this->alertIf($rejected, 'client.rejected_payments', 'Client', 'information', $rejected . ' Rejected Client Payments', '/admin/salary-payments?status=rejected', 'Finance Team'),
            $this->alertIf($noActivity, 'client.no_activity', 'Client', 'information', $noActivity . ' Clients With No Activity', '/admin/clients', 'Management'),
        ]));
    }

    private function facebookAlerts(): array
    {
        $accounts = AdAccount::all();
        $upcomingBilling = $accounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'upcoming');
        $overdueBilling = $accounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'overdue');
        $lowBalance = $accounts->filter(fn (AdAccount $account) => $account->balanceStatus() === 'low');
        $thresholdReached = $accounts->filter(fn (AdAccount $account) => $account->thresholdStatus() === 'limit_reached');
        $thresholdWarning = $accounts->filter(fn (AdAccount $account) => $account->thresholdStatus() === 'warning');
        $disabled = $accounts->where('status', 'disabled');
        $paymentIssue = $accounts->where('status', 'payment_issue');
        $endingSoon = Campaign::all()->filter(fn (Campaign $campaign) => $campaign->isEndingSoon());
        $pendingOrders = EmployeeDailySubmission::where('status', 'pending')->where('submission_type', 'order')->count();
        $pendingSpend = EmployeeDailySubmission::where('status', 'pending')->where('submission_type', 'spend')->count();
        $readyToMerge = EmployeeDailySubmission::where('status', 'approved')
            ->whereNotNull('campaign_id')
            ->get()
            ->groupBy(fn (EmployeeDailySubmission $submission) => implode(':', [
                $submission->submission_date?->toDateString(),
                $submission->client_id,
                $submission->page_id,
                $submission->campaign_id,
            ]))
            ->filter(fn ($group) => $group->pluck('submission_type')->unique()->count() === 2)
            ->count();
        $mismatches = PerformanceVerification::where('status', 'mismatch')->count();

        return array_values(array_filter([
            $this->alertIf($upcomingBilling->count(), 'facebook.billing_due', 'Facebook', 'warning', $upcomingBilling->count() . ' Ad Account Billing Due Soon', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($overdueBilling->count(), 'facebook.billing_overdue', 'Facebook', 'critical', $overdueBilling->count() . ' Ad Account Billing Overdue', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($lowBalance->count(), 'facebook.low_balance', 'Facebook', 'warning', $lowBalance->count() . ' Low Ad Account Balance', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($thresholdReached->count(), 'facebook.threshold_reached', 'Facebook', 'critical', $thresholdReached->count() . ' Threshold Limit Reached', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($thresholdWarning->count(), 'facebook.threshold_warning', 'Facebook', 'warning', $thresholdWarning->count() . ' Threshold Above 80%', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($disabled->count(), 'facebook.disabled_accounts', 'Facebook', 'critical', $disabled->count() . ' Ad Accounts Disabled', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($paymentIssue->count(), 'facebook.payment_issue', 'Facebook', 'critical', $paymentIssue->count() . ' Payment Issue Ad Accounts', '/admin/ad-accounts', 'Facebook Team'),
            $this->alertIf($endingSoon->count(), 'facebook.campaign_ending_soon', 'Facebook', 'information', $endingSoon->count() . ' Campaigns Ending This Week', '/admin/campaigns', 'Facebook Team'),
            $this->alertIf($pendingOrders, 'facebook.pending_employee_orders', 'Facebook', 'warning', $pendingOrders . ' Pending Order Submissions', '/admin/employee-submissions?status=pending&type=order', 'Facebook Team'),
            $this->alertIf($pendingSpend, 'facebook.pending_employee_spend', 'Facebook', 'warning', $pendingSpend . ' Pending Spend Submissions', '/admin/employee-submissions?status=pending&type=spend', 'Facebook Team'),
            $this->alertIf($readyToMerge, 'facebook.employee_reports_ready', 'Facebook', 'information', $readyToMerge . ' Ready to Merge Performance Report'.($readyToMerge === 1 ? '' : 's'), '/admin/employee-submissions?status=approved', 'Facebook Team'),
            $this->alertIf($mismatches, 'facebook.performance_mismatch', 'Facebook', 'critical', $mismatches . ' Performance Reports Marked Mismatch', '/admin/performance-verification?status=mismatch', 'Facebook Team'),
        ]));
    }

    private function financeAlerts(): array
    {
        $balances = FundingBalance::all()->keyBy('source');
        $accounts = FinanceAccount::all();
        $negativeAccounts = $accounts->filter(fn (FinanceAccount $account) => (float) $account->current_balance < 0);
        $highFees = CardTransaction::where('fee_usd', '>=', 5)
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->count();
        $pendingBonuses = EmployeeBonusEarning::where('status', 'pending')->count();

        return array_values(array_filter([
            $this->fundingAlert($balances->get('binance'), 'finance.low_binance', 'Binance Balance Below 200 USD'),
            $this->fundingAlert($balances->get('redotpay'), 'finance.low_redotpay', 'RedotPay Balance Below 100 USD'),
            $this->fundingAlert($balances->get('tavao'), 'finance.low_tavao', 'Tavao Balance Below 100 USD'),
            $this->alertIf($negativeAccounts->count(), 'finance.negative_account_balance', 'Finance', 'critical', $negativeAccounts->count() . ' Finance Accounts Negative Balance', '/admin/finance/accounts', 'Finance Team'),
            $this->alertIf($highFees, 'finance.high_card_fees', 'Finance', 'warning', $highFees . ' High Card Fee Transactions This Month', '/admin/facebook-financial/card-transactions', 'Finance Team'),
            $this->alertIf($pendingBonuses, 'employee.bonus_pending', 'Employee', 'warning', $pendingBonuses . ' Bonus Earnings Pending Approval', '/admin/bonuses', 'HR Team'),
        ]));
    }

    private function profitAlerts(): array
    {
        $lossTransactions = CardTransaction::where('net_profit', '<', 0)->count();
        $belowTarget = CardTransaction::where('spend_usd', '>', 0)->get()
            ->filter(fn (CardTransaction $transaction) => $transaction->profitPerUsd() > 0 && $transaction->profitPerUsd() < 15)
            ->count();
        $highSpendLowOrders = DailyPerformanceReport::with('campaign')
            ->where('spend', '>=', 20)
            ->where('orders', '<=', 0)
            ->whereDate('report_date', '>=', today()->copy()->subDays(7))
            ->count();
        $thisMonthProfit = (float) CardTransaction::whereMonth('transaction_date', now()->month)->whereYear('transaction_date', now()->year)->sum('net_profit');
        $lastMonthProfit = (float) CardTransaction::whereMonth('transaction_date', now()->copy()->subMonth()->month)->whereYear('transaction_date', now()->copy()->subMonth()->year)->sum('net_profit');
        $profitDrop = $lastMonthProfit > 0 && $thisMonthProfit < ($lastMonthProfit * 0.75);

        return array_values(array_filter([
            $this->alertIf($lossTransactions, 'profit.negative_profit', 'Profit', 'critical', $lossTransactions . ' Campaign Transactions Running at Loss', '/admin/facebook-financial/profit-dashboard', 'Management'),
            $this->alertIf($belowTarget, 'profit.below_target', 'Profit', 'warning', $belowTarget . ' Transactions Below Expected Margin', '/admin/facebook-financial/profit-dashboard', 'Management'),
            $this->alertIf($highSpendLowOrders, 'profit.high_spend_low_orders', 'Profit', 'warning', $highSpendLowOrders . ' High Spend Reports With No Orders', '/admin/profit-history', 'Management'),
            $this->alertIf($profitDrop ? 1 : 0, 'profit.monthly_drop', 'Profit', 'warning', 'Monthly Profit Dropped More Than 25%', '/admin/facebook-financial/profit-dashboard', 'Management'),
        ]));
    }

    private function alertIf(int|float $count, string $key, string $department, string $priority, string $message, string $actionUrl, string $targetTeam): ?array
    {
        if ($count <= 0) {
            return null;
        }

        return [
            'notification_key' => $key,
            'department' => $department,
            'priority' => $priority,
            'message' => $message,
            'action_url' => $actionUrl,
            'target_team' => $targetTeam,
        ];
    }

    private function fundingAlert(?FundingBalance $balance, string $key, string $message): ?array
    {
        if (! $balance || ! $balance->isLowBalance()) {
            return null;
        }

        return [
            'notification_key' => $key,
            'department' => 'Finance',
            'priority' => 'warning',
            'message' => $message,
            'action_url' => '/admin/facebook-financial/funding-dashboard',
            'target_team' => 'Finance Team',
        ];
    }

    private function upcomingSalaryCount(): int
    {
        return $this->payrollCategory->upcomingCycles()->count();
    }

    private function finalSettlementMessage(Collection $payrolls): string
    {
        $first = $payrolls->first();

        if (! $first) {
            return 'Final Settlement Due';
        }

        return $payrolls->count() . ' Final Settlements Due. '
            . $first->snapshotEmployeeName()
            . ' | Client: ' . ($first->client?->company_name ?: '-')
            . ' | Last Working Date: ' . ($first->employee?->last_working_date?->toDateString() ?: '-')
            . ' | Working Days: ' . number_format((float) $first->working_days, 2)
            . ' | Non Working Days: ' . number_format((float) $first->non_working_days, 2)
            . ' | Payable: BDT ' . number_format((float) $first->payable_salary, 2)
            . ' | Period: ' . $first->salary_period
            . ' | ' . $first->overdueLabel();
    }

    private function finalSettlementStageMessage(Collection $stages): string
    {
        $payrolls = $stages
            ->map(fn (array $row) => data_get($row, 'stage.payroll'))
            ->filter()
            ->values();

        if ($payrolls->isNotEmpty()) {
            return $this->finalSettlementMessage($payrolls);
        }

        $first = $stages->first();
        $employee = data_get($first, 'employee');
        return $stages->count() . ' Final Settlements Due. '
            . ($employee?->name ?: 'Final settlement')
            . ' | Last Working Date: ' . ($employee?->last_working_date?->toDateString() ?: '-')
            . ' | ' . ($employee ? app(FinalSettlementService::class)->deadlineLabel($employee) : 'Final Settlement Deadline: -');
    }

    private function terminatedEmployeesMissingFinalPayroll(): Collection
    {
        return Employee::with(['payrolls' => fn ($query) => $query->current()])
            ->where('status', 'terminated')
            ->whereNotNull('last_working_date')
            ->get()
            ->filter(function (Employee $employee) {
                return ! $employee->hasFinalSalaryPayroll();
            })
            ->values();
    }

    private function finalSalaryPendingMessage(Collection $employees): string
    {
        $first = $employees->first();

        if (! $first) {
            return 'Final Salary Pending';
        }

        return $employees->count() . ' Final Salaries Pending. '
            . $first->name
            . ' | Last Working Date: ' . ($first->last_working_date?->toDateString() ?: '-')
            . ' | Final salary not generated yet.';
    }
}
