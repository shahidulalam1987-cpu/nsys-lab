<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('user');

        if ($request->search) {
            $query->where(function ($inner) use ($request) {
                $inner->where('employee_id', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('mobile', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $employees = $query->latest()->get();

        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        $users = User::where('role', 'employee')->orderBy('name')->get();

        return view('admin.employees.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedEmployee($request);
        $data['employee_id'] = $this->nextEmployeeId();
        $data['salary_type'] = 'monthly';
        $data['status'] = $data['status'] ?? 'probation';

        Employee::create($data);

        return redirect('/admin/employees')->with('success', 'Employee created successfully.');
    }

    public function show($id)
    {
        $employee = Employee::with(['user', 'assignments.client', 'salaryDays.client'])->findOrFail($id);
        $clients = Client::orderBy('company_name')->get();

        return view('admin.employees.show', compact('employee', 'clients'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $users = User::where('role', 'employee')
            ->where(function ($query) use ($employee) {
                $query->whereDoesntHave('employee')
                    ->orWhere('id', $employee->user_id);
            })
            ->orderBy('name')
            ->get();

        return view('admin.employees.edit', compact('employee', 'users'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $data = $this->validatedEmployee($request);
        $employee->update($data);

        return redirect('/admin/employees/' . $employee->id)->with('success', 'Employee updated successfully.');
    }

    public function confirm($id)
    {
        $employee = Employee::findOrFail($id);

        if (! $employee->isEligibleForConfirmation()) {
            return back()->with('success', 'Employee is not eligible for confirmation yet.');
        }

        $employee->update([
            'status' => 'active',
            'confirmation_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'Employee confirmed successfully.');
    }

    public function terminate(Employee $employee)
    {
        $employee->update([
            'status' => 'terminated',
            'last_working_date' => $employee->last_working_date ?: now()->toDateString(),
        ]);

        return redirect('/admin/employees/' . $employee->id)
            ->with('success', 'Employee terminated successfully.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->assignments()->exists()
            || $employee->salaryDays()->exists()
            || $employee->payrolls()->exists()) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee has history. Please terminate instead.');
        }

        $user = $employee->user;
        $employee->delete();

        if ($user && $user->role === 'employee') {
            $user->delete();
        }

        return redirect('/admin/employees')
            ->with('success', 'Employee deleted successfully.');
    }

    public function createLogin(Employee $employee)
    {
        if ($employee->user_id) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee already has a linked login.');
        }

        return view('admin.employees.create-login', compact('employee'));
    }

    public function storeLogin(Request $request, Employee $employee)
    {
        if ($employee->user_id) {
            return redirect('/admin/employees/' . $employee->id)
                ->with('success', 'This employee already has a linked login.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee->update([
            'user_id' => $user->id,
        ]);

        return redirect('/admin/employees/' . $employee->id)
            ->with('success', 'Employee login created and linked successfully.');
    }

    private function validatedEmployee(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'department' => ['required', Rule::in(Employee::DEPARTMENTS)],
            'role' => ['required', Rule::in(Employee::ROLES)],
            'joining_date' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'last_working_date' => ['nullable', 'date'],
            'status' => ['required', 'in:probation,active,on_leave,suspended,terminated'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'mobile_banking_info' => ['nullable', 'string'],
        ]);
    }

    private function nextEmployeeId(): string
    {
        $usedNumbers = Employee::where('employee_id', 'like', 'NSYS-EM-%')
            ->pluck('employee_id')
            ->map(function ($employeeId) {
                if (preg_match('/^NSYS-EM-(\d+)$/', $employeeId, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter()
            ->flip();

        $number = 1;
        while ($usedNumbers->has($number)) {
            $number++;
        }

        return 'NSYS-EM-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
