<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .page {
            padding: 34px;
        }

        .top-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 28px;
        }

        .top-table td {
            border: none;
            vertical-align: top;
            padding-bottom: 15px;
        }

        .logo {
            height: 80px;
            margin-bottom: 8px;
        }

        .brand {
            font-size: 34px;
            font-weight: bold;
            color: #2563eb;
            margin: 0 0 6px 0;
            letter-spacing: 1px;
        }

        .agency-info {
            color: #334155;
            line-height: 1.7;
            font-size: 13px;
        }

        .invoice-title {
            text-align: right;
            font-size: 34px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 1px;
            margin-top: 20px;
        }

        .invoice-number {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 10px;
        }

        .generated {
            text-align: right;
            color: #64748b;
            margin-top: 6px;
            font-size: 12px;
        }

        .two-col {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 26px;
        }

        .two-col td {
            border: none;
            vertical-align: top;
        }

        .box {
            border: 1px solid #cbd5e1;
            padding: 16px;
            border-radius: 8px;
            min-height: 95px;
        }

        .box-title {
            font-size: 17px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .box-line {
            line-height: 1.7;
            color: #111827;
        }

        .status-badge {
            color: #ffffff;
            padding: 5px 13px;
            border-radius: 18px;
            font-weight: bold;
            font-size: 11px;
        }

        .status-paid {
            background: #16a34a;
        }

        .status-sent {
            background: #2563eb;
        }

        .status-overdue {
            background: #dc2626;
        }

        .status-cancelled {
            background: #991b1b;
        }

        .status-draft {
            background: #f59e0b;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            margin: 22px 0 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background: #f1f5f9;
            color: #111827;
            text-align: left;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            padding: 11px;
        }

        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 11px;
        }

        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        .total-table td {
            border: none;
        }

        .total-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 15px;
            text-align: right;
        }

        .total-label {
            font-size: 14px;
            color: #334155;
            font-weight: bold;
        }

        .total-amount {
            font-size: 26px;
            color: #2563eb;
            font-weight: bold;
            margin-top: 6px;
        }

        .payment-box {
            margin-top: 24px;
            border: 1px solid #cbd5e1;
            padding: 15px;
            background: #f8fafc;
            line-height: 1.65;
        }

        .signature-table {
            width: 100%;
            margin-top: 55px;
            border-collapse: collapse;
        }

        .signature-table td {
            border: none;
        }

        .signature-line {
            border-top: 1px solid #111827;
            width: 210px;
            text-align: center;
            padding-top: 8px;
            font-weight: bold;
            color: #111827;
        }

        .footer {
            margin-top: 35px;
            border-top: 1px solid #cbd5e1;
            padding-top: 14px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }
    </style>
</head>

<body>
<div class="page">

    @php
        $logoPath = public_path('images/nsys-logo.png');
        $logoBase64 = null;

        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    @endphp

    <table class="top-table">
        <tr>
            <td style="width:60%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo">
                @endif

                <div class="brand">NSYS Agency</div>

                <div class="agency-info">
                    Digital Marketing & Client Billing Portal<br>
                    Phone: +8801817628409<br>
                    Email: contact@nsysagency.com
                </div>
            </td>

            <td style="width:40%;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="generated">Generated: {{ date('Y-m-d H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td style="width:50%; padding-right:10px;">
                <div class="box">
                    <div class="box-title">Bill To</div>

                    <div class="box-line">
                        <strong>{{ $invoice->client->company_name ?? 'N/A' }}</strong><br>
                        Phone: {{ $invoice->client->phone ?? 'N/A' }}<br>
                        Email: {{ $invoice->client->user->email ?? 'N/A' }}
                    </div>
                </div>
            </td>

            <td style="width:50%; padding-left:10px;">
                <div class="box">
                    <div class="box-title">Invoice Summary</div>

                    <div class="box-line">
                        <strong>Issue Date:</strong> {{ $invoice->issue_date }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date }}<br>
                        <strong>Status:</strong>

                        @if($invoice->status == 'paid')
                            <span class="status-badge status-paid">PAID</span>
                        @elseif($invoice->status == 'sent')
                            <span class="status-badge status-sent">SENT</span>
                        @elseif($invoice->status == 'overdue')
                            <span class="status-badge status-overdue">OVERDUE</span>
                        @elseif($invoice->status == 'cancelled')
                            <span class="status-badge status-cancelled">CANCELLED</span>
                        @else
                            <span class="status-badge status-draft">DRAFT</span>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Invoice Details</div>

    <table class="items-table">
        <tr>
            <th style="width:28%;">Title</th>
            <th>Description</th>
            <th style="width:25%;">Amount</th>
        </tr>

        <tr>
            <td>{{ $invoice->title }}</td>
            <td>{{ $invoice->description ?? '-' }}</td>
            <td>BDT {{ number_format($invoice->amount, 2) }}</td>
        </tr>
    </table>

    <table class="total-table">
        <tr>
            <td style="width:58%;"></td>
            <td style="width:42%;">
                <div class="total-card">
                    <div class="total-label">TOTAL DUE</div>
                    <div class="total-amount">BDT {{ number_format($invoice->amount, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="payment-box">
        <strong>Payment Instructions</strong><br><br>
        Please pay the invoice amount using your preferred payment method and submit the payment proof from the client portal.<br>
        Supported Methods: bKash, Nagad, Rocket, Bank Transfer, Cash.
    </div>

    <div class="payment-box">
        <strong>Note</strong><br><br>
        Thank you for doing business with NSYS Agency. This invoice was generated automatically from NSYS Agency Portal.
    </div>

    <table class="signature-table">
        <tr>
            <td style="width:62%;"></td>
            <td style="width:38%; text-align:center;">
                <div class="signature-line">
                    Authorized Signature<br>
                    NSYS Agency
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        NSYS Agency<br>
        Contact: +8801817628409 | Email: contact@nsysagency.com<br>
        www.nsysagency.com<br>
        Reference: {{ $invoice->invoice_number }} | Generated: {{ date('Y-m-d H:i') }}
    </div>

</div>
</body>
</html>