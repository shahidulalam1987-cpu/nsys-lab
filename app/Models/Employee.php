<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public const STATUSES = [
        'active' => 'Active',
        'probation' => 'Probation',
        'on_leave' => 'On Leave',
        'inactive' => 'Inactive',
        'terminated' => 'Terminated',
    ];

    public const STATUS_FILTERS = [
        'active' => 'Active',
        'probation' => 'Probation',
        'on_leave' => 'On Leave',
        'inactive' => 'Inactive',
        'terminated' => 'Terminated',
    ];

    public const DEPARTMENTS = [
        'Moderator',
        'Customer Care',
        'Sales',
        'Creative',
        'Management',
        'Support',
    ];

    public const AGENCY_DEPARTMENTS = [
        'Administration',
        'Facebook Operations',
        'TikTok Operations',
        'Client Department',
        'Employee Department',
        'Finance',
        'HR',
        'Development',
        'Design',
        'Support',
        'Management',
    ];

    public const EMPLOYEE_TYPES = [
        'client_assigned' => 'Client Assigned',
        'agency_internal' => 'Agency Internal',
    ];

    public const SALARY_SOURCES = [
        'client_fund' => 'Client Fund',
        'agency_payroll' => 'Agency Payroll',
    ];

    public const PERMISSION_GROUPS = [
        'system_tools' => 'System Tools',
        'admin_dashboard' => 'Admin Dashboard',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'client_department' => 'Client Department',
        'employee_department' => 'Employee Department',
        'reports' => 'Reports',
        'finance' => 'Finance',
        'future_modules' => 'Future Modules',
    ];

    protected $fillable = [
        'user_id',
        'employee_type',
        'employee_id',
        'name',
        'mobile',
        'email',
        'address',
        'nid_number',
        'date_of_birth',
        'gender',
        'profile_photo',
        'nid_front_file',
        'nid_back_file',
        'cv_file',
        'appointment_letter_file',
        'agreement_file',
        'department',
        'department_id',
        'role',
        'role_id',
        'shift_id',
        'joining_date',
        'confirmation_date',
        'last_working_date',
        'status',
        'salary_type',
        'salary_day',
        'monthly_salary',
        'salary_source',
        'permission_group',
        'bank_name',
        'account_name',
        'account_number',
        'branch_name',
        'bkash_number',
        'nagad_number',
        'rocket_number',
        'preferred_payment_method',
        'mobile_banking_info',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'confirmation_date' => 'date',
            'last_working_date' => 'date',
            'date_of_birth' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->hasMany(EmployeeAssignment::class)->where('status', 'active');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function departmentRecord()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function departmentName(): string
    {
        return $this->departmentRecord?->name ?: ($this->department ?: '-');
    }

    public function roleRecord()
    {
        return $this->belongsTo(EmployeeRole::class, 'role_id');
    }

    public function roleName(): string
    {
        return $this->roleRecord?->name ?: ($this->role ?: '-');
    }

    public function salaryDays()
    {
        return $this->hasMany(SalaryDay::class);
    }

    public function payrolls()
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function workStatuses()
    {
        return $this->hasMany(EmployeeWorkStatus::class);
    }

    public function dailySubmissions()
    {
        return $this->hasMany(EmployeeDailySubmission::class);
    }

    public function bonusEarnings()
    {
        return $this->hasMany(EmployeeBonusEarning::class);
    }

    public function isEligibleForConfirmation(): bool
    {
        return $this->status === 'probation'
            && $this->confirmation_date === null
            && Carbon::parse($this->joining_date)->addDays(7)->lte(now());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    public function employeeTypeLabel(): string
    {
        return self::EMPLOYEE_TYPES[$this->employee_type ?: 'client_assigned'] ?? 'Client Assigned';
    }

    public function salarySourceLabel(): string
    {
        return self::SALARY_SOURCES[$this->salary_source ?: $this->defaultSalarySource()] ?? 'Client Fund';
    }

    public function permissionGroupLabel(): string
    {
        return $this->permission_group
            ? (self::PERMISSION_GROUPS[$this->permission_group] ?? ucwords(str_replace('_', ' ', $this->permission_group)))
            : '-';
    }

    public function isAgencyInternal(): bool
    {
        return $this->employee_type === 'agency_internal';
    }

    public function defaultSalarySource(): string
    {
        return $this->employee_type === 'agency_internal' ? 'agency_payroll' : 'client_fund';
    }

    public function shortStatusLabel(): string
    {
        return self::STATUS_FILTERS[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    public function nextSalaryDate(): ?Carbon
    {
        if (! $this->isSalaryEligible() || ! $this->salaryCycleDay()) {
            return null;
        }

        $today = now()->startOfDay();
        $date = $this->salaryDateForMonth($today->copy());

        if ($date->lt($today)) {
            $date = $this->salaryDateForMonth($today->copy()->addMonthNoOverflow());
        }

        return $date;
    }

    public function currentSalaryDueDate(?Carbon $today = null): ?Carbon
    {
        $today = ($today ?: now())->copy()->startOfDay();

        if (! $this->isSalaryEligible($today) || ! $this->salaryCycleDay()) {
            return null;
        }

        return $this->salaryDateForMonth($today);
    }

    public function salaryEligibilityDate(): ?Carbon
    {
        return $this->confirmation_date?->copy()->startOfDay();
    }

    public function isSalaryEligible(?Carbon $date = null): bool
    {
        $eligibilityDate = $this->salaryEligibilityDate();
        $selectedDate = ($date ?: now())->copy()->startOfDay();

        if (! $eligibilityDate || $eligibilityDate->gt($selectedDate)) {
            return false;
        }

        return ! ($this->status === 'terminated'
            && $this->last_working_date
            && $this->last_working_date->lt($eligibilityDate));
    }

    public function salaryCycleDay(): ?int
    {
        if (! $this->confirmation_date) {
            return null;
        }

        if ($this->salary_day) {
            return (int) $this->salary_day;
        }

        return $this->confirmation_date ? (int) $this->confirmation_date->format('j') : null;
    }

    public function salaryCycleStatus(?Carbon $today = null): string
    {
        if (! $this->isSalaryEligible($today)) {
            return 'not_salary_eligible';
        }

        if ($this->status === 'terminated') {
            return 'terminated';
        }

        $today = ($today ?: now())->copy()->startOfDay();
        $payroll = $this->payrollForSalaryCycle($this->currentSalaryDueDate($today));

        if ($payroll) {
            $paymentStatus = EmployeePayroll::statusFor((float) $payroll->payable_salary, (float) $payroll->paid_amount);

            if (in_array($paymentStatus, ['paid', 'partial'], true)) {
                return $paymentStatus;
            }
        }

        $dueDate = $this->currentSalaryDueDate($today);

        if (! $dueDate) {
            return $payroll?->calculated_status ?? 'upcoming';
        }

        if ($dueDate->lt($today)) {
            return 'unpaid';
        }

        return 'upcoming';
    }

    public function salaryStatusLabel(?Carbon $today = null): string
    {
        return [
            'upcoming' => 'Upcoming',
            'unpaid' => 'Unpaid',
            'partial' => 'Partially Paid',
            'paid' => 'Paid',
            'terminated' => 'Terminated',
            'not_salary_eligible' => 'Not Salary Eligible',
        ][$this->salaryCycleStatus($today)] ?? 'Upcoming';
    }

    public function salaryDateForMonth(Carbon $month): ?Carbon
    {
        if (! $this->salaryCycleDay()) {
            return null;
        }

        $cycleMonth = $month->copy()->startOfMonth();
        $eligibilityDate = $this->salaryEligibilityDate();

        do {
            $day = min($this->salaryCycleDay(), $cycleMonth->copy()->endOfMonth()->day);
            $salaryDate = $cycleMonth->copy()->addDays($day - 1);
            $cycleMonth->addMonthNoOverflow()->startOfMonth();
        } while ($eligibilityDate && $salaryDate->lte($eligibilityDate));

        return $salaryDate;
    }

    public function hasFinalSalaryPayroll(): bool
    {
        if ($this->status !== 'terminated' || ! $this->last_working_date) {
            return false;
        }

        $lastWorkingDate = $this->last_working_date;
        $payrolls = $this->relationLoaded('payrolls')
            ? $this->payrolls->filter(fn (EmployeePayroll $payroll) => $payroll->is_current || $payroll->is_current === null)
            : $this->payrolls()->current()->get();

        return $payrolls->contains(function (EmployeePayroll $payroll) use ($lastWorkingDate) {
            return $payroll->isFinalSettlementPayroll()
                || $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $lastWorkingDate->copy()->startOfMonth()->toDateString()
                || ($payroll->salary_period_from
                    && $payroll->salary_period_to
                    && $lastWorkingDate->betweenIncluded($payroll->salary_period_from, $payroll->salary_period_to));
        });
    }

    private function payrollForSalaryCycle(?Carbon $salaryDate): ?EmployeePayroll
    {
        if (! $salaryDate) {
            return null;
        }

        $payrolls = $this->relationLoaded('payrolls')
            ? $this->payrolls
            : $this->payrolls()->current()->get();

        return $payrolls
            ->each(fn (EmployeePayroll $payroll) => $payroll->setRelation('employee', $this))
            ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesSalaryCycleDate($salaryDate))
            ->sortByDesc('id')
            ->first();
    }
}
