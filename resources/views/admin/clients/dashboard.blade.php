<form method="POST" action="/logout">
    @csrf
    <button type="submit">Logout</button>
</form>
<h1>Client Dashboard</h1>

<h3>{{ $client->company_name }}</h3>
<p>Today: {{ $today }}</p>

<hr>

<h3>Today Summary</h3>
<p>Total Spend: ${{ $todaySpend }}</p>
<p>Total Orders: {{ $todayOrders }}</p>

<hr>

<h3>Balance Status</h3>
<p>Total Approved Payment: ৳{{ $approvedPayments }}</p>
<p>Total Spend BDT: ৳{{ $totalSpendBdt }}</p>

<h2>
    Balance:
    @if($balance >= 0)
        +৳{{ $balance }}
    @else
        -৳{{ abs($balance) }}
    @endif
</h2>

<hr>

<h3>Today Page Reports</h3>

<table border="1" cellpadding="10">
    <tr>
        <th>Page Name</th>
        <th>Dollar Spend</th>
        <th>Orders</th>
    </tr>

    @foreach($todayReports as $report)
    <tr>
        <td>{{ $report->page_name }}</td>
        <td>${{ $report->dollar_spend }}</td>
        <td>{{ $report->orders }}</td>
    </tr>
    @endforeach
</table>

<br>

<a href="/client/payments">Payment History</a> |
<a href="/client/payments/create">Submit Payment</a>