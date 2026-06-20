<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

   protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (! Schema::hasTable('roles') || ! Schema::hasTable('user_roles')) {
                return;
            }

            $roleSlug = match ($user->role) {
                'admin' => 'super_admin',
                'client' => 'client',
                'employee' => 'employee',
                default => null,
            };

            if ($roleSlug && $roleId = Role::where('slug', $roleSlug)->value('id')) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }
        });
    }

    public function client()
    {
        return $this->hasOne(\App\Models\Client::class);
    }

    public function employee()
    {
        return $this->hasOne(\App\Models\Employee::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function hasRole(string $role): bool
    {
        $role = str($role)->slug('_')->toString();

        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        $roles = collect($roles)->map(fn ($role) => str($role)->slug('_')->toString());

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function isSuperAdmin(): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->role === 'admin' && ! $this->roles()->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('key', $permission))
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->isSuperAdmin() || collect($permissions)->contains(fn ($permission) => $this->hasPermission($permission));
    }

    public function primaryRoleName(): string
    {
        return $this->roles()->orderBy('roles.id')->value('name')
            ?: match ($this->role) {
                'admin' => 'Super Admin',
                'client' => 'Client',
                'employee' => 'Employee',
                default => ucfirst((string) $this->role),
            };
    }
}
