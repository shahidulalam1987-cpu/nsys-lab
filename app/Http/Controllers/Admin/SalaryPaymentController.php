<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FinanceAccount;
use App\Models\FinanceAccountLedger;
use App\Models\SalaryPayment;
use App\Services\ActivityLogger;
use App\Services\FinanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryPayment::with('client');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->get();
        $clients = Client::orderBy('company_name')->get();

        return view('admin.salary-payments.index', compact('payments', 'clients'));
    }

    public function create()
    {
        $clients = Client::orderBy('company_name')->get();

        $financeAccounts = FinanceAccount::where('currency', 'BDT')->where('status', 'active')->orderBy('account_name')->get();

        return view('admin.salary-payments.create', compact('clients', 'financeAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:100'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,approved'],
            'finance_account_id' => ['nullable', 'required_if:status,approved', 'exists:finance_accounts,id'],
        ]);

        if ($request->hasFile('screenshot')) {
            $data['screenshot'] = $request->file('screenshot')->store('salary-payment-screenshots', 'public');
        }

        $data['salary_month'] = date('Y-m-d', strtotime($data['payment_date']));
        unset($data['payment_date']);

        if ($data['status'] === 'approved') {
            $data['approved_at'] = now();
        }

        $payment = DB::transaction(function () use ($data, $request) {
            $payment = SalaryPayment::create($data);

            if ($payment->status === 'approved') {
                $this->creditClientPayment($payment, $request);
            }

            return $payment;
        });

        app(ActivityLogger::class)->log('Client Fund', 'Payment Received', 'Client payment #' . $payment->id . ' saved.', $request);

        return redirect('/admin/salary-payments')->with('success', 'Client fund payment saved successfully.');
    }

    public function pending()
    {
        $payments = SalaryPayment::with('client')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $financeAccounts = FinanceAccount::where('currency', 'BDT')->where('status', 'active')->orderBy('account_name')->get();

        return view('admin.salary-payments.pending', compact('payments', 'financeAccounts'));
    }

    public function approve(Request $request, $id)
    {
        $data = $request->validate([
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
        ]);

        $payment = DB::transaction(function () use ($id, $data, $request) {
            $payment = SalaryPayment::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'approved'
                && app(FinanceLedgerService::class)->hasEntry('client_payment', SalaryPayment::class, $payment->id, 'credit')) {
                return $payment;
            }

            $payment->finance_account_id = $data['finance_account_id'];
            $this->creditClientPayment($payment, $request);
            $payment->update([
                'status' => 'approved',
                'approved_at' => now(),
                'rejected_at' => null,
                'reject_reason' => null,
            ]);

            return $payment;
        });

        app(ActivityLogger::class)->log('Client Fund', 'Payment Approved', 'Client payment #' . $payment->id . ' approved.', request());

        return back()->with('success', 'Client payment approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment = DB::transaction(function () use ($id, $request) {
            $payment = SalaryPayment::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->reverseClientPaymentIfNeeded($payment, $request, 'Client payment rejected.');
            $payment->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'reject_reason' => $request->reject_reason,
            ]);

            return $payment;
        });

        app(ActivityLogger::class)->log('Client Fund', 'Payment Rejected', 'Client payment #' . $payment->id . ' rejected.', $request);

        return back()->with('success', 'Client payment rejected successfully.');
    }

    public function destroy(SalaryPayment $payment)
    {
        $description = 'Client payment #' . $payment->id . ' deleted.';
        DB::transaction(function () use ($payment) {
            $lockedPayment = SalaryPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->reverseClientPaymentIfNeeded($lockedPayment, request(), 'Client payment deleted.');
            $lockedPayment->delete();
        });

        app(ActivityLogger::class)->log('Client Fund', 'Payment Deleted', $description, request());

        return redirect('/admin/salary-payments')->with('success', 'Client payment record deleted successfully.');
    }

    private function creditClientPayment(SalaryPayment $payment, Request $request): void
    {
        $account = FinanceAccount::findOrFail($payment->finance_account_id);
        app(FinanceLedgerService::class)->credit($account, (float) $payment->amount, [
            'transaction_type' => 'client_payment',
            'currency' => 'BDT',
            'required_currency' => 'BDT',
            'reference_type' => SalaryPayment::class,
            'reference_id' => $payment->id,
            'ledger_date' => $payment->salary_month,
            'description' => 'Client payment received - ' . ($payment->client?->company_name ?: 'Client') . '.',
            'transaction_reference' => $payment->transaction_id,
            'created_by' => $request->user()?->id,
        ]);
    }

    private function reverseClientPaymentIfNeeded(SalaryPayment $payment, Request $request, string $description): void
    {
        $ledger = FinanceAccountLedger::query()
            ->where('transaction_type', 'client_payment')
            ->where('reference_type', SalaryPayment::class)
            ->where('reference_id', $payment->id)
            ->where('direction', 'credit')
            ->first();

        if (! $ledger || app(FinanceLedgerService::class)->hasEntry('client_payment_reversal', SalaryPayment::class, $payment->id, 'debit')) {
            return;
        }

        app(FinanceLedgerService::class)->reverse($ledger, [
            'transaction_type' => 'client_payment_reversal',
            'currency' => 'BDT',
            'required_currency' => 'BDT',
            'reference_type' => SalaryPayment::class,
            'reference_id' => $payment->id,
            'ledger_date' => now()->toDateString(),
            'description' => $description,
            'transaction_reference' => $payment->transaction_id,
            'created_by' => $request->user()?->id,
        ]);
    }
}
