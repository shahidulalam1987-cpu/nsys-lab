<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $module, string $action, string $description, ?Request $request = null): void
    {
        $request ??= request();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
