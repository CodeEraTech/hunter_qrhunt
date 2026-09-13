<?php
declare(strict_types=1);

/**
 * Product Management — Backend Functions
 * Single source of truth for all product-related operations
 */

function product_categories(array $filters = []): array
{
    $pdo = db();
    $where = [];
    $params = [];

    if (!empty($filters['status']) && in_array($filters['status'], ['ACTIVE', 'INACTIVE'], true)) {
        $where[] = 'status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(name LIKE ? OR code LIKE ?)';
        $params[] = "%{$filters['q']}%";
        $params[] = "%{$filters['q']}%";
    }

    $sql = 'SELECT pc.*, 
        (SELECT COUNT(*) FROM vendor_products vp WHERE vp.category_id = pc.id) as product_count,
        (SELECT COUNT(DISTINCT vp.vendor_id) FROM vendor_products vp WHERE vp.category_id = pc.id) as vendor_count
        FROM product_categories pc';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY sort_order, name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function product_category_get(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM product_categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function product_category_create(array $data, int $adminId): int
{
    $pdo = db();
    $pdo->prepare('INSERT INTO product_categories (name, code, icon_url, description, status, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$data['name'], $data['code'], $data['icon_url'] ?? null, $data['description'] ?? null, $data['status'] ?? 'ACTIVE', $data['sort_order'] ?? 0, $adminId]);
    return (int) $pdo->lastInsertId();
}

function product_category_update(int $id, array $data): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE product_categories SET name=?, code=?, icon_url=?, description=?, status=?, sort_order=?, updated_at=NOW() WHERE id=?');
    return $stmt->execute([$data['name'], $data['code'], $data['icon_url'] ?? null, $data['description'] ?? null, $data['status'] ?? 'ACTIVE', $data['sort_order'] ?? 0, $id]);
}

function product_category_delete(int $id): bool
{
    $pdo = db();
    // Check for products using this category
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM vendor_products WHERE category_id = ?');
    $cnt->execute([$id]);
    if ((int) $cnt->fetchColumn() > 0) {
        throw new RuntimeException('Cannot delete category: products are using it.');
    }
    return $pdo->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$id]);
}

function product_stats(): array
{
    $pdo = db();
    $row = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(status='APPROVED') as active,
        SUM(status IN ('PENDING_REVIEW','RESUBMITTED')) as pending,
        SUM(status='REJECTED') as rejected,
        SUM(status='DISABLED') as disabled,
        SUM(CASE WHEN JSON_EXTRACT(metadata, '$.stock') IS NOT NULL AND CAST(JSON_EXTRACT(metadata, '$.stock') AS UNSIGNED) = 0 THEN 1 ELSE 0 END) as out_of_stock,
        COUNT(DISTINCT vendor_id) as vendors_with_products
        FROM vendor_products")->fetch();
    return $row ?: [];
}

function product_recent(int $limit = 10): array
{
    $stmt = db()->prepare("SELECT vp.*, v.vendor_code, v.shop_name FROM vendor_products vp JOIN vendors v ON v.id = vp.vendor_id ORDER BY vp.submitted_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function product_categories_summary(): array
{
    $stmt = db()->query("SELECT pc.name, pc.code, COUNT(vp.id) as product_count FROM product_categories pc LEFT JOIN vendor_products vp ON vp.category_id = pc.id GROUP BY pc.id ORDER BY product_count DESC LIMIT 10");
    return $stmt->fetchAll();
}

function products_list(array $filters = [], int $page = 1, int $perPage = 20): array
{
    $pdo = db();
    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(vp.product_code LIKE ? OR vp.title LIKE ? OR v.name LIKE ? OR v.shop_name LIKE ?)';
        $term = "%{$filters['q']}%";
        array_push($params, $term, $term, $term, $term);
    }
    if (!empty($filters['status']) && in_array($filters['status'], ['PENDING_REVIEW','APPROVED','REJECTED','DISABLED','RESUBMITTED'], true)) {
        $where[] = 'vp.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['category_id'])) {
        $where[] = 'vp.category_id = ?';
        $params[] = (int) $filters['category_id'];
    }
    if (!empty($filters['vendor_id'])) {
        $where[] = 'vp.vendor_id = ?';
        $params[] = (int) $filters['vendor_id'];
    }
    if (!empty($filters['qoin_enabled'])) {
        $where[] = 'JSON_EXTRACT(vp.metadata, "$.qoin_enabled") = true';
    }

    $condition = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countSql = "SELECT COUNT(*) FROM vendor_products vp JOIN vendors v ON v.id = vp.vendor_id $condition";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Data
    $sql = "SELECT vp.*, v.vendor_code, v.name vendor_name, v.shop_name, pc.name category_name, pc.code category_code
            FROM vendor_products vp 
            JOIN vendors v ON v.id = vp.vendor_id
            LEFT JOIN product_categories pc ON pc.id = vp.category_id
            $condition 
            ORDER BY vp.submitted_at DESC, vp.id DESC 
            LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = ($page - 1) * $perPage;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int) ceil($total / $perPage))];
}

