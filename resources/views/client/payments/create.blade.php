@extends('layouts.client')

@section('content')
    <h1>Submit Payment</h1>

    <a class="btn" href="/client/payments">Payment History</a>
    <a class="btn" href="/client/invoices">My Invoices</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($invoice)
        <div class="card" style="margin-top:20px; border-color:#42e8ff;">
            <h3>Invoice Payment</h3>
            <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Title:</strong> {{ $invoice->title }}</p>
            <p><strong>Amount:</strong> ৳{{ number_format($invoice->amount, 2) }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date }}</p>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <form method="POST" action="/client/payments" enctype="multipart/form-data">
            @csrf

            @if($invoice)
                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            @endif

            <p>
                Amount (BDT)<br>
                <input
                    type="number"
                    name="amount"
                    value="{{ old('amount', $invoice ? $invoice->amount : '') }}"
                    {{ $invoice ? 'readonly' : '' }}
                    required
                >
            </p>

            <p>
                Payment Method<br>
                <select name="payment_method" required>
                    <option value="bKash" {{ old('payment_method') == 'bKash' ? 'selected' : '' }}>bKash</option>
                    <option value="Nagad" {{ old('payment_method') == 'Nagad' ? 'selected' : '' }}>Nagad</option>
                    <option value="Rocket" {{ old('payment_method') == 'Rocket' ? 'selected' : '' }}>Rocket</option>
                    <option value="Bank" {{ old('payment_method') == 'Bank' ? 'selected' : '' }}>Bank</option>
                    <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                </select>
            </p>

            <p>
                Transaction ID<br>
                <input
                    type="text"
                    name="transaction_id"
                    value="{{ old('transaction_id') }}"
                    required
                >
            </p>

            <p>
                Screenshot<br>
                <input
                    type="file"
                    name="screenshot"
                    id="screenshotInput"
                    accept="image/*"
                >
            </p>

            <div id="previewBox" style="display:none; margin:15px 0;">
                <p>Screenshot Preview</p>
                <img
                    id="previewImage"
                    src=""
                    style="max-width:300px; border-radius:12px; border:1px solid rgba(255,255,255,.16);"
                >
            </div>

            <p>
                Note<br>
                <textarea name="note">{{ old('note', $invoice ? 'Payment for invoice ' . $invoice->invoice_number : '') }}</textarea>
            </p>

            <button class="btn" type="submit">Submit Payment</button>
        </form>
    </div>

    <script>
        const screenshotInput = document.getElementById('screenshotInput');

        if (screenshotInput) {
            screenshotInput.addEventListener('change', function(event) {
                const file = event.target.files[0];

                if (file) {
                    const previewBox = document.getElementById('previewBox');
                    const previewImage = document.getElementById('previewImage');

                    previewImage.src = URL.createObjectURL(file);
                    previewBox.style.display = 'block';
                }
            });
        }
    </script>
@endsection