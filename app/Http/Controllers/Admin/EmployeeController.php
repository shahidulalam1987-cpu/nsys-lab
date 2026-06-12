<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientFundDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('user');

        if ($request->search) {
            $query->where(function ($inner) use ($request) {
                $inner->where('employee_id', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('mobile', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->employee_type) {
            $query->where('employee_type', $request->employee_type);
        }

        if ($request->department) {
            $query->where('department', $request->department);
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->salary_source) {
            $query->where('salary_source', $request->salary_source);
        }

        $employees = $query->latest()->get();
        $statusCounts = Employee::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $summary = [
            'total' => Employee::count(),
            'active' => (int) ($statusCounts['active'] ?? 0),
            'probation' => (int) ($statusCounts['probation'] ?? 0),
            'on_leave' => (int) ($statusCounts['on_leave'] ?? 0),
            'inactive' => (int) ($statusCounts['inactive'] ?? 0),
            'terminated' => (int) ($statusCounts['terminated'] ?? 0),
            'client_assigned' => Employee::where('employee_type', 'client_assigned')->count(),
            'agency_internal' => Employee::where('employee_type', 'agency_internal')->count(),
        ];

        return view('admin.employees.index', compact('employees', 'summary'));
    }

    public function create()
    {
        $users = User::where('role', 'employee')->orderBy('name')->get();
        $shifts = Shift::where('status', 'active')->orderBy('id')->get();

        return view('admin.employees.create', compact('users', 'shifts'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedEmployee($request);
        $employeeId = $this->nextEmployeeId();
        $data['employee_id'] = $employeeId;
        $data['salary_type'] = 'monthly';
        $data['status'] = $data['status'] ?? 'probation';
        $data['employee_type'] = $data['employee_type'] ?? 'client_assigned';
        $data['salary_source'] = $this->salarySourceForEmployeeType($data['employee_type'], $data['salary_source'] ?? null);
        $data['salary_day'] = $data['salary_day'] ?? $this->salaryDayFromConfirmation($data['confirmation_date'] ?? null);
        $this->storeEmployeeFiles($request, $data, $employeeId);

        $employee = Employee::create($data);

        app(ActivityLogger::class)->log('Employee', 'Employee Created', $employee->employee_id . ' - ' . $employee->name, $request);

        return redirect('/admin/employees')->with('success', 'Employee created successfully.');
    }

    public function show($id)
    {
        $employee = Employee::with([
            'user',
            'shift',
            'assignments.client',
            'assignments.page',
            'assignments.campaignRecord',
            'assignments.shift',
            'salaryDays.client',
            'payrolls.client',
        ])->findOrFail($id);
        $clients = Client::orderBy('company_name')->get();
        $clientPages = ClientPage::with('client')->orderBy('page_name')->get();
        $campaigns = Campaign::with(['client', 'page'])->orderBy('campaign_name')->get();
        $shifts = Shift::where('status', 'active')->orderBy('id')->get();
        $salarySummary = $this->salarySummary($employee);
        $salaryLedgerRows = $this->salaryLedgerRows($employee);
        $salaryLedgerSummary = [
            'total_generated' => $salaryLedgerRows->sum('generated_salary'),
            'total_paid' => $salaryLedgerRows->sum('paid_amount'),
            'current_due' => $salaryLedgerRows->sum('due_amount'),
            'last_payment_date' => $employee->payrolls
                ->filter(fn ($payroll) => (float) $payroll->paid_amount > 0)
                ->sortByDesc(fn ($payroll) => $payroll->payment_date ?: $payroll->paid_at ?: $payroll->created_at)
                ->first()?->payment_date,
        ];

        return view('admin.employees.show', compact('employee', 'clients', 'clientPages', 'campaigns', 'shifts', 'salarySummary', 'salaryLedgerRows', 'salaryLedgerSummary'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $users = User::where('role', 'employee')
            ->where(function ($query) use ($employee) {
                $query->whereDoesntHave('employee')
                    ->orWhere('id', $employee->user_id);
            })
            ->orderBy('name')
            ->get();
        $shifts = Shift::where('status', 'active')->orderBy('id')->get();

        return view('admin.employees.edit', compact('employee', 'users', 'shifts'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $data = $this->validatedEmployee($request);
        $data['salary_source'] = $this->salarySourceForEmployeeType($data['employee_type'] ?? $employee->employee_type, $data['salary_source'] ?? null);
        $data['salary_day'] = $data['salary_day'] ?? $this->salaryDayFromConfirmation($data['confirmation_date'] ?? null);
        $this->storeEmployeeFiles($request, $data, $employee->employee_id, $employee);
        $employee->update($data);

        app(ActivityLogger::class)->log('Employee', 'Employee Updated', $employee->employee_id . ' - ' . $employee->name, $request);

        return redirect('/admin/employees/' . $employee->id)->with('success', 'Employee updated successfully.');
    }

    public function confirm($id)
    {
        $employee = Employee::findOrFail($id);

        if (! $employee->isEligibleForConfirmation()) {
            return back()->with('success', 'Employee is not eligible for confirmation yet.');
        }

        $employee->update([
            'status' => 'active',
            'confirmation_date' => now()->toDateString(),
            'salary_day' => $employee->salary_day ?: (int) now()->format('j'),
        ]);

        return back()->with('success', 'Employee confirmed successfully.');
    }

    public function terminate(Employee $employee)
    {
        $employee->update([
            'status' => 'terminated',
            'last_working_date' => $employee->last_working_date ?: now()->toDateString(),
        ]);

        app(ActivityLogger::class)->log('Employee', 'Employee Terminated', $employee->employee_id . ' - ' . $employee->name, request());

        return redirect('/admin/employees/' . $employee->id)
            ->with('success', 'Employee terminated successfully.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->assignments()->exists()
            || $employee->salaryDays()->exists()
            || $employee->payrolls()->exists()) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee has history. Please terminate instead.');
        }

        $user = $employee->user;
        $description = $employee->employee_id . ' - ' . $employee->name;
        $employee->delete();

        if ($user && $user->role === 'employee') {
            $user->delete();
        }

        app(ActivityLogger::class)->log('Employee', 'Employee Deleted', $description, request());

        return redirect('/admin/employees')
            ->with('success', 'Employee deleted successfully.');
    }

    public function createLogin(Employee $employee)
    {
        if ($employee->user_id) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee already has a linked login.');
        }

        return view('admin.employees.create-login', compact('employee'));
    }

    public function storeLogin(Request $request, Employee $employee)
    {
        if ($employee->user_id) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee already has a linked login.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee->update([
            'user_id' => $user->id,
        ]);

        app(ActivityLogger::class)->log('Employee', 'Employee Login Created', $employee->employee_id . ' login created for ' . $user->email, $request);

        return redirect('/admin/employees/' . $employee->id)
            ->with('success', 'Employee login created and linked successfully.');
    }

    public function resetLoginPassword(Employee $employee)
    {
        if (! $employee->user_id) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee has no linked login. Please create login first.');
        }

        return view('admin.employees.reset-login-password', compact('employee'));
    }

    public function updateLoginPassword(Request $request, Employee $employee)
    {
        if (! $employee->user_id) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee has no linked login. Please create login first.');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $employee->user->update([
            'password' => Hash::make($data['password']),
        ]);

        app(ActivityLogger::class)->log('Employee', 'Password Reset', $employee->employee_id . ' login password reset.', $request);

        return redirect('/admin/employees/' . $employee->id)
            ->with('success', 'Employee login password reset successfully.');
    }

    public function salaryLedgerCsv(Employee $employee)
    {
        $rows = $this->salaryLedgerRows($employee->load('payrolls.client'));

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Month', 'Client', 'Working Days', 'Non Working Days', 'Generated Salary', 'Paid Amount', 'Due Amount', 'Status', 'Generated Date', 'Paid Date']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['month'],
                    $row['client'],
                    $row['working_days'],
                    $row['non_working_days'],
                    number_format($row['generated_salary'], 2, '.', ''),
                    number_format($row['paid_amount'], 2, '.', ''),
                    number_format($row['due_amount'], 2, '.', ''),
                    $row['status'],
                    $row['generated_date'],
                    $row['paid_date'],
                ]);
            }

            fclose($handle);
        }, 'employee-salary-ledger-' . $employee->id . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function salaryLedgerExcel(Employee $employee)
    {
        return response()->view('admin.employees.salary-ledger-excel', [
            'employee' => $employee,
            'rows' => $this->salaryLedgerRows($employee->load('payrolls.client')),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="employee-salary-ledger-' . $employee->id . '.xls"',
        ]);
    }

    private function validatedEmployee(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nid_number' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'nid_front_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'nid_back_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:5120'],
            'appointment_letter_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:5120'],
            'agreement_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:5120'],
            'employee_type' => ['nullable', 'in:' . implode(',', array_keys(Employee::EMPLOYEE_TYPES))],
            'department' => ['required', 'in:' . implode(',', array_values(array_unique(array_merge(Employee::DEPARTMENTS, Employee::AGENCY_DEPARTMENTS))))],
            'role' => ['required', 'in:' . implode(',', Employee::ROLES)],
            'salary_source' => ['nullable', 'in:' . implode(',', array_keys(Employee::SALARY_SOURCES))],
            'permission_group' => ['nullable', 'in:' . implode(',', array_keys(Employee::PERMISSION_GROUPS))],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'joining_date' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'last_working_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'salary_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'bkash_number' => ['nullable', 'string', 'max:50'],
            'nagad_number' => ['nullable', 'string', 'max:50'],
            'rocket_number' => ['nullable', 'string', 'max:50'],
            'preferred_payment_method' => ['nullable', 'in:bank,bkash,nagad,rocket,cash'],
            'mobile_banking_info' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
        ]);
    }

    private function storeEmployeeFiles(Request $request, array &$data, string $employeeId, ?Employee $employee = null): void
    {
        $fileFields = [
            'profile_photo',
            'nid_front_file',
            'nid_back_file',
            'cv_file',
            'appointment_letter_file',
            'agreement_file',
        ];

        foreach ($fileFields as $field) {
            if (! $request->hasFile($field)) {
                unset($data[$field]);
                continue;
            }

            if ($employee?->{$field}) {
                Storage::disk('public')->delete($employee->{$field});
            }

            $data[$field] = $request->file($field)->store('employees/' . $employeeId, 'public');
        }
    }

    private function nextEmployeeId(): string
    {
        $usedNumbers = Employee::where('employee_id', 'like', 'NSYS-EM-%')
            ->pluck('employee_id')
            ->map(function ($employeeId) {
                if (preg_match('/^NSYS-EM-(\d+)$/', $employeeId, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter()
            ->flip();

        $number = 1;
        while ($usedNumbers->has($number)) {
            $number++;
        }

        return 'NSYS-EM-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    private function salaryDayFromConfirmation(?string $confirmationDate): ?int
    {
        if (! $confirmationDate) {
            return null;
        }

        return (int) date('j', strtotime($confirmationDate));
    }

    private function salarySourceForEmployeeType(string $employeeType, ?string $selectedSource): string
    {
        return $employeeType === 'agency_internal' ? 'agency_payroll' : 'client_fund';
    }

    private function salarySummary(Employee $employee): array
    {
        $payrolls = $employee->payrolls;
        $assignedClient = $employee->assignments
            ->where('status', 'active')
            ->sortByDesc('assigned_from')
            ->first()
            ?->client;
        $lastPayment = $payrolls
            ->filter(fn ($payroll) => (float) $payroll->paid_amount > 0)
            ->sortByDesc(fn ($payroll) => $payroll->payment_date ?: $payroll->created_at)
            ->first();

        return [
            'working_days' => $employee->salaryDays->where('is_counted', true)->count(),
            'non_working_days' => $employee->salaryDays->where('is_counted', false)->count(),
            'total_payable_salary' => (float) $payrolls->sum('payable_salary'),
            'total_paid_salary' => (float) $payrolls->sum('paid_amount'),
            'current_salary_due' => $payrolls->sum(fn ($payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'last_salary_payment' => $lastPayment,
            'assigned_client' => $assignedClient,
            'client_fund_balance' => $assignedClient
                ? app(ClientFundDashboardService::class)->clientAvailableBalance($assignedClient->id)
                : null,
            'upcoming_salary_date' => $employee->nextSalaryDate(),
            'salary_status' => $employee->salaryStatusLabel(),
        ];
    }

    private function salaryLedgerRows(Employee $employee)
    {
        return $employee->payrolls
            ->sortByDesc(fn ($payroll) => $payroll->salary_month ?: $payroll->created_at)
            ->map(fn ($payroll) => [
                'month' => $payroll->salary_month?->format('Y-m') ?: '-',
                'client' => $payroll->client?->company_name ?: '-',
                'working_days' => $payroll->working_days ?? 0,
                'non_working_days' => $payroll->non_working_days ?? 0,
                'generated_salary' => (float) $payroll->payable_salary,
                'paid_amount' => (float) $payroll->paid_amount,
                'due_amount' => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0),
                'status' => $payroll->payrollStatusLabel() . ' / ' . ([
                    'upcoming' => 'Upcoming',
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partially Paid',
                    'paid' => 'Paid',
                ][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status)),
                'generated_date' => $payroll->created_at?->toDateString() ?: '-',
                'paid_date' => $payroll->payment_date?->toDateString() ?: $payroll->paid_at?->toDateString() ?: '-',
                'payroll' => $payroll,
            ])
            ->values();
    }
}
