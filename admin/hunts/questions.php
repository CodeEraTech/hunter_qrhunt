<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_permission('hunts.manage');

$pdo = db();

if (is_post()) {
    verify_csrf();
    try {
        $action = (string)input('action');
        $id = (int)input('id');

        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM riddle_pool WHERE riddle_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM question_collection_items WHERE riddle_id = ?')->execute([$id]);
            audit_log((int)admin()['id'], 'QUESTION_DELETED', 'riddle_pool', (string)$id);
            flash('success', 'Question deleted successfully.');
        } else {
            $spot = trim((string)input('spot_id'));
            $text = trim((string)input('riddle_text'));
            $instructions = trim((string)input('instruction_text'));
            $answer = trim((string)input('primary_answer'));
            $mode = strtoupper((string)input('question_mode', 'SINGLE'));
            $time = max(5, min(3600, (int)input('answer_time_seconds', 60)));
            $rewardType = strtoupper(trim((string)input('reward_type', 'COINS')));
            $rewardValue = max(0.01, (float)input('reward_value', 50.00));
            $rewardExpiryDays = max(1, min(365, (int)input('reward_expiry_days', 7)));
            $fallbackOffer = trim((string)input('fallback_offer_title'));
            $collectionId = (int)input('collection_id') ?: null;
            $videoOrUrl = trim((string)input('yt_video_id'));
            $channelUrl = trim((string)input('channel_url'));
            $status = strtoupper((string)input('status', 'ACTIVE'));

            if ($spot === '' || $text === '' || $answer === '' || !in_array($mode, ['SINGLE', 'MULTIPLE'], true)) {
                throw new InvalidArgumentException('Master Category Campaign, question text, and primary answer are required.');
            }
            if (!in_array($rewardType, ['COINS', 'CASH', 'CASHBACK', 'COUPON', 'FREE_PIN'], true)) {
                throw new InvalidArgumentException('Valid reward payout type is required.');
            }

            // Extract YouTube video ID if full URL is supplied
            $videoId = $videoOrUrl;
            if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $videoOrUrl, $matches)) {
                $videoId = $matches[1];
            }

            $syn = array_values(array_filter(array_map('trim', explode(',', (string)input('synonyms')))));

            if ($action === 'edit' && $id > 0) {
                $updateStmt = $pdo->prepare('UPDATE riddle_pool SET 
                    spot_id = ?, question_mode = ?, riddle_text = ?, instruction_text = ?, 
                    primary_answer = ?, synonyms = ?, yt_video_id = ?, channel_url = ?, 
                    answer_time_seconds = ?, reward_type = ?, reward_value = ?, 
                    reward_expiry_days = ?, fallback_offer_title = ?, collection_id = ?, 
                    status = ?, updated_at = NOW() 
                    WHERE riddle_id = ?');
                $updateStmt->execute([
                    $spot, $mode, $text, $instructions ?: null,
                    $answer, safe_json($syn), $videoId ?: null, $channelUrl ?: null,
                    $time, $rewardType, $rewardValue,
                    $rewardExpiryDays, $fallbackOffer ?: null, $collectionId,
                    $status, $id
                ]);

                // Sync collection mapping
                $pdo->prepare('DELETE FROM question_collection_items WHERE riddle_id = ?')->execute([$id]);
                if ($collectionId) {
                    $nextOrder = (int)$pdo->query("SELECT COALESCE(MAX(question_order), 0) + 1 FROM question_collection_items WHERE collection_id = {$collectionId}")->fetchColumn();
                    $pdo->prepare('INSERT IGNORE INTO question_collection_items(collection_id, riddle_id, question_order) VALUES(?,?,?)')
                        ->execute([$collectionId, $id, $nextOrder]);
                }

                audit_log((int)admin()['id'], 'QUESTION_UPDATED', 'riddle_pool', (string)$id);
                flash('success', 'Question and reward rules updated successfully.');
            } else {
                $insertStmt = $pdo->prepare('INSERT INTO riddle_pool(
                    spot_id, question_mode, riddle_text, instruction_text, 
                    primary_answer, synonyms, yt_video_id, channel_url, 
                    answer_time_seconds, reward_type, reward_value, 
                    reward_expiry_days, fallback_offer_title, collection_id, 
                    status, created_at, updated_at
                ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())');
                $insertStmt->execute([
                    $spot, $mode, $text, $instructions ?: null,
                    $answer, safe_json($syn), $videoId ?: null, $channelUrl ?: null,
                    $time, $rewardType, $rewardValue,
                    $rewardExpiryDays, $fallbackOffer ?: null, $collectionId,
                    $status
                ]);
                $newId = (int)$pdo->lastInsertId();

                if ($collectionId) {
                    $nextOrder = (int)$pdo->query("SELECT COALESCE(MAX(question_order), 0) + 1 FROM question_collection_items WHERE collection_id = {$collectionId}")->fetchColumn();
                    $pdo->prepare('INSERT IGNORE INTO question_collection_items(collection_id, riddle_id, question_order) VALUES(?,?,?)')
                        ->execute([$collectionId, $newId, $nextOrder]);
                }

                audit_log((int)admin()['id'], 'QUESTION_CREATED', 'riddle_pool', (string)$newId);
                flash('success', 'New question and reward rules published successfully.');
            }
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/hunts/questions.php');
}

