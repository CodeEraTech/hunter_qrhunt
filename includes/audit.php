<?php
declare(strict_types=1);

function audit_log(?int $adminId, string $action, string $module, ?string $recordId = null, mixed $old = null, mixed $new = null): void
{
    $stmt = db()->prepare('INSERT INTO audit_logs (admin_id,action,module,record_id,old_data,new_data,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$adminId, $action, $module, $recordId, $old === null ? null : safe_json($old), $new === null ? null : safe_json($new), client_ip(), user_agent()]);
}
