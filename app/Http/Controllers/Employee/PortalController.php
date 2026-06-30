<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeNotice;
use App\Models\EmployeeNoticeRead;
use App\Services\AssignmentResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PortalController extends Controller
{
    public function salary()
    {
        $employee = auth()->user()
            ->employee()
            ->with(['payrolls.client'])
            ->firstOrFail();
        $payrolls = $employee->payrolls->sortByDesc('salary_month');
        $currentPayrolls = $payrolls->filter(fn ($payroll) => $payroll->is_current);
        $lastPayment = $payrolls
            ->filter(fn ($payroll) => (float) $payroll->paid_amount > 0)
            ->sortByDesc(fn ($payroll) => $payroll->payment_date ?: $payroll->paid_at ?: $payroll->created_at)
            ->first();
        $summary = [
            'total_generated' => (float) $currentPayrolls->sum('payable_salary'),
            'total_paid' => (float) $currentPayrolls->sum('paid_amount'),
            'current_due' => $currentPayrolls->sum(fn ($payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'last_payment_date' => $lastPayment?->payment_date ?: $lastPayment?->paid_at,
        ];

        return view('employee.salary', compact('employee', 'payrolls', 'summary'));
    }

    public function assignments()
    {
        $employee = auth()->user()
            ->employee()
            ->with(['assignments.client', 'assignments.page', 'assignments.shift'])
            ->firstOrFail();

        return view('employee.assignments', compact('employee'));
    }

    public function profile()
    {
        $employee = auth()->user()
            ->employee()
            ->with(['shift', 'assignments.client', 'assignments.page', 'assignments.shift'])
            ->firstOrFail();
        $primaryAssignment = app(AssignmentResolver::class)->current($employee);

        return view('employee.profile', compact('employee', 'primaryAssignment'));
    }

    public function notices()
    {
        $employee = auth()->user()->employee()->firstOrFail();
        $notices = EmployeeNotice::with('reads')
            ->latest('published_at')
            ->latest()
            ->get();
        $readNoticeIds = EmployeeNoticeRead::where('employee_id', $employee->id)
            ->pluck('employee_notice_id')
            ->all();

        return view('employee.notices', compact('employee', 'notices', 'readNoticeIds'));
    }

    public function markNoticeRead(EmployeeNotice $notice)
    {
        $employee = auth()->user()->employee()->firstOrFail();

        EmployeeNoticeRead::updateOrCreate([
            'employee_notice_id' => $notice->id,
            'employee_id' => $employee->id,
        ], [
            'read_at' => now(),
        ]);

        return back()->with('success', 'Notice marked as read.');
    }

    public function documents()
    {
        $employee = auth()->user()->employee()->firstOrFail();

        return view('employee.documents', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $employee = auth()->user()->employee()->firstOrFail();
        $data = $request->validate([
            'mobile' => ['nullable', 'string', 'max:50'],
        ]);

        $employee->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
