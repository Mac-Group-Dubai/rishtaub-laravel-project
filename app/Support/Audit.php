<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class Audit
{
    // Writes one row to audit_logs for every admin change
    public static function log(
        Request $request,
        string $action,
        string $table,
        string $recordId,
        ?array $old = null,
        ?array $new = null
    ): void {
        AuditLog::create([
            'admin_id' => $request->user()?->id,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_data' => $old,
            'new_data' => $new,
            'ip_address' => $request->ip(),
        ]);
    }
}
