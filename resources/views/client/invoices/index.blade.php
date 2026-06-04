@extends('layouts.client')

@section('content')
    <h1>My Invoices</h1>

    <div class="card" style="margin-top:20px;">
        <table>
            <tr>
                <th>Invoice No</th>
                <th>Title</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Action</th>
            </tr>

            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
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
                        <a class="btn" href="/client/invoices/{{ $invoice->id }}/pdf">Download PDF</a>

                        @if($invoice->status != 'paid' && $invoice->status != 'cancelled')
                            <a class="btn" href="/client/payments/create?invoice_id={{ $invoice->id }}">
                                Submit Payment
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No invoices found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection