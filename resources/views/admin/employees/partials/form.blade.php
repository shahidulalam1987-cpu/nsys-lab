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

        <p>Department<br>
            <select name="department" required>
                <option value="">Select Department</option>
                @foreach(\App\Models\Employee::DEPARTMENTS as $department)
                    <option value="{{ $department }}" {{ old('department', $employee?->department) == $department ? 'selected' : '' }}>{{ $department }}</option>
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

        <h2>Banking / Mobile Banking Information</h2>

        <p>Bank Name<br><input type="text" name="bank_name" value="{{ old('bank_name', $employee?->bank_name) }}"></p>
        <p>Account Name<br><input type="text" name="account_name" value="{{ old('account_name', $employee?->account_name) }}"></p>
        <p>Account Number<br><input type="text" name="account_number" value="{{ old('account_number', $employee?->account_number) }}"></p>
        <p>Branch Name<br><input type="text" name="branch_name" value="{{ old('branch_name', $employee?->branch_name) }}"></p>
        <p>bKash Number<br><input type="text" name="bkash_number" value="{{ old('bkash_number', $employee?->bkash_number) }}"></p>
        <p>Nagad Number<br><input type="text" name="nagad_number" value="{{ old('nagad_number', $employee?->nagad_number) }}"></p>
        <p>Rocket Number<br><input type="text" name="rocket_number" value="{{ old('rocket_number', $employee?->rocket_number) }}"></p>
        <p>Preferred Payment Method<br>
            <select name="preferred_payment_method">
                <option value="">Select Payment Method</option>
                @foreach(['bank' => 'Bank', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'cash' => 'Cash'] as $value => $label)
                    <option value="{{ $value }}" {{ old('preferred_payment_method', $employee?->preferred_payment_method) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Mobile Banking Info<br><textarea name="mobile_banking_info">{{ old('mobile_banking_info', $employee?->mobile_banking_info) }}</textarea></p>

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
