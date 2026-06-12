@extends('layouts.admin')

@section('content')
    <h1>Edit Family Expense</h1>

    <div class="card">
        <form method="POST" action="/admin/finance/family-expenses/{{ $expense->id }}/update" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.family-expense-fields')
            <div>
                <button class="btn" type="submit">Update Expense</button>
                <a class="btn" href="/admin/finance/family-expenses">Back</a>
            </div>
        </form>
    </div>
@endsection
