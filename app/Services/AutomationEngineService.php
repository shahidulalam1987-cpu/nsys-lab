<?php

namespace App\Services;

use App\Models\AutomationAudit;
use App\Models\AutomationTask;
use App\Models\ClientFundLedger;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePayroll;
use App\Models\FacebookCard;
use App\Models\FinanceAccount;
use App\Models\SalaryPayment;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AutomationEngineService
{
    public function __construct(
        private ClientFundSummaryService $clientFunds,
        private PayrollCategoryService $payrollCategory,
        private PerformanceOperationsService $performanceOperations
    ) {}

    public function trigger(string $eventName, array $payload = [], ?Model $related = null, ?User $triggeredBy = null): ?AutomationTask
    {
        $rule = $this->eventRule($eventName, $payload, $related);

        if (! $rule) {
            $this->audit(null, 'unknown_event', $eventName, 'ignored', 'No automation rule matched the event.', $payload, $triggeredBy);

            return null;
        }

        $task = $this->createTask($rule + [
            'event_name' => $eventName,
            'related' => $related,
            'payload' => $payload,
        ], $triggeredBy);

        return $task;
    }

    public function syncBusinessRules(?User $triggeredBy = null): Collection
    {
        $rules = collect()
            ->merge($this->payrollRules())
            ->merge($this->clientFundRules())
            ->merge($this->performanceRules())
            ->merge($this->employeeRules())
            ->merge($this->financeRules())
            ->merge($this->assignmentRules());

        return $rules
            ->map(fn (array $rule) => $this->createTask($rule + ['event_name' => 'scheduled_rule_scan'], $triggeredBy))
            ->filter()
            ->values();
    }

    public function dashboard(array $filters = [], ?User $user = null): array
    {
        $this->syncBusinessRules($user);
        $query = $this->query($filters, $user);
        $base = $this->visibleTasks($user);

        return [
            'summary' => [
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'overdue' => (clone $base)->where('status', 'pending')->whereDate('due_date', '<', today())->count(),
                'today' => (clone $base)->whereDate('created_at', today())->count(),
            ],
            'department_queue' => (clone $base)
                ->selectRaw("COALESCE(department, 'Unassigned') as department_name, COUNT(*) as total")
                ->where('status', 'pending')
                ->groupBy('department_name')
                ->orderByDesc('total')
                ->get(),
            'tasks' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'departments' => AutomationTask::select('department')->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'modules' => AutomationTask::select('related_module')->whereNotNull('related_module')->distinct()->orderBy('related_module')->pluck('related_module'),
        ];
    }

    public function query(array $filters = [], ?User $user = null)
    {
        return $this->visibleTasks($user)
            ->with(['assignedUser', 'completedBy'])
            ->when($filters['department'] ?? null, fn ($query, $value) => $query->where('department', $value))
            ->when($filters['priority'] ?? null, fn ($query, $value) => $query->where('priority', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['module'] ?? null, fn ($query, $value) => $query->where('related_module', $value))
            ->when($filters['date'] ?? null, fn ($query, $value) => $query->whereDate('created_at', $value))
            ->when($filters['overdue'] ?? false, fn ($query) => $query
                ->where('status', 'pending')
                ->whereDate('due_date', '<', today()));
    }

    public function completeTask(AutomationTask $task, User $user): AutomationTask
    {
        if ($task->status === 'completed') {
            $this->audit($task, 'task_completion', 'automation.task_completed', 'duplicate', 'Task was already completed.', [], $user);

            return $task;
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        SystemNotification::where('notification_key', $this->notificationKey($task))
            ->whereNotIn('status', ['resolved', 'dismissed'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => $user->id,
            ]);

        $this->audit($task, 'task_completion', 'automation.task_completed', 'completed', 'Automation task completed.', [], $user);

        return $task;
    }

    private function createTask(array $rule, ?User $triggeredBy = null): ?AutomationTask
    {
        $task = AutomationTask::firstOrNew(['task_key' => $rule['task_key']]);
        $result = $task->exists ? 'duplicate_prevented' : 'created';

        if (! $task->exists || $task->status !== 'completed') {
            $task->fill([
                'title' => $rule['title'],
                'priority' => $rule['priority'] ?? 'medium',
                'status' => $task->exists ? $task->status : 'pending',
                'department' => $rule['department'] ?? null,
                'assigned_user_id' => $rule['assigned_user_id'] ?? null,
                'related_module' => $rule['related_module'] ?? null,
                'related_record_type' => $rule['related_record_type'] ?? $this->relatedClass($rule['related'] ?? null),
                'related_record_id' => $rule['related_record_id'] ?? ($rule['related'] ?? null)?->getKey(),
                'due_date' => $rule['due_date'] ?? null,
            ]);
            $task->save();
            $this->syncNotification($task, $rule);
        }

        $this->audit(
            $task,
            $rule['rule_key'],
            $rule['event_name'] ?? null,
            $result,
            $rule['title'],
            $rule['payload'] ?? [],
            $triggeredBy
        );

        return $task;
    }

    private function syncNotification(AutomationTask $task, array $rule): void
    {
        $notification = SystemNotification::firstOrNew([
            'notification_key' => $this->notificationKey($task),
        ]);

        $notification->fill([
            'type' => 'automation',
            'department' => $task->department ?: 'Automation',
            'priority' => $this->notificationPriority($task->priority),
            'message' => $task->title,
            'status' => $notification->exists && $notification->status !== 'resolved' ? $notification->status : 'unread',
            'action_url' => $rule['action_url'] ?? '/admin/automation?status=pending',
            'reference_type' => AutomationTask::class,
            'reference_id' => $task->id,
            'target_team' => $rule['target_team'] ?? $task->department,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
        $notification->save();
    }

    private function payrollRules(): array
    {
        $upcomingTomorrow = $this->payrollCategory->upcomingCycles()
            ->filter(fn (array $row) => today()->diffInDays($row['salary_date'], false) === 1);
        $missingWorkStatus = $this->payrollCategory->employeeStages()
            ->where('stage.category', PayrollCategoryService::PENDING_WORK_STATUS);

        $rules = [];

        foreach ($upcomingTomorrow as $row) {
            $employee = $row['employee'];
            $rules[] = [
                'rule_key' => 'payroll_due_tomorrow',
                'task_key' => 'payroll_due_tomorrow:' . $employee->id . ':' . $row['salary_date']->toDateString(),
                'title' => 'Payroll due tomorrow for ' . $employee->name,
                'priority' => 'high',
                'department' => 'Payroll',
                'related_module' => 'Payroll',
                'related_record_type' => Employee::class,
                'related_record_id' => $employee->id,
                'due_date' => $row['salary_date']->toDateString(),
                'action_url' => '/admin/payroll?status=upcoming',
                'target_team' => 'Payroll',
            ];
        }

        foreach ($missingWorkStatus as $row) {
            $employee = $row['employee'];
            $salaryDate = data_get($row, 'stage.salary_date');
            $rules[] = [
                'rule_key' => 'work_status_missing',
                'task_key' => 'work_status_missing:' . $employee->id . ':' . ($salaryDate?->toDateString() ?: today()->toDateString()),
                'title' => 'Work Status missing for ' . $employee->name,
                'priority' => 'medium',
                'department' => 'Employee',
                'related_module' => 'Work Status',
                'related_record_type' => Employee::class,
                'related_record_id' => $employee->id,
                'due_date' => today()->toDateString(),
                'action_url' => '/admin/work-status/create?employee_id=' . $employee->id,
                'target_team' => 'Employee, Team Leader, Admin',
            ];
        }

        foreach (EmployeePayroll::where('payroll_status', 'generated')->current()->get() as $payroll) {
            $rules[] = [
                'rule_key' => 'payroll_approval_pending',
                'task_key' => 'payroll_approval_pending:' . $payroll->id,
                'title' => 'Payroll approval pending for ' . $payroll->snapshotEmployeeName(),
                'priority' => 'high',
                'department' => 'Payroll',
                'related_module' => 'Payroll',
                'related_record_type' => EmployeePayroll::class,
                'related_record_id' => $payroll->id,
                'due_date' => today()->toDateString(),
                'action_url' => '/admin/payroll/' . $payroll->id,
                'target_team' => 'Payroll',
            ];
        }

        return $rules;
    }

    private function clientFundRules(): array
    {
        $dashboard = $this->clientFunds->dashboard();
        $rules = [];

        foreach ($dashboard['rows'] as $row) {
            $client = $row['client'];
            if ((float) $row['funds']['salary']['balance'] < 0) {
                $rules[] = [
                    'rule_key' => 'negative_salary_fund',
                    'task_key' => 'negative_salary_fund:' . $client->id,
                    'title' => 'Negative salary fund for ' . $client->company_name,
                    'priority' => 'critical',
                    'department' => 'Finance',
                    'related_module' => 'Client Fund',
                    'related_record_type' => $client::class,
                    'related_record_id' => $client->id,
                    'due_date' => today()->toDateString(),
                    'action_url' => '/admin/client-fund/' . $client->id,
                    'target_team' => 'Finance',
                ];
            }

            if ((float) $row['funds']['ads']['balance'] < 0) {
                $rules[] = [
                    'rule_key' => 'negative_ads_fund',
                    'task_key' => 'negative_ads_fund:' . $client->id,
                    'title' => 'Negative ads fund for ' . $client->company_name,
                    'priority' => 'critical',
                    'department' => 'Finance',
                    'related_module' => 'Client Fund',
                    'related_record_type' => $client::class,
                    'related_record_id' => $client->id,
                    'due_date' => today()->toDateString(),
                    'action_url' => '/admin/client-fund/' . $client->id,
                    'target_team' => 'Finance, Client Manager',
                ];
            }
        }

        foreach (SalaryPayment::where('status', 'pending')->get() as $payment) {
            $rules[] = [
                'rule_key' => 'client_payment_pending',
                'task_key' => 'client_payment_pending:' . $payment->id,
                'title' => 'Client payment pending approval',
                'priority' => 'medium',
                'department' => 'Finance',
                'related_module' => 'Client Payments',
                'related_record_type' => SalaryPayment::class,
                'related_record_id' => $payment->id,
                'due_date' => today()->toDateString(),
                'action_url' => '/admin/salary-payments/pending',
                'target_team' => 'Accounts',
            ];
        }

        return $rules;
    }

    private function performanceRules(): array
    {
        return $this->performanceOperations
            ->verificationGroups(['date_from' => today()->copy()->subDays(7)->toDateString(), 'date_to' => today()->toDateString()])
            ->where('status', 'ready_to_merge')
            ->map(fn (array $group) => [
                'rule_key' => 'daily_performance_ready_to_merge',
                'task_key' => 'daily_performance_ready_to_merge:' . $group['key'],
                'title' => 'Daily performance ready to merge for ' . ($group['client']?->company_name ?: 'client'),
                'priority' => 'medium',
                'department' => 'Facebook',
                'related_module' => 'Daily Performance',
                'due_date' => today()->toDateString(),
                'action_url' => '/admin/performance-verification',
                'target_team' => 'Facebook Team',
            ])
            ->values()
            ->all();
    }

    private function employeeRules(): array
    {
        $rules = [];

        foreach (Employee::where('status', 'probation')->whereNotNull('confirmation_date')->whereDate('confirmation_date', '<=', today())->get() as $employee) {
            $rules[] = [
                'rule_key' => 'employee_probation_ends',
                'task_key' => 'employee_probation_ends:' . $employee->id,
                'title' => 'Probation review due for ' . $employee->name,
                'priority' => 'medium',
                'department' => 'HR',
                'related_module' => 'Employees',
                'related_record_type' => Employee::class,
                'related_record_id' => $employee->id,
                'due_date' => $employee->confirmation_date?->toDateString(),
                'action_url' => '/admin/employees/' . $employee->id,
                'target_team' => 'HR',
            ];
        }

        return $rules;
    }

    private function financeRules(): array
    {
        $rules = [];

        foreach (FinanceAccount::where('status', 'active')->where('current_balance', '<', 1000)->get() as $account) {
            $rules[] = [
                'rule_key' => 'finance_account_low_balance',
                'task_key' => 'finance_account_low_balance:' . $account->id,
                'title' => 'Finance account low balance: ' . $account->account_name,
                'priority' => 'high',
                'department' => 'Finance',
                'related_module' => 'Finance Accounts',
                'related_record_type' => FinanceAccount::class,
                'related_record_id' => $account->id,
                'due_date' => today()->toDateString(),
                'action_url' => '/admin/finance/accounts',
                'target_team' => 'Owner',
            ];
        }

        foreach (FacebookCard::all()->filter(fn (FacebookCard $card) => $card->effectiveStatus() === 'low_balance') as $card) {
            $rules[] = [
                'rule_key' => 'card_balance_low',
                'task_key' => 'card_balance_low:' . $card->id,
                'title' => 'Card balance low: ' . $card->card_name,
                'priority' => 'high',
                'department' => 'Finance',
                'related_module' => 'Card Management',
                'related_record_type' => FacebookCard::class,
                'related_record_id' => $card->id,
                'due_date' => today()->toDateString(),
                'action_url' => '/admin/facebook-cards/' . $card->id,
                'target_team' => 'Finance',
            ];
        }

        return $rules;
    }

    private function assignmentRules(): array
    {
        return EmployeeAssignment::with('employee')
            ->where('status', 'active')
            ->whereNotNull('assigned_to')
            ->whereDate('assigned_to', '<', today())
            ->get()
            ->map(fn (EmployeeAssignment $assignment) => [
                'rule_key' => 'assignment_expired',
                'task_key' => 'assignment_expired:' . $assignment->id,
                'title' => 'Assignment expired for ' . ($assignment->employee?->name ?: 'employee'),
                'priority' => 'low',
                'department' => 'HR',
                'related_module' => 'Assignments',
                'related_record_type' => EmployeeAssignment::class,
                'related_record_id' => $assignment->id,
                'due_date' => $assignment->assigned_to?->toDateString(),
                'action_url' => '/admin/assignments/' . $assignment->id,
                'target_team' => 'HR, Manager',
            ])
            ->values()
            ->all();
    }

    private function eventRule(string $eventName, array $payload, ?Model $related): ?array
    {
        $key = str($eventName)->slug('_')->toString();
        $title = ucwords(str_replace('_', ' ', $key));

        return match ($key) {
            'payroll_approved' => $this->simpleEventRule($key, $title, 'Payroll', 'Payroll', 'medium', '/admin/payroll', $related, $payload),
            'payroll_paid' => $this->simpleEventRule($key, $title, 'Payroll', 'Payroll', 'low', '/admin/payroll/payment-report', $related, $payload),
            'client_payment_received' => $this->simpleEventRule($key, $title, 'Finance', 'Client Payments', 'medium', '/admin/salary-payments', $related, $payload),
            'daily_performance_merged' => $this->simpleEventRule($key, $title, 'Facebook', 'Daily Performance', 'medium', '/admin/executive-performance', $related, $payload),
            'employee_submission_approved' => $this->simpleEventRule($key, $title, 'Facebook', 'Employee Submissions', 'low', '/admin/employee-submissions', $related, $payload),
            'employee_confirmed' => $this->simpleEventRule($key, $title, 'HR', 'Employees', 'low', '/admin/employees', $related, $payload),
            'employee_terminated' => $this->simpleEventRule($key, $title, 'HR', 'Employees', 'high', '/admin/employees', $related, $payload),
            default => null,
        };
    }

    private function simpleEventRule(string $key, string $title, string $department, string $module, string $priority, string $url, ?Model $related, array $payload): array
    {
        $recordId = $related?->getKey() ?: ($payload['id'] ?? md5(json_encode($payload)));

        return [
            'rule_key' => $key,
            'task_key' => 'event:' . $key . ':' . $recordId,
            'title' => $payload['title'] ?? $title,
            'priority' => $payload['priority'] ?? $priority,
            'department' => $payload['department'] ?? $department,
            'related_module' => $module,
            'related_record_type' => $this->relatedClass($related),
            'related_record_id' => $related?->getKey(),
            'due_date' => $payload['due_date'] ?? today()->toDateString(),
            'action_url' => $payload['action_url'] ?? $url,
            'target_team' => $payload['target_team'] ?? $department,
        ];
    }

    private function visibleTasks(?User $user)
    {
        $query = AutomationTask::query();

        if (! $user || $user->isSuperAdmin() || $user->hasRole('agency_owner') || $user->hasPermission('system_tools.manage')) {
            return $query;
        }

        if ($user->role === 'employee') {
            return $query->where('assigned_user_id', $user->id);
        }

        $role = strtolower($user->primaryRoleName());

        return $query->where(function ($builder) use ($role, $user) {
            $builder->where('assigned_user_id', $user->id)
                ->orWhere(function ($departmentQuery) use ($role) {
                    if (str_contains($role, 'finance')) {
                        $departmentQuery->where('department', 'Finance');
                    } elseif (str_contains($role, 'hr')) {
                        $departmentQuery->whereIn('department', ['HR', 'Employee']);
                    } elseif (str_contains($role, 'manager')) {
                        $departmentQuery->whereNotNull('department');
                    } else {
                        $departmentQuery->whereRaw('1 = 0');
                    }
                });
        });
    }

    private function audit(?AutomationTask $task, string $ruleKey, ?string $eventName, string $result, string $description, array $payload, ?User $triggeredBy): void
    {
        AutomationAudit::create([
            'automation_task_id' => $task?->id,
            'triggered_by' => $triggeredBy?->id ?? auth()->id(),
            'rule_key' => $ruleKey,
            'event_name' => $eventName,
            'result' => $result,
            'description' => $description,
            'payload' => $payload,
        ]);
    }

    private function notificationKey(AutomationTask $task): string
    {
        return 'automation.task.' . $task->task_key;
    }

    private function notificationPriority(string $priority): string
    {
        return match ($priority) {
            'critical', 'high' => 'critical',
            'medium' => 'warning',
            default => 'information',
        };
    }

    private function relatedClass(?Model $model): ?string
    {
        return $model ? $model::class : null;
    }
}
