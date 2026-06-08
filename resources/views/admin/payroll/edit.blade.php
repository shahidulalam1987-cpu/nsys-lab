@extends('layouts.admin')

@section('content')
    <h1>Edit Salary</h1>

    <a class="btn" href="/admin/payroll/{{ $payroll->id }}">Back to Salary Details</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <h2>{{ $payroll->employee?->name }} - {{ $payroll->salary_month?->format('Y-m') }}</h2>
        <p><strong>Salary Period:</strong> {{ $payroll->salary_period }}</p>
        <p><strong>Working Days:</strong> {{ $payroll->working_days ?? '-' }}</p>
        <p><strong>Non Working Days:</strong> {{ $payroll->non_working_days ?? '-' }}</p>
        <p><strong>Payable Salary (BDT):</strong> BDT {{ number_format($payroll->payable_salary, 2) }}</p>

        @if($clientFundBalance !== null && $clientFundNeed > $clientFundBalance)
            <div style="margin:14px 0; padding:12px; border:1px solid #f59e0b; border-radius:8px; color:#fcd34d; background:rgba(245,158,11,.12);">
                <strong>Insufficient Client Fund</strong><br>
                Need: BDT {{ number_format($clientFundNeed, 2) }}<br>
                Available: BDT {{ number_format($clientFundBalance, 2) }}<br>
                Admin can still update salary if approved.
            </div>
        @endif

        <form method="POST" action="/admin/payroll/{{ $payroll->id }}/update" enctype="multipart/form-data">
            @csrf

            <p>Salary status is calculated automatically from salary date and paid salary.</p>

            <p>
                Paid Salary<br>
                <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', $payroll->paid_amount) }}">
            </p>

            <p>
                Payment Method<br>
                <input type="text" name="payment_method" id="payment_method" value="{{ old('payment_method', $payroll->payment_method) }}">
            </p>

            <p>
                Payment Date<br>
                <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', $payroll->payment_date?->toDateString()) }}">
            </p>

            <p>
                Transaction ID / Reference<br>
                <input type="text" name="transaction_id" value="{{ old('transaction_id', $payroll->transaction_id) }}">
            </p>

            <p>
                Payment Proof Screenshot<br>
                <input type="file" name="payment_proof" accept="image/*">
                @if($payroll->payment_proof)
                    <br><a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View current proof</a>
                @endif
            </p>

            <p>
                Note<br>
                <textarea name="note">{{ old('note', $payroll->note) }}</textarea>
            </p>

            <button class="btn" type="submit">Update Salary</button>
        </form>
    </div>

    <script>
        const paidAmount = document.getElementById('paid_amount');
        const paymentMethod = document.getElementById('payment_method');
        const paymentDate = document.getElementById('payment_date');

        function syncPaymentRequirements() {
            const needsPaymentDetails = Number(paidAmount.value || 0) > 0;
            paidAmount.required = needsPaymentDetails;
            paymentMethod.required = needsPaymentDetails;
            paymentDate.required = needsPaymentDetails;
        }

        paidAmount.addEventListener('input', syncPaymentRequirements);
        paidAmount.addEventListener('change', syncPaymentRequirements);
        syncPaymentRequirements();
    </script>
@endsection
