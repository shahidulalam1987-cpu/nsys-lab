<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $module,
        string $action,
        string $description,
        ?Request $request = null,
        mixed $oldValue = null,
        mixed $newValue = null
    ): void
    {
        $request ??= request();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'role_name' => Auth::user()?->primaryRoleName(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
