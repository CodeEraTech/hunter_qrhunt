<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_permission('hunts.manage');

$pdo = db();

if (is_post()) {
    verify_csrf();
    try {
        $a = (string)input('action');

        if ($a === 'create') {
            $name = trim((string)input('collection_name'));
            $spot = trim((string)input('spot_id'));
            $desc = trim((string)input('description'));
            $rotation = strtoupper((string)input('rotation_method', 'SEQUENTIAL'));
            $qPerSession = max(1, (int)input('questions_per_session', 1));
            $negativeMarking = max(0, (int)input('negative_marking_points', 0));
            $fallback = trim((string)input('fallback_offer_title'));
            $status = strtoupper((string)input('status', 'ACTIVE'));

            if ($name === '' || $spot === '') {
                throw new InvalidArgumentException('Collection name and campaign are required.');
            }

            $stmt = $pdo->prepare('INSERT INTO question_collections(
                collection_name, spot_id, description, rotation_method,
                questions_per_session, negative_marking_points, fallback_offer_title,
                status, created_by, created_at, updated_at
            ) VALUES(?,?,?,?,?,?,?,?,?, NOW(), NOW())');

            $stmt->execute([
                $name,
                $spot,
                $desc ?: null,
                $rotation,
                $qPerSession,
                $negativeMarking,
                $fallback ?: null,
                $status,
                admin()['id'],
            ]);

            audit_log((int)admin()['id'], 'QUESTION_COLLECTION_CREATED', 'question_collections', (string)$pdo->lastInsertId());
            flash('success', 'Collection created successfully.');
        } elseif ($a === 'edit') {
            $colId = (int)input('collection_id');
            $name = trim((string)input('collection_name'));
            $spot = trim((string)input('spot_id'));
            $desc = trim((string)input('description'));
            $rotation = strtoupper((string)input('rotation_method', 'SEQUENTIAL'));
            $qPerSession = max(1, (int)input('questions_per_session', 1));
            $negativeMarking = max(0, (int)input('negative_marking_points', 0));
            $fallback = trim((string)input('fallback_offer_title'));
            $status = strtoupper((string)input('status', 'ACTIVE'));

            if ($colId <= 0 || $name === '' || $spot === '') {
                throw new InvalidArgumentException('Valid collection details are required.');
            }

            $updateStmt = $pdo->prepare('UPDATE question_collections SET 
                collection_name = ?, spot_id = ?, description = ?, rotation_method = ?,
                questions_per_session = ?, negative_marking_points = ?, fallback_offer_title = ?,
                status = ?, updated_at = NOW()
                WHERE collection_id = ?');

            $updateStmt->execute([
                $name,
                $spot,
                $desc ?: null,
                $rotation,
                $qPerSession,
                $negativeMarking,
                $fallback ?: null,
                $status,
                $colId,
            ]);

            audit_log((int)admin()['id'], 'QUESTION_COLLECTION_UPDATED', 'question_collections', (string)$colId);
            flash('success', 'Collection updated successfully.');
        } elseif ($a === 'delete') {
            $colId = (int)input('collection_id');
            $pdo->prepare('DELETE FROM question_collection_items WHERE collection_id = ?')->execute([$colId]);
            $pdo->prepare('DELETE FROM question_collections WHERE collection_id = ?')->execute([$colId]);
            audit_log((int)admin()['id'], 'QUESTION_COLLECTION_DELETED', 'question_collections', (string)$colId);
            flash('success', 'Collection deleted successfully.');
        } elseif ($a === 'add') {
            $collectionId = (int)input('collection_id');
            $questionId = (int)input('riddle_id');

            $next = $pdo->prepare('SELECT COALESCE(MAX(question_order), 0) + 1 FROM question_collection_items WHERE collection_id = ?');
            $next->execute([$collectionId]);
            $order = (int)$next->fetchColumn();

            $pdo->prepare('INSERT IGNORE INTO question_collection_items(collection_id, riddle_id, question_order) VALUES(?,?,?)')
                ->execute([$collectionId, $questionId, $order]);

            $pdo->prepare('UPDATE riddle_pool SET collection_id = ? WHERE riddle_id = ?')
                ->execute([$collectionId, $questionId]);

            flash('success', 'Question added to collection.');
        } elseif ($a === 'remove') {
            $collectionId = (int)input('collection_id');
            $questionId = (int)input('riddle_id');

            $pdo->prepare('DELETE FROM question_collection_items WHERE collection_id = ? AND riddle_id = ?')
                ->execute([$collectionId, $questionId]);

            $pdo->prepare('UPDATE riddle_pool SET collection_id = NULL WHERE riddle_id = ? AND collection_id = ?')
                ->execute([$questionId, $collectionId]);

            flash('success', 'Question removed from collection.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/hunts/collections.php');
}

$editColId = (int)input('edit', 0);
$editCol = null;
if ($editColId > 0) {
    $ecStmt = $pdo->prepare('SELECT * FROM question_collections WHERE collection_id = ?');
    $ecStmt->execute([$editColId]);
    $editCol = $ecStmt->fetch() ?: null;
}

$spots = $pdo->query('SELECT spot_id, spot_name FROM spot_campaigns ORDER BY spot_name')->fetchAll();

try {
    $questions = $pdo->query('SELECT riddle_id, spot_id, riddle_text, primary_answer FROM riddle_pool WHERE status = "ACTIVE" ORDER BY riddle_id DESC')->fetchAll();
} catch (Throwable $e) {
    $questions = $pdo->query('SELECT riddle_id, spot_id, riddle_text, primary_answer FROM riddle_pool ORDER BY riddle_id DESC')->fetchAll();
}

$collections = $pdo->query('SELECT c.*, s.spot_name,
    (SELECT COUNT(*) FROM question_collection_items i WHERE i.collection_id = c.collection_id) AS question_count
    FROM question_collections c 
    JOIN spot_campaigns s ON s.spot_id = c.spot_id 
    ORDER BY c.collection_id DESC')->fetchAll();

// Fetch items for all collections to render membership details
$collectionItems = [];
$itemsStmt = $pdo->query('SELECT i.collection_id, i.riddle_id, i.question_order, r.riddle_text, r.primary_answer, r.reward_type, r.reward_value 
    FROM question_collection_items i 
    JOIN riddle_pool r ON r.riddle_id = i.riddle_id 
    ORDER BY i.collection_id, i.question_order ASC');
while ($it = $itemsStmt->fetch()) {
    $collectionItems[(int)$it['collection_id']][] = $it;
}

$pageTitle = 'Question Collections';
$pageSubtitle = 'Organize questions with configurable rotation, negative marking penalties, and fallback offers';
require APP_ROOT . '/includes/header.php';
?>

<section class="panel">
    <div class="panel-head">
        <h2><?= $editCol ? 'Edit Collection: ' . e($editCol['collection_name']) : 'Create Collection' ?></h2>
        <div class="actions">
            <?php if ($editCol): ?>
                <a class="btn secondary small" href="<?= e(app_url('/admin/hunts/collections.php')) ?>">Cancel Edit</a>
            <?php endif; ?>
            <a class="btn secondary" href="<?= e(app_url('/admin/hunts/questions.php')) ?>">Question Manager</a>
        </div>
    </div>

    <!-- UI Layout Blueprint: Create Collection Form -->
    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editCol ? 'edit' : 'create' ?>">
        <?php if ($editCol): ?>
            <input type="hidden" name="collection_id" value="<?= e((string)$editCol['collection_id']) ?>">
        <?php endif; ?>

        <div class="field">
            <label>Collection Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="collection_name" placeholder="e.g., Patna Heritage Hunt Series 1" value="<?= e($editCol['collection_name'] ?? '') ?>" required>
        </div>

        <div class="field">
            <label>Master Category Campaign <span style="color:var(--red)">*</span></label>
            <select name="spot_id" required>
                <option value="">Select target campaign</option>
                <?php foreach ($spots as $s): ?>
                    <option value="<?= e($s['spot_id']) ?>" <?= ($editCol && $editCol['spot_id'] === $s['spot_id']) ? 'selected' : '' ?>>
                        <?= e($s['spot_name'] . ' · ' . $s['spot_id']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field full">
            <label>Description</label>
            <textarea name="description" placeholder="Detailed overview of what this collection covers..."><?= e($editCol['description'] ?? '') ?></textarea>
        </div>

        <div class="field">
            <label>Rotation Method</label>
            <select name="rotation_method">
                <?php foreach (['SEQUENTIAL', 'RANDOM', 'RANDOM_NO_REPEAT', 'ROUND_ROBIN', 'WEIGHTED'] as $rm): ?>
                    <option value="<?= $rm ?>" <?= ($editCol && $editCol['rotation_method'] === $rm) ? 'selected' : '' ?>><?= $rm ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Questions per Session</label>
            <input type="number" name="questions_per_session" min="1" value="<?= e((string)($editCol['questions_per_session'] ?? 1)) ?>" required>
            <small class="help">Number of questions presented in each player session</small>
        </div>

        <div class="field" style="border: 1px dashed var(--red); padding: 10px; border-radius: 8px; background: #fffafb;">
            <label style="color:var(--red);">Negative Marking (Wrong Answer) Points</label>
            <input type="number" name="negative_marking_points" min="0" value="<?= e((string)($editCol['negative_marking_points'] ?? 0)) ?>" placeholder="e.g., 2 Points (0 = No Penalty)">
            <small class="help" style="color:var(--red);">Penalty points auto-deducted on wrong answer. System protects wallet balance from dropping below 0.</small>
        </div>

        <div class="field">
            <label>Collection Fallback Offer (Optional)</label>
            <input type="text" name="fallback_offer_title" placeholder="e.g., Flat 10% Off on Partner Store" value="<?= e($editCol['fallback_offer_title'] ?? '') ?>">
            <small class="help">Default consolation offer when question-level offer is not set</small>
        </div>

        <div class="field">
            <label>Status</label>
            <select name="status">
                <option value="ACTIVE" <?= ($editCol && $editCol['status'] === 'ACTIVE') ? 'selected' : '' ?>>ACTIVE</option>
                <option value="INACTIVE" <?= ($editCol && $editCol['status'] === 'INACTIVE') ? 'selected' : '' ?>>INACTIVE</option>
            </select>
        </div>

        <div class="field full actions">
            <button class="btn" type="submit"><?= $editCol ? 'Update Collection' : 'Create Collection' ?></button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Existing Collections (<?= count($collections) ?>)</h2>
    </div>

    <?php foreach ($collections as $c): ?>
        <article class="card" style="margin-bottom: 16px; border: 1px solid var(--line);">
            <div class="panel-head" style="margin-bottom: 10px;">
                <div>
                    <strong style="font-size: 16px;"><?= e($c['collection_name']) ?></strong>
                    <div style="margin-top: 4px; font-size: 12px; color: var(--muted);">
                        <span>Campaign: <strong><?= e($c['spot_name']) ?></strong></span> · 
                        <span>Rotation: <strong><?= e($c['rotation_method']) ?></strong></span> · 
                        <span>Questions/Session: <strong><?= e((string)$c['questions_per_session']) ?></strong></span> · 
                        <span>Penalty: <strong style="color:<?= (int)$c['negative_marking_points'] > 0 ? 'var(--red)' : 'var(--muted)' ?>"><?= e((string)$c['negative_marking_points']) ?> pts</strong></span>
                        <?php if (!empty($c['fallback_offer_title'])): ?>
                            · <span>Fallback Offer: <strong style="color:var(--amber)"><?= e($c['fallback_offer_title']) ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <span class="badge <?= strtolower((string)$c['status']) === 'active' ? 'active' : 'blocked' ?>"><?= e($c['status']) ?></span>
                    <a class="btn secondary small" href="?edit=<?= e((string)$c['collection_id']) ?>">Edit</a>
                    <form method="post" data-confirm="Delete this collection?" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="collection_id" value="<?= e((string)$c['collection_id']) ?>">
                        <button class="btn danger small" type="submit">Delete</button>
                    </form>
                </div>
            </div>

            <?php if (!empty($c['description'])): ?>
                <p style="margin: 0 0 12px; color: var(--muted); font-size: 13px;"><?= e($c['description']) ?></p>
            <?php endif; ?>

            <!-- Assigned Questions in this collection -->
            <?php $items = $collectionItems[(int)$c['collection_id']] ?? []; ?>
            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 12px;">
                <div style="font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--muted); margin-bottom: 8px;">
                    Questions in this Collection (<?= count($items) ?>)
                </div>
                <?php if ($items): ?>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <?php foreach ($items as $item): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: white; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--line);">
                                <div>
                                    <span style="font-weight: 700; margin-right: 8px; color: var(--blue);">#<?= e((string)$item['question_order']) ?></span>
                                    <span><?= e(mb_strimwidth($item['riddle_text'], 0, 80, '…')) ?></span>
                                    <span style="margin-left: 8px; color: var(--green); font-size: 12px;">[Ans: <?= e($item['primary_answer']) ?>]</span>
                                    <span class="badge success" style="margin-left: 6px; font-size: 10px;"><?= e($item['reward_type'] ?? 'COINS') ?> <?= e((string)($item['reward_value'] ?? '50.00')) ?></span>
                                </div>
                                <form method="post" style="margin: 0;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="collection_id" value="<?= e((string)$c['collection_id']) ?>">
                                    <input type="hidden" name="riddle_id" value="<?= e((string)$item['riddle_id']) ?>">
                                    <button class="btn danger small" style="padding: 2px 7px; font-size: 11px;" type="submit">Remove</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="color: var(--muted); font-size: 12px; font-style: italic;">No questions assigned to this collection yet. Add questions below.</div>
                <?php endif; ?>
            </div>

            <!-- Add question to collection form -->
            <form method="post" class="actions" style="background: white; border: 1px solid var(--line); padding: 8px 12px; border-radius: 8px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="collection_id" value="<?= e((string)$c['collection_id']) ?>">
                <label style="font-weight: 600; font-size: 13px;">Add Question:</label>
                <select name="riddle_id" required style="flex: 1; min-width: 250px;">
                    <option value="">Select question from campaign</option>
                    <?php 
                    $existingIds = array_column($items, 'riddle_id');
                    foreach ($questions as $q):
                        if ($q['spot_id'] === $c['spot_id'] && !in_array($q['riddle_id'], $existingIds, true)):
                    ?>
                        <option value="<?= e((string)$q['riddle_id']) ?>">
                            <?= e(mb_strimwidth($q['riddle_text'], 0, 75, '…')) ?> [Ans: <?= e($q['primary_answer']) ?>]
                        </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
                <button class="btn small" type="submit">Add to Collection</button>
            </form>
        </article>
    <?php endforeach; ?>

    <?php if (!$collections): ?>
        <div class="empty">No collections configured yet. Use the form above to create your first question collection.</div>
    <?php endif; ?>
</section>

<?php require APP_ROOT . '/includes/footer.php'; ?>
