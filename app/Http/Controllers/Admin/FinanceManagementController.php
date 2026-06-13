<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyExpense;
use App\Models\FinanceAccount;
use App\Models\FinanceLoan;
use App\Models\FinanceLoanRepayment;
use App\Models\EmployeePayroll;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        FinanceAccount::create($this->validatedAccount($request));

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
        $account->update($this->validatedAccount($request));

        return redirect('/admin/finance/accounts')->with('success', 'Finance account updated successfully.');
    }

    public function destroyAccount(FinanceAccount $account)
    {
        $account->delete();

        return redirect('/admin/finance/accounts')->with('success', 'Finance account deleted successfully.');
    }

    public function loans()
    {
        return view('admin.finance.loans', [
            'loans' => FinanceLoan::with('repayments')->latest()->get(),
            'types' => FinanceLoan::TYPES,
        ]);
    }

    public function storeLoan(Request $request)
    {
        FinanceLoan::create($this->validatedLoan($request));

        return redirect('/admin/finance/loans')->with('success', 'Loan saved successfully.');
    }

    public function showLoan(FinanceLoan $loan)
    {
        return view('admin.finance.loan-show', [
            'loan' => $loan->load('repayments'),
        ]);
    }

    public function editLoan(FinanceLoan $loan)
    {
        return view('admin.finance.loan-edit', [
            'loan' => $loan,
            'types' => FinanceLoan::TYPES,
        ]);
    }

    public function updateLoan(Request $request, FinanceLoan $loan)
    {
        $loan->update($this->validatedLoan($request));

        return redirect('/admin/finance/loans/' . $loan->id)->with('success', 'Loan updated successfully.');
    }

    public function destroyLoan(FinanceLoan $loan)
    {
        $loan->delete();

        return redirect('/admin/finance/loans')->with('success', 'Loan deleted successfully.');
    }

    public function storeRepayment(Request $request, FinanceLoan $loan)
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $loan->repayments()->create($data);
        $loan->paid_amount = (float) $loan->paid_amount + (float) $data['amount'];
        $loan->save();

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
            'accounts' => FinanceAccount::orderBy('account_name')->get(),
            'categories' => FamilyExpense::CATEGORIES,
            'summary' => $this->familyExpenseSummary(),
            'filters' => $request->only(['date_from', 'date_to', 'person', 'category', 'payment_method']),
        ]);
    }

    public function storeFamilyExpense(Request $request)
    {
        $data = $this->validatedFamilyExpense($request);
        $expense = FamilyExpense::create($data);
        $this->applyFamilyExpenseDeduction($expense);

        return redirect('/admin/finance/family-expenses')->with('success', 'Family expense saved successfully.');
    }

    public function editFamilyExpense(FamilyExpense $expense)
    {
        return view('admin.finance.family-expense-edit', [
            'expense' => $expense,
            'accounts' => FinanceAccount::orderBy('account_name')->get(),
            'categories' => FamilyExpense::CATEGORIES,
        ]);
    }

    public function updateFamilyExpense(Request $request, FamilyExpense $expense)
    {
        $this->restoreFamilyExpenseDeduction($expense);
        $expense->update($this->validatedFamilyExpense($request));
        $this->applyFamilyExpenseDeduction($expense);

        return redirect('/admin/finance/family-expenses')->with('success', 'Family expense updated successfully.');
    }

    public function destroyFamilyExpense(FamilyExpense $expense)
    {
        $this->restoreFamilyExpenseDeduction($expense);
        $expense->delete();

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

    private function applyFamilyExpenseDeduction(FamilyExpense $expense): void
    {
        if ($expense->finance_account_id) {
            $expense->account?->decrement('current_balance', (float) $expense->amount);
        }
    }

    private function restoreFamilyExpenseDeduction(FamilyExpense $expense): void
    {
        if ($expense->finance_account_id) {
            $expense->account?->increment('current_balance', (float) $expense->amount);
        }
    }

    private function summary(): array
    {
        $accounts = FinanceAccount::all();
        $loans = FinanceLoan::all();
        $familyExpenseSummary = $this->familyExpenseSummary();

        return $familyExpenseSummary + [
            'total_bdt_balance' => (float) $accounts->where('currency', 'BDT')->sum('current_balance'),
            'total_usd_balance' => (float) $accounts->where('currency', 'USD')->sum('current_balance'),
            'total_loan_taken' => (float) $loans->where('loan_type', 'taken')->sum('amount'),
            'total_loan_given' => (float) $loans->where('loan_type', 'given')->sum('amount'),
            'total_remaining_payable' => (float) $loans->where('loan_type', 'taken')->sum('remaining_balance'),
            'total_remaining_receivable' => (float) $loans->where('loan_type', 'given')->sum('remaining_balance'),
            'salary_paid_this_month' => (float) EmployeePayroll::where('payroll_status', 'paid')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('paid_amount'),
            'salary_paid_today' => (float) EmployeePayroll::where('payroll_status', 'paid')
                ->whereDate('payment_date', now()->toDateString())
                ->sum('paid_amount'),
            'upcoming_salary_liability' => (float) EmployeePayroll::with('employee')
                ->get()
                ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('upcoming'))
                ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'largest_salary_payment' => (float) (EmployeePayroll::where('payroll_status', 'paid')->max('paid_amount') ?? 0),
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
}
