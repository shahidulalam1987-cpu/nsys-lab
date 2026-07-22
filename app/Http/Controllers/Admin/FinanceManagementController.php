<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\EmployeePayroll;
use App\Models\FacebookCard;
use App\Models\BinancePurchase;
use App\Models\SalaryPayment;
use App\Services\FinanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceManagementController extends Controller
{
    public function dashboard()
    {
        return view('admin.finance.dashboard', [
            'summary' => $this->summary(),
            'accounts' => FinanceAccount::latest()->take(8)->get(),
        ]);
    }

    public function accounts(Request $request)
    {
        $filters = $request->only(['account_type', 'currency', 'status']);

        $accounts = FinanceAccount::withCount('ledgers')
            ->when($filters['account_type'] ?? null, fn ($query, $type) => $query->where('account_type', $type))
            ->when($filters['currency'] ?? null, fn ($query, $currency) => $query->where('currency', $currency))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.finance.accounts', [
            'accounts' => $accounts,
            'types' => FinanceAccount::TYPES,
            'currencies' => FinanceAccount::CURRENCIES,
            'statuses' => FinanceAccount::STATUSES,
            'filters' => $filters,
            'summary' => [
                'total_accounts' => $accounts->count(),
                'active_accounts' => $accounts->where('status', 'active')->count(),
                'bdt_balance' => (float) $accounts->where('currency', 'BDT')->sum('current_balance'),
                'usd_balance' => (float) $accounts->where('currency', 'USD')->sum('current_balance'),
            ],
        ]);
    }

    public function storeAccount(Request $request)
    {
        $data = $this->validatedAccount($request);
        $openingBalance = round((float) $data['current_balance'], 2);
        $data['current_balance'] = 0;

        DB::transaction(function () use ($data, $openingBalance, $request) {
            $account = FinanceAccount::create($data);

            if ($openingBalance > 0) {
                app(FinanceLedgerService::class)->credit($account, $openingBalance, [
                    'transaction_type' => 'opening_balance',
                    'currency' => $account->currency,
                    'reference_type' => FinanceAccount::class,
                    'reference_id' => $account->id,
                    'description' => 'Opening balance for ' . $account->account_name . '.',
                    'transaction_reference' => 'finance-account:' . $account->id,
                    'created_by' => $request->user()?->id,
                ]);
            } elseif ($openingBalance < 0) {
                app(FinanceLedgerService::class)->debit($account, abs($openingBalance), [
                    'transaction_type' => 'opening_balance',
                    'currency' => $account->currency,
                    'reference_type' => FinanceAccount::class,
                    'reference_id' => $account->id,
                    'description' => 'Opening balance for ' . $account->account_name . '.',
                    'transaction_reference' => 'finance-account:' . $account->id,
                    'created_by' => $request->user()?->id,
                    'allow_negative' => true,
                ]);
            }
        });

        return redirect('/admin/finance/accounts')->with('success', 'Finance account saved successfully.');
    }

    public function editAccount(FinanceAccount $account)
    {
        return view('admin.finance.account-edit', [
            'account' => $account,
            'types' => FinanceAccount::TYPES,
            'currencies' => FinanceAccount::CURRENCIES,
            'statuses' => FinanceAccount::STATUSES,
        ]);
    }

    public function updateAccount(Request $request, FinanceAccount $account)
    {
        $data = $this->validatedAccountUpdate($request);

        $ledger = DB::transaction(function () use ($account, $data, $request) {
            $lockedAccount = FinanceAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $metadata = collect($data)->except(['adjustment_type', 'adjustment_amount', 'adjustment_reason'])->all();

            $lockedAccount->update($metadata);

            $context = [
                'transaction_type' => 'manual_adjustment',
                'currency' => $lockedAccount->currency,
                'reference_type' => FinanceAccount::class,
                'reference_id' => $lockedAccount->id,
                'description' => $data['adjustment_reason'],
                'transaction_reference' => 'finance-account:' . $lockedAccount->id,
                'created_by' => $request->user()?->id,
            ];

            return $data['adjustment_type'] === 'credit'
                ? app(FinanceLedgerService::class)->credit($lockedAccount, (float) $data['adjustment_amount'], $context)
                : app(FinanceLedgerService::class)->debit($lockedAccount, (float) $data['adjustment_amount'], array_merge($context, ['allow_negative' => true]));
        });

        $sign = $data['adjustment_type'] === 'credit' ? '+' : '-';

        return redirect('/admin/finance/accounts')->with('success', sprintf(
            'Finance Account Updated Successfully | Adjustment: %s%s %s | Ledger Created: Manual Adjustment | New Balance: %s %s',
            $sign,
            $ledger->currency,
            number_format((float) $data['adjustment_amount'], 2),
            $ledger->currency,
            number_format((float) $ledger->new_balance, 2)
        ));
    }

    public function destroyAccount(FinanceAccount $account)
    {
        if ($account->ledgers()->exists()) {
            return redirect('/admin/finance/accounts')
                ->withErrors(['account' => 'This finance account has transaction history and cannot be deleted.']);
        }

        $account->delete();

        return redirect('/admin/finance/accounts')->with('success', 'Finance account deleted successfully.');
    }

    public function balanceSheet(Request $request)
    {
        $filters = $request->only(['account_type', 'currency', 'status']);
        $accounts = FinanceAccount::query()
            ->when($filters['account_type'] ?? null, fn ($query, $type) => $query->where('account_type', $type))
            ->when($filters['currency'] ?? null, fn ($query, $currency) => $query->where('currency', $currency))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('currency')
            ->orderBy('account_type')
            ->get();

        return view('admin.finance.balance-sheet', [
            'summary' => $this->summary(),
            'reportSummary' => [
                'bdt_balance' => (float) $accounts->where('currency', 'BDT')->sum('current_balance'),
                'usd_balance' => (float) $accounts->where('currency', 'USD')->sum('current_balance'),
                'active_accounts' => $accounts->where('status', 'active')->count(),
                'inactive_accounts' => $accounts->where('status', 'inactive')->count(),
            ],
            'filters' => $filters,
            'types' => FinanceAccount::TYPES,
            'currencies' => FinanceAccount::CURRENCIES,
            'statuses' => FinanceAccount::STATUSES,
            'accounts' => $accounts,
        ]);
    }

    public function reconciliationReport(Request $request)
    {
        $accounts = FinanceAccount::with(['ledgers' => fn ($query) => $query->latest('id')])
            ->orderBy('account_name')
            ->get()
            ->map(function (FinanceAccount $account) {
                $latestLedger = $account->ledgers->first();
                $ledgerBalance = $latestLedger
                    ? (float) ($latestLedger->new_balance_snapshot ?? $latestLedger->new_balance)
                    : 0.0;
                $currentBalance = (float) $account->current_balance;

                return [
                    'account' => $account,
                    'current_balance' => $currentBalance,
                    'ledger_balance' => $ledgerBalance,
                    'difference' => round($currentBalance - $ledgerBalance, 2),
                    'last_ledger_at' => $latestLedger?->created_at,
                    'has_ledger' => (bool) $latestLedger,
                ];
            });

        $filter = $request->input('status', 'all');
        $rows = $filter === 'mismatch'
            ? $accounts->filter(fn ($row) => (float) $row['difference'] !== 0.0)->values()
            : $accounts;

        return view('admin.finance.reconciliation-report', [
            'rows' => $rows,
            'filter' => $filter,
            'summary' => [
                'total_accounts' => $accounts->count(),
                'matched' => $accounts->where('difference', 0)->count(),
                'mismatched' => $accounts->filter(fn ($row) => (float) $row['difference'] !== 0.0)->count(),
                'no_ledger' => $accounts->where('has_ledger', false)->count(),
            ],
            'mismatchCount' => $accounts->where('difference', '!=', 0)->count(),
        ]);
    }

    private function validatedAccount(Request $request): array
    {
        return $request->validate([
            'account_type' => ['required', Rule::in(array_keys(FinanceAccount::TYPES))],
            'account_name' => ['required', 'string', 'max:255'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(array_keys(FinanceAccount::CURRENCIES))],
            'current_balance' => ['required', 'numeric'],
            'status' => ['required', Rule::in(array_keys(FinanceAccount::STATUSES))],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function validatedAccountUpdate(Request $request): array
    {
        return $request->validate([
            'account_type' => ['required', Rule::in(array_keys(FinanceAccount::TYPES))],
            'account_name' => ['required', 'string', 'max:255'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(array_keys(FinanceAccount::CURRENCIES))],
            'status' => ['required', Rule::in(array_keys(FinanceAccount::STATUSES))],
            'note' => ['nullable', 'string'],
            'adjustment_type' => ['required', Rule::in(['credit', 'debit'])],
            'adjustment_amount' => ['required', 'numeric', 'gt:0'],
            'adjustment_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
    }

    private function summary(): array
    {
        $accounts = FinanceAccount::all();

        return [
            'total_finance_accounts' => $accounts->count(),
            'total_cash' => (float) $accounts->where('account_type', 'cash')->where('currency', 'BDT')->sum('current_balance'),
            'total_usd_assets' => (float) $accounts->where('currency', 'USD')->sum('current_balance')
                + (float) BinancePurchase::sum('remaining_usd')
                + (float) FacebookCard::sum('current_balance'),
            'total_bdt_balance' => (float) $accounts->where('currency', 'BDT')->sum('current_balance'),
            'total_usd_balance' => (float) $accounts->where('currency', 'USD')->sum('current_balance'),
            'salary_paid_this_month' => (float) EmployeePayroll::current()
                ->where('payroll_status', 'paid')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('paid_amount'),
            'salary_paid_today' => (float) EmployeePayroll::current()
                ->where('payroll_status', 'paid')
                ->whereDate('payment_date', now()->toDateString())
                ->sum('paid_amount'),
            'client_payments_this_month' => (float) SalaryPayment::where('status', 'approved')
                ->whereMonth('salary_month', now()->month)
                ->whereYear('salary_month', now()->year)
                ->sum('amount'),
            'upcoming_salary_liability' => (float) EmployeePayroll::current()
                ->with('employee')
                ->get()
                ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('upcoming'))
                ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'largest_salary_payment' => (float) (EmployeePayroll::current()->where('payroll_status', 'paid')->max('paid_amount') ?? 0),
        ];
    }

}
