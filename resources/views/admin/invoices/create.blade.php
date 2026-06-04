@extends('layouts.admin')

@section('content')

<h1>Create Invoice</h1>

<div class="card">

<form action="/admin/invoices" method="POST">
    @csrf

    <label>Client</label>

    <select name="client_id" required>
        <option value="">Select Client</option>

        @foreach($clients as $client)
            <option value="{{ $client->id }}">
                {{ $client->company_name }}
            </option>
        @endforeach
    </select>

    <br><br>

    <label>Title</label>
    <input type="text" name="title" required>

    <br><br>

    <label>Description</label>
    <textarea name="description"></textarea>

    <br><br>

    <label>Amount</label>
    <input type="number" step="0.01" name="amount" required>

    <br><br>

    <label>Issue Date</label>
    <input type="date" name="issue_date" required>

    <br><br>

    <label>Due Date</label>
    <input type="date" name="due_date" required>

    <br><br>

    <label>Status</label>

    <select name="status">
        <option value="draft">Draft</option>
        <option value="sent">Sent</option>
        <option value="paid">Paid</option>
        <option value="overdue">Overdue</option>
        <option value="cancelled">Cancelled</option>
    </select>

    <br><br>

    <button class="btn" type="submit">
        Create Invoice
    </button>

</form>

</div>

@endsection