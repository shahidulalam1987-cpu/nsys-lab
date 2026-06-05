@if ($errors->any())
    <div class="card" style="color:#ef4444; margin-top:20px;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="margin-top:20px;">
    <form method="POST" action="{{ $action }}">
        @csrf

        <p>Login User<br>
            <select name="user_id">
                <option value="">No Login User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $employee?->user_id) == $user->id ? 'selected' : '' }}>
                        #{{ $user->id }} - {{ $user->name }} - {{ $user->email }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>Employee ID<br><input type="text" name="employee_id" value="{{ old('employee_id', $employee?->employee_id) }}" required></p>
        <p>Name<br><input type="text" name="name" value="{{ old('name', $employee?->name) }}" required></p>
        <p>Mobile<br><input type="text" name="mobile" value="{{ old('mobile', $employee?->mobile) }}"></p>
        <p>Department<br><input type="text" name="department" value="{{ old('department', $employee?->department) }}" required></p>
        <p>Role<br><input type="text" name="role" value="{{ old('role', $employee?->role) }}" required></p>
        <p>Joining Date<br><input type="date" name="joining_date" value="{{ old('joining_date', $employee?->joining_date?->toDateString()) }}" required></p>
        <p>Confirmation Date<br><input type="date" name="confirmation_date" value="{{ old('confirmation_date', $employee?->confirmation_date?->toDateString()) }}"></p>
        <p>Last Working Date<br><input type="date" name="last_working_date" value="{{ old('last_working_date', $employee?->last_working_date?->toDateString()) }}"></p>
        <p>Status<br>
            <select name="status" required>
                @foreach(['probation' => 'Probation', 'active' => 'Active', 'on_leave' => 'On Leave', 'suspended' => 'Suspended', 'terminated' => 'Terminated'] as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $employee?->status ?? 'probation') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Monthly Salary<br><input type="number" step="0.01" name="monthly_salary" value="{{ old('monthly_salary', $employee?->monthly_salary ?? 0) }}" required></p>
        <p>Bank Name<br><input type="text" name="bank_name" value="{{ old('bank_name', $employee?->bank_name) }}"></p>
        <p>Account Name<br><input type="text" name="account_name" value="{{ old('account_name', $employee?->account_name) }}"></p>
        <p>Account Number<br><input type="text" name="account_number" value="{{ old('account_number', $employee?->account_number) }}"></p>
        <p>Mobile Banking Info<br><textarea name="mobile_banking_info">{{ old('mobile_banking_info', $employee?->mobile_banking_info) }}</textarea></p>

        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>
