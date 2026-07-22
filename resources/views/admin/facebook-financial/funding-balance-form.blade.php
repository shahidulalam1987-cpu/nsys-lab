@extends('layouts.admin')

@section('content')
    <style>
        .funding-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .funding-form-grid label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .funding-form-grid input,
        .funding-form-grid select,
        .funding-form-grid textarea {
            width: 100%;
            min-width: 0;
        }

        .funding-form-grid .wide {
            grid-column: 1 / -1;
        }

        .funding-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        @media (max-width: 900px) {
            .funding-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <h1>Update Funding Balance</h1>
    <p>Manually update Binance, RedotPay, or Tavao available USD balance.</p>

    <div class="funding-note">
        This creates a funding history record and ledger-backed adjustment for the selected funding source. It does not directly update card balances or Facebook spend records.
    </div>

    <div class="card">
        <form method="POST" action="/admin/facebook-financial/funding-dashboard/update" class="funding-form-grid">
            @csrf
            <label>
                Funding Source
                <select name="source" required>
                    <option value="">Select Source</option>
                    @foreach($sources as $value => $label)
                        <option value="{{ $value }}" {{ old('source') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Balance USD
                <input type="number" step="0.01" min="0" name="balance" value="{{ old('balance') }}" required>
            </label>
            <label>
                Balance Date
                <input type="date" name="balance_date" value="{{ old('balance_date', now()->toDateString()) }}" required>
            </label>
            <label class="wide">
                Note
                <textarea name="note" rows="3">{{ old('note') }}</textarea>
            </label>
            <div class="wide" style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn" type="submit">Save Balance</button>
                <a class="btn" href="/admin/facebook-financial/funding-dashboard">Back to Dashboard</a>
            </div>
        </form>
    </div>
@endsection
