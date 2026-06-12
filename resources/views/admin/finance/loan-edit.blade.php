@extends('layouts.admin')

@section('content')
    <h1>Edit Loan</h1>

    <div class="card">
        <form method="POST" action="/admin/finance/loans/{{ $loan->id }}/update" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.loan-fields')
            <div>
                <button class="btn" type="submit">Update Loan</button>
                <a class="btn" href="/admin/finance/loans/{{ $loan->id }}">Back</a>
            </div>
        </form>
    </div>
@endsection
