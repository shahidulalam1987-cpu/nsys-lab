<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceLoan;
use App\Models\FinanceLoanRepayment;
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

    private function summary(): array
    {
        $accounts = FinanceAccount::all();
        $loans = FinanceLoan::all();

        return [
            'total_bdt_balance' => (float) $accounts->where('currency', 'BDT')->sum('current_balance'),
            'total_usd_balance' => (float) $accounts->where('currency', 'USD')->sum('current_balance'),
            'total_loan_taken' => (float) $loans->where('loan_type', 'taken')->sum('amount'),
            'total_loan_given' => (float) $loans->where('loan_type', 'given')->sum('amount'),
            'total_remaining_payable' => (float) $loans->where('loan_type', 'taken')->sum('remaining_balance'),
            'total_remaining_receivable' => (float) $loans->where('loan_type', 'given')->sum('remaining_balance'),
        ];
    }
}
