<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an administrative change in the database.
     *
     * @param string $action (e.g. 'create', 'update', 'delete', 'restore')
     * @param string $tableName
     * @param string $recordId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return AuditLog
     */
    public function log(
        string $action,
        string $tableName,
        string $recordId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => (string) $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'actor_name' => $user?->name ?? 'System',
            'actor_email' => $user?->email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
