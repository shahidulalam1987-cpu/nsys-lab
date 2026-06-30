<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssignmentResolver
{
    public function current(Employee $employee, ?Carbon $date = null): ?EmployeeAssignment
    {
        return $this->allCurrent($employee, $date)->first();
    }

    public function allCurrent(Employee $employee, ?Carbon $date = null): Collection
    {
        $date = ($date ?: now())->copy()->startOfDay();

        return $employee->assignments()
            ->with(['client', 'page', 'campaignRecord', 'shift'])
            ->where('status', 'active')
            ->whereDate('assigned_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $date->toDateString());
            })
            ->latest('assigned_from')
            ->latest('id')
            ->get();
    }
}
