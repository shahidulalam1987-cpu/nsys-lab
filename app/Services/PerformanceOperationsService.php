<?php

namespace App\Services;

use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeeTarget;
use App\Models\PerformanceVerification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PerformanceOperationsService
{
    public function verificationGroups(array $filters = []): Collection
    {
        $submissions = EmployeeDailySubmission::with(['employee.roleRecord', 'employee.departmentRecord', 'client', 'page', 'campaign'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('submission_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('submission_date', '<=', $date))
            ->get();

        return $submissions->groupBy(fn (EmployeeDailySubmission $submission) => $this->groupKey($submission))
            ->map(function (Collection $items, string $key) {
                $order = $items->where('submission_type', 'order')->sortByDesc('id')->first();
                $spend = $items->where('submission_type', 'spend')->sortByDesc('id')->first();
                $sample = $order ?: $spend;
                $verification = PerformanceVerification::where('group_key', $key)->first();
                $orders = (int) ($order?->orders ?? 0);
                $usd = (float) ($spend?->dollar_spend ?? 0);
                $clientRate = (float) ($sample?->client?->client_rate ?? 0);
                $buyRate = (float) ($sample?->client?->buy_rate ?? 0);
                $revenue = round($usd * $clientRate, 2);
                $cost = round($usd * $buyRate, 2);
                $profit = round($revenue - $cost, 2);

                return [
                    'key' => $key,
                    'date' => $sample->submission_date,
                    'client' => $sample->client,
                    'page' => $sample->page,
                    'campaign' => $sample->campaign,
                    'order' => $order,
                    'spend' => $spend,
                    'status' => $this->groupStatus($order, $spend, $verification),
                    'admin_note' => $verification?->admin_note,
                    'calculation' => [
                        'spend' => $usd,
                        'orders' => $orders,
                        'cost_per_order' => DailyPerformanceReport::costPer($usd, $orders),
                        'bdt_spend' => $revenue,
                        'client_rate' => $clientRate,
                        'buy_rate' => $buyRate,
                        'profit' => $profit,
                        'profit_margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
                    ],
                ];
            })
            ->sortByDesc('date')
            ->values();
    }

    public function kpiRows(Carbon $from, Carbon $to, array $filters = []): Collection
    {
        return Employee::with(['departmentRecord', 'roleRecord', 'dailySubmissions' => function ($query) use ($from, $to) {
            $query->whereDate('submission_date', '>=', $from->toDateString())
                ->whereDate('submission_date', '<=', $to->toDateString());
        }])
            ->when($filters['employee_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filters['department_id'] ?? null, fn ($query, $id) => $query->where('department_id', $id))
            ->when($filters['role_id'] ?? null, fn ($query, $id) => $query->where('role_id', $id))
            ->get()
            ->map(fn (Employee $employee) => $this->employeeKpi($employee, $from, $to, $filters))
            ->filter(fn (array $row) => $row['total_submissions'] > 0)
            ->values();
    }

    public function employeeKpi(Employee $employee, Carbon $from, Carbon $to, array $filters = []): array
    {
        $submissions = $employee->relationLoaded('dailySubmissions')
            ? $employee->dailySubmissions
            : $employee->dailySubmissions()
                ->whereDate('submission_date', '>=', $from->toDateString())
                ->whereDate('submission_date', '<=', $to->toDateString())
                ->get();
        $submissions = $submissions
            ->when($filters['client_id'] ?? null, fn ($rows, $id) => $rows->where('client_id', (int) $id))
            ->when($filters['page_id'] ?? null, fn ($rows, $id) => $rows->where('page_id', (int) $id));
        $orders = $submissions->where('submission_type', 'order');
        $spend = $submissions->where('submission_type', 'spend');
        $approved = $submissions->whereIn('status', ['approved', 'merged']);
        $approvedOrders = $orders->whereIn('status', ['approved', 'merged']);
        $approvedSpend = $spend->whereIn('status', ['approved', 'merged']);
        $matchedOrderCount = (int) $approvedSpend->sum(function (EmployeeDailySubmission $spendSubmission) {
            return (int) EmployeeDailySubmission::where('submission_type', 'order')
                ->whereIn('status', ['approved', 'merged'])
                ->whereDate('submission_date', $spendSubmission->submission_date)
                ->where('client_id', $spendSubmission->client_id)
                ->where('page_id', $spendSubmission->page_id)
                ->where('campaign_id', $spendSubmission->campaign_id)
                ->value('orders');
        });
        $activeDays = $submissions->pluck('submission_date')->map->toDateString()->unique()->count();
        $total = $submissions->count();
        $periodType = $from->isSameDay($to) ? 'daily' : ($from->diffInDays($to) <= 7 ? 'weekly' : 'monthly');
        $target = $this->targetFor($employee, $orders->isNotEmpty() ? 'orders' : 'spend', $to, $periodType);
        $targetMetric = $orders->isNotEmpty() ? (float) $approvedOrders->sum('confirmed_orders') : (float) $approvedSpend->sum('dollar_spend');

        return [
            'employee' => $employee,
            'total_submissions' => $total,
            'total_orders' => (int) $orders->sum('orders'),
            'confirmed_orders' => (int) $orders->sum('confirmed_orders'),
            'cancelled_orders' => (int) $orders->sum('cancelled_orders'),
            'approved_orders' => $approvedOrders->count(),
            'rejected_orders' => $orders->where('status', 'rejected')->count(),
            'average_orders' => $activeDays ? round((float) $orders->sum('orders') / $activeDays, 2) : 0,
            'approved_spend' => (float) $approvedSpend->sum('dollar_spend'),
            'rejected_spend' => $spend->where('status', 'rejected')->count(),
            'average_spend' => $activeDays ? round((float) $spend->sum('dollar_spend') / $activeDays, 2) : 0,
            'average_cpo' => DailyPerformanceReport::costPer((float) $approvedSpend->sum('dollar_spend'), $matchedOrderCount),
            'average_cpm' => round((float) $approvedSpend->avg('cpm'), 2),
            'average_cpc' => round((float) $approvedSpend->avg('cpc'), 2),
            'average_ctr' => round((float) $approvedSpend->avg('ctr'), 2),
            'pages_handled' => $submissions->pluck('page_id')->filter()->unique()->count(),
            'approval_rate' => $total ? round(($approved->count() / $total) * 100, 2) : 0,
            'rejection_rate' => $total ? round(($submissions->where('status', 'rejected')->count() / $total) * 100, 2) : 0,
            'active_days' => $activeDays,
            'missing_days' => max($from->diffInDays($to) + 1 - $activeDays, 0),
            'issues_count' => $submissions->filter(fn ($item) => $item->status === 'rejected' || $item->admin_note)->count(),
            'profit_contribution' => round((float) $approvedSpend->sum(fn ($item) => (float) $item->dollar_spend * ((float) $item->client?->client_rate - (float) $item->client?->buy_rate)), 2),
            'target' => $target,
            'target_achievement' => $target && (float) $target->target_value > 0 ? round(($targetMetric / (float) $target->target_value) * 100, 2) : 0,
            'consistency' => $from->diffInDays($to) + 1 > 0 ? round(($activeDays / ($from->diffInDays($to) + 1)) * 100, 2) : 0,
        ];
    }

    public function targetFor(Employee $employee, string $type, Carbon $date, ?string $periodType = null): ?EmployeeTarget
    {
        return EmployeeTarget::where('target_type', $type)
            ->where('status', 'active')
            ->when($periodType, fn ($query) => $query->where('period_type', $periodType))
            ->whereDate('start_date', '<=', $date)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->where(function ($query) use ($employee) {
                $query->where('employee_id', $employee->id)
                    ->orWhere('role_id', $employee->role_id)
                    ->orWhere('department_id', $employee->department_id);
            })
            ->orderByRaw('CASE WHEN employee_id IS NOT NULL THEN 1 WHEN role_id IS NOT NULL THEN 2 ELSE 3 END')
            ->first();
    }

    public function groupKey(EmployeeDailySubmission $submission): string
    {
        return implode(':', [$submission->submission_date?->toDateString(), $submission->client_id ?: 0, $submission->page_id ?: 0, $submission->campaign_id ?: 0]);
    }

    private function groupStatus(?EmployeeDailySubmission $order, ?EmployeeDailySubmission $spend, ?PerformanceVerification $verification): string
    {
        if ($verification?->status === 'mismatch') {
            return 'mismatch';
        }
        if (($order?->status === 'merged') && ($spend?->status === 'merged')) {
            return 'merged';
        }
        if ($order?->status === 'rejected' || $spend?->status === 'rejected') {
            return 'rejected';
        }
        if ($order?->status === 'approved' && $spend?->status === 'approved') {
            return 'ready_to_merge';
        }
        if ($order && ! $spend) {
            return $order->status === 'pending' ? 'pending_order' : 'partial_order';
        }
        if ($spend && ! $order) {
            return $spend->status === 'pending' ? 'pending_spend' : 'partial_spend';
        }

        return 'partial';
    }
}
