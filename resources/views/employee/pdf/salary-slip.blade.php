<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#111827; font-size: 12px; }
        h1 { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>NSYS Agency</h1>
    <p class="muted">Employee Salary Slip</p>

    <table>
        <tr><th>Employee Name</th><td>{{ $employee->name }}</td></tr>
        <tr><th>Employee ID</th><td>{{ $employee->employee_id }}</td></tr>
        <tr><th>Client</th><td>{{ $payroll->client?->company_name ?: '-' }}</td></tr>
        <tr><th>Page</th><td>{{ $assignment?->page?->page_name ?: '-' }}</td></tr>
        <tr><th>Month</th><td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td></tr>
        <tr><th>Salary Policy</th><td>Fixed 30 Days</td></tr>
        <tr><th>Working Days</th><td>{{ $payroll->working_days ?? '-' }}</td></tr>
        <tr><th>Work Status Count</th><td>{{ number_format((float) ($payroll->working_days ?? 0), 2) }}</td></tr>
        <tr><th>Payable Count</th><td>{{ number_format(\App\Models\EmployeePayroll::effectiveSalaryCount((float) ($payroll->working_days ?? 0)), 2) }}</td></tr>
        <tr><th>Cap Applied</th><td>{{ \App\Models\EmployeePayroll::salaryCountCapApplied((float) ($payroll->working_days ?? 0)) ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Half Days</th><td>{{ $halfDays }}</td></tr>
        <tr><th>Generated Salary</th><td>BDT {{ number_format($payroll->payable_salary, 2) }}</td></tr>
        <tr><th>Paid Salary</th><td>BDT {{ number_format($payroll->paid_amount, 2) }}</td></tr>
        <tr><th>Due Salary</th><td>BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</td></tr>
        <tr><th>Payment Date</th><td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td></tr>
    </table>

    <p style="margin-top: 36px;"><strong>Prepared By:</strong><br>NSYS Agency</p>
</body>
</html>
