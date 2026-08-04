<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        string  $action,
        ?object $entity      = null,
        ?array  $before      = null,
        ?array  $after       = null,
        ?string $description = null
    ): AuditLog {
        return AuditLog::create([
            'user_id'       => Auth::id(),
            'action'        => $action,
            'description'   => $description ?? $action,
            'auditable_type'=> $entity ? get_class($entity) : null,
            'auditable_id'  => $entity?->id ?? null,
            'before_json'   => $before,
            'after_json'    => $after,
            'changes_json'  => $after ? array_diff_assoc($after, $before ?? []) : null,
            'ip_address'    => Request::ip(),
            'user_agent'    => Request::userAgent(),
        ]);
    }
}
