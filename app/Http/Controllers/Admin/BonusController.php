<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BonusRule;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBonusEarning;
use App\Models\EmployeeRole;
use App\Services\PerformanceOperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BonusController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'rule_status' => ['nullable', Rule::in(['active', 'inactive'])],
            'earning_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'period_type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);
        $summary = [
            'active_rules' => BonusRule::where('status', 'active')->count(),
            'pending_earnings' => EmployeeBonusEarning::where('status', 'pending')->count(),
            'approved_earnings' => EmployeeBonusEarning::where('status', 'approved')->count(),
            'pending_bonus' => (float) EmployeeBonusEarning::where('status', 'pending')->sum('bonus_amount'),
        ];
        $rules = BonusRule::query()
            ->when($filters['rule_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['period_type'] ?? null, fn ($query, $period) => $query->where('period_type', $period))
            ->latest()
            ->get();
        $earnings = EmployeeBonusEarning::with(['employee', 'rule'])
            ->when($filters['earning_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['period_type'] ?? null, fn ($query, $period) => $query->whereHas('rule', fn ($ruleQuery) => $ruleQuery->where('period_type', $period)))
            ->latest()
            ->get();

        return view('admin.bonuses.index', [
            'filters' => $filters, 'summary' => $summary, 'rules' => $rules, 'earnings' => $earnings,
            'employees' => Employee::orderBy('name')->get(), 'departments' => Department::ordered()->get(), 'roles' => EmployeeRole::ordered()->get(),
        ]);
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'], 'applies_to_type' => ['required', Rule::in(['department', 'role', 'employee'])],
            'employee_id' => ['nullable', 'exists:employees,id'], 'department_id' => ['nullable', 'exists:departments,id'], 'role_id' => ['nullable', 'exists:employee_roles,id'],
            'metric' => ['required', Rule::in(['confirmed_orders', 'approved_spend', 'approval_rate', 'average_cpo', 'consistency'])],
            'comparison' => ['required', Rule::in(['gte', 'lte'])], 'threshold' => ['required', 'numeric', 'min:0'],
            'bonus_amount' => ['required', 'numeric', 'min:0'], 'bonus_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])], 'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        $scopeField = $data['applies_to_type'].'_id';
        if (empty($data[$scopeField])) {
            throw ValidationException::withMessages([$scopeField => 'Select the scope this bonus rule applies to.']);
        }
        $data['created_by'] = auth()->id();
        BonusRule::create($data);

        return back()->with('success', 'Bonus rule saved.');
    }

    public function evaluate(BonusRule $rule, PerformanceOperationsService $service)
    {
        abort_unless($rule->status === 'active', 422);
        [$from, $to] = match ($rule->period_type) {
            'daily' => [today(), today()], 'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
        $employees = Employee::with(['departmentRecord', 'roleRecord'])->get()->filter(fn ($employee) => match ($rule->applies_to_type) {
            'employee' => (int) $employee->id === (int) $rule->employee_id,
            'role' => (int) $employee->role_id === (int) $rule->role_id,
            'department' => (int) $employee->department_id === (int) $rule->department_id,
        });
        $created = 0;
        foreach ($employees as $employee) {
            $kpi = $service->employeeKpi($employee, $from, $to);
            $value = (float) ($kpi[$rule->metric] ?? 0);
            $achieved = $rule->comparison === 'lte' ? $value <= (float) $rule->threshold : $value >= (float) $rule->threshold;
            if (! $achieved) {
                continue;
            }
            $amount = $rule->bonus_type === 'percentage' ? round($value * (float) $rule->bonus_amount / 100, 2) : (float) $rule->bonus_amount;
            EmployeeBonusEarning::firstOrCreate([
                'employee_id' => $employee->id, 'bonus_rule_id' => $rule->id,
                'period_start' => $from->toDateString(), 'period_end' => $to->toDateString(),
            ], ['metric_value' => $value, 'bonus_amount' => $amount, 'status' => 'pending']);
            $created++;
        }

        return back()->with('success', $created.' bonus earning(s) evaluated. No payment was created.');
    }

    public function approve(Request $request, EmployeeBonusEarning $earning)
    {
        DB::transaction(function () use ($earning, $request) {
            $locked = EmployeeBonusEarning::whereKey($earning->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'Only pending bonus earnings can be approved.');
            $locked->update(['status' => 'approved', 'approved_by' => auth()->id(), 'note' => $request->input('note')]);
        });

        return back()->with('success', 'Bonus earning approved. It has not been paid.');
    }

    public function reject(Request $request, EmployeeBonusEarning $earning)
    {
        DB::transaction(function () use ($earning, $request) {
            $locked = EmployeeBonusEarning::whereKey($earning->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'Only pending bonus earnings can be rejected.');
            $locked->update(['status' => 'rejected', 'note' => $request->input('note')]);
        });

        return back()->with('success', 'Bonus earning rejected.');
    }

    public function export()
    {
        $rows = EmployeeBonusEarning::with(['employee', 'rule'])->get();

        return response()->streamDownload(function () use ($rows) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Employee', 'Rule', 'Period Start', 'Period End', 'Metric', 'Bonus BDT', 'Status']);
            foreach ($rows as $row) {
                fputcsv($h, [$row->employee?->name, $row->rule?->name, $row->period_start?->toDateString(), $row->period_end?->toDateString(), $row->metric_value, $row->bonus_amount, $row->status]);
            }
            fclose($h);
        }, 'bonus-earnings.csv', ['Content-Type' => 'text/csv']);
    }
}
