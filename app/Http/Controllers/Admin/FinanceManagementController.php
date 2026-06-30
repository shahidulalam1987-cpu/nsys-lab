<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyExpense;
use App\Models\FinanceAccount;
use App\Models\FinanceLoan;
use App\Models\FinanceLoanRepayment;
use App\Models\EmployeePayroll;
use App\Models\FacebookCard;
use App\Models\BinancePurchase;
use App\Models\SalaryPayment;
use App\Services\FinanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceManagementController extends Controller
{
    public function dashboard()
    {
        return view('admin.finance.dashboard', [
            'summary' => $this->summary(),
            'accounts' => FinanceAccount::latest()->take(8)->get(),
            'loans' => FinanceLoan::latest()->take(8)->get(),
            'familyExpenses' => FamilyExpense::with('account')->latest('expense_date')->take(8)->get(),
        ]);
    }

    public function accounts()
    {
        return view('admin.finance.accounts', [
            'accounts' => FinanceAccount::latest()->get(),
            'types' => FinanceAccount::TYPES,
            'currencies' => FinanceAccount::CURRENCIES,
            'statuses' => FinanceAccount::STATUSES,
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
        $data = $this->validatedAccount($request);
        $newBalance = round((float) $data['current_balance'], 2);
        $currentBalance = round((float) $account->current_balance, 2);

        if ($newBalance !== $currentBalance) {
            $request->validate([
                'adjustment_reason' => ['required', 'string', 'max:1000'],
            ]);
        }

        DB::transaction(function () use ($account, $data, $newBalance, $request) {
            $lockedAccount = FinanceAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $previousBalance = round((float) $lockedAccount->current_balance, 2);
            $metadata = $data;
            unset($metadata['current_balance']);

            if ($newBalance !== $previousBalance && ! $request->filled('adjustment_reason')) {
                throw ValidationException::withMessages([
                    'adjustment_reason' => 'Balance adjustment reason is required.',
                ]);
            }

            $lockedAccount->update($metadata);

            if ($newBalance > $previousBalance) {
                app(FinanceLedgerService::class)->credit($lockedAccount, $newBalance - $previousBalance, [
                    'transaction_type' => 'manual_adjustment',
                    'currency' => $lockedAccount->currency,
                    'reference_type' => FinanceAccount::class,
                    'reference_id' => $lockedAccount->id,
                    'description' => $request->string('adjustment_reason')->toString(),
                    'transaction_reference' => 'finance-account:' . $lockedAccount->id,
                    'created_by' => $request->user()?->id,
                ]);
            } elseif ($newBalance < $previousBalance) {
                app(FinanceLedgerService::class)->debit($lockedAccount, $previousBalance - $newBalance, [
                    'transaction_type' => 'manual_adjustment',
                    'currency' => $lockedAccount->currency,
                    'reference_type' => FinanceAccount::class,
                    'reference_id' => $lockedAccount->id,
                    'description' => $request->string('adjustment_reason')->toString(),
                    'transaction_reference' => 'finance-account:' . $lockedAccount->id,
                    'created_by' => $request->user()?->id,
                    'allow_negative' => true,
                ]);
            }
        });

        return redirect('/admin/finance/accounts')->with('success', 'Finance account updated successfully.');
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

    public function loans()
    {
        return view('admin.finance.loans', [
            'loans' => FinanceLoan::with('repayments')->latest()->get(),
            'types' => FinanceLoan::TYPES,
            'accounts' => FinanceAccount::where('currency', 'BDT')->where('status', 'active')->orderBy('account_name')->get(),
        ]);
    }

    public function storeLoan(Request $request)
    {
        $data = $this->validatedLoan($request);
        $data['paid_amount'] = 0;

        DB::transaction(function () use ($data, $request) {
            $loan = FinanceLoan::create($data);
            $account = FinanceAccount::findOrFail($data['finance_account_id']);
            $context = $this->loanLedgerContext($loan, $request->user()?->id);

            $loan->loan_type === 'taken'
                ? app(FinanceLedgerService::class)->credit($account, (float) $loan->amount, $context)
                : app(FinanceLedgerService::class)->debit($account, (float) $loan->amount, $context);
        });

        return redirect('/admin/finance/loans')->with('success', 'Loan saved successfully.');
    }

    public function showLoan(FinanceLoan $loan)
    {
        return view('admin.finance.loan-show', [
            'loan' => $loan->load('repayments'),
            'accounts' => FinanceAccount::where('currency', 'BDT')->where('status', 'active')->orderBy('account_name')->get(),
        ]);
    }

    public function editLoan(FinanceLoan $loan)
    {
        return view('admin.finance.loan-edit', [
            'loan' => $loan,
            'types' => FinanceLoan::TYPES,
            'accounts' => FinanceAccount::where('currency', 'BDT')->where('status', 'active')->orderBy('account_name')->get(),
        ]);
    }

    public function updateLoan(Request $request, FinanceLoan $loan)
    {
        $data = $this->validatedLoan($request);

        if ($loan->finance_account_id
            && ((int) $loan->finance_account_id !== (int) $data['finance_account_id']
                || $loan->loan_type !== $data['loan_type']
                || round((float) $loan->amount, 2) !== round((float) $data['amount'], 2))) {
            throw ValidationException::withMessages([
                'amount' => 'Loan account, type, and amount cannot change after the finance transaction is recorded.',
            ]);
        }

        unset($data['paid_amount']);
        $loan->update($data);

        return redirect('/admin/finance/loans/' . $loan->id)->with('success', 'Loan updated successfully.');
    }

    public function destroyLoan(FinanceLoan $loan)
    {
        if ($loan->finance_account_id || $loan->repayments()->exists()) {
            return back()->withErrors(['loan' => 'Loan with finance history cannot be deleted.']);
        }

        $loan->delete();

        return redirect('/admin/finance/loans')->with('success', 'Loan deleted successfully.');
    }

    public function storeRepayment(Request $request, FinanceLoan $loan)
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'],
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ((float) $data['amount'] > (float) $loan->remaining_balance) {
            throw ValidationException::withMessages(['amount' => 'Repayment cannot exceed the remaining loan balance.']);
        }

        DB::transaction(function () use ($loan, $data, $request) {
            $lockedLoan = FinanceLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ((float) $data['amount'] > (float) $lockedLoan->remaining_balance) {
                throw ValidationException::withMessages(['amount' => 'Repayment cannot exceed the remaining loan balance.']);
            }

            $repayment = $lockedLoan->repayments()->create($data);
            $lockedLoan->paid_amount = (float) $lockedLoan->paid_amount + (float) $data['amount'];
            $lockedLoan->save();
            $account = FinanceAccount::findOrFail($data['finance_account_id']);
            $context = [
                'transaction_type' => 'loan_repayment',
                'currency' => 'BDT',
                'required_currency' => 'BDT',
                'reference_type' => FinanceLoanRepayment::class,
                'reference_id' => $repayment->id,
                'ledger_date' => $repayment->payment_date,
                'description' => 'Loan repayment - ' . $lockedLoan->person_company_name . '.',
                'transaction_reference' => 'loan-repayment:' . $repayment->id,
                'created_by' => $request->user()?->id,
            ];

            $lockedLoan->loan_type === 'taken'
                ? app(FinanceLedgerService::class)->debit($account, (float) $repayment->amount, $context)
                : app(FinanceLedgerService::class)->credit($account, (float) $repayment->amount, $context);
        });

        return redirect('/admin/finance/loans/' . $loan->id)->with('success', 'Repayment saved successfully.');
    }

    public function balanceSheet()
    {
        return view('admin.finance.balance-sheet', [
            'summary' => $this->summary(),
            'accounts' => FinanceAccount::orderBy('currency')->orderBy('account_type')->get(),
        ]);
    }

    public function loanReport()
    {
        return view('admin.finance.loan-report', [
            'summary' => $this->summary(),
            'loans' => FinanceLoan::latest()->get(),
        ]);
    }

    public function familyExpenses(Request $request)
    {
        $expenses = FamilyExpense::with('account')
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->date_to))
            ->when($request->filled('person'), fn ($query) => $query->where('person_name', 'like', '%' . $request->person . '%'))
            ->when($request->filled('category'), fn ($query) => $query->where('expense_category', $request->category))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', 'like', '%' . $request->payment_method . '%'))
            ->latest('expense_date')
            ->latest()
            ->get();

        return view('admin.finance.family-expenses', [
            'expenses' => $expenses,
            'accounts' => FinanceAccount::where('currency', 'BDT')->orderBy('account_name')->get(),
            'categories' => FamilyExpense::CATEGORIES,
            'summary' => $this->familyExpenseSummary(),
            'filters' => $request->only(['date_from', 'date_to', 'person', 'category', 'payment_method']),
        ]);
    }

    public function storeFamilyExpense(Request $request)
    {
        $data = $this->validatedFamilyExpense($request);

        DB::transaction(function () use ($data, $request) {
            $expense = FamilyExpense::create($data);
            $this->applyFamilyExpenseDeduction($expense, $request->user()?->id);
        });

        return redirect('/admin/finance/family-expenses')->with('success', 'Family expense saved successfully.');
    }

    public function editFamilyExpense(FamilyExpense $expense)
    {
        return view('admin.finance.family-expense-edit', [
            'expense' => $expense,
            'accounts' => FinanceAccount::where('currency', 'BDT')->orderBy('account_name')->get(),
            'categories' => FamilyExpense::CATEGORIES,
        ]);
    }

    public function updateFamilyExpense(Request $request, FamilyExpense $expense)
    {
        $data = $this->validatedFamilyExpense($request);

        DB::transaction(function () use ($expense, $data, $request) {
            $lockedExpense = FamilyExpense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $this->restoreFamilyExpenseDeduction($lockedExpense, $request->user()?->id, 'Family expense updated.');
            $lockedExpense->update($data);
            $this->applyFamilyExpenseDeduction($lockedExpense, $request->user()?->id);
        });

        return redirect('/admin/finance/family-expenses')->with('success', 'Family expense updated successfully.');
    }

    public function destroyFamilyExpense(FamilyExpense $expense)
    {
        DB::transaction(function () use ($expense) {
            $lockedExpense = FamilyExpense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $this->restoreFamilyExpenseDeduction($lockedExpense, auth()->id(), 'Family expense deleted.');
            $lockedExpense->delete();
        });

        return redirect('/admin/finance/family-expenses')->with('success', 'Family expense deleted successfully.');
    }

    public function familyExpenseReport()
    {
        $expenses = FamilyExpense::with('account')->latest('expense_date')->get();

        return view('admin.finance.family-expense-report', [
            'summary' => $this->familyExpenseSummary(),
            'personRows' => $expenses->groupBy('person_name')->map(fn ($rows, $person) => [
                'name' => $person,
                'total' => (float) $rows->sum('amount'),
                'count' => $rows->count(),
            ])->sortByDesc('total')->values(),
            'categoryRows' => $expenses->groupBy('expense_category')->map(fn ($rows, $category) => [
                'name' => FamilyExpense::CATEGORIES[$category] ?? ucwords(str_replace('_', ' ', (string) $category)),
                'total' => (float) $rows->sum('amount'),
                'count' => $rows->count(),
            ])->sortByDesc('total')->values(),
            'monthRows' => $expenses->groupBy(fn (FamilyExpense $expense) => $expense->expense_date?->format('Y-m') ?: '-')->map(fn ($rows, $month) => [
                'month' => $month,
                'total' => (float) $rows->sum('amount'),
                'count' => $rows->count(),
            ])->sortByDesc('month')->values(),
        ]);
    }

    public function reconciliationReport()
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
                ];
            });

        return view('admin.finance.reconciliation-report', [
            'rows' => $accounts,
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

    private function validatedLoan(Request $request): array
    {
        return $request->validate([
            'loan_type' => ['required', Rule::in(array_keys(FinanceLoan::TYPES))],
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'person_company_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'loan_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function validatedFamilyExpense(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'person_name' => ['required', 'string', 'max:255'],
            'relation' => ['nullable', 'string', 'max:255'],
            'expense_category' => ['required', Rule::in(array_keys(FamilyExpense::CATEGORIES))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'finance_account_id' => ['nullable', 'exists:finance_accounts,id'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function applyFamilyExpenseDeduction(FamilyExpense $expense, ?int $userId): void
    {
        if (! $expense->finance_account_id) {
            return;
        }

        $account = FinanceAccount::findOrFail($expense->finance_account_id);
        app(FinanceLedgerService::class)->debit($account, (float) $expense->amount, [
            'ledger_date' => $expense->expense_date,
            'transaction_type' => 'family_expense',
            'currency' => 'BDT',
            'required_currency' => 'BDT',
            'reference_type' => FamilyExpense::class,
            'reference_id' => $expense->id,
            'transaction_reference' => 'family-expense:' . $expense->id,
            'description' => $expense->note ?: 'Family expense payment.',
            'created_by' => $userId,
        ]);
    }

    private function restoreFamilyExpenseDeduction(FamilyExpense $expense, ?int $userId, string $note): void
    {
        if (! $expense->finance_account_id) {
            return;
        }

        $account = FinanceAccount::findOrFail($expense->finance_account_id);
        app(FinanceLedgerService::class)->credit($account, (float) $expense->amount, [
            'ledger_date' => now()->toDateString(),
            'transaction_type' => 'family_expense_reversal',
            'currency' => 'BDT',
            'required_currency' => 'BDT',
            'reference_type' => FamilyExpense::class,
            'reference_id' => $expense->id,
            'transaction_reference' => 'family-expense:' . $expense->id,
            'description' => $note,
            'created_by' => $userId,
        ]);
    }

    private function summary(): array
    {
        $accounts = FinanceAccount::all();
        $loans = FinanceLoan::all();
        $familyExpenseSummary = $this->familyExpenseSummary();

        return $familyExpenseSummary + [
            'total_finance_accounts' => $accounts->count(),
            'total_cash' => (float) $accounts->where('account_type', 'cash')->where('currency', 'BDT')->sum('current_balance'),
            'total_usd_assets' => (float) $accounts->where('currency', 'USD')->sum('current_balance')
                + (float) BinancePurchase::sum('remaining_usd')
                + (float) FacebookCard::sum('current_balance'),
            'total_bdt_balance' => (float) $accounts->where('currency', 'BDT')->sum('current_balance'),
            'total_usd_balance' => (float) $accounts->where('currency', 'USD')->sum('current_balance'),
            'total_loan_taken' => (float) $loans->where('loan_type', 'taken')->sum('amount'),
            'total_loan_given' => (float) $loans->where('loan_type', 'given')->sum('amount'),
            'total_remaining_payable' => (float) $loans->where('loan_type', 'taken')->sum('remaining_balance'),
            'total_remaining_receivable' => (float) $loans->where('loan_type', 'given')->sum('remaining_balance'),
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

    private function familyExpenseSummary(): array
    {
        $expenses = FamilyExpense::all();
        $monthExpenses = $expenses->filter(fn (FamilyExpense $expense) => $expense->expense_date?->format('Y-m') === now()->format('Y-m'));
        $topPerson = $expenses
            ->groupBy('person_name')
            ->map(fn ($rows) => (float) $rows->sum('amount'))
            ->sortDesc()
            ->take(1);

        return [
            'this_month_family_expense' => (float) $monthExpenses->sum('amount'),
            'total_family_expense' => (float) $expenses->sum('amount'),
            'medical_expense' => (float) $expenses->where('expense_category', 'medical')->sum('amount'),
            'emergency_expense' => (float) $expenses->where('expense_category', 'emergency')->sum('amount'),
            'top_person_expense_name' => $topPerson->keys()->first() ?: '-',
            'top_person_expense_amount' => (float) ($topPerson->first() ?? 0),
        ];
    }

    private function loanLedgerContext(FinanceLoan $loan, ?int $userId): array
    {
        return [
            'transaction_type' => $loan->loan_type === 'taken' ? 'loan_taken' : 'loan_given',
            'currency' => 'BDT',
            'required_currency' => 'BDT',
            'reference_type' => FinanceLoan::class,
            'reference_id' => $loan->id,
            'ledger_date' => $loan->loan_date,
            'description' => $loan->typeLabel() . ' - ' . $loan->person_company_name . '.',
            'transaction_reference' => 'loan:' . $loan->id,
            'created_by' => $userId,
        ];
    }
}
