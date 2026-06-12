@extends('layouts.admin')

@section('content')
    <h1>Update Funding Balance</h1>
    <p>Manually update Binance, RedotPay, or Tavao available USD balance.</p>

    <div class="card">
        <form method="POST" action="/admin/facebook-financial/funding-dashboard/update" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end;">
            @csrf
            <label>Funding Source<br>
                <select name="source" required>
                    <option value="">Select Source</option>
                    @foreach($sources as $value => $label)
                        <option value="{{ $value }}" {{ old('source') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Balance (USD)<br><input type="number" step="0.01" min="0" name="balance" value="{{ old('balance') }}" required></label>
            <label>Date<br><input type="date" name="balance_date" value="{{ old('balance_date', now()->toDateString()) }}" required></label>
            <label style="grid-column:1/-1;">Note<br><textarea name="note" rows="3" style="width:100%;">{{ old('note') }}</textarea></label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn" type="submit">Save Balance</button>
                <a class="btn" href="/admin/facebook-financial/funding-dashboard">Back to Dashboard</a>
            </div>
        </form>
    </div>
@endsection