$editId = (int)input('edit', 0);
$editRow = null;
if ($editId > 0) {
    $eStmt = $pdo->prepare('SELECT * FROM riddle_pool WHERE riddle_id = ?');
    $eStmt->execute([$editId]);
    $editRow = $eStmt->fetch() ?: null;
}

$spots = $pdo->query('SELECT spot_id, spot_name FROM spot_campaigns ORDER BY spot_name')->fetchAll();
$collections = $pdo->query('SELECT collection_id, collection_name, spot_id FROM question_collections WHERE status = "ACTIVE" ORDER BY collection_name')->fetchAll();
$rows = $pdo->query('SELECT r.*, s.spot_name, c.collection_name 
    FROM riddle_pool r 
    JOIN spot_campaigns s ON s.spot_id = r.spot_id 
    LEFT JOIN question_collections c ON c.collection_id = r.collection_id 
    ORDER BY r.riddle_id DESC')->fetchAll();

$pageTitle = 'Question Manager';
$pageSubtitle = 'Create, configure reward payouts, consolation rules, and bulk-manage dynamic riddle questions';
require APP_ROOT . '/includes/header.php';
?>

<style>
.section-banner {
    grid-column: 1 / -1;
    background: #f0f4f9;
    border-left: 4px solid var(--blue);
    padding: 10px 14px;
    margin-top: 10px;
    margin-bottom: 2px;
    border-radius: 4px;
    font-weight: 700;
    color: var(--navy);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.help-muted {
    font-size: 12px;
    color: var(--muted);
    margin-top: 2px;
}
</style>

<section class="panel">
    <div class="panel-head">
        <h2><?= $editRow ? 'Edit Question #' . e((string)$editRow['riddle_id']) : 'Add Question & Reward Rules' ?></h2>
        <div class="actions">
            <?php if ($editRow): ?>
                <a class="btn secondary small" href="<?= e(app_url('/admin/hunts/questions.php')) ?>">Cancel Edit</a>
            <?php endif; ?>
            <a class="btn secondary" href="<?= e(app_url('/admin/hunts/collections.php')) ?>">Question Collections</a>
            <a class="btn secondary" href="<?= e(app_url('/admin/hunts/questions_bulk.php')) ?>">Bulk Upload (CSV)</a>
        </div>
    </div>

    <form method="post" class="form-grid" id="question-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editRow ? 'edit' : 'create' ?>">
        <?php if ($editRow): ?>
            <input type="hidden" name="id" value="<?= e((string)$editRow['riddle_id']) ?>">
        <?php endif; ?>

        <!-- SECTION 1: CAMPAIGN MAPPING & QUESTION SECTION -->
        <div class="section-banner">1. Campaign Mapping &amp; Question Section</div>

        <div class="field">
            <label>Master Category Campaign <span style="color:var(--red)">*</span></label>
            <select name="spot_id" id="field-spot-id" required>
                <option value="">Select Target Hunt / Campaign</option>
                <?php foreach ($spots as $s): ?>
                    <option value="<?= e($s['spot_id']) ?>" <?= ($editRow && $editRow['spot_id'] === $s['spot_id']) ? 'selected' : '' ?>>
                        <?= e($s['spot_name'] . ' · ' . $s['spot_id']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="help-muted">Associates question dynamically with specific campaign</span>
        </div>

        <div class="field">
            <label>Question Section</label>
            <select name="question_mode" id="field-question-mode">
                <option value="SINGLE" <?= ($editRow && $editRow['question_mode'] === 'SINGLE') ? 'selected' : '' ?>>Single Question Entry</option>
                <option value="MULTIPLE" <?= ($editRow && $editRow['question_mode'] === 'MULTIPLE') ? 'selected' : '' ?>>Multiple Questions</option>
            </select>
            <span class="help-muted">For high-volume entries, use Bulk Upload</span>
        </div>

        <div class="field full">
            <label>Question / Riddle Text (Hindi / English) <span style="color:var(--red)">*</span></label>
            <textarea name="riddle_text" id="field-riddle-text" placeholder="Enter main quiz question or location riddle text here..." required><?= e($editRow['riddle_text'] ?? '') ?></textarea>
        </div>

        <div class="field full">
            <label>Instructions for User (Optional)</label>
            <textarea name="instruction_text" id="field-instruction-text" placeholder="Read clue, visit location, scan product QR, and answer within time limit..."><?= e($editRow['instruction_text'] ?? '') ?></textarea>
        </div>

        <!-- SECTION 2: ANSWERS, MEDIA & TIMING CONTROLS -->
        <div class="section-banner">2. Answers, Media &amp; Timing Controls</div>

        <div class="field">
            <label>Primary Answer <span style="color:var(--red)">*</span></label>
            <input type="text" name="primary_answer" id="field-primary-answer" placeholder="Exact correct answer string..." value="<?= e($editRow['primary_answer'] ?? '') ?>" required>
            <span class="help-muted">System matches user input with case &amp; punctuation normalization</span>
        </div>

        <div class="field">
            <label>Alternative Synonyms (Comma-Separated)</label>
            <?php
            $synText = '';
            if ($editRow && !empty($editRow['synonyms'])) {
                $synArr = json_decode((string)$editRow['synonyms'], true) ?: [];
                $synText = implode(', ', $synArr);
            }
            ?>
            <input type="text" name="synonyms" id="field-synonyms" placeholder="Synonym 1, Synonym 2, Spelling variants..." value="<?= e($synText) ?>">
            <span class="help-muted">Acceptable alternate spellings or multilingual answers</span>
        </div>

        <div class="field">
            <label>YouTube Video ID / Channel URL</label>
            <input type="text" name="yt_video_id" id="field-yt-video" placeholder="https://youtube.com/watch?v=... (Hint Clue)" value="<?= e($editRow['yt_video_id'] ?? '') ?>">
            <span class="help-muted">Serves as interactive multimedia clue</span>
        </div>

        <div class="field">
            <label>Video Channel URL (Optional)</label>
            <input type="url" name="channel_url" id="field-channel-url" placeholder="https://youtube.com/@channel" value="<?= e($editRow['channel_url'] ?? '') ?>">
        </div>

        <div class="field">
            <label>Time to Answer (Seconds) <span style="color:var(--red)">*</span></label>
            <input type="number" name="answer_time_seconds" id="field-answer-time" min="5" max="3600" value="<?= e((string)($editRow['answer_time_seconds'] ?? 60)) ?>" required>
            <span class="help-muted">Strict server-enforced countdown timer</span>
        </div>

        <div class="field">
            <label>Status</label>
            <select name="status">
                <option value="ACTIVE" <?= ($editRow && $editRow['status'] === 'ACTIVE') ? 'selected' : '' ?>>ACTIVE</option>
                <option value="INACTIVE" <?= ($editRow && $editRow['status'] === 'INACTIVE') ? 'selected' : '' ?>>INACTIVE</option>
            </select>
        </div>

        <!-- SECTION 3: STRUCTURED REWARD PAYOUT & EXPIRY COLUMNS -->
        <div class="section-banner">3. Structured Reward Payout &amp; Expiry Columns</div>

        <div class="field">
            <label>Reward Payout Type <span style="color:var(--red)">*</span></label>
            <select name="reward_type" id="field-reward-type" required>
                <option value="COINS" <?= ($editRow && $editRow['reward_type'] === 'COINS') ? 'selected' : '' ?>>Coins (Wallet Bonus)</option>
                <option value="CASH" <?= ($editRow && $editRow['reward_type'] === 'CASH') ? 'selected' : '' ?>>Cash (Real Balance)</option>
                <option value="CASHBACK" <?= ($editRow && $editRow['reward_type'] === 'CASHBACK') ? 'selected' : '' ?>>Cashback</option>
                <option value="COUPON" <?= ($editRow && $editRow['reward_type'] === 'COUPON') ? 'selected' : '' ?>>Discount Coupon</option>
                <option value="FREE_PIN" <?= ($editRow && $editRow['reward_type'] === 'FREE_PIN') ? 'selected' : '' ?>>Free Claim PIN</option>
            </select>
        </div>

        <div class="field">
            <label>Reward Points / Amount <span style="color:var(--red)">*</span></label>
            <input type="number" step="0.01" min="0.01" name="reward_value" id="field-reward-value" placeholder="e.g. 50.00" value="<?= e((string)($editRow['reward_value'] ?? '50.00')) ?>" required>
            <span class="help-muted">Value credited upon correct answer</span>
        </div>

        <div class="field">
            <label>Reward Expiry &amp; Validity (Days) <span style="color:var(--red)">*</span></label>
            <input type="number" min="1" max="365" name="reward_expiry_days" id="field-expiry-days" placeholder="e.g. 7" value="<?= e((string)($editRow['reward_expiry_days'] ?? 7)) ?>" required>
            <span class="help-muted">e.g., 7 days (Valid for 1 Week Auto-Expiry)</span>
        </div>

        <!-- SECTION 4: WRONG ANSWER & CONSOLATION RULES -->
        <div class="section-banner">4. Wrong Answer &amp; Consolation Rules</div>

        <div class="field">
            <label>Consolation / Wrong Answer Fallback Offer</label>
            <input type="text" name="fallback_offer_title" id="field-fallback-offer" placeholder="e.g., Partner Petrol Pump 2% Discount Coupon (Auto-triggered on incorrect attempt)" value="<?= e($editRow['fallback_offer_title'] ?? '') ?>">
            <span class="help-muted">Engages users even when they submit an incorrect answer</span>
        </div>

        <div class="field">
            <label>Assign to Question Collection</label>
            <select name="collection_id" id="field-collection-id">
                <option value="">-- No Collection / Unassigned --</option>
                <?php foreach ($collections as $col): ?>
                    <option value="<?= e((string)$col['collection_id']) ?>" <?= ($editRow && (int)$editRow['collection_id'] === (int)$col['collection_id']) ? 'selected' : '' ?>>
                        <?= e($col['collection_name'] . ' (' . $col['spot_id'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="help-muted">Inherits collection rotation &amp; negative marking rules</span>
        </div>

        <div class="field full actions" style="margin-top: 12px;">
            <button class="btn" type="submit">
                <?= $editRow ? 'Update Question &amp; Save Rules' : 'Add Question to Collection &amp; Save Rules' ?>
            </button>
            <?php if (!$editRow): ?>
                <button class="btn secondary" type="button" id="load-question-sample">Load Sample Hindi/English Riddle</button>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Configured Questions &amp; Reward Rules (<?= count($rows) ?>)</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Campaign &amp; Mode</th>
                    <th>Question / Clue</th>
                    <th>Answer / Synonyms</th>
                    <th>Reward Payout</th>
                    <th>Consolation Offer</th>
                    <th>Timer</th>
                    <th>Collection</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $synList = json_decode((string)$r['synonyms'], true) ?: [];
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($r['spot_name']) ?></strong><br>
                            <small class="badge"><?= e($r['question_mode'] ?? 'SINGLE') ?></small>
                        </td>
                        <td style="max-width: 280px; white-space: normal;">
                            <strong><?= e($r['riddle_text']) ?></strong>
                            <?php if (!empty($r['instruction_text'])): ?>
                                <br><small style="color:var(--muted)"><?= e($r['instruction_text']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($r['yt_video_id'])): ?>
                                <br><small style="color:var(--blue)">▶ Video Clue: <?= e($r['yt_video_id']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color:var(--green)"><?= e($r['primary_answer']) ?></strong>
                            <?php if ($synList): ?>
                                <br><small style="color:var(--muted)">Synonyms: <?= e(implode(', ', $synList)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge success"><?= e($r['reward_type'] ?? 'COINS') ?>: <?= e((string)($r['reward_value'] ?? '50.00')) ?></span>
                            <br><small style="color:var(--muted)">Expires in <?= e((string)($r['reward_expiry_days'] ?? 7)) ?> days</small>
                        </td>
                        <td style="max-width: 180px; white-space: normal;">
                            <?= !empty($r['fallback_offer_title']) ? '<span style="color:var(--amber)">' . e($r['fallback_offer_title']) . '</span>' : '<span style="color:var(--muted)">—</span>' ?>
                        </td>
                        <td><?= e((string)($r['answer_time_seconds'] ?? 60)) ?> sec</td>
                        <td><?= !empty($r['collection_name']) ? e($r['collection_name']) : '<span style="color:var(--muted)">Unassigned</span>' ?></td>
                        <td>
                            <span class="badge <?= strtolower((string)$r['status']) === 'active' ? 'active' : 'blocked' ?>">
                                <?= e($r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary small" href="?edit=<?= e((string)$r['riddle_id']) ?>">Edit</a>
                                <form method="post" data-confirm="Are you sure you want to delete this question?" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e((string)$r['riddle_id']) ?>">
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="9" class="empty">No questions configured yet. Use the form above to add your first question.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.getElementById('load-question-sample')?.addEventListener('click', function() {
    document.getElementById('field-riddle-text').value = 'Mujhe pehno toh waqt rukta nahi, batao main kaun? (Wear me and time never stops, who am I?)';
    document.getElementById('field-instruction-text').value = 'Visit the central clock tower, scan the merchant QR code, and submit your answer before the timer expires!';
    document.getElementById('field-primary-answer').value = 'Ghadi';
    document.getElementById('field-synonyms').value = 'watch, wrist watch, clock, samay, waqt';
    document.getElementById('field-yt-video').value = 'dQw4w9WgXcQ';
    document.getElementById('field-channel-url').value = 'https://youtube.com/@hunter_wallet';
    document.getElementById('field-answer-time').value = '60';
    document.getElementById('field-reward-type').value = 'COINS';
    document.getElementById('field-reward-value').value = '100.00';
    document.getElementById('field-expiry-days').value = '7';
    document.getElementById('field-fallback-offer').value = 'Partner Petrol Pump 2% Discount Coupon (Auto-issued on incorrect answer)';
});
</script>

<?php require APP_ROOT . '/includes/footer.php'; ?>
