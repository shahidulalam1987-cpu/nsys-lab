<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'client_id',
        'client_page_id',
        'shift_id',
        'assigned_from',
        'assigned_to',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'assigned_from' => 'date',
            'assigned_to' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function page()
    {
        return $this->belongsTo(ClientPage::class, 'client_page_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function statusLabel(): string
    {
        return $this->status === 'active' ? 'Active' : 'Inactive';
    }
}
