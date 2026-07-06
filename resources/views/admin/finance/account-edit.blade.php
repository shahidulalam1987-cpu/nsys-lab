@extends('layouts.admin')

@section('content')
    <h1>Edit Finance Account</h1>

    @if($errors->any())
        <div class="card" style="border-color:#ef4444; color:#fecaca;">
            <strong>Account was not updated.</strong>
            <ul style="margin-bottom:0;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <form id="finance-account-form" method="POST" action="/admin/finance/accounts/{{ $account->id }}/update" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.account-fields')
            <div>
                <button class="btn" type="submit">Update Account</button>
                <a class="btn" href="/admin/finance/accounts">Back</a>
            </div>
        </form>
    </div>

    <div id="adjustment-modal" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.72); align-items:center; justify-content:center; padding:20px;">
        <div class="card" style="width:min(480px,100%); margin:0;">
            <h2>Confirm Balance Adjustment</h2>
            <p>Current Balance<br><strong id="modal-current"></strong></p>
            <p>Adjustment<br><strong id="modal-adjustment"></strong></p>
            <p>New Balance<br><strong id="modal-new"></strong></p>
            <p>Reason<br><strong id="modal-reason"></strong></p>
            <p style="color:var(--muted);">A Manual Adjustment Ledger will be created.</p>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button class="btn" id="cancel-adjustment" type="button">Cancel</button>
                <button class="btn" id="confirm-adjustment" type="button">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const form = document.getElementById('finance-account-form');
            const balanceNode = document.getElementById('current-balance');
            const amountInput = document.getElementById('adjustment-amount');
            const reasonInput = document.getElementById('adjustment-reason');
            const modal = document.getElementById('adjustment-modal');
            const current = Number(balanceNode.dataset.balance);
            const currency = balanceNode.dataset.currency;
            let confirmed = false;

            const money = value => `${currency} ${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            const preview = () => {
                const type = form.querySelector('[name="adjustment_type"]:checked')?.value || 'credit';
                const amount = Math.max(Number(amountInput.value) || 0, 0);
                const sign = type === 'credit' ? '+' : '-';
                const color = type === 'credit' ? '#22c55e' : '#ef4444';
                const next = type === 'credit' ? current + amount : current - amount;
                document.getElementById('preview-current').textContent = money(current);
                document.getElementById('preview-adjustment').textContent = `${sign} ${money(amount)}`;
                document.getElementById('preview-adjustment').style.color = color;
                document.getElementById('preview-new').textContent = money(next);
                document.getElementById('preview-new').style.color = color;
                return {amount, sign, color, next};
            };

            form.addEventListener('input', preview);
            form.addEventListener('change', preview);
            form.addEventListener('submit', event => {
                if (confirmed || !form.reportValidity()) return;
                event.preventDefault();
                const values = preview();
                document.getElementById('modal-current').textContent = money(current);
                document.getElementById('modal-adjustment').textContent = `${values.sign} ${money(values.amount)}`;
                document.getElementById('modal-adjustment').style.color = values.color;
                document.getElementById('modal-new').textContent = money(values.next);
                document.getElementById('modal-new').style.color = values.color;
                document.getElementById('modal-reason').textContent = reasonInput.value;
                modal.style.display = 'flex';
            });
            document.getElementById('cancel-adjustment').addEventListener('click', () => modal.style.display = 'none');
            document.getElementById('confirm-adjustment').addEventListener('click', () => {
                confirmed = true;
                form.requestSubmit();
            });
            preview();
        })();
    </script>
@endsection
