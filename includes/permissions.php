<?php
declare(strict_types=1);

function permissions(): array
{
    static $items;
    if ($items !== null) return $items;
    $current = admin(); if (!$current) return $items = [];
    if ($current['role_name'] === 'Super Admin') return $items = ['*'];
    $stmt = db()->prepare('SELECT p.code FROM admin_permissions p JOIN admin_role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=?');
    $stmt->execute([$current['role_id']]); return $items = array_column($stmt->fetchAll(), 'code');
}
function can(string $permission): bool { $all = permissions(); return in_array('*', $all, true) || in_array($permission, $all, true); }
function require_permission(string $permission): void { require_admin(); if (!can($permission)) { http_response_code(403); exit('Forbidden'); } }

