<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_permission('hunts.manage');

$id = (int)input('id');
$hunt = null;
$pdo = db();

if ($id > 0) {
    $s = $pdo->prepare('SELECT * FROM hunts WHERE id = ?');
    $s->execute([$id]);
    $hunt = $s->fetch();
    if (!$hunt) {
        http_response_code(404);
        exit('Hunt campaign not found.');
    }
}

if (is_post()) {
    verify_csrf();
    $title = trim((string)input('title'));
    $slug = strtolower(trim((string)input('slug')));
    $description = trim((string)input('description'));
    $rules = trim((string)input('rules'));
    $videoUrl = trim((string)input('tutorial_video_url'));
    $featuredImage = trim((string)input('featured_image'));
    $lat = input('latitude') !== null && trim((string)input('latitude')) !== '' ? (float)input('latitude') : 25.5940950;
    $lon = input('longitude') !== null && trim((string)input('longitude')) !== '' ? (float)input('longitude') : 85.1375660;
    $radius = max(5, min(10000, (int)input('allowed_radius_meters', 20)));
    $start = str_replace('T', ' ', trim((string)input('start_at')));
    $end = str_replace('T', ' ', trim((string)input('end_at')));
    $fee = trim((string)input('activation_fee', '0.00'));
    $minReward = trim((string)input('reward_min', '10.00'));
    $maxReward = trim((string)input('reward_max', '100.00'));
    $status = strtoupper((string)input('status', 'ACTIVE'));

    // Handle tutorial video file upload if provided
    $videoPath = $hunt['tutorial_video_path'] ?? null;
    if (isset($_FILES['tutorial_video']) && $_FILES['tutorial_video']['error'] === UPLOAD_ERR_OK) {
        $allowedExts = ['mp4', 'mov', 'webm', 'mkv'];
        $ext = strtolower(pathinfo((string)$_FILES['tutorial_video']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExts, true)) {
            $uploadDir = APP_ROOT . '/uploads/hunts';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'hunt_vid_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = $uploadDir . '/' . $filename;
            if (move_uploaded_file((string)$_FILES['tutorial_video']['tmp_name'], $dest)) {
                $videoPath = '/uploads/hunts/' . $filename;
            }
        }
    }

    if ($slug === '' && $title !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? 'hunt');
        $slug = trim($slug, '-');
    }

    if ($title === '' || !preg_match('/^[a-z0-9-]{3,190}$/', $slug) || !in_array($status, ['DRAFT', 'ACTIVE', 'INACTIVE', 'ENDED'], true) || !strtotime($start) || !strtotime($end) || strtotime($end) <= strtotime($start) || !preg_match('/^\d{1,16}(\.\d{1,2})?$/', $fee) || !preg_match('/^\d{1,16}(\.\d{1,2})?$/', $minReward) || !preg_match('/^\d{1,16}(\.\d{1,2})?$/', $maxReward) || decimal_compare($minReward, '0.00') <= 0 || decimal_compare($maxReward, $minReward) < 0) {
        flash('error', 'Enter valid hunt dates, monetary ranges, status, coordinates, title and slug.');
    } else {
        try {
            $pdo->beginTransaction();

            $qrToken = $hunt['qr_token'] ?? null;
            if (!$qrToken) {
                $qrToken = 'HUNT_' . strtoupper(bin2hex(random_bytes(16)));
            }

            if ($id > 0) {
                $updateStmt = $pdo->prepare('UPDATE hunts SET 
                    title = ?, slug = ?, description = ?, rules = ?, 
                    tutorial_video_url = ?, tutorial_video_path = ?, featured_image = ?, 
                    latitude = ?, longitude = ?, allowed_radius_meters = ?, qr_token = ?,
                    start_at = ?, end_at = ?, activation_fee = ?, 
                    reward_min = ?, reward_max = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?');
                $updateStmt->execute([
                    $title, $slug, $description, $rules,
                    $videoUrl ?: null, $videoPath, $featuredImage ?: null,
                    $lat, $lon, $radius, $qrToken,
                    $start, $end, $fee,
                    $minReward, $maxReward, $status,
                    $id
                ]);

                // Sync primary hunt location
                $locCheck = $pdo->prepare('SELECT id FROM hunt_locations WHERE hunt_id = ? ORDER BY id LIMIT 1');
                $locCheck->execute([$id]);
                if ($locId = $locCheck->fetchColumn()) {
                    $pdo->prepare('UPDATE hunt_locations SET name = ?, latitude = ?, longitude = ?, radius_meters = ?, status = "ACTIVE" WHERE id = ?')
                        ->execute([$title . ' - Primary Perimeter', $lat, $lon, $radius, $locId]);
                } else {
                    $pdo->prepare('INSERT INTO hunt_locations(hunt_id, name, latitude, longitude, radius_meters, status) VALUES(?,?,?,?,?, "ACTIVE")')
                        ->execute([$id, $title . ' - Primary Perimeter', $lat, $lon, $radius]);
                    $locId = $pdo->lastInsertId();
                }

                // Sync hunt QR code record
                $qrCheck = $pdo->prepare('SELECT id FROM hunt_qr_codes WHERE hunt_id = ? ORDER BY id LIMIT 1');
                $qrCheck->execute([$id]);
                if (!$qrCheck->fetchColumn()) {
                    $pdo->prepare('INSERT INTO hunt_qr_codes(hunt_id, location_id, token_hash, label, status, created_by) VALUES(?,?,?,?,"ACTIVE",?)')
                        ->execute([$id, $locId, hash('sha256', $qrToken), $title . ' Main QR', (int)admin()['id']]);
                }

                audit_log((int)admin()['id'], 'HUNT_UPDATED', 'hunts', (string)$id, $hunt, [
                    'title' => $title, 'slug' => $slug, 'status' => $status, 'radius' => $radius
                ]);
            } else {
                $insertStmt = $pdo->prepare('INSERT INTO hunts(
                    title, slug, description, rules, 
                    tutorial_video_url, tutorial_video_path, featured_image, 
                    latitude, longitude, allowed_radius_meters, qr_token,
                    start_at, end_at, activation_fee, 
                    reward_min, reward_max, status, created_by, created_at, updated_at
                ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())');
                $insertStmt->execute([
                    $title, $slug, $description, $rules,
                    $videoUrl ?: null, $videoPath, $featuredImage ?: null,
                    $lat, $lon, $radius, $qrToken,
                    $start, $end, $fee,
                    $minReward, $maxReward, $status,
                    (int)admin()['id']
                ]);
                $id = (int)$pdo->lastInsertId();

                // Create primary hunt location for geo-fencing validation
                $pdo->prepare('INSERT INTO hunt_locations(hunt_id, name, latitude, longitude, radius_meters, status) VALUES(?,?,?,?,?, "ACTIVE")')
                    ->execute([$id, $title . ' - Primary Perimeter', $lat, $lon, $radius]);
                $locId = (int)$pdo->lastInsertId();

                // Create primary hunt QR code
                $pdo->prepare('INSERT INTO hunt_qr_codes(hunt_id, location_id, token_hash, label, status, created_by) VALUES(?,?,?,?,"ACTIVE",?)')
                    ->execute([$id, $locId, hash('sha256', $qrToken), $title . ' Main QR', (int)admin()['id']]);

                audit_log((int)admin()['id'], 'HUNT_CREATED', 'hunts', (string)$id, null, [
                    'title' => $title, 'slug' => $slug, 'status' => $status, 'radius' => $radius
                ]);
            }

            $pdo->commit();
            flash('success', 'Hunt campaign saved and published successfully.');
            redirect('/admin/hunts/manage.php?id=' . $id);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Could not save hunt campaign: ' . $e->getMessage());
        }
    }
}

$pageTitle = $hunt ? 'Edit Master Hunt Campaign' : 'Master Campaign Setup';
$pageSubtitle = 'Complete campaign configuration with multimedia tutorial integration, geofencing, charges, and QR management';
require APP_ROOT . '/includes/header.php';

$val = static fn(string $k, string $d = '') => (string)($hunt[$k] ?? $d);
$activeQrToken = $hunt['qr_token'] ?? ('HUNT_' . strtoupper(bin2hex(random_bytes(16))));
?>

<style>
.section-banner {
    grid-column: 1 / -1;
    background: #0d233e;
    color: #ffffff;
    padding: 10px 16px;
    margin-top: 14px;
    margin-bottom: 4px;
    border-radius: 6px;
    font-weight: 750;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 8px;
}
.qr-management-card {
    grid-column: 1 / -1;
    background: #f8fafc;
    border: 2px dashed #ccd5df;
    border-radius: 10px;
    padding: 18px;
    text-align: center;
    margin: 8px 0;
}
.developer-notes-box {
    background: #fdfdfd;
    border: 1px solid #d0d7de;
    border-radius: 10px;
    padding: 18px;
    margin-top: 24px;
    font-size: 13px;
    line-height: 1.6;
}
.developer-notes-box h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.developer-notes-box ol {
    margin: 0;
    padding-left: 20px;
}
.developer-notes-box li {
    margin-bottom: 6px;
}
.btn-save-campaign {
    background: #0d9f6e;
    color: white;
    font-size: 16px;
    font-weight: 750;
    padding: 14px 28px;
    border-radius: 9px;
    border: none;
    cursor: pointer;
    width: 100%;
}
.btn-save-campaign:hover {
    background: #0a845b;
}
</style>

<section class="panel">
    <div class="panel-head">
        <h2><?= $hunt ? 'Edit Campaign: ' . e($val('title')) : 'Create Master Hunt Campaign' ?></h2>
        <div class="actions">
            <?php if ($hunt): ?>
                <a class="btn secondary small" href="<?= e(app_url('/admin/hunts/manage.php?id=' . $hunt['id'])) ?>">Campaign Overview</a>
            <?php endif; ?>
            <a class="btn secondary" href="<?= e(app_url('/admin/hunts/index.php')) ?>">Back to Hunts</a>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="form-grid" id="campaign-form">
        <?= csrf_field() ?>

        <!-- 1. BASIC CAMPAIGN INFORMATION -->
        <div class="section-banner">
            <span>1</span> Basic Campaign Information
        </div>

        <div class="field">
            <label>Title <span style="color:var(--red)">*</span></label>
            <input name="title" id="camp-title" maxlength="190" placeholder="Enter campaign title (e.g., Coaching Science Quiz)..." value="<?= e($val('title')) ?>" required>
        </div>

        <div class="field">
            <label>Slug <span style="color:var(--red)">*</span></label>
            <input name="slug" id="camp-slug" pattern="[a-z0-9-]{3,190}" placeholder="Auto-generated or manual URL path..." value="<?= e($val('slug')) ?>" required>
            <small class="help">URL identifier for player routing and sharing</small>
        </div>

        <div class="field full">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Detailed overview of campaign purpose and target audience..."><?= e($val('description')) ?></textarea>
        </div>

        <!-- 2. RULES & MULTIMEDIA INTEGRATION -->
        <div class="section-banner">
            <span>2</span> Rules &amp; Multimedia Integration
        </div>

        <div class="field full">
            <label>Rules &amp; Regulations</label>
            <textarea name="rules" rows="3" placeholder="Enter game rules, attempt limits, and instructions..."><?= e($val('rules')) ?></textarea>
        </div>

        <div class="field">
            <label>Tutorial Video Link (YouTube / External)</label>
            <input type="url" name="tutorial_video_url" placeholder="https://youtube.com/watch?v=..." value="<?= e($val('tutorial_video_url')) ?>">
            <small class="help">Direct link to clue or gameplay walkthrough</small>
        </div>

        <div class="field">
            <label>Upload Tutorial Video (MP4 / Animation)</label>
            <input type="file" name="tutorial_video" accept="video/mp4,video/quicktime,video/webm">
            <small class="help">
                <?= !empty($hunt['tutorial_video_path']) ? 'Current file: <code>' . e($hunt['tutorial_video_path']) . '</code>' : 'Choose file to upload (.mp4, .mov)' ?>
            </small>
        </div>

        <div class="field full">
            <label>Featured Image URL / Path</label>
            <input name="featured_image" maxlength="255" placeholder="Enter image link or upload path for banner/logo..." value="<?= e($val('featured_image')) ?>">
        </div>

        <!-- 3. GEOLOCATION & SECURITY (GEO-FENCING) -->
        <div class="section-banner">
            <span>3</span> Geolocation &amp; Security (Geo-Fencing)
        </div>

        <div class="field">
            <label>Latitude &amp; Longitude (GPS Coordinates) <span style="color:var(--red)">*</span></label>
            <div style="display: flex; gap: 8px;">
                <input type="number" step="0.0000001" name="latitude" id="field-lat" placeholder="e.g., 25.5940950" value="<?= e($val('latitude', '25.5940950')) ?>" required style="flex: 1;">
                <input type="number" step="0.0000001" name="longitude" id="field-lon" placeholder="e.g., 85.1375660" value="<?= e($val('longitude', '85.1375660')) ?>" required style="flex: 1;">
            </div>
            <small class="help">Map Pin GPS coordinates of target hunt venue</small>
        </div>

        <div class="field">
            <label>Allowed Radius / Distance Range (Meters) <span style="color:var(--red)">*</span></label>
            <input type="number" name="allowed_radius_meters" min="5" max="10000" placeholder="e.g., 20 Meters (Anti-Cheat Security Fence)" value="<?= e($val('allowed_radius_meters', '20')) ?>" required>
            <small class="help">Strict anti-cheat perimeter enforced by mobile backend</small>
        </div>

        <!-- 4. SCHEDULE, CHARGES & QR MANAGEMENT -->
        <div class="section-banner">
            <span>4</span> Schedule, Charges &amp; QR Management
        </div>

        <div class="field">
            <label>Starts Date-Time <span style="color:var(--red)">*</span></label>
            <input type="datetime-local" name="start_at" value="<?= e(str_replace(' ', 'T', substr($val('start_at', date('Y-m-d H:i:s')), 0, 16))) ?>" required>
        </div>

        <div class="field">
            <label>Ends Date-Time <span style="color:var(--red)">*</span></label>
            <input type="datetime-local" name="end_at" value="<?= e(str_replace(' ', 'T', substr($val('end_at', date('Y-m-d H:i:s', strtotime('+30 days'))), 0, 16))) ?>" required>
        </div>

        <div class="field">
            <label>Activation Fee (₹)</label>
            <input name="activation_fee" inputmode="decimal" placeholder="Fee: 0.00" value="<?= e($val('activation_fee', '0.00')) ?>" required>
        </div>

        <div class="field">
            <label>Status <span style="color:var(--red)">*</span></label>
            <select name="status">
                <?php foreach (['DRAFT', 'ACTIVE', 'INACTIVE', 'ENDED'] as $v): ?>
                    <option <?= $val('status', 'ACTIVE') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Reward Scratch Minimum (₹)</label>
            <input name="reward_min" inputmode="decimal" value="<?= e($val('reward_min', '10.00')) ?>" required>
        </div>

        <div class="field">
            <label>Reward Scratch Maximum (₹)</label>
            <input name="reward_max" inputmode="decimal" value="<?= e($val('reward_max', '100.00')) ?>" required>
        </div>

        <!-- CAMPAIGN QR CODE MANAGEMENT -->
        <div class="qr-management-card">
            <div style="font-weight: 750; color: var(--navy); margin-bottom: 6px; font-size: 14px;">CAMPAIGN QR CODE MANAGEMENT</div>
            <p style="color: var(--muted); font-size: 12px; margin: 0 0 12px;">Dynamic scannable cryptographic payload generated for physical checkpoints.</p>
            <div style="margin-bottom: 12px;">
                <code style="background: white; border: 1px solid var(--line); padding: 8px 16px; border-radius: 6px; font-size: 15px; font-weight: 700; color: var(--blue);" id="display-qr-token">
                    <?= e($activeQrToken) ?>
                </code>
            </div>
            <div class="actions" style="justify-content: center; gap: 10px;">
                <button type="button" class="btn secondary small" id="btn-regen-qr">Generate / Rotate Token</button>
                <button type="button" class="btn secondary small" id="btn-download-qr">Download QR Code (SVG / PNG)</button>
            </div>
        </div>

        <div class="field full actions" style="margin-top: 14px;">
            <button class="btn-save-campaign" type="submit">SAVE HUNT &amp; PUBLISH CAMPAIGN</button>
        </div>
    </form>
</section>

<!-- DEVELOPER IMPLEMENTATION NOTES (COMPLETE WORKFLOW GUIDE) -->
<section class="developer-notes-box">
    <h3>Developer Implementation Notes (Complete Workflow Guide)</h3>
    <ol>
        <li><strong>Basic Info:</strong> Title serves as the main campaign name, and Slug handles system/URL routing. Description stores the complete overview for target users.</li>
        <li><strong>Rules &amp; Multimedia:</strong> Rules define attempt limits and terms. Developers must provide dual options: a Tutorial Video Link (YouTube/External) and direct MP4/Animation File Upload for pre-game tutorials. Featured Image handles banner/logo display.</li>
        <li><strong>Geolocation &amp; Geo-Fencing:</strong> Stores Latitude/Longitude and Allowed Radius (e.g., 20 Meters). The mobile backend must validate the user's live GPS coordinates against this perimeter to prevent remote screenshots, cheating, or unauthorized access.</li>
        <li><strong>Schedule, Charges &amp; QR:</strong> Starts/Ends manage campaign duration windows. Activation Fee tracks plan charges, and Status controls DRAFT/ACTIVE visibility. QR Code management allows dynamic generation and downloading (SVG/PNG).</li>
        <li><strong>Execution:</strong> 'Save Hunt &amp; Publish Campaign' commits all inputs to the database to deploy the hunt.</li>
    </ol>
</section>

<script>
// Auto slug generation from title
document.getElementById('camp-title')?.addEventListener('input', function() {
    const slugField = document.getElementById('camp-slug');
    if (!slugField.dataset.customized) {
        slugField.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});
document.getElementById('camp-slug')?.addEventListener('input', function() {
    this.dataset.customized = 'true';
});

// Dynamic QR generator
document.getElementById('btn-regen-qr')?.addEventListener('click', function() {
    const chars = '0123456789ABCDEF';
    let hex = '';
    for (let i = 0; i < 32; i++) {
        hex += chars[Math.floor(Math.random() * chars.length)];
    }
    const token = 'HUNT_' + hex;
    document.getElementById('display-qr-token').textContent = token;
});

// Download QR trigger
document.getElementById('btn-download-qr')?.addEventListener('click', function() {
    const token = document.getElementById('display-qr-token').textContent.trim();
    const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(token);
    window.open(qrUrl, '_blank');
});
</script>

<?php require APP_ROOT . '/includes/footer.php'; ?>
