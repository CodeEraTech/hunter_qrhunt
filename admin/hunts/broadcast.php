<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_permission('hunts.manage');

$pdo = db();

if (is_post()) {
    verify_csrf();
    try {
        $action = (string)input('action');

        if ($action === 'create') {
            $campaignId = trim((string)input('campaign_id'));
            $targetType = strtoupper(trim((string)input('target_type', 'PROMOTER')));
            $targetId = trim((string)input('target_id'));
            $message = trim((string)input('message'));
            $rewardType = strtoupper(trim((string)input('reward_type', 'COINS')));
            $rewardValue = (float)input('reward_value', 0);
            $validityMinutes = (int)input('validity_window_minutes', 60);
            $pin = trim((string)input('pin'));

            $result = create_hunt_broadcast([
                'campaign_id' => $campaignId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'message' => $message,
                'reward_type' => $rewardType,
                'reward_value' => $rewardValue,
                'validity_window_minutes' => $validityMinutes,
                'pin' => $pin,
            ], (int)admin()['id']);

            flash('success', 'Live broadcast published successfully! Clue dispatched via FCM Push and Dynamic QR token activated.');
        } elseif ($action === 'cancel') {
            $id = (int)input('id');
            $pdo->prepare('UPDATE hunt_broadcasts SET status = "CANCELLED" WHERE id = ?')->execute([$id]);
            audit_log((int)admin()['id'], 'HUNT_BROADCAST_CANCELLED', 'hunt_broadcasts', (string)$id);
            flash('success', 'Broadcast cancelled and deactivated.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/hunts/broadcast.php');
}

// Fetch campaigns
$campaigns = $pdo->query('SELECT spot_id AS id, spot_name AS title FROM spot_campaigns ORDER BY spot_name')->fetchAll();
if (!$campaigns) {
    $campaigns = $pdo->query('SELECT id, title FROM hunts WHERE status = "ACTIVE" ORDER BY title')->fetchAll();
}

// Fetch broadcasts with claims stats
$broadcasts = $pdo->query('SELECT b.*,
    (SELECT COUNT(*) FROM hunt_broadcast_claims c WHERE c.broadcast_id = b.id AND c.status = "SUCCESS") AS claim_count,
    (SELECT COUNT(*) FROM hunt_broadcast_claims c WHERE c.broadcast_id = b.id AND c.status = "INVALID_PIN") AS failed_pin_count
    FROM hunt_broadcasts b 
    ORDER BY b.id DESC LIMIT 100')->fetchAll();

// Pre-fetch some promoter/vendor suggestions for target ID autocomplete
$sampleVendors = $pdo->query('SELECT vendor_code, shop_name FROM vendors LIMIT 10')->fetchAll();

$pageTitle = 'Hunt Broadcast Messaging';
$pageSubtitle = 'Configure real-time clues, flexible target entities, timed countdowns, and PIN-verified dynamic QR claims';
require APP_ROOT . '/includes/header.php';
?>

<style>
.blueprint-card {
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}
.blueprint-title {
    color: var(--navy);
    font-size: 17px;
    font-weight: 750;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.blueprint-sub {
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 20px;
}
.btn-broadcast {
    background: #0d9f6e;
    color: white;
    font-size: 15px;
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 9px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}
.btn-broadcast:hover {
    background: #0a845b;
}
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(18, 38, 63, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}
.modal-overlay.open {
    display: flex;
}
.modal-box {
    background: white;
    border-radius: 14px;
    max-width: 520px;
    width: 100%;
    padding: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.modal-box h3 {
    margin-top: 0;
    font-size: 18px;
    color: var(--navy);
}
.modal-summary {
    background: #f8fafc;
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 14px;
    margin: 16px 0;
    font-size: 13px;
}
.modal-summary .row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #edf2f7;
}
.modal-summary .row:last-child {
    border-bottom: none;
}
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.countdown-tag {
    font-family: monospace;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    background: #edf2f7;
}
</style>

<!-- 1. Admin Panel: Live Broadcast Form UI Layout (Master Blueprint 2) -->
<section class="blueprint-card">
    <div class="blueprint-title">
        <span>📢</span> Live Broadcast Form UI Layout
    </div>
    <div class="blueprint-sub">
        Broadcast real-time clues, flexible target entities, timed expiry, and secret PIN verification across active hunt campaigns.
    </div>

    <form method="post" id="broadcast-form" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <div class="field">
            <label>Select Target Campaign <span style="color:var(--red)">*</span></label>
            <select name="campaign_id" id="field-campaign" required>
                <option value="">Select Active Campaign (e.g., CAMP_01 - Patna Heritage Hunt)</option>
                <?php foreach ($campaigns as $camp): ?>
                    <option value="<?= e((string)$camp['id']) ?>"><?= e($camp['title'] . ' · ' . $camp['id']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Target Entity Type</label>
            <select name="target_type" id="field-target-type">
                <option value="PROMOTER">Promoter (e.g. Ground Agent with Clue)</option>
                <option value="VENDOR">Vendor (Merchant Shop Owner)</option>
                <option value="SHOP">Specific Shop / Counter Location</option>
                <option value="CUSTOMER">Target Customer / Secret User</option>
            </select>
        </div>

        <div class="field full">
            <label>Flexible Target ID (Promoter, Vendor, Shop, or Customer ID) <span style="color:var(--red)">*</span></label>
            <input type="text" name="target_id" id="field-target-id" placeholder="e.g., PROM_RED_DRESS_01 or VND-PATNA-09" required list="target-suggestions">
            <datalist id="target-suggestions">
                <option value="PROM_RED_DRESS_01">Promoter: Red Dress Girl (Main Gate)</option>
                <option value="PROM_CAP_BLUE_02">Promoter: Blue Cap Agent (Food Court)</option>
                <?php foreach ($sampleVendors as $sv): ?>
                    <option value="<?= e($sv['vendor_code']) ?>"><?= e($sv['shop_name'] . ' (' . $sv['vendor_code'] . ')') ?></option>
                <?php endforeach; ?>
            </datalist>
            <small class="help">Specifies the physical entity holding the QR code and 4-digit verbal PIN</small>
        </div>

        <div class="field full">
            <label>Broadcast Message / Clue Text <span style="color:var(--red)">*</span></label>
            <textarea name="message" id="field-message" rows="3" placeholder='e.g., "Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!"' required></textarea>
            <small class="help">Sent directly to customer devices via Firebase Cloud Messaging (FCM) real-time push alert</small>
        </div>

        <div class="field">
            <label>Reward Type &amp; Value <span style="color:var(--red)">*</span></label>
            <div style="display: flex; gap: 8px;">
                <select name="reward_type" id="field-reward-type" style="width: 140px;">
                    <option value="COINS">Coins</option>
                    <option value="BONUS_CASH">Bonus Cash</option>
                    <option value="COUPON">Coupon</option>
                </select>
                <input type="number" step="0.01" min="1" name="reward_value" id="field-reward-val" placeholder="e.g., 200" value="200.00" required style="flex: 1;">
            </div>
            <small class="help">Number Input: e.g., 200 Coins / Bonus Cash / Coupon</small>
        </div>

        <div class="field">
            <label>Secret Verification PIN <span style="color:var(--red)">*</span></label>
            <input type="text" name="pin" id="field-pin" maxlength="4" pattern="\d{4}" placeholder="4-Digit Code: e.g., 4892" required>
            <small class="help" style="color:var(--red);">Verbal 4-digit Secret PIN told by promoter upon visit. Stored securely hashed on server.</small>
        </div>

        <div class="field">
            <label>Time Period / Validity Window (Auto-Deactivation Timer) <span style="color:var(--red)">*</span></label>
            <select name="validity_window_minutes" id="field-validity">
                <option value="30">30 Minutes (Rapid Hunt)</option>
                <option value="60" selected>1 Hour (Standard Clue)</option>
                <option value="120">2 Hours</option>
                <option value="360">6 Hours</option>
                <option value="1440">24 Hours (Full Day Flash Event)</option>
            </select>
            <small class="help">Clue and dynamic QR automatically expire when countdown reaches zero</small>
        </div>

        <div class="field full actions" style="margin-top: 14px;">
            <button type="button" class="btn-broadcast" id="open-confirm-modal">
                🚀 Broadcast Live Message Now (Trigger FCM Push &amp; Activate QR)
            </button>
            <button type="button" class="btn secondary" id="fill-sample-broadcast">Load Sample Clue</button>
        </div>
    </form>
</section>

<!-- Confirmation Modal Before Publishing Live Broadcast -->
<div class="modal-overlay" id="confirm-modal">
    <div class="modal-box">
        <h3>Confirm Live Hunt Broadcast</h3>
        <p style="color:var(--muted); font-size: 13px;">
            Please review the broadcast parameters carefully. Once triggered, the clue will be published to customer apps immediately and the dynamic QR token will become active.
        </p>

        <div class="modal-summary">
            <div class="row">
                <span style="color:var(--muted);">Campaign:</span>
                <strong id="modal-conf-campaign">—</strong>
            </div>
            <div class="row">
                <span style="color:var(--muted);">Target Entity:</span>
                <strong id="modal-conf-target">—</strong>
            </div>
            <div class="row">
                <span style="color:var(--muted);">Reward:</span>
                <strong id="modal-conf-reward" style="color:var(--green)">—</strong>
            </div>
            <div class="row">
                <span style="color:var(--muted);">Validity Window:</span>
                <strong id="modal-conf-validity">—</strong>
            </div>
            <div class="row">
                <span style="color:var(--muted);">Secret PIN:</span>
                <strong id="modal-conf-pin" style="letter-spacing: 2px;">••••</strong>
            </div>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line); font-size: 12px; color: var(--navy);">
                <strong>Clue Message:</strong>
                <div id="modal-conf-message" style="margin-top: 4px; font-style: italic;">—</div>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn secondary" type="button" id="close-confirm-modal">Cancel</button>
            <button class="btn-broadcast" type="button" id="execute-broadcast-submit">Confirm &amp; Dispatch Live Now</button>
        </div>
    </div>
</div>

<!-- QR Token Inspection Modal -->
<div class="modal-overlay" id="qr-modal">
    <div class="modal-box" style="text-align: center;">
        <h3 id="qr-modal-title">Dynamic Hunt QR Token</h3>
        <p style="color:var(--muted); font-size: 13px;" id="qr-modal-sub">Physical dynamic token scanned by users</p>
        
        <div style="padding: 20px; background: #fafbfc; border: 2px dashed #ccd5df; border-radius: 12px; margin: 16px 0; word-break: break-all;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;">Dynamic QR Scannable Payload</div>
            <code style="font-size: 16px; font-weight: 700; color: var(--blue);" id="qr-modal-token">HBQR_...</code>
        </div>

        <div style="font-size: 12px; color: var(--muted); margin-bottom: 16px;">
            Target must request customer to provide the secret 4-digit verbal PIN upon scan to authenticate reward redemption.
        </div>

        <div class="modal-actions" style="justify-content: center;">
            <button class="btn secondary" type="button" id="close-qr-modal">Close</button>
        </div>
    </div>
</div>

<!-- Live Broadcasts History & Claims Monitoring -->
<section class="panel">
    <div class="panel-head">
        <h2>Live &amp; Recent Hunt Broadcasts (<?= count($broadcasts) ?>)</h2>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Broadcast Code</th>
                    <th>Target Campaign &amp; Entity</th>
                    <th>Clue Message</th>
                    <th>Reward</th>
                    <th>Validity Window</th>
                    <th>Dynamic QR Token</th>
                    <th>FCM Push</th>
                    <th>Claims / Redemptions</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($broadcasts as $b): ?>
                    <?php
                    $isExpired = (date('Y-m-d H:i:s') > $b['expires_at']);
                    $effectiveStatus = ($b['status'] === 'ACTIVE' && $isExpired) ? 'EXPIRED' : $b['status'];
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($b['broadcast_code']) ?></strong><br>
                            <small style="color:var(--muted)"><?= e(date('M d, H:i', strtotime($b['created_at']))) ?></small>
                        </td>
                        <td>
                            <strong><?= e($b['campaign_id']) ?></strong><br>
                            <span class="badge" style="background:#eef2ff; color:#3730a3;"><?= e($b['target_type']) ?>: <?= e($b['target_id']) ?></span>
                        </td>
                        <td style="max-width: 260px; white-space: normal;">
                            <?= e($b['message']) ?>
                        </td>
                        <td>
                            <strong style="color:var(--green)"><?= e((string)$b['reward_value']) ?></strong><br>
                            <small class="badge success"><?= e($b['reward_type']) ?></small>
                        </td>
                        <td>
                            <div class="countdown-tag"><?= e((string)$b['validity_window_minutes']) ?> mins</div>
                            <small style="display:block; color:var(--muted); margin-top:2px;">
                                Exp: <?= e(date('H:i:s', strtotime($b['expires_at']))) ?>
                            </small>
                        </td>
                        <td>
                            <button type="button" class="btn secondary small view-qr-btn" data-token="<?= e($b['qr_token']) ?>" data-code="<?= e($b['broadcast_code']) ?>" data-target="<?= e($b['target_id']) ?>">
                                🔍 View Token
                            </button>
                        </td>
                        <td>
                            <span class="badge active">FCM SENT</span>
                        </td>
                        <td>
                            <strong style="color:var(--green); font-size:14px;"><?= e((string)$b['claim_count']) ?></strong> claims
                            <?php if ((int)$b['failed_pin_count'] > 0): ?>
                                <br><small style="color:var(--red)"><?= e((string)$b['failed_pin_count']) ?> wrong PINs</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $effectiveStatus === 'ACTIVE' ? 'active' : ($effectiveStatus === 'EXPIRED' ? 'high' : 'blocked') ?>">
                                <?= e($effectiveStatus) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($effectiveStatus === 'ACTIVE'): ?>
                                <form method="post" data-confirm="Deactivate this broadcast now?" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id" value="<?= e((string)$b['id']) ?>">
                                    <button class="btn danger small" type="submit">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--muted); font-size: 12px;">Closed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$broadcasts): ?>
                    <tr>
                        <td colspan="10" class="empty">No hunt broadcasts initiated yet. Use the form above to trigger a live clue.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
// Sample Clue Loader
document.getElementById('fill-sample-broadcast')?.addEventListener('click', function() {
    document.getElementById('field-target-type').value = 'PROMOTER';
    document.getElementById('field-target-id').value = 'PROM_RED_DRESS_01';
    document.getElementById('field-message').value = 'Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!';
    document.getElementById('field-reward-type').value = 'COINS';
    document.getElementById('field-reward-val').value = '200.00';
    document.getElementById('field-pin').value = '4892';
    document.getElementById('field-validity').value = '60';
});

// Confirmation Modal Wiring
const confirmModal = document.getElementById('confirm-modal');
const form = document.getElementById('broadcast-form');

document.getElementById('open-confirm-modal')?.addEventListener('click', function() {
    const campaignSel = document.getElementById('field-campaign');
    const targetType = document.getElementById('field-target-type').value;
    const targetId = document.getElementById('field-target-id').value.trim();
    const message = document.getElementById('field-message').value.trim();
    const rewardType = document.getElementById('field-reward-type').value;
    const rewardVal = document.getElementById('field-reward-val').value.trim();
    const pin = document.getElementById('field-pin').value.trim();
    const validity = document.getElementById('field-validity');

    if (!campaignSel.value) {
        alert('Please select a target campaign.');
        campaignSel.focus();
        return;
    }
    if (!targetId) {
        alert('Please enter a target ID.');
        document.getElementById('field-target-id').focus();
        return;
    }
    if (!message) {
        alert('Please enter the broadcast message / clue text.');
        document.getElementById('field-message').focus();
        return;
    }
    if (!pin || pin.length !== 4 || isNaN(pin)) {
        alert('Please enter a valid 4-digit secret PIN.');
        document.getElementById('field-pin').focus();
        return;
    }

    document.getElementById('modal-conf-campaign').textContent = campaignSel.options[campaignSel.selectedIndex].text;
    document.getElementById('modal-conf-target').textContent = targetType + ': ' + targetId;
    document.getElementById('modal-conf-reward').textContent = rewardVal + ' ' + rewardType;
    document.getElementById('modal-conf-validity').textContent = validity.options[validity.selectedIndex].text;
    document.getElementById('modal-conf-pin').textContent = pin;
    document.getElementById('modal-conf-message').textContent = message;

    confirmModal.classList.add('open');
});

document.getElementById('close-confirm-modal')?.addEventListener('click', function() {
    confirmModal.classList.remove('open');
});

document.getElementById('execute-broadcast-submit')?.addEventListener('click', function() {
    confirmModal.classList.remove('open');
    form.submit();
});

// View Dynamic QR Token Modal
const qrModal = document.getElementById('qr-modal');
document.querySelectorAll('.view-qr-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const token = this.getAttribute('data-token');
        const code = this.getAttribute('data-code');
        const target = this.getAttribute('data-target');

        document.getElementById('qr-modal-title').textContent = 'Dynamic QR Token: ' + code;
        document.getElementById('qr-modal-sub').textContent = 'Presented by Target Entity: ' + target;
        document.getElementById('qr-modal-token').textContent = token;

        qrModal.classList.add('open');
    });
});

document.getElementById('close-qr-modal')?.addEventListener('click', function() {
    qrModal.classList.remove('open');
});

window.addEventListener('click', function(e) {
    if (e.target === confirmModal) confirmModal.classList.remove('open');
    if (e.target === qrModal) qrModal.classList.remove('open');
});
</script>

<?php require APP_ROOT . '/includes/footer.php'; ?>

