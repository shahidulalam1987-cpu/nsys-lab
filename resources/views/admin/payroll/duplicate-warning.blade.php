@extends('layouts.admin')

@section('content')
    <h1>Salary Already Generated</h1>

    <div class="card" style="border-color:#f59e0b; background:rgba(245,158,11,.12);">
        <h2>Salary already generated for this period.</h2>
        <p>
            {{ $existingPayroll->employee?->name }} already has a salary record for
            {{ $existingPayroll->client?->company_name ?: 'this client' }} in
            {{ $existingPayroll->salary_month?->format('Y-m') }}.
        </p>

        <div class="employee-info-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-top:16px;">
            <p><strong>Existing Salary:</strong> #{{ $existingPayroll->id }}</p>
            <p><strong>Payable Salary:</strong> BDT {{ number_format($existingPayroll->payable_salary, 2) }}</p>
            <p><strong>Paid Salary:</strong> BDT {{ number_format($existingPayroll->paid_amount, 2) }}</p>
            <p><strong>Status:</strong> {{ $existingPayroll->payrollStatusLabel() }}</p>
        </div>
    </div>

    <div class="card">
        <p>Default action is Cancel. Use Regenerate only when this salary needs a corrected replacement while keeping the old history.</p>

        <a class="btn" href="/admin/payroll/{{ $existingPayroll->id }}">View Existing Salary</a>

        <form method="POST" action="/admin/payroll" style="display:inline;">
            @csrf
            @foreach($requestData as $name => $value)
                @if(is_array($value))
                    @foreach($value as $index => $items)
                        @if(is_array($items))
                            @foreach($items as $itemName => $itemValue)
                                <input type="hidden" name="{{ $name }}[{{ $index }}][{{ $itemName }}]" value="{{ $itemValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}[{{ $index }}]" value="{{ $items }}">
                        @endif
                    @endforeach
                @else
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <button class="btn btn-danger" type="submit" onclick="return confirm('Regenerate this salary and keep existing history?');">Regenerate</button>
        </form>

        <a class="btn" href="/admin/payroll">Cancel</a>
    </div>
@endsection
