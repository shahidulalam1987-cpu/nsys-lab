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

                    <a class="btn btn-success" href="/admin/invoices/{{ $invoice->id }}/status/paid">Paid</a>
                    <a class="btn btn-danger" href="/admin/invoices/{{ $invoice->id }}/status/overdue">Overdue</a>
                    <a class="btn" href="/admin/invoices/{{ $invoice->id }}/status/sent">Sent</a>
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