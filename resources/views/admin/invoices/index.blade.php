@extends('layouts.admin')

@section('content')

<h1>Invoices</h1>

<a class="btn" href="/admin/invoices/create">Create Invoice</a>

<div class="card" style="margin-top:20px;">
    <table>
        <tr>
            <th>ID</th>
            <th>Invoice No</th>
            <th>Client</th>
            <th>Title</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Due Date</th>
            <th>Action</th>
        </tr>

        @forelse($invoices as $invoice)
            <tr>
                <td>{{ $invoice->id }}</td>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->client->company_name ?? 'N/A' }}</td>
                <td>{{ $invoice->title }}</td>
                <td>৳{{ number_format($invoice->amount, 2) }}</td>
                <td>
                    @if($invoice->status == 'paid')
                        <span class="badge badge-success">Paid</span>
                    @elseif($invoice->status == 'sent')
                        <span class="badge badge-info">Sent</span>
                    @elseif($invoice->status == 'overdue')
                        <span class="badge badge-danger">Overdue</span>
                    @elseif($invoice->status == 'cancelled')
                        <span class="badge badge-danger">Cancelled</span>
                    @else
                        <span class="badge badge-warning">Draft</span>
                    @endif
                </td>
                <td>{{ $invoice->issue_date }}</td>
                <td>{{ $invoice->due_date }}</td>
                <td>
                    <a class="btn" href="/admin/invoices/{{ $invoice->id }}/pdf">PDF</a>

                    <br><br>

                    <form method="POST" action="/admin/invoices/{{ $invoice->id }}/status/paid" style="display:inline">@csrf<button class="btn btn-success" type="submit">Paid</button></form>
                    <form method="POST" action="/admin/invoices/{{ $invoice->id }}/status/overdue" style="display:inline">@csrf<button class="btn btn-danger" type="submit">Overdue</button></form>
                    <form method="POST" action="/admin/invoices/{{ $invoice->id }}/status/sent" style="display:inline">@csrf<button class="btn" type="submit">Sent</button></form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No invoices found.</td>
            </tr>
        @endforelse
    </table>
</div>

@endsection
