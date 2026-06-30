<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Carbon\Carbon;

class AssignmentResolver
{
    public function current(Employee $employee, ?Carbon $date = null): ?EmployeeAssignment
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
            ->first();
    }
}
