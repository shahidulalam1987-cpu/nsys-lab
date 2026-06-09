<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeNotice extends Model
{
    public const CATEGORIES = [
        'general' => 'General',
        'salary' => 'Salary',
        'client' => 'Client',
        'emergency' => 'Emergency',
    ];

    protected $fillable = [
        'title',
        'category',
        'description',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function reads()
    {
        return $this->hasMany(EmployeeNoticeRead::class);
    }
}
