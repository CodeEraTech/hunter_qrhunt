<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_permission('hunts.manage');

$pdo = db();

if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hunter-questions-master-template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'question_mode',
        'riddle_text',
        'instruction_text',
        'primary_answer',
        'synonyms',
        'yt_video_id',
        'channel_url',
        'answer_time_seconds',
        'reward_type',
        'reward_value',
        'reward_expiry_days',
        'fallback_offer_title',
        'status',
    ]);
    fputcsv($out, [
        'SINGLE',
        'Mujhe pehno toh waqt rukta nahi, batao main kaun? (Hindi / English riddle)',
        'Visit location, scan product QR and submit answer before countdown ends.',
        'Ghadi',
        'watch, wrist watch, clock, samay',
        'dQw4w9WgXcQ',
        'https://youtube.com/@hunter_wallet',
        '60',
        'COINS',
        '50.00',
        '7',
        'Partner Petrol Pump 2% Discount Coupon',
        'ACTIVE',
    ]);
    fputcsv($out, [
        'SINGLE',
        'Gol gol ghoomti hai par chalti nahi, dhoop mein sukhati nahi, batao kya?',
        'Look around the merchant store and scan the clue QR code.',
        'Pankha',
        'fan, ceiling fan, table fan',
        '',
        '',
        '45',
        'CASHBACK',
        '25.00',
        '14',
        '10% OFF at Grocery Corner',
        'ACTIVE',
    ]);
    fclose($out);
    exit;
}

