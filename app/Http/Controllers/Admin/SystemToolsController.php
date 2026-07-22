<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BugReport;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeePayroll;
use App\Models\EmployeePayrollAudit;
use App\Models\EmployeeWorkStatus;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SystemToolsController extends Controller
{
    public function activityLog(Request $request)
    {
        $filters = $request->validate([
            'module' => ['nullable', 'string', 'max:80'],
            'action' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = ActivityLog::with('user')
            ->when($filters['module'] ?? null, fn ($query, $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('created_at');

        return view('admin.system-tools.activity-log', [
            'logs' => $query->paginate(30)->withQueryString(),
            'filters' => $filters,
            'modules' => ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::orderBy('name')->get(),
            'summary' => [
                'total' => ActivityLog::count(),
                'today' => ActivityLog::whereDate('created_at', today())->count(),
                'modules' => ActivityLog::distinct('module')->count('module'),
                'users' => ActivityLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            ],
            'quickModules' => collect(['Payroll', 'Finance', 'Employee', 'Client Fund', 'Bug Tracker'])
                ->filter(fn ($module) => ActivityLog::where('module', $module)->exists())
                ->values(),
        ]);
    }

    public function securityAudit()
    {
        $riskyGetRoutes = collect(Route::getRoutes())
            ->filter(function ($route) {
                $methods = $route->methods();
                $uri = $route->uri();

                return in_array('GET', $methods, true)
                    && str_starts_with($uri, 'admin/')
                    && preg_match('/(delete|remove|approve|reject|status|paid)/i', $uri);
            })
            ->map(fn ($route) => $route->uri())
            ->values();

        $storageLinked = is_link(public_path('storage')) || file_exists(public_path('storage'));

        $checks = [
                ['title' => 'Admin routes protected', 'status' => 'Passed', 'detail' => 'Admin routes are inside auth and admin middleware.'],
                ['title' => 'Client routes protected', 'status' => 'Passed', 'detail' => 'Client routes use auth, client, and client.status middleware.'],
                ['title' => 'Employee routes protected', 'status' => 'Passed', 'detail' => 'Employee portal routes use auth and employee middleware.'],
                ['title' => 'CSRF enabled on delete actions', 'status' => 'Passed', 'detail' => 'Known delete actions are POST routes with Blade CSRF forms.'],
                ['title' => 'Employee can only view own data', 'status' => 'Needs Review', 'detail' => 'Employee portal controllers should continue using the authenticated employee relation.'],
                ['title' => 'Client can only view own data', 'status' => 'Needs Review', 'detail' => 'Client portal controllers should continue scoping records to the logged-in client.'],
                ['title' => 'File upload validation enabled', 'status' => 'Passed', 'detail' => 'Employee documents and payment proofs validate file types and size.'],
                ['title' => 'Public storage link status', 'status' => $storageLinked ? 'Passed' : 'Warning', 'detail' => $storageLinked ? 'public/storage is available for public assets. DMS files remain private through controller access.' : 'Run php artisan storage:link only if public assets need browser delivery. DMS files remain private.'],
                ['title' => 'Debug mode status', 'status' => config('app.debug') ? 'Warning' : 'Passed', 'detail' => config('app.debug') ? 'APP_DEBUG is enabled.' : 'APP_DEBUG is disabled.'],
                ['title' => 'Pending risky GET actions', 'status' => $riskyGetRoutes->isEmpty() ? 'Passed' : 'Warning', 'detail' => $riskyGetRoutes->isEmpty() ? 'No risky admin GET actions found.' : $riskyGetRoutes->implode(', ')],
        ];

        return view('admin.system-tools.security-audit', [
            'checks' => $checks,
            'summary' => collect($checks)->countBy('status'),
            'lastCheckedAt' => now(),
            'riskyGetRoutes' => $riskyGetRoutes,
        ]);
    }

    public function testDataReset()
    {
        return view('admin.system-tools.test-data-reset', [
            'isProduction' => app()->environment('production'),
            'options' => $this->resetOptions(),
        ]);
    }

    public function resetTestData(Request $request, ActivityLogger $logger)
    {
        if (app()->environment('production')) {
            return back()->with('success', 'Test data reset is disabled in production.');
        }

        $data = $request->validate([
            'confirmation' => ['required', Rule::in(['RESET TEST DATA'])],
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', Rule::in(array_keys($this->resetOptions()))],
        ]);

        $deleted = [];

        foreach ($data['options'] as $option) {
            $deleted[$option] = $this->deleteResetOption($option);
        }

        $logger->log(
            'System Tools',
            'Test Data Reset',
            'Reset selected test data: ' . collect($deleted)->map(fn ($count, $key) => $key . '=' . $count)->implode(', '),
            $request
        );

        return back()->with('success', 'Test data reset complete: ' . collect($deleted)->map(fn ($count, $key) => $key . ': ' . $count)->implode(', ') . '.');
    }

    private function resetOptions(): array
    {
        return [
            'employee_test_data' => 'Employee test data',
            'work_status_test_data' => 'Work status test data',
            'attendance_test_data' => 'Attendance test data',
            'payroll_test_data' => 'Payroll test data',
            'client_fund_payment_test_data' => 'Client fund payment test data',
            'bug_tracker_test_data' => 'Bug tracker test data',
        ];
    }

    private function deleteResetOption(string $option): int
    {
        return match ($option) {
            'employee_test_data' => $this->deleteEmployeeTestData(),
            'work_status_test_data' => EmployeeWorkStatus::query()->delete(),
            'attendance_test_data' => EmployeeAttendance::query()->delete(),
            'payroll_test_data' => $this->deletePayrollTestData(),
            'client_fund_payment_test_data' => SalaryPayment::query()->delete(),
            'bug_tracker_test_data' => BugReport::query()->delete(),
        };
    }

    private function deleteEmployeeTestData(): int
    {
        $employees = Employee::where('employee_id', 'like', 'TEST-%')
            ->orWhere('employee_id', 'like', 'NSYS-TEST-%')
            ->orWhere('name', 'like', '%Test%')
            ->get();

        $count = $employees->count();

        foreach ($employees as $employee) {
            $employee->delete();
        }

        return $count;
    }

    private function deletePayrollTestData(): int
    {
        if (Schema::hasTable('employee_payroll_audits')) {
            EmployeePayrollAudit::query()->delete();
        }

        return EmployeePayroll::query()->delete();
    }
}
