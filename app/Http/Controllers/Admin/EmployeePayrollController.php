<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\EmployeeWorkStatus;
use App\Models\FinanceAccount;
use App\Services\ActivityLogger;
use App\Services\ClientFundDashboardService;
use App\Services\PayrollCategoryService;
use App\Services\PayrollEstimateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeePayrollController extends Controller
{
    public function __construct(
        private PayrollEstimateService $payrollEstimator,
        private PayrollCategoryService $payrollCategory
    )
    {
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->filteredPayrollData($filters);

        return view('admin.payroll.index', [
            'filters' => $filters,
            'payrolls' => $data['payrolls'],
            'employees' => $data['employees'],
            'financeAccounts' => FinanceAccount::where('status', 'active')->orderBy('account_name')->get(),
            'cycleEmployees' => $data['cycleEmployees'],
            'summary' => $data['summary'],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $rows = $this->payrollExportRows($filters);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Client', 'Salary Source', 'Salary Period', 'Salary Date', 'Working Days', 'Payable Salary', 'Paid Salary', 'Remaining Due', 'Status', 'Payment Date', 'Method', 'Reference']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['employee'],
                    $row['client'],
                    $row['salary_source'],
                    $row['salary_period'],
                    $row['salary_date'],
                    $row['working_days'],
                    number_format($row['payable_salary'], 2, '.', ''),
                    number_format($row['paid_salary'], 2, '.', ''),
                    number_format($row['remaining_due'], 2, '.', ''),
                    $row['status'],
                    $row['payment_date'],
                    $row['method'],
                    $row['reference'],
                ]);
            }

            fclose($handle);
        }, 'salary-generate-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->validatedFilters($request);

        return response()->view('admin.payroll.export-excel', [
            'rows' => $this->payrollExportRows($filters),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="salary-generate-report.xls"',
        ]);
    }

    public function create(ClientFundDashboardService $clientFundDashboardService)
    {
        $employees = Employee::orderBy('name')->get();
        $clients = Client::orderBy('company_name')->get();
        $clientBalances = $clientFundDashboardService->clientBalanceMap();
        $employeePaymentInfo = $this->employeePaymentInfo($employees);
        $workStatusRecords = EmployeeWorkStatus::orderBy('work_date')
            ->get()
            ->map(fn (EmployeeWorkStatus $workStatus) => [
                'employee_id' => $workStatus->employee_id,
                'client_id' => $workStatus->client_id,
                'date' => $workStatus->work_date?->toDateString(),
                'status' => $workStatus->status,
                'status_label' => $workStatus->statusLabel(),
                'salary_count_value' => (float) $workStatus->salary_count_value,
                'note' => $workStatus->note,
            ])
            ->values();
        $workStatusPreviewRows = null;
        $workStatusFilters = [
            'salary_month' => now()->format('Y-m'),
            'employee_id' => null,
            'client_id' => null,
        ];

        return view('admin.payroll.create', compact('employees', 'clients', 'clientBalances', 'employeePaymentInfo', 'workStatusRecords', 'workStatusPreviewRows', 'workStatusFilters'));
    }

    public function store(Request $request)
    {
        if ($request->input('generation_mode') === 'work_status') {
            return $this->storeFromWorkStatus($request);
        }

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'calculation_type' => ['required', Rule::in(['date_to_date', 'monthly_cycle'])],
            'salary_month' => ['nullable', 'required_if:calculation_type,monthly_cycle', 'date_format:Y-m'],
            'from_date' => ['nullable', 'required_if:calculation_type,date_to_date', 'date'],
            'to_date' => ['nullable', 'required_if:calculation_type,date_to_date', 'date', 'after_or_equal:from_date'],
            'use_work_status_records' => ['nullable', 'boolean'],
            'working_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'non_working_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'salary_day_adjustments' => ['nullable', 'array'],
            'salary_day_adjustments.*.date' => ['required_with:salary_day_adjustments', 'date'],
            'salary_day_adjustments.*.day_type' => ['required_with:salary_day_adjustments', Rule::in(['working', 'non_working'])],
            'salary_day_adjustments.*.salary_count_value' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'salary_day_adjustments.*.reason' => ['required_with:salary_day_adjustments', Rule::in([
                'active_working',
                'absent',
                'client_issue',
                'boosting_off',
                'business_closed',
                'agency_hold',
                'on_leave',
                'sick_leave',
                'holiday',
                'agency_closed',
                'other',
            ])],
            'salary_day_adjustments.*.note' => ['nullable', 'string', 'max:500'],
            'payment_status' => ['nullable', Rule::in(['upcoming', 'unpaid', 'partial', 'paid'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'image', 'max:4096'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'confirm_regenerate' => ['nullable', 'boolean'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        if (! $employee->isAgencyInternal() && empty($data['client_id'])) {
            return back()->withInput()->withErrors(['client_id' => 'Client is required for Client Assigned employees.']);
        }

        $calculation = $this->calculatePayroll($employee, $data);
        $client = isset($data['client_id']) ? Client::find($data['client_id']) : null;
        if ((float) $calculation['payable_salary'] <= 0
            && ! $this->payrollEstimator->hasWorkStatusRecordsForPeriod(
                $employee,
                $calculation['from_date'],
                $calculation['to_date'],
                $client
            )) {
            return back()
                ->withInput()
                ->withErrors(['work_status' => 'Work Status records are required before salary generation.']);
        }
        $existingPayroll = $this->existingPayrollForPeriod(
            (int) $data['employee_id'],
            isset($data['client_id']) ? (int) $data['client_id'] : null,
            $calculation['salary_month']
        );

        if ($existingPayroll && empty($data['confirm_regenerate'])) {
            return view('admin.payroll.duplicate-warning', [
                'existingPayroll' => $existingPayroll,
                'requestData' => collect($request->except(['_token', 'payment_proof']))
                    ->put('confirm_regenerate', 1)
                    ->all(),
            ]);
        }

        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $paymentStatus = EmployeePayroll::paymentStatusFor(null, $calculation['payable_salary'], $paidAmount);
        $this->validatePaymentWorkflow($request, $paidAmount);
        $paymentSnapshot = $this->employeePaymentSnapshot($employee);

        $payroll = EmployeePayroll::create(array_merge([
            'employee_id' => $data['employee_id'],
            'client_id' => $data['client_id'] ?? null,
            'salary_source' => $employee->defaultSalarySource(),
            'calculation_type' => $data['calculation_type'],
            'salary_period_from' => $calculation['from_date']->toDateString(),
            'salary_period_to' => $calculation['to_date']->toDateString(),
            'from_date' => $calculation['from_date']->toDateString(),
            'to_date' => $calculation['to_date']->toDateString(),
            'working_days' => $calculation['working_days'],
            'non_working_days' => $calculation['non_working_days'],
            'month_days' => $calculation['month_days'],
            'daily_salary' => $calculation['daily_salary'],
            'salary_day_adjustments' => $calculation['salary_day_adjustments'],
            'salary_month' => $calculation['salary_month']->toDateString(),
            'payable_salary' => $calculation['payable_salary'],
            'payroll_employee_name' => $employee->name,
            'payroll_employee_code' => $employee->employee_id,
            'payroll_salary_amount' => $calculation['payable_salary'],
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_status' => $paymentStatus,
            'payment_proof' => $request->file('payment_proof')?->store('employee-payroll-proofs', 'public'),
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => EmployeePayroll::statusFor($calculation['payable_salary'], $paidAmount),
            'payroll_status' => 'generated',
            'generation_status' => $existingPayroll ? 'regenerated' : 'generated',
            'is_current' => true,
            'regenerated_from_id' => $existingPayroll?->id,
            'note' => $data['note'] ?? null,
        ], $paymentSnapshot));

        if ($existingPayroll) {
            $existingPayroll->update([
                'is_current' => false,
                'superseded_by_id' => $payroll->id,
            ]);
        }

        $payroll->markAudit(
            $existingPayroll ? 'salary_regenerated' : 'salary_generated',
            auth()->id(),
            $existingPayroll ? 'Regenerated from salary #' . $existingPayroll->id : null
        );

        app(ActivityLogger::class)->log(
            'Payroll',
            $existingPayroll ? 'Salary Regenerated' : 'Salary Generated',
            'Salary #' . $payroll->id . ' generated for ' . ($payroll->employee?->name ?? 'employee') . '.',
            $request
        );

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Employee payroll saved successfully.');
    }

    private function storeFromWorkStatus(Request $request)
    {
        $action = $request->input('work_status_action', 'preview');

        if ($action === 'generate') {
            $data = $request->validate([
                'salary_month' => ['required', 'date_format:Y-m'],
                'rows' => ['nullable', 'array'],
                'rows.*.employee_id' => ['required', 'exists:employees,id'],
                'rows.*.client_id' => ['nullable', 'exists:clients,id'],
                'rows.*.action' => ['required', Rule::in(['skip', 'generate', 'regenerate'])],
            ]);

            $created = 0;
            $regenerated = 0;
            $skipped = collect($data['rows'] ?? [])->where('action', 'skip')->count();
            $blockedMissingWorkStatus = 0;
            $salaryMonth = Carbon::createFromFormat('Y-m', $data['salary_month'])->startOfMonth();
            $selectedRows = collect($data['rows'] ?? [])
                ->filter(fn (array $row) => in_array($row['action'], ['generate', 'regenerate'], true));

            foreach ($selectedRows as $row) {
                $existingPayroll = $this->existingPayrollForPeriod((int) $row['employee_id'], isset($row['client_id']) ? (int) $row['client_id'] : null, $salaryMonth);

                if ($existingPayroll && $row['action'] !== 'regenerate') {
                    $skipped++;
                    continue;
                }

                $previewRow = collect($this->workStatusPreviewRows([
                    'salary_month' => $data['salary_month'],
                    'employee_id' => $row['employee_id'],
                    'client_id' => $row['client_id'],
                ]))->first();

                if (! $previewRow || $previewRow['working_count'] <= 0) {
                    $blockedMissingWorkStatus++;
                    $skipped++;
                    continue;
                }

                $payroll = EmployeePayroll::create(array_merge([
                    'employee_id' => $previewRow['employee']->id,
                    'client_id' => $previewRow['client']?->id,
                    'salary_source' => $previewRow['employee']->defaultSalarySource(),
                    'calculation_type' => 'monthly_cycle',
                    'salary_period_from' => $salaryMonth->toDateString(),
                    'salary_period_to' => $salaryMonth->copy()->endOfMonth()->toDateString(),
                    'from_date' => $salaryMonth->toDateString(),
                    'to_date' => $salaryMonth->copy()->endOfMonth()->toDateString(),
                    'working_days' => $previewRow['working_count'],
                    'non_working_days' => $previewRow['non_working_count'],
                    'month_days' => EmployeePayroll::FIXED_SALARY_MONTH_DAYS,
                    'daily_salary' => $previewRow['daily_salary'],
                    'salary_day_adjustments' => $previewRow['adjustments'],
                    'salary_month' => $salaryMonth->toDateString(),
                    'payable_salary' => $previewRow['payable_salary'],
                    'payroll_employee_name' => $previewRow['employee']->name,
                    'payroll_employee_code' => $previewRow['employee']->employee_id,
                    'payroll_salary_amount' => $previewRow['payable_salary'],
                    'paid_amount' => 0,
                    'payment_status' => EmployeePayroll::paymentStatusFor(null, $previewRow['payable_salary'], 0),
                    'status' => EmployeePayroll::statusFor($previewRow['payable_salary'], 0),
                    'payroll_status' => 'generated',
                    'generation_status' => $existingPayroll ? 'regenerated' : 'generated',
                    'is_current' => true,
                    'regenerated_from_id' => $existingPayroll?->id,
                    'note' => 'Generated from Work Status records.',
                ], $this->employeePaymentSnapshot($previewRow['employee'])));

                if ($existingPayroll) {
                    $existingPayroll->update([
                        'is_current' => false,
                        'superseded_by_id' => $payroll->id,
                    ]);
                }

                $payroll->markAudit(
                    $existingPayroll ? 'salary_regenerated' : 'salary_generated',
                    auth()->id(),
                    'Generated from Work Status records.'
                );

                app(ActivityLogger::class)->log(
                    'Payroll',
                    $existingPayroll ? 'Salary Regenerated' : 'Salary Generated',
                    'Salary #' . $payroll->id . ' generated from Work Status records.',
                    $request
                );

                $existingPayroll ? $regenerated++ : $created++;
            }

            if ($selectedRows->isNotEmpty() && ($created + $regenerated) === 0 && $blockedMissingWorkStatus > 0) {
                return back()
                    ->withInput()
                    ->withErrors(['work_status' => 'Work Status records are required before salary generation.']);
            }

            return redirect('/admin/payroll')->with(
                'success',
                "Work Status salary generation complete. Created: {$created}, Regenerated: {$regenerated}, Skipped: {$skipped}."
            );
        }

        $filters = $request->validate([
            'salary_month' => ['required', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);

        $employees = Employee::orderBy('name')->get();
        $clients = Client::orderBy('company_name')->get();
        $clientBalances = app(ClientFundDashboardService::class)->clientBalanceMap();
        $employeePaymentInfo = $this->employeePaymentInfo($employees);
        $workStatusRecords = EmployeeWorkStatus::orderBy('work_date')
            ->get()
            ->map(fn (EmployeeWorkStatus $workStatus) => [
                'employee_id' => $workStatus->employee_id,
                'client_id' => $workStatus->client_id,
                'date' => $workStatus->work_date?->toDateString(),
                'status' => $workStatus->status,
                'status_label' => $workStatus->statusLabel(),
                'salary_count_value' => (float) $workStatus->salary_count_value,
                'note' => $workStatus->note,
            ])
            ->values();
        $workStatusPreviewRows = $this->workStatusPreviewRows($filters);
        $workStatusFilters = $filters;

        return view('admin.payroll.create', compact('employees', 'clients', 'clientBalances', 'employeePaymentInfo', 'workStatusRecords', 'workStatusPreviewRows', 'workStatusFilters'));
    }

    public function show($id)
    {
        $payroll = EmployeePayroll::with(['employee', 'client', 'audits.user', 'approver', 'payer', 'financeAccount', 'financeLedgers.account', 'financeLedgers.creator'])->findOrFail($id);
        $workStatusSummary = $this->workStatusSummary($payroll);
        $financeAccounts = FinanceAccount::where('status', 'active')->orderBy('account_name')->get();

        return view('admin.payroll.show', compact('payroll', 'workStatusSummary', 'financeAccounts'));
    }

    public function edit($id, ClientFundDashboardService $clientFundDashboardService)
    {
        $payroll = EmployeePayroll::with(['employee', 'client'])->findOrFail($id);
        $clientFundBalance = $payroll->client_id
            ? $clientFundDashboardService->clientAvailableBalance($payroll->client_id)
            : null;
        $clientFundNeed = max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0);

        return view('admin.payroll.edit', compact('payroll', 'clientFundBalance', 'clientFundNeed'));
    }

    public function update(Request $request, $id)
    {
        $payroll = EmployeePayroll::findOrFail($id);
        $data = $request->validate([
            'payment_status' => ['nullable', Rule::in(['upcoming', 'unpaid', 'partial', 'paid'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'image', 'max:4096'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $this->validatePaymentWorkflow($request, $paidAmount);

        $paymentProof = $payroll->payment_proof;

        if ($request->hasFile('payment_proof')) {
            if ($paymentProof) {
                Storage::disk('public')->delete($paymentProof);
            }

            $paymentProof = $request->file('payment_proof')->store('employee-payroll-proofs', 'public');
        }

        $payroll->update([
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_status' => EmployeePayroll::paymentStatusFor(null, (float) $payroll->payable_salary, $paidAmount),
            'payment_proof' => $paymentProof,
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => EmployeePayroll::statusFor((float) $payroll->payable_salary, $paidAmount),
            'note' => $data['note'] ?? null,
        ]);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Employee payroll updated successfully.');
    }

    public function approve(EmployeePayroll $payroll)
    {
        if (! $payroll->canApprove()) {
            return redirect('/admin/payroll/' . $payroll->id)
                ->with('success', 'This salary is already approved or paid.');
        }

        $payroll->update([
            'payroll_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $payroll->markAudit('salary_approved', auth()->id());

        app(ActivityLogger::class)->log('Payroll', 'Salary Approved', 'Salary #' . $payroll->id . ' approved.', request());

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Payroll approved successfully.');
    }

    public function markPaid(EmployeePayroll $payroll)
    {
        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Use Confirm Payment to record finance account, reference, and ledger details.');
    }

    public function confirmPayment(Request $request, EmployeePayroll $payroll)
    {
        $payroll->refresh();

        if ($this->salaryPaymentAlreadyConfirmed($payroll)) {
            app(ActivityLogger::class)->log('Payroll', 'Duplicate Payment Blocked', 'Duplicate payment confirmation blocked for salary #' . $payroll->id . '.', $request);

            return redirect('/admin/payroll/' . $payroll->id)
                ->with('success', 'This salary payment is already confirmed.');
        }

        if ($payroll->payroll_status !== 'approved') {
            return redirect('/admin/payroll/' . $payroll->id)
                ->with('success', 'Approve payroll before marking it paid.');
        }

        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'payment_note' => ['required', 'string', 'max:1000'],
            'salary_payment_attachment' => ['nullable', 'image', 'max:4096'],
        ]);

        $blockedMessage = null;

        DB::transaction(function () use ($payroll, $request, $data, &$blockedMessage) {
            $payroll = EmployeePayroll::whereKey($payroll->id)->lockForUpdate()->firstOrFail();

            if ($this->salaryPaymentAlreadyConfirmed($payroll)) {
                $blockedMessage = 'This salary payment is already confirmed.';
                app(ActivityLogger::class)->log('Payroll', 'Duplicate Payment Blocked', 'Duplicate payment confirmation blocked for salary #' . $payroll->id . '.', $request);

                return;
            }

            if ($payroll->payroll_status !== 'approved') {
                $blockedMessage = 'Approve payroll before marking it paid.';

                return;
            }

            $account = FinanceAccount::lockForUpdate()->findOrFail($data['finance_account_id']);
            $paidAmount = (float) $payroll->payable_salary;
            $previousBalance = (float) $account->current_balance;

            if ($previousBalance < $paidAmount) {
                $blockedMessage = 'Insufficient finance account balance.';

                return;
            }

            $newBalance = $previousBalance - $paidAmount;
            $attachment = $request->file('salary_payment_attachment')?->store('employee-salary-payments', 'public');

            $account->update(['current_balance' => $newBalance]);

            $payroll->update([
                'payroll_employee_name' => $payroll->employee?->name,
                'payroll_employee_code' => $payroll->employee?->employee_id,
                'payroll_salary_amount' => $paidAmount,
                'paid_amount' => $paidAmount,
                'payroll_status' => 'paid',
                'payment_status' => 'paid',
                'status' => 'paid',
                'payment_date' => $data['payment_date'],
                'payment_method' => $account->account_name,
                'finance_account_id' => $account->id,
                'finance_account_name' => $account->account_name,
                'transaction_id' => $data['transaction_id'],
                'payment_note' => $data['payment_note'],
                'salary_payment_attachment' => $attachment,
                'payment_confirmed_at' => now(),
                'paid_at' => now(),
                'paid_by' => auth()->id(),
                'reversed_at' => null,
                'reversed_by' => null,
                'reversal_note' => null,
            ]);

            $account->ledgers()->create([
                'employee_payroll_id' => $payroll->id,
                'ledger_date' => $data['payment_date'],
                'transaction_type' => 'salary_payment',
                'amount' => $paidAmount,
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'reference' => $data['transaction_id'],
                'note' => 'Salary Payment - ' . $payroll->snapshotEmployeeName(),
                'created_by' => auth()->id(),
            ]);

            $payroll->markAudit('salary_paid', auth()->id(), 'Paid from ' . $account->account_name . '.');
        });

        if ($blockedMessage) {
            return redirect('/admin/payroll/' . $payroll->id)
                ->with('success', $blockedMessage);
        }

        app(ActivityLogger::class)->log('Payroll', 'Salary Paid', 'Salary #' . $payroll->id . ' confirmed and deducted from finance account.', $request);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Salary payment confirmed and finance account balance updated.');
    }

    public function reversePayment(Request $request, EmployeePayroll $payroll)
    {
        $data = $request->validate([
            'reversal_note' => ['required', 'string', 'max:1000'],
        ]);

        $payroll->refresh();

        if ($payroll->payroll_status !== 'paid'
            || $payroll->payment_status !== 'paid'
            || ! $payroll->finance_account_id
            || $payroll->reversed_at
            || $payroll->financeLedgers()->where('transaction_type', 'salary_payment_reversal')->exists()) {
            return redirect('/admin/payroll/' . $payroll->id)
                ->with('success', 'This salary payment cannot be reversed.');
        }

        $blockedMessage = null;

        DB::transaction(function () use ($payroll, $data, &$blockedMessage) {
            $payroll = EmployeePayroll::whereKey($payroll->id)->lockForUpdate()->firstOrFail();

            if ($payroll->payroll_status !== 'paid'
                || $payroll->payment_status !== 'paid'
                || ! $payroll->finance_account_id
                || $payroll->reversed_at
                || $payroll->financeLedgers()->where('transaction_type', 'salary_payment_reversal')->exists()) {
                $blockedMessage = 'This salary payment cannot be reversed.';

                return;
            }

            $account = FinanceAccount::lockForUpdate()->findOrFail($payroll->finance_account_id);
            $amount = (float) $payroll->paid_amount;
            $previousBalance = (float) $account->current_balance;
            $newBalance = $previousBalance + $amount;

            $account->update(['current_balance' => $newBalance]);

            $account->ledgers()->create([
                'employee_payroll_id' => $payroll->id,
                'ledger_date' => now()->toDateString(),
                'transaction_type' => 'salary_payment_reversal',
                'amount' => $amount,
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'reference' => $payroll->transaction_id,
                'note' => $data['reversal_note'],
                'created_by' => auth()->id(),
            ]);

            $payroll->update([
                'payroll_status' => 'approved',
                'payment_status' => 'unpaid',
                'status' => 'unpaid',
                'paid_amount' => 0,
                'paid_at' => null,
                'paid_by' => null,
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
                'reversal_note' => $data['reversal_note'],
            ]);

            $payroll->markAudit('salary_reversed', auth()->id(), $data['reversal_note']);
        });

        if ($blockedMessage) {
            return redirect('/admin/payroll/' . $payroll->id)
                ->with('success', $blockedMessage);
        }

        app(ActivityLogger::class)->log('Payroll', 'Salary Reversed', 'Salary #' . $payroll->id . ' payment reversed.', $request);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Salary payment reversed and finance account balance restored.');
    }

    private function salaryPaymentAlreadyConfirmed(EmployeePayroll $payroll): bool
    {
        return $payroll->payroll_status === 'paid'
            || $payroll->payment_status === 'paid'
            || $payroll->paid_at !== null
            || $payroll->payment_date !== null
            || $payroll->financeLedgers()->where('transaction_type', 'salary_payment')->exists();
    }

    public function paymentReport(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'finance_account_id' => ['nullable', 'exists:finance_accounts,id'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return view('admin.payroll.payment-report', [
            'payrolls' => $this->paymentReportQuery($filters)->get(),
            'filters' => $filters,
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'financeAccounts' => FinanceAccount::orderBy('account_name')->get(),
        ]);
    }

    public function paymentReportCsv(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'finance_account_id' => ['nullable', 'exists:finance_accounts,id'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $rows = $this->paymentReportQuery($filters)->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Employee ID', 'Client', 'Month', 'Salary', 'Payment Date', 'Finance Account', 'Reference', 'Status']);
            foreach ($rows as $payroll) {
                fputcsv($handle, [
                    $payroll->snapshotEmployeeName(),
                    $payroll->snapshotEmployeeCode(),
                    $payroll->client?->company_name ?: '-',
                    $payroll->salary_month?->format('Y-m') ?: '-',
                    number_format($payroll->snapshotSalaryAmount(), 2, '.', ''),
                    $payroll->payment_date?->toDateString() ?: '-',
                    $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-'),
                    $payroll->transaction_id ?: '-',
                    $payroll->payrollStatusLabel(),
                ]);
            }
            fclose($handle);
        }, 'salary-payment-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function paymentReportExcel(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'finance_account_id' => ['nullable', 'exists:finance_accounts,id'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return response()->view('admin.payroll.payment-report-excel', [
            'payrolls' => $this->paymentReportQuery($filters)->get(),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="salary-payment-report.xls"',
        ]);
    }

    public function destroy(EmployeePayroll $payroll)
    {
        $description = 'Salary #' . $payroll->id . ' deleted.';
        $payroll->delete();

        app(ActivityLogger::class)->log('Payroll', 'Salary Deleted', $description, request());

        return redirect('/admin/payroll')
            ->with('success', 'Salary record deleted successfully.');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:upcoming,unpaid,partial,paid,due'],
            'salary_source' => ['nullable', 'in:' . implode(',', array_keys(Employee::SALARY_SOURCES))],
            'employee_scope' => ['nullable', 'in:all,active,terminated'],
        ]);
    }

    private function filteredPayrollData(array $filters): array
    {
        $query = EmployeePayroll::current()
            ->with(['employee', 'client', 'financeAccount'])
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereDate('salary_month', $month . '-01'))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['salary_source'] ?? null, fn ($query, $salarySource) => $query->where('salary_source', $salarySource));

        $payrolls = $query->latest('salary_month')
            ->latest()
            ->get()
            ->filter(fn (EmployeePayroll $payroll) => $this->payrollMatchesStatus($payroll, $filters['status'] ?? null))
            ->filter(function (EmployeePayroll $payroll) use ($filters) {
                if (($filters['status'] ?? null) !== 'due') {
                    return true;
                }

                return match ($filters['employee_scope'] ?? 'all') {
                    'active' => $payroll->employee?->status !== 'terminated',
                    'terminated' => $payroll->employee?->status === 'terminated',
                    default => true,
                };
            })
            ->values();

        $cycleEmployees = $this->cycleEmployeesForStatus($filters['status'] ?? null);
        if (($filters['status'] ?? null) === 'due') {
            $cycleEmployees = $cycleEmployees->filter(function (Employee $employee) use ($filters) {
                return match ($filters['employee_scope'] ?? 'all') {
                    'active' => $employee->status !== 'terminated',
                    'terminated' => $employee->status === 'terminated',
                    default => true,
                };
            })->values();
        }
        if (! empty($filters['salary_source'])) {
            $cycleEmployees = $cycleEmployees
                ->filter(fn (Employee $employee) => ($employee->salary_source ?: $employee->defaultSalarySource()) === $filters['salary_source'])
                ->values();
        }
        $cycleEmployees = $this->attachCycleEstimates($cycleEmployees, $filters['status'] ?? null);

        return [
            'payrolls' => $payrolls,
            'employees' => Employee::orderBy('name')->get(),
            'cycleEmployees' => $cycleEmployees,
            'summary' => [
                'total_payable' => $payrolls->sum('payable_salary') + $cycleEmployees->sum(fn (Employee $employee) => (float) data_get($employee->cycle_estimate, 'estimated_payable_salary', 0)),
                'total_paid' => $payrolls->sum('paid_amount'),
                'total_due' => $payrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0))
                    + $cycleEmployees->sum(fn (Employee $employee) => (float) data_get($employee->cycle_estimate, 'estimated_payable_salary', 0)),
                'record_count' => $payrolls->count() + $cycleEmployees->count(),
                'upcoming_count' => $payrolls->where('calculated_status', 'upcoming')->filter(fn (EmployeePayroll $payroll) => $payroll->employee?->status !== 'terminated')->count()
                    + (($filters['status'] ?? null) === 'upcoming' ? $cycleEmployees->where('status', '!=', 'terminated')->count() : 0),
                'overdue_count' => $payrolls->filter(fn (EmployeePayroll $payroll) => in_array($payroll->calculated_status, ['unpaid', 'partial'], true) && $payroll->employee?->status !== 'terminated')->count()
                    + (($filters['status'] ?? null) === 'due' ? $cycleEmployees->where('status', '!=', 'terminated')->count() : 0),
                'final_settlement_count' => $payrolls->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue())->count()
                    + (($filters['status'] ?? null) === 'due' ? $cycleEmployees->where('status', 'terminated')->count() : 0),
                'final_settlement_amount' => $payrolls->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue())
                    ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0))
                    + (($filters['status'] ?? null) === 'due'
                        ? $cycleEmployees->where('status', 'terminated')->sum(fn (Employee $employee) => (float) data_get($employee->cycle_estimate, 'estimated_payable_salary', 0))
                        : 0),
                'current_month_payable' => $payrolls
                    ->filter(fn (EmployeePayroll $payroll) => $payroll->salary_month?->isSameMonth(now()))
                    ->sum('payable_salary'),
                'current_month_paid' => $payrolls
                    ->filter(fn (EmployeePayroll $payroll) => $payroll->salary_month?->isSameMonth(now()))
                    ->sum('paid_amount'),
                'current_month_due' => $payrolls
                    ->filter(fn (EmployeePayroll $payroll) => $payroll->salary_month?->isSameMonth(now()))
                    ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            ],
        ];
    }

    private function paymentReportQuery(array $filters)
    {
        return EmployeePayroll::current()
            ->with(['employee', 'client', 'financeAccount'])
            ->where('payroll_status', 'paid')
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('payment_date', '<=', $date))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['finance_account_id'] ?? null, fn ($query, $accountId) => $query->where('finance_account_id', $accountId))
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereDate('salary_month', $month . '-01'))
            ->latest('payment_date')
            ->latest();
    }

    private function payrollMatchesStatus(EmployeePayroll $payroll, ?string $status): bool
    {
        if (! $status) {
            return true;
        }

        if (! $payroll->employee) {
            return $payroll->matchesStatusFilter($status);
        }

        $category = $this->payrollCategory->resolveEmployee(
            $payroll->employee,
            $payroll->salaryDueDate() ?: $payroll->salary_month
        )['category'] ?? null;

        return match ($status) {
            'paid' => in_array($category, [PayrollCategoryService::PAID, PayrollCategoryService::FINAL_SETTLEMENT_PAID], true),
            'due' => in_array($category, [PayrollCategoryService::UNPAID, PayrollCategoryService::FINAL_SETTLEMENT_UNPAID], true),
            'upcoming' => $payroll->matchesStatusFilter('upcoming') && $category !== PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            default => $payroll->matchesStatusFilter($status),
        };
    }

    private function existingPayrollForPeriod(int $employeeId, ?int $clientId, Carbon $salaryMonth): ?EmployeePayroll
    {
        return EmployeePayroll::current()
            ->with(['employee', 'client'])
            ->where('employee_id', $employeeId)
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId), fn ($query) => $query->whereNull('client_id'))
            ->whereDate('salary_month', $salaryMonth->copy()->startOfMonth()->toDateString())
            ->latest()
            ->first();
    }

    private function workStatusSummary(EmployeePayroll $payroll): array
    {
        $adjustments = collect($payroll->salary_day_adjustments ?? []);

        return [
            'working_days' => (float) $adjustments->sum(fn (array $adjustment) => (float) ($adjustment['salary_count_value'] ?? (($adjustment['day_type'] ?? 'working') === 'working' ? 1 : 0))),
            'half_days' => $adjustments->filter(fn (array $adjustment) => (float) ($adjustment['salary_count_value'] ?? 0) === 0.5)->count(),
            'leave' => $adjustments->whereIn('reason', ['on_leave', 'sick_leave'])->count(),
            'client_issue' => $adjustments->where('reason', 'client_issue')->count(),
            'boosting_off' => $adjustments->where('reason', 'boosting_off')->count(),
        ];
    }

    private function employeePaymentSnapshot(Employee $employee): array
    {
        return [
            'payroll_bank_name' => $employee->bank_name,
            'payroll_account_name' => $employee->account_name,
            'payroll_account_number' => $employee->account_number,
            'payroll_branch_name' => $employee->branch_name,
        ];
    }

    private function employeePaymentInfo($employees): array
    {
        return $employees
            ->mapWithKeys(fn (Employee $employee) => [
                $employee->id => [
                    'name' => $employee->name,
                    'employee_id' => $employee->employee_id,
                    'status' => $employee->statusLabel(),
                    'joining_date' => $employee->joining_date?->toDateString(),
                    'bank_name' => $employee->bank_name,
                    'account_name' => $employee->account_name,
                    'account_number' => $employee->account_number,
                    'branch_name' => $employee->branch_name,
                    'routing_number' => $employee->routing_number ?? null,
                ],
            ])
            ->all();
    }

    private function payrollExportRows(array $filters)
    {
        $data = $this->filteredPayrollData($filters);
        $statusLabels = [
            'upcoming' => 'Upcoming',
            'unpaid' => 'Unpaid',
            'partial' => 'Partially Paid',
            'paid' => 'Paid',
        ];

        $rows = $data['payrolls']->map(function (EmployeePayroll $payroll) use ($statusLabels) {
            return [
                'employee' => trim(($payroll->employee?->employee_id ?: '-') . ' ' . ($payroll->employee?->name ?: '')),
                'client' => $payroll->client?->company_name ?: '-',
                'salary_source' => $payroll->salarySourceLabel(),
                'salary_period' => $payroll->salary_period,
                'salary_date' => $payroll->employee?->salaryDateForMonth($payroll->salary_month?->copy() ?: now())?->toDateString() ?: '-',
                'working_days' => $payroll->working_days ?? 0,
                'payable_salary' => (float) $payroll->payable_salary,
                'paid_salary' => (float) $payroll->paid_amount,
                'remaining_due' => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0),
                'status' => $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status),
                'payment_date' => $payroll->payment_date?->toDateString() ?: '-',
                'method' => $payroll->payment_method ?: '-',
                'reference' => $payroll->transaction_id ?: '-',
            ];
        });

        return $rows
            ->concat($data['cycleEmployees']->map(function (Employee $employee) use ($filters) {
                $salaryDate = ($filters['status'] ?? null) === 'due'
                    ? $employee->currentSalaryDueDate()
                    : $employee->nextSalaryDate();
                $estimate = $employee->cycle_estimate ?? $this->payrollEstimator->estimateCycle(
                    $employee,
                    $salaryDate,
                    $this->estimateClientFor($employee)
                );

                return [
                    'employee' => trim(($employee->employee_id ?: '-') . ' ' . $employee->name),
                    'client' => $employee->activeAssignments->first()?->client?->company_name ?: '-',
                    'salary_source' => $employee->salarySourceLabel(),
                    'salary_period' => $salaryDate?->format('Y-m') ?: '-',
                    'salary_date' => $salaryDate?->toDateString() ?: '-',
                    'working_days' => (float) data_get($estimate, 'working_salary_count', 0),
                    'payable_salary' => (float) data_get($estimate, 'estimated_payable_salary', 0),
                    'paid_salary' => 0,
                    'remaining_due' => (float) data_get($estimate, 'estimated_payable_salary', 0),
                    'status' => data_get($estimate, 'estimate_status_label') ?: $employee->salaryStatusLabel(),
                    'payment_date' => '-',
                    'method' => '-',
                    'reference' => '-',
                ];
            }))
            ->values();
    }

    private function attachCycleEstimates($cycleEmployees, ?string $status)
    {
        return $cycleEmployees
            ->map(function (Employee $employee) use ($status) {
                $salaryDate = $employee->status === 'terminated'
                    ? $employee->last_working_date
                    : (($status ?? null) === 'upcoming'
                        ? $employee->nextSalaryDate()
                        : $employee->currentSalaryDueDate());

                $employee->setAttribute('cycle_estimate', $this->payrollEstimator->estimateCycle(
                    $employee,
                    $salaryDate,
                    $this->estimateClientFor($employee)
                ));

                return $employee;
            })
            ->values();
    }

    private function estimateClientFor(Employee $employee): ?Client
    {
        if ($employee->isAgencyInternal()) {
            return null;
        }

        return $employee->activeAssignments->first()?->client;
    }

    private function workStatusPreviewRows(array $filters): array
    {
        $salaryMonth = Carbon::createFromFormat('Y-m', $filters['salary_month'])->startOfMonth();
        $monthEnd = $salaryMonth->copy()->endOfMonth();

        return EmployeeWorkStatus::with(['employee', 'client'])
            ->whereNotNull('client_id')
            ->whereDate('work_date', '>=', $salaryMonth->toDateString())
            ->whereDate('work_date', '<=', $monthEnd->toDateString())
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->orderBy('work_date')
            ->get()
            ->groupBy(fn (EmployeeWorkStatus $workStatus) => $workStatus->employee_id . ':' . $workStatus->client_id)
            ->map(function ($records) use ($salaryMonth) {
                $employee = $records->first()->employee;
                $client = $records->first()->client;
                $workingCount = (float) $records->sum('salary_count_value');
                $nonWorkingCount = $records->filter(fn (EmployeeWorkStatus $workStatus) => (float) $workStatus->salary_count_value <= 0)->count();
                $monthlySalary = (float) ($employee?->monthly_salary ?? 0);
                $dailySalary = round($monthlySalary / EmployeePayroll::FIXED_SALARY_MONTH_DAYS, 2);
                $payableSalary = $workingCount >= EmployeePayroll::FIXED_SALARY_MONTH_DAYS
                    ? round($monthlySalary, 2)
                    : round($dailySalary * $workingCount, 2);
                $existingPayroll = $employee && $client
                    ? $this->existingPayrollForPeriod($employee->id, $client->id, $salaryMonth)
                    : null;

                return [
                    'employee' => $employee,
                    'client' => $client,
                    'working_count' => $workingCount,
                    'non_working_count' => $nonWorkingCount,
                    'monthly_salary' => $monthlySalary,
                    'daily_salary' => $dailySalary,
                    'payable_salary' => $payableSalary,
                    'existing_payroll' => $existingPayroll,
                    'adjustments' => $records->map(fn (EmployeeWorkStatus $workStatus) => [
                        'date' => $workStatus->work_date?->toDateString(),
                        'day_type' => (float) $workStatus->salary_count_value > 0 ? 'working' : 'non_working',
                        'salary_count_value' => (float) $workStatus->salary_count_value,
                        'reason' => $this->workStatusReason($workStatus->status),
                        'note' => $workStatus->note ?: 'From work status record',
                    ])->values()->all(),
                ];
            })
            ->filter(fn (array $row) => $row['employee'] && $row['client'])
            ->sortBy(fn (array $row) => $row['employee']->name . ' ' . $row['client']->company_name)
            ->values()
            ->all();
    }

    private function calculatePayroll(Employee $employee, array $data): array
    {
        $submittedWorkingDays = $data['working_days'] ?? null;
        $submittedNonWorkingDays = $data['non_working_days'] ?? null;

        if ($data['calculation_type'] === 'monthly_cycle') {
            if (empty($data['salary_month'])) {
                abort(422, 'Salary month is required for monthly cycle salary.');
            }

            $salaryMonth = Carbon::createFromFormat('Y-m', $data['salary_month'])->startOfMonth();
            $fromDate = $salaryMonth->copy();
            $toDate = $salaryMonth->copy()->endOfMonth();
            $workingDays = $submittedWorkingDays !== null
                ? (float) $submittedWorkingDays
                : EmployeePayroll::FIXED_SALARY_MONTH_DAYS;
            $nonWorkingDays = $submittedNonWorkingDays !== null
                ? (float) $submittedNonWorkingDays
                : 0;
        } else {
            if (empty($data['from_date']) || empty($data['to_date'])) {
                abort(422, 'From Date and To Date are required for Date To Date salary.');
            }

            $fromDate = Carbon::parse($data['from_date']);
            $toDate = Carbon::parse($data['to_date']);
            $salaryMonth = $fromDate->copy()->startOfMonth();
            $adjustments = ! empty($data['use_work_status_records'])
                ? $this->workStatusAdjustments($employee, (int) ($data['client_id'] ?? 0), $fromDate, $toDate)
                : $this->normalizeSalaryDayAdjustments($data['salary_day_adjustments'] ?? [], $fromDate, $toDate);

            if ($adjustments !== []) {
                $nonWorkingDays = collect($adjustments)
                    ->where('day_type', 'non_working')
                    ->count();
                $workingDays = collect($adjustments)->contains(fn (array $adjustment) => array_key_exists('salary_count_value', $adjustment))
                    ? (float) collect($adjustments)->sum('salary_count_value')
                    : count($adjustments) - $nonWorkingDays;
            } else {
                $workingDays = $submittedWorkingDays !== null
                    ? (float) $submittedWorkingDays
                    : ((int) $fromDate->diffInDays($toDate)) + 1;
                $nonWorkingDays = (float) ($submittedNonWorkingDays ?? 0);
            }
        }

        $monthDays = EmployeePayroll::FIXED_SALARY_MONTH_DAYS;
        $monthlySalary = (float) $employee->monthly_salary;
        $dailySalary = round($monthlySalary / $monthDays, 2);
        $payableSalary = (float) $workingDays >= $monthDays
            ? round($monthlySalary, 2)
            : round($dailySalary * $workingDays, 2);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'salary_month' => $salaryMonth,
            'working_days' => $workingDays,
            'non_working_days' => $nonWorkingDays,
            'month_days' => $monthDays,
            'daily_salary' => $dailySalary,
            'salary_day_adjustments' => $adjustments ?? null,
            'payable_salary' => $payableSalary,
        ];
    }

    private function workStatusAdjustments(Employee $employee, int $clientId, Carbon $fromDate, Carbon $toDate): array
    {
        $workStatusByDate = $employee->workStatuses()
            ->whereDate('work_date', '>=', $fromDate->toDateString())
            ->whereDate('work_date', '<=', $toDate->toDateString())
            ->where(function ($query) use ($clientId) {
                $query->whereNull('client_id')
                    ->orWhere('client_id', $clientId);
            })
            ->get()
            ->keyBy(fn (EmployeeWorkStatus $workStatus) => $workStatus->work_date?->toDateString());

        $adjustments = [];
        $current = $fromDate->copy();

        while ($current->lte($toDate)) {
            $date = $current->toDateString();
            $workStatus = $workStatusByDate->get($date);
            $salaryCount = $workStatus ? (float) $workStatus->salary_count_value : 0.0;

            $adjustments[] = [
                'date' => $date,
                'day_type' => $salaryCount > 0 ? 'working' : 'non_working',
                'salary_count_value' => $salaryCount,
                'reason' => $workStatus ? $this->workStatusReason($workStatus->status) : 'other',
                'note' => $workStatus?->note ?: ($workStatus ? 'From work status record' : 'No work status record'),
            ];

            $current->addDay();
        }

        return $adjustments;
    }

    private function workStatusReason(string $status): string
    {
        return [
            'working' => 'active_working',
            'half_day' => 'active_working',
            'absent' => 'absent',
            'on_leave' => 'on_leave',
            'client_issue' => 'client_issue',
            'boosting_off' => 'boosting_off',
            'sick_leave' => 'sick_leave',
            'agency_closed' => 'agency_closed',
        ][$status] ?? 'other';
    }

    private function normalizeSalaryDayAdjustments(array $adjustments, Carbon $fromDate, Carbon $toDate): array
    {
        if ($adjustments === []) {
            return [];
        }

        $allowedReasons = [
            'active_working',
            'absent',
            'client_issue',
            'boosting_off',
            'business_closed',
            'agency_hold',
            'on_leave',
            'sick_leave',
            'holiday',
            'agency_closed',
            'other',
        ];
        $normalized = [];

        foreach ($adjustments as $adjustment) {
            if (empty($adjustment['date'])) {
                continue;
            }

            $date = Carbon::parse($adjustment['date'])->startOfDay();

            if ($date->lt($fromDate->copy()->startOfDay()) || $date->gt($toDate->copy()->startOfDay())) {
                continue;
            }

            $dayType = ($adjustment['day_type'] ?? 'working') === 'non_working'
                ? 'non_working'
                : 'working';
            $reason = in_array($adjustment['reason'] ?? 'active_working', $allowedReasons, true)
                ? $adjustment['reason']
                : 'active_working';

            $normalized[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'day_type' => $dayType,
                'salary_count_value' => array_key_exists('salary_count_value', $adjustment)
                    ? (float) $adjustment['salary_count_value']
                    : ($dayType === 'working' ? 1.0 : 0.0),
                'reason' => $reason,
                'note' => trim((string) ($adjustment['note'] ?? '')),
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    private function validatePaymentWorkflow(Request $request, float $paidAmount): void
    {
        if ($paidAmount <= 0) {
            return;
        }

        $request->validate([
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
        ]);

    }

    private function cycleEmployeesForStatus(?string $status)
    {
        if (! in_array($status, ['upcoming', 'due'], true)) {
            return collect();
        }

        $today = now()->startOfDay();

        return Employee::with(['payrolls' => fn ($query) => $query->current(), 'activeAssignments.client'])
            ->orderBy('name')
            ->get()
            ->filter(function (Employee $employee) use ($status, $today) {
                if ($employee->status === 'terminated' && $status !== 'due') {
                    return false;
                }

                $cycleDate = $employee->status === 'terminated'
                    ? $employee->last_working_date
                    : ($status === 'upcoming'
                        ? $employee->nextSalaryDate()
                        : $employee->currentSalaryDueDate($today));

                if (! $cycleDate) {
                    return false;
                }

                $cycleMonth = $cycleDate->copy()->startOfMonth()->toDateString();
                $hasGeneratedSalary = $employee->status === 'terminated'
                    ? $employee->hasFinalSalaryPayroll()
                    : $employee->payrolls->contains(
                        fn (EmployeePayroll $payroll) => $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $cycleMonth
                    );

                if ($hasGeneratedSalary) {
                    return false;
                }

                if ($status === 'upcoming') {
                    return $cycleDate->betweenIncluded($today, $today->copy()->addDays(5));
                }

                return $employee->status === 'terminated' || $cycleDate->lt($today);
            })
            ->values();
    }

}
