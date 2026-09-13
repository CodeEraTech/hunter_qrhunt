<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/includes/bootstrap.php';
require_vendor();
$vendor = vendor_user();
$vendorUserId = (int) $vendor['id'];

if (is_post()) {
    verify_csrf();
    if (input('action') === 'revoke_session' && input('session_id')) {
        $sessionId = (int) input('session_id');
        $stmt = db()->prepare('SELECT id FROM vendor_sessions WHERE id=? AND vendor_user_id=? AND revoked_at IS NULL');
        $stmt->execute([$sessionId, $vendorUserId]);
        $session = $stmt->fetch();
        if ($session) {
            db()->prepare('UPDATE vendor_sessions SET revoked_at=NOW() WHERE id=?')->execute([$sessionId]);
            audit_log(null, 'VENDOR_SESSION_REVOKED', 'vendor_security', (string) $vendorUserId, null, ['session_id' => $sessionId]);
            flash('success', 'Session revoked successfully.');
            redirect('/vendor/profile/devices.php');
        }
    } elseif (input('action') === 'logout_others') {
        $currentHash = hash('sha256', $_SESSION['vendor_session_token']);
        db()->prepare('UPDATE vendor_sessions SET revoked_at=NOW() WHERE vendor_user_id=? AND php_session_hash<>? AND revoked_at IS NULL')->execute([$vendorUserId, $currentHash]);
        audit_log(null, 'VENDOR_OTHER_SESSIONS_LOGGED_OUT', 'vendor_security', (string) $vendorUserId);
        flash('success', 'All other sessions have been logged out.');
        redirect('/vendor/profile/devices.php');
    }
}

$stmt = db()->prepare('SELECT id,php_session_hash,ip_address,created_at,expires_at,revoked_at FROM vendor_sessions WHERE vendor_user_id=? ORDER BY created_at DESC');
$stmt->execute([$vendorUserId]);
$sessions = $stmt->fetchAll();

$pageTitle = 'Devices / Sessions';
$pageSubtitle = 'Active and recent login sessions';
require APP_ROOT.'/includes/vendor_header.php';

$notices = take_flashes();
foreach ($notices as $n): ?>
    <div class="alert <?=e($n['type'])?>"><?=e($n['message'])?></div>
<?php endforeach ?>

<section class="store-panel" aria-labelledby="sessions-heading">
    <div class="panel-head">
        <h2 id="sessions-heading">Active Sessions</h2>
        <form method="post" style="margin:0" onsubmit="return confirm('Log out all other sessions?')">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="logout_others">
            <button class="btn danger" type="submit">Logout all other sessions</button>
        </form>
    </div>
    <p class="help" style="margin-bottom:18px">Manage your logged-in devices. Revoke any session you do not recognize.</p>
    <?php if ($sessions): ?>
        <div class="table-wrap">
            <table class="store-table">
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>IP Address</th>
                        <th>Created At</th>
                        <th>Expires At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $s):
                        $isCurrent = hash('sha256', $_SESSION['vendor_session_token']) === ($s['php_session_hash'] ?? null);
                        $isExpired = strtotime($s['expires_at']) <= time();
                        $isRevoked = $s['revoked_at'] !== null;
                        if ($isRevoked) $status = 'Revoked';
                        elseif ($isExpired) $status = 'Expired';
                        else $status = 'Active';
                        $statusClass = $isRevoked ? 'rejected' : ($isExpired ? 'pending' : 'approved');
                    ?>
                        <tr>
                            <td><strong>#<?=e($s['id'])?></strong><?php if ($isCurrent): ?> <small>(this device)</small><?php endif ?></td>
                            <td><?=e($s['ip_address'] ?? '—')?></td>
                            <td><?=e($s['created_at'])?></td>
                            <td><?=e($s['expires_at'])?></td>
                            <td><span class="store-status status-<?=e($statusClass)?>"><?=e($status)?></span></td>
                            <td>
                                <?php if (!$isCurrent && !$isRevoked && !$isExpired): ?>
                                    <form method="post" style="margin:0;display:inline" onsubmit="return confirm('Revoke this session?')">
                                        <?=csrf_field()?>
                                        <input type="hidden" name="action" value="revoke_session">
                                        <input type="hidden" name="session_id" value="<?=e($s['id'])?>">
                                        <button class="btn danger small" type="submit">Revoke</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:var(--muted);font-size:12px">—</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty">No sessions found.</p>
    <?php endif ?>
</section>

<?php
require APP_ROOT.'/includes/vendor_footer.php';