if (is_post()) {
    verify_csrf();
    try {
        $spot = trim((string)input('spot_id'));
        $collectionId = (int)input('collection_id') ?: null;
        $file = $_FILES['csv'] ?? null;

        if ($spot === '' || !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Select a target campaign and valid CSV file.');
        }

        $fh = fopen((string)$file['tmp_name'], 'rb');
        if (!$fh) {
            throw new RuntimeException('Could not read the uploaded CSV file.');
        }

        $expected = [
            'question_mode',
            'riddle_text',
            'instruction_text',
            'primary_answer',
            'synonyms',
            'yt_video_id',
            'channel_url',
            'answer_time_seconds',
            'reward_type',
            'reward_value',
            'reward_expiry_days',
            'fallback_offer_title',
            'status',
        ];

        // Also support legacy 9-column format gracefully if uploaded
        $legacyExpected = [
            'question_mode',
            'riddle_text',
            'instruction_text',
            'primary_answer',
            'synonyms',
            'yt_video_id',
            'channel_url',
            'answer_time_seconds',
            'status',
        ];

        $rawHeader = fgetcsv($fh) ?: [];
        $headers = array_map(static fn($v) => strtolower(trim((string)$v)), $rawHeader);

        $isLegacy = ($headers === $legacyExpected);
        if ($headers !== $expected && !$isLegacy) {
            throw new InvalidArgumentException('CSV format mismatch. Please download and use the official CSV template.');
        }

        $rows = [];
        $activeCols = $isLegacy ? $legacyExpected : $expected;

        while (($r = fgetcsv($fh)) !== false) {
            if (count(array_filter($r, static fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            if (count($r) !== count($activeCols)) {
                throw new InvalidArgumentException('Row contains incorrect number of columns: expected ' . count($activeCols) . ', found ' . count($r));
            }
            $v = array_combine($activeCols, $r);

            $mode = strtoupper(trim((string)$v['question_mode']));
            $status = strtoupper(trim((string)$v['status']));
            $time = max(5, min(3600, (int)($v['answer_time_seconds'] ?? 60)));
            $text = trim((string)$v['riddle_text']);
            $answer = trim((string)$v['primary_answer']);

            if (!in_array($mode, ['SINGLE', 'MULTIPLE'], true)) $mode = 'SINGLE';
            if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) $status = 'ACTIVE';
            if ($text === '' || $answer === '') {
                throw new InvalidArgumentException('Question text and primary answer cannot be empty in any row.');
            }

            $rewardType = strtoupper(trim((string)($v['reward_type'] ?? 'COINS')));
            if (!in_array($rewardType, ['COINS', 'CASH', 'CASHBACK', 'COUPON', 'FREE_PIN'], true)) {
                $rewardType = 'COINS';
            }
            $rewardVal = max(0.01, (float)($v['reward_value'] ?? 50.00));
            $rewardExp = max(1, min(365, (int)($v['reward_expiry_days'] ?? 7)));
            $fallback = trim((string)($v['fallback_offer_title'] ?? ''));

            $rows[] = [
                'mode' => $mode,
                'text' => $text,
                'instructions' => trim((string)($v['instruction_text'] ?? '')) ?: null,
                'answer' => $answer,
                'synonyms' => array_values(array_filter(array_map('trim', explode(',', (string)($v['synonyms'] ?? ''))))),
                'yt' => trim((string)($v['yt_video_id'] ?? '')) ?: null,
                'channel' => trim((string)($v['channel_url'] ?? '')) ?: null,
                'time' => $time,
                'reward_type' => $rewardType,
                'reward_value' => $rewardVal,
                'reward_expiry_days' => $rewardExp,
                'fallback_offer_title' => $fallback ?: null,
                'status' => $status,
            ];
        }
        fclose($fh);

        if (!$rows) {
            throw new InvalidArgumentException('CSV contains no data rows.');
        }
        if (count($rows) > 5000) {
            throw new InvalidArgumentException('Maximum 5,000 questions per batch upload.');
        }

        $pdo->beginTransaction();
        $insStmt = $pdo->prepare('INSERT INTO riddle_pool(
            spot_id, question_mode, riddle_text, instruction_text,
            primary_answer, synonyms, yt_video_id, channel_url,
            answer_time_seconds, reward_type, reward_value,
            reward_expiry_days, fallback_offer_title, collection_id,
            status, created_at, updated_at
        ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())');

        $orderStart = 1;
        if ($collectionId) {
            $orderStart = (int)$pdo->query("SELECT COALESCE(MAX(question_order), 0) + 1 FROM question_collection_items WHERE collection_id = {$collectionId}")->fetchColumn();
        }

        $colItemStmt = $pdo->prepare('INSERT IGNORE INTO question_collection_items(collection_id, riddle_id, question_order) VALUES(?,?,?)');

        foreach ($rows as $item) {
            $insStmt->execute([
                $spot,
                $item['mode'],
                $item['text'],
                $item['instructions'],
                $item['answer'],
                safe_json($item['synonyms']),
                $item['yt'],
                $item['channel'],
                $item['time'],
                $item['reward_type'],
                $item['reward_value'],
                $item['reward_expiry_days'],
                $item['fallback_offer_title'],
                $collectionId,
                $item['status'],
            ]);
            $newRiddleId = (int)$pdo->lastInsertId();

            if ($collectionId) {
                $colItemStmt->execute([$collectionId, $newRiddleId, $orderStart++]);
            }
        }

        audit_log((int)admin()['id'], 'QUESTIONS_BULK_IMPORTED', 'riddle_pool', null, null, [
            'count' => count($rows),
            'spot_id' => $spot,
            'collection_id' => $collectionId,
        ]);
        $pdo->commit();
        flash('success', count($rows) . ' questions imported and rules configured successfully.');
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', $e->getMessage());
    }
    redirect('/admin/hunts/questions_bulk.php');
}

$spots = $pdo->query('SELECT spot_id, spot_name FROM spot_campaigns ORDER BY spot_name')->fetchAll();
$collections = $pdo->query('SELECT collection_id, collection_name, spot_id FROM question_collections WHERE status = "ACTIVE" ORDER BY collection_name')->fetchAll();

$pageTitle = 'Bulk Question Upload';
$pageSubtitle = 'Import validated single or multiple questions, rewards, and consolation rules using CSV';
require APP_ROOT . '/includes/header.php';
?>

<section class="panel">
    <div class="panel-head">
        <h2>Upload Question Bank &amp; Reward Rules</h2>
        <div class="actions">
            <a class="btn secondary" href="<?= e(app_url('/admin/hunts/questions.php')) ?>">Question Manager</a>
            <a class="btn secondary" href="?download_template=1">Download Sample CSV Template</a>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>

        <div class="field">
            <label>Master Category Campaign <span style="color:var(--red)">*</span></label>
            <select name="spot_id" required>
                <option value="">Select target campaign</option>
                <?php foreach ($spots as $s): ?>
                    <option value="<?= e($s['spot_id']) ?>"><?= e($s['spot_name'] . ' · ' . $s['spot_id']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Assign to Question Collection (Optional)</label>
            <select name="collection_id">
                <option value="">-- No Collection / Unassigned --</option>
                <?php foreach ($collections as $c): ?>
                    <option value="<?= e((string)$c['collection_id']) ?>"><?= e($c['collection_name'] . ' (' . $c['spot_id'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field full">
            <label>Question CSV File <span style="color:var(--red)">*</span></label>
            <input type="file" name="csv" accept=".csv,text/csv" required>
            <small class="help">
                CSV headers: <code>question_mode, riddle_text, instruction_text, primary_answer, synonyms, yt_video_id, channel_url, answer_time_seconds, reward_type, reward_value, reward_expiry_days, fallback_offer_title, status</code>.
                Supports up to 5,000 questions per upload.
            </small>
        </div>

        <div class="field full actions">
            <button class="btn" type="submit">Upload &amp; Import Questions</button>
        </div>
    </form>
</section>

<?php require APP_ROOT . '/includes/footer.php'; ?>
