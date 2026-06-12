@extends('layouts.admin')

@section('content')
    <h1>Family Expense Report</h1>
    <p>Person-wise, category-wise, and monthly family expense summaries.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>This Month Family Expense</p><h2>BDT {{ number_format($summary['this_month_family_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Family Expense</p><h2>BDT {{ number_format($summary['total_family_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Medical Expense</p><h2>BDT {{ number_format($summary['medical_expense'], 2) }}</h2></div>
        <div class="stat-card"><p>Emergency Expense</p><h2>BDT {{ number_format($summary['emergency_expense'], 2) }}</h2></div>
    </div>

    @foreach(['Person-wise Expense' => $personRows, 'Category-wise Expense' => $categoryRows, 'Monthly Family Expense Report' => $monthRows] as $title => $rows)
        <div class="card">
            <h2>{{ $title }}</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>{{ $title === 'Monthly Family Expense Report' ? 'Month' : 'Name' }}</th>
                        <th>Records</th>
                        <th>Total Amount</th>
                    </tr>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['name'] ?? $row['month'] }}</td>
                            <td>{{ number_format($row['count']) }}</td>
                            <td>BDT {{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No report data found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    @endforeach
@endsection
