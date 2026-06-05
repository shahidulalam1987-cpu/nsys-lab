@extends('layouts.client')

@section('content')
    <h1>Submit Salary Payment</h1>

    <a class="btn" href="/client/salary-payments">Salary Payment History</a>
    <a class="btn" href="/client/salary-fund">Salary Fund Summary</a>

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
        <form method="POST" action="/client/salary-payments" enctype="multipart/form-data">
            @csrf
            <p>Salary Month<br><input type="month" name="salary_month" value="{{ old('salary_month', now()->format('Y-m')) }}" required></p>
            <p>Amount (BDT)<br><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required></p>
            <p>Payment Method<br>
                <select name="payment_method" required>
                    @foreach(['bKash', 'Nagad', 'Rocket', 'Bank', 'Cash'] as $method)
                        <option value="{{ $method }}" {{ old('payment_method') == $method ? 'selected' : '' }}>{{ $method }}</option>
                    @endforeach
                </select>
            </p>
            <p>Transaction ID<br><input type="text" name="transaction_id" value="{{ old('transaction_id') }}" required></p>
            <p>Screenshot<br><input type="file" name="screenshot" accept="image/*"></p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>
            <button class="btn" type="submit">Submit Salary Payment</button>
        </form>
    </div>
@endsection
