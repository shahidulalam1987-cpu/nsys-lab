@extends('layouts.admin')

@section('content')
    <h1>Family Expenses</h1>
    <p>Track personal and family support payments with account deduction history.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>This Month Family Expense</p><h2>BDT {{ number_format($summary['this_month_family_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Family Expense</p><h2>BDT {{ number_format($summary['total_family_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Medical Expense</p><h2>BDT {{ number_format($summary['medical_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Emergency Expense</p><h2>BDT {{ number_format($summary['emergency_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Top Person Expense</p><h2>BDT {{ number_format($summary['top_person_expense_amount'], 2) }}</h2><p>{{ $summary['top_person_expense_name'] }}</p></div>
    </div>

    <div class="card">
        <h2>Add Expense</h2>
        <form method="POST" action="/admin/finance/family-expenses" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.family-expense-fields', ['expense' => null])
            <button class="btn" type="submit">Save Expense</button>
        </form>
    </div>

    <div class="card">
        <form method="GET" action="/admin/finance/family-expenses" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end;">
            <label>From Date<br><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>To Date<br><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            <label>Person<br><input type="text" name="person" value="{{ $filters['person'] ?? '' }}"></label>
            <label>Category<br>
                <select name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['category'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Payment Method<br><input type="text" name="payment_method" value="{{ $filters['payment_method'] ?? '' }}"></label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/finance/family-expenses">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Person</th>
                    <th>Relation</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>From Account</th>
                    <th>Purpose / Note</th>
                    <th>Action</th>
                </tr>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date?->toDateString() }}</td>
                        <td>{{ $expense->person_name }}</td>
                        <td>{{ $expense->relation ?: '-' }}</td>
                        <td>{{ $expense->categoryLabel() }}</td>
                        <td>BDT {{ number_format((float) $expense->amount, 2) }}</td>
                        <td>{{ $expense->payment_method ?: '-' }}</td>
                        <td>{{ $expense->account?->account_name ?: '-' }}</td>
                        <td>{{ $expense->note ?: '-' }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/finance/family-expenses/{{ $expense->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/finance/family-expenses/{{ $expense->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this family expense? Account balance will be restored if an account was selected.');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No family expenses found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
