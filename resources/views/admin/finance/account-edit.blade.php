@extends('layouts.admin')

@section('content')
    <h1>Edit Finance Account</h1>

    <div class="card">
        <form method="POST" action="/admin/finance/accounts/{{ $account->id }}/update" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.account-fields')
            <div>
                <button class="btn" type="submit">Update Account</button>
                <a class="btn" href="/admin/finance/accounts">Back</a>
            </div>
        </form>
    </div>
@endsection
