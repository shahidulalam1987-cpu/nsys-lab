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
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf

        <h2>Basic Information</h2>

        <p>Employee ID<br>
            @if($employee)
                <input type="text" value="{{ $employee->employee_id }}" readonly>
            @else
                <input type="text" value="Auto Generated" readonly>
            @endif
        </p>
        <p>Full Name<br><input type="text" name="name" value="{{ old('name', $employee?->name) }}" required></p>
        <p>Mobile<br><input type="text" name="mobile" value="{{ old('mobile', $employee?->mobile) }}"></p>
        <p>Email<br><input type="email" name="email" value="{{ old('email', $employee?->email) }}"></p>
        <p>Address<br><textarea name="address">{{ old('address', $employee?->address) }}</textarea></p>
        <p>NID Number<br><input type="text" name="nid_number" value="{{ old('nid_number', $employee?->nid_number) }}"></p>
        <p>Date of Birth<br><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee?->date_of_birth?->toDateString()) }}"></p>
        <p>Gender<br>
            <select name="gender">
                <option value="">Select Gender</option>
                @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" {{ old('gender', $employee?->gender) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Profile Photo<br>
            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            @if($employee?->profile_photo)
                <br><a href="{{ \Illuminate\Support\Facades\Storage::url($employee->profile_photo) }}" target="_blank">View current photo</a>
            @endif
        </p>

        <h2>Login Information</h2>

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

        <h2>Employment Information</h2>

        <p>Employee Type<br>
            <select name="employee_type" id="employee_type" required>
                @foreach(\App\Models\Employee::EMPLOYEE_TYPES as $value => $label)
                    <option value="{{ $value }}" {{ old('employee_type', $employee?->employee_type ?? 'client_assigned') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Department<br>
            <select name="department_id" required>
                <option value="">Select Department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ (int) old('department_id', $employee?->department_id) === $department->id ? 'selected' : '' }}>
                        {{ $department->name }}{{ $department->status === 'inactive' ? ' (Inactive)' : '' }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>Role<br>
            <select name="role" required>
                <option value="">Select Role</option>
                @foreach(\App\Models\Employee::ROLES as $role)
                    <option value="{{ $role }}" {{ old('role', $employee?->role) == $role ? 'selected' : '' }}>{{ $role }}</option>
                @endforeach
            </select>
        </p>
        <p>Salary Source<br>
            <select name="salary_source" id="salary_source">
                @foreach(\App\Models\Employee::SALARY_SOURCES as $value => $label)
                    <option value="{{ $value }}" {{ old('salary_source', $employee?->salary_source ?? 'client_fund') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Permission Group<br>
            <select name="permission_group">
                <option value="">No Extra Access</option>
                @foreach(\App\Models\Employee::PERMISSION_GROUPS as $value => $label)
                    <option value="{{ $value }}" {{ old('permission_group', $employee?->permission_group) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Shift<br>
            <select name="shift_id">
                <option value="">No Default Shift</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" {{ old('shift_id', $employee?->shift_id) == $shift->id ? 'selected' : '' }}>
                        {{ $shift->name }}: {{ $shift->timeRange() }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>Joining Date<br><input type="date" name="joining_date" value="{{ old('joining_date', $employee?->joining_date?->toDateString()) }}" required></p>
        <p>Confirmation Date<br><input type="date" name="confirmation_date" value="{{ old('confirmation_date', $employee?->confirmation_date?->toDateString()) }}"></p>
        <p>Last Working Date<br><input type="date" name="last_working_date" value="{{ old('last_working_date', $employee?->last_working_date?->toDateString()) }}"></p>
        <p>Salary Cycle Day<br><input type="number" min="1" max="31" name="salary_day" value="{{ old('salary_day', $employee?->salary_day) }}" placeholder="Auto from confirmation date if blank"></p>
        <p>Status<br>
            <select name="status" required>
                @foreach(\App\Models\Employee::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $employee?->status ?? 'probation') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Monthly Salary<br><input type="number" step="0.01" name="monthly_salary" value="{{ old('monthly_salary', $employee?->monthly_salary ?? 0) }}" required></p>

        <h2>Banking Information</h2>

        <p>Bank Name<br><input type="text" name="bank_name" value="{{ old('bank_name', $employee?->bank_name) }}"></p>
        <p>Account Name<br><input type="text" name="account_name" value="{{ old('account_name', $employee?->account_name) }}"></p>
        <p>Account Number<br><input type="text" name="account_number" value="{{ old('account_number', $employee?->account_number) }}"></p>
        <p>Branch Name<br><input type="text" name="branch_name" value="{{ old('branch_name', $employee?->branch_name) }}"></p>

        <h2>Employee Documents</h2>
        <p>NID Front<br>
            <input type="file" name="nid_front_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            @if($employee?->nid_front_file)
                <br><a href="{{ \Illuminate\Support\Facades\Storage::url($employee->nid_front_file) }}" target="_blank">View current file</a>
            @endif
        </p>
        <p>NID Back<br>
            <input type="file" name="nid_back_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            @if($employee?->nid_back_file)
                <br><a href="{{ \Illuminate\Support\Facades\Storage::url($employee->nid_back_file) }}" target="_blank">View current file</a>
            @endif
        </p>
        <p>CV<br>
            <input type="file" name="cv_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp">
            @if($employee?->cv_file)
                <br><a href="{{ \Illuminate\Support\Facades\Storage::url($employee->cv_file) }}" target="_blank">View current file</a>
            @endif
        </p>
        <p>Appointment Letter<br>
            <input type="file" name="appointment_letter_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp">
            @if($employee?->appointment_letter_file)
                <br><a href="{{ \Illuminate\Support\Facades\Storage::url($employee->appointment_letter_file) }}" target="_blank">View current file</a>
            @endif
        </p>
        <p>Agreement<br>
            <input type="file" name="agreement_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp">
            @if($employee?->agreement_file)
                <br><a href="{{ \Illuminate\Support\Facades\Storage::url($employee->agreement_file) }}" target="_blank">View current file</a>
            @endif
        </p>

        <h2>Admin Notes</h2>
        <p>Admin Note<br><textarea name="admin_note">{{ old('admin_note', $employee?->admin_note) }}</textarea></p>

        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>

<script>
    const employeeTypeSelect = document.getElementById('employee_type');
    const salarySourceSelect = document.getElementById('salary_source');

    employeeTypeSelect?.addEventListener('change', () => {
        if (employeeTypeSelect.value === 'agency_internal') {
            salarySourceSelect.value = 'agency_payroll';
        } else {
            salarySourceSelect.value = 'client_fund';
        }
    });
</script>