function product_get(int $id): ?array
{
    $stmt = db()->prepare("SELECT vp.*, v.vendor_code, v.name vendor_name, v.shop_name, pc.name category_name FROM vendor_products vp JOIN vendors v ON v.id = vp.vendor_id LEFT JOIN product_categories pc ON pc.id = vp.category_id WHERE vp.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function product_get_by_code(string $code, int $vendorId = 0): ?array
{
    $sql = 'SELECT * FROM vendor_products WHERE product_code = ?';
    $params = [$code];
    if ($vendorId > 0) {
        $sql .= ' AND vendor_id = ?';
        $params[] = $vendorId;
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

function product_create(array $data, int $vendorId, int $adminId = 0): int
{
    $pdo = db();
    $metadata = [
        'category' => $data['category'] ?? null,
        'mrp' => $data['mrp'] !== '' ? (float) $data['mrp'] : null,
        'stock' => $data['stock'] !== '' ? (int) $data['stock'] : null,
        'min_quantity' => $data['min_quantity'] !== '' ? (int) $data['min_quantity'] : null,
        'qoin_enabled' => isset($data['qoin_enabled']) && $data['qoin_enabled'] === '1',
        'qoin_value' => $data['qoin_value'] !== '' ? (float) $data['qoin_value'] : null,
    ];
    $metadata = array_filter($metadata, static fn($v) => $v !== null);

    $pdo->prepare('INSERT INTO vendor_products (vendor_id, category_id, product_code, title, price, metadata, image_url, description, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())')
        ->execute([
            $vendorId,
            $data['category_id'] ?? null,
            $data['product_code'],
            $data['title'],
            $data['price'],
            safe_json($metadata),
            $data['image_url'] ?? null,
            $data['description'] ?? null,
            'PENDING_REVIEW'
        ]);
    $productId = (int) $pdo->lastInsertId();

    // Audit log
    product_audit_log($productId, $data['product_code'], $vendorId, 'CREATE', null, null, safe_json($metadata), 'Product submitted for approval', $adminId, 0);

    return $productId;
}

function product_update(int $id, array $data, int $adminId = 0): bool
{
    $pdo = db();
    $old = product_get($id);
    if (!$old) return false;

    $metadata = $old['metadata'] ? json_decode((string) $old['metadata'], true) : [];
    $metadata = array_merge($metadata, [
        'category' => $data['category'] ?? ($metadata['category'] ?? null),
        'mrp' => $data['mrp'] !== '' ? (float) $data['mrp'] : ($metadata['mrp'] ?? null),
        'stock' => $data['stock'] !== '' ? (int) $data['stock'] : ($metadata['stock'] ?? null),
        'min_quantity' => $data['min_quantity'] !== '' ? (int) $data['min_quantity'] : ($metadata['min_quantity'] ?? null),
        'qoin_enabled' => isset($data['qoin_enabled']) && $data['qoin_enabled'] === '1',
        'qoin_value' => $data['qoin_value'] !== '' ? (float) $data['qoin_value'] : ($metadata['qoin_value'] ?? null),
    ]);
    $metadata = array_filter($metadata, static fn($v) => $v !== null);

    $changes = [];
    foreach (['title', 'price', 'description', 'image_url', 'category_id'] as $field) {
        if (isset($data[$field]) && $data[$field] !== ($old[$field] ?? null)) {
            $changes[$field] = ['old' => $old[$field] ?? null, 'new' => $data[$field]];
        }
    }
    // Metadata changes
    if (safe_json($metadata) !== $old['metadata']) {
        $changes['metadata'] = ['old' => $old['metadata'], 'new' => safe_json($metadata)];
    }

    $pdo->prepare('UPDATE vendor_products SET title=?, price=?, metadata=?, image_url=?, description=?, category_id=?, status="PENDING_REVIEW", updated_at=NOW() WHERE id=?')
        ->execute([$data['title'], $data['price'], safe_json($metadata), $data['image_url'] ?? $old['image_url'], $data['description'] ?? $old['description'], $data['category_id'] ?? $old['category_id'] ?? null, $id]);

    // Audit log
    foreach ($changes as $field => $change) {
        product_audit_log($id, $old['product_code'], $old['vendor_id'], 'UPDATE', $field, $change['old'] ?? null, $change['new'] ?? null, 'Product updated and resubmitted for review', $adminId, 0);
    }

    return true;
}

function product_moderate(array $ids, string $action, ?string $note, int $adminId): int
{
    if (!in_array($action, ['APPROVE', 'REJECT', 'DISABLE'], true)) return 0;
    $status = ['APPROVE' => 'APPROVED', 'REJECT' => 'REJECTED', 'DISABLE' => 'DISABLED'][$action];

    $pdo = db();
    $in = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$status, $note ?? null, $adminId], $ids);
    $stmt = $pdo->prepare("UPDATE vendor_products SET status=?, admin_note=?, moderated_by=?, moderated_at=NOW() WHERE status IN ('PENDING_REVIEW','RESUBMITTED') AND id IN ($in)");
    $stmt->execute($params);
    $count = (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();

    foreach ($ids as $pid) {
        $p = product_get($pid);
        if ($p) {
            product_audit_log($pid, $p['product_code'], $p['vendor_id'], 'MODERATE_' . $action, 'status', $p['status'], $status, $note, $adminId, 0);
        }
    }
    return $count;
}

function product_audit_log(int $productId, string $productCode, int $vendorId, string $action, ?string $field = null, $oldVal = null, $newVal = null, ?string $reason = null, int $adminId = 0, int $userId = 0): void
{
    db()->prepare('INSERT INTO product_audit_log (product_id, product_code, vendor_id, action, field_name, old_value, new_value, reason, admin_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $productId, $productCode, $vendorId, $action, $field,
            $oldVal !== null ? (is_scalar($oldVal) ? (string) $oldVal : safe_json($oldVal)) : null,
            $newVal !== null ? (is_scalar($newVal) ? (string) $newVal : safe_json($newVal)) : null,
            $reason,
            $adminId ?: null,
            $userId ?: null,
            client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
}

function product_audit_history(int $productId, int $page = 1, int $perPage = 20): array
{
    $pdo = db();
    $total = (int) $pdo->prepare('SELECT COUNT(*) FROM product_audit_log WHERE product_id = ?')->execute([$productId]) ? $pdo->query('SELECT FOUND_ROWS()')->fetchColumn() : 0;
    // Simplified count
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM product_audit_log WHERE product_id = ?');
    $totalStmt->execute([$productId]);
    $total = (int) $totalStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT pal.*, a.name admin_name, u.name user_name FROM product_audit_log pal LEFT JOIN admins a ON a.id = pal.admin_id LEFT JOIN users u ON u.id = pal.user_id WHERE pal.product_id = ? ORDER BY pal.created_at DESC, pal.id DESC LIMIT ? OFFSET ?");
    $stmt->execute([$productId, $perPage, ($page - 1) * $perPage]);
    $rows = $stmt->fetchAll();

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int) ceil($total / $perPage))];
}

function product_qr_get(int $productId): ?array
{
    $stmt = db()->prepare('SELECT * FROM product_qr_codes WHERE product_id = ? AND status = "ACTIVE" ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$productId]);
    return $stmt->fetch() ?: null;
}

function product_qr_create(int $productId, string $productCode, int $vendorId, int $adminId = 0): string
{
    $qrCode = 'QR-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $payload = safe_json(['product_code' => $productCode]);
    db()->prepare('INSERT INTO product_qr_codes (qr_code, vendor_id, product_id, product_code, payload, generated_by) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$qrCode, $vendorId, $productId, $productCode, $payload, $adminId]);
    product_audit_log($productId, $productCode, $vendorId, 'QR_GENERATE', 'qr_code', null, $qrCode, 'QR code generated', $adminId, 0);
    return $qrCode;
}

function product_qr_regenerate(int $qrId, int $adminId): bool
{
    $pdo = db();
    $old = $pdo->prepare('SELECT * FROM product_qr_codes WHERE id = ?')->execute([$qrId]) ? $pdo->query('SELECT * FROM product_qr_codes WHERE id = ' . $qrId)->fetch() : null;
    if (!$old) return false;

    $newCode = 'QR-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $pdo->prepare('UPDATE product_qr_codes SET qr_code=?, status="REGENERATED", regenerated_at=NOW(), regenerated_by=? WHERE id=?')
        ->execute([$newCode, $adminId, $qrId]);
    $pdo->prepare('INSERT INTO product_qr_codes (qr_code, vendor_id, product_id, product_code, payload, generated_by) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$newCode, $old['vendor_id'], $old['product_id'], $old['product_code'], $old['payload'], $adminId]);

    product_audit_log($old['product_id'], $old['product_code'], $old['vendor_id'], 'QR_REGENERATE', 'qr_code', $old['qr_code'], $newCode, 'QR code regenerated', $adminId, 0);
    return true;
}

function product_qr_list(array $filters = [], int $page = 1, int $perPage = 20): array
{
    $pdo = db();
    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(pqc.qr_code LIKE ? OR pqc.product_code LIKE ? OR vp.title LIKE ?)';
        $term = "%{$filters['q']}%";
        array_push($params, $term, $term, $term);
    }
    if (!empty($filters['vendor_id'])) {
        $where[] = 'pqc.vendor_id = ?';
        $params[] = (int) $filters['vendor_id'];
    }
    if (!empty($filters['status']) && in_array($filters['status'], ['ACTIVE','REGENERATED','ARCHIVED'], true)) {
        $where[] = 'pqc.status = ?';
        $params[] = $filters['status'];
    }

    $condition = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM product_qr_codes pqc JOIN vendor_products vp ON vp.id = pqc.product_id $condition");
    $totalStmt->execute($params);
    $total = (int) $totalStmt->fetchColumn();

    $sql = "SELECT pqc.*, vp.title, v.vendor_code, v.shop_name FROM product_qr_codes pqc JOIN vendor_products vp ON vp.id = pqc.product_id JOIN vendors v ON v.id = pqc.vendor_id $condition ORDER BY pqc.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = ($page - 1) * $perPage;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int) ceil($total / $perPage))];
}

function product_bulk_upload_batch(int $vendorId, int $adminId, string $filename): int
{
    $batchCode = 'BATCH-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    db()->prepare('INSERT INTO product_upload_batches (batch_code, vendor_id, admin_id, filename, status) VALUES (?, ?, ?, ?, "PENDING")')
        ->execute([$batchCode, $vendorId, $adminId, $filename]);
    return (int) db()->lastInsertId();
}

function product_bulk_upload_record(int $batchId, int $rowNum, ?string $productCode, ?string $title, string $status, ?string $error, ?int $productId = null): void
{
    db()->prepare('INSERT INTO product_upload_records (batch_id, row_number, product_code, title, status, error_message, product_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$batchId, $rowNum, $productCode, $title, $status, $error, $productId]);
}

function product_bulk_upload_update_batch(int $batchId, int $total, int $success, int $failed, string $status, ?array $errorSummary = null): void
{
    db()->prepare('UPDATE product_upload_batches SET total_rows=?, success_rows=?, failed_rows=?, status=?, error_summary=?, completed_at=IF(?="COMPLETED",NOW(),completed_at) WHERE id=?')
        ->execute([$total, $success, $failed, $status, $errorSummary ? safe_json($errorSummary) : null, $status, $batchId]);
}

function product_upload_batches(int $page = 1, int $perPage = 20): array
{
    $pdo = db();
    $total = (int) $pdo->query('SELECT COUNT(*) FROM product_upload_batches')->fetchColumn();
    $stmt = $pdo->prepare("SELECT pub.*, v.vendor_code, v.shop_name, a.name admin_name FROM product_upload_batches pub JOIN vendors v ON v.id = pub.vendor_id LEFT JOIN admins a ON a.id = pub.admin_id ORDER BY pub.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, ($page - 1) * $perPage]);
    $rows = $stmt->fetchAll();
    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int) ceil($total / $perPage))];
}

function product_upload_batch_details(int $batchId, int $page = 1, int $perPage = 50): array
{
    $pdo = db();
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM product_upload_records WHERE batch_id = ?');
    $totalStmt->execute([$batchId]);
    $total = (int) $totalStmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT * FROM product_upload_records WHERE batch_id = ? ORDER BY row_number LIMIT ? OFFSET ?');
    $stmt->execute([$batchId, $perPage, ($page - 1) * $perPage]);
    $rows = $stmt->fetchAll();

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int) ceil($total / $perPage))];
}