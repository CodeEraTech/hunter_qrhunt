<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");

$_SERVER['REQUEST_URI'] = '/tests/question_broadcast_offers_integration.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Hunter integration test';

require dirname(__DIR__) . '/includes/bootstrap.php';

function assert_true(bool $cond, string $msg): void {
    if (!$cond) throw new RuntimeException("Assertion failed: {$msg}");
}

function assert_throws(callable $fn, string $msg): void {
    try {
        $fn();
    } catch (Throwable $e) {
        return;
    }
    throw new RuntimeException("Expected exception but none thrown: {$msg}");
}

$pdo = db();
$pfx = bin2hex(random_bytes(4));

// 1. Setup test user and wallet
$testMobile = '9' . str_pad((string)random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
$pdo->prepare('INSERT INTO users(name, mobile, email, password_hash, status, is_verified) VALUES(?,?,?,?,"ACTIVE",1)')
    ->execute(['Test Hunter User', $testMobile, "hunter-{$pfx}@example.test", password_hash('Pass123!', PASSWORD_DEFAULT)]);
$userId = (int)$pdo->lastInsertId();

$pdo->prepare('INSERT INTO wallets(user_id, balance, bonus_balance, status) VALUES(?, 100.00, 20.00, "ACTIVE")')
    ->execute([$userId]);
$walletId = (int)$pdo->lastInsertId();

// Setup admin
$role = (int)$pdo->query("SELECT id FROM admin_roles WHERE name='Super Admin'")->fetchColumn();
$pdo->prepare('INSERT INTO admins(role_id, name, username, email, password_hash, status) VALUES(?,?,?,?,?,"ACTIVE")')
    ->execute([$role, 'Test Admin', "adm-{$pfx}", "adm-{$pfx}@example.test", password_hash('Pass123!', PASSWORD_DEFAULT)]);
$adminId = (int)$pdo->lastInsertId();

// Setup spot campaign
$spotId = "SPOT_{$pfx}";
$pdo->prepare('INSERT INTO spot_campaigns(spot_id, master_token_hash, spot_name, latitude, longitude, is_active, created_by) VALUES(?, ?, "Test Spot", 25.594095, 85.137566, 1, ?)')
    ->execute([$spotId, hash('sha256', $spotId), $adminId]);

// ==========================================
// TEST 1: Question Normalization & Synonyms
// ==========================================
echo "Running Test 1: Question normalization & synonyms...\n";
assert_true(match_question_answer('  Ghadi  ', 'ghadi', ['watch', 'clock']), 'Trim & case failed.');
assert_true(match_question_answer('Watch!', 'ghadi', ['watch', 'clock']), 'Punctuation stripping failed.');
assert_true(match_question_answer('wrist   watch', 'ghadi', ['wrist watch']), 'Whitespace collapsing failed.');
assert_true(!match_question_answer('wrong answer', 'ghadi', ['watch']), 'Incorrect answer matched erroneously.');

// ==========================================
// TEST 2: Question Creation & Correct Attempt
// ==========================================
echo "Running Test 2: Question creation & correct answer reward...\n";
$pdo->prepare('INSERT INTO riddle_pool(
    spot_id, question_mode, riddle_text, instruction_text,
    primary_answer, synonyms, yt_video_id, channel_url,
    answer_time_seconds, reward_type, reward_value, reward_expiry_days,
    fallback_offer_title, status
) VALUES(?, "SINGLE", "Mujhe pehno toh waqt rukta nahi?", "Scan QR", "Ghadi", ?, "vid123", "", 60, "COINS", 50.00, 7, "Q-Fallback-Offer", "ACTIVE")')
    ->execute([$spotId, safe_json(['watch', 'clock', 'samay'])]);
$riddleId1 = (int)$pdo->lastInsertId();

$resCorrect = process_question_attempt($userId, $riddleId1, 'watch', null, "attempt-correct-{$pfx}");
assert_true($resCorrect['is_correct'] === true, 'Answer was not marked correct.');
assert_true($resCorrect['reward_type'] === 'COINS', 'Reward type mismatch.');
assert_true($resCorrect['reward_amount'] === '50.00', 'Reward amount mismatch.');

// Check wallet bonus balance increased by 50.00 (from 20.00 to 70.00)
$bonusBal = (string)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
assert_true($bonusBal === '70.00', "Wallet bonus balance expected 70.00, found {$bonusBal}");

// Check idempotency on correct attempt
$resCorrectDupe = process_question_attempt($userId, $riddleId1, 'watch', null, "attempt-correct-{$pfx}");
assert_true($resCorrectDupe['created'] === false, 'Idempotent attempt should return created=false.');
$bonusBalAfterDupe = (string)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
assert_true($bonusBalAfterDupe === '70.00', 'Bonus balance changed on duplicate attempt!');

// ==========================================
// TEST 3: Negative Marking & Fallback Offers
// ==========================================
echo "Running Test 3: Negative marking & fallback offer precedence...\n";
// Create collection with negative marking = 5 points
$pdo->prepare('INSERT INTO question_collections(
    collection_name, spot_id, rotation_method, questions_per_session,
    negative_marking_points, fallback_offer_title, status, created_by
) VALUES("Test Col", ?, "RANDOM", 1, 5, "Collection-Fallback-Offer", "ACTIVE", ?)')
    ->execute([$spotId, $adminId]);
$colId = (int)$pdo->lastInsertId();

// Riddle 1 has fallback_offer_title = "Q-Fallback-Offer"
$resWrong1 = process_question_attempt($userId, $riddleId1, 'Incorrect Answer', $colId, "attempt-wrong1-{$pfx}");
assert_true($resWrong1['is_correct'] === false, 'Incorrect answer was marked correct.');
assert_true($resWrong1['penalty_applied'] === '5.00', 'Negative marking penalty of 5.00 was not applied.');
assert_true($resWrong1['fallback_offer'] === 'Q-Fallback-Offer', 'Question fallback offer did not override collection fallback.');

// Check bonus balance decreased by 5.00 (from 70.00 to 65.00)
$bonusBalAfterWrong = (string)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
assert_true($bonusBalAfterWrong === '65.00', "Wallet bonus expected 65.00 after penalty, found {$bonusBalAfterWrong}");

// Riddle 2 without question-level fallback offer (should fallback to collection level)
$pdo->prepare('INSERT INTO riddle_pool(
    spot_id, question_mode, riddle_text, primary_answer, synonyms,
    answer_time_seconds, reward_type, reward_value, reward_expiry_days,
    fallback_offer_title, collection_id, status
) VALUES(?, "SINGLE", "Question 2", "Right", ?, 60, "COINS", 30.00, 7, NULL, ?, "ACTIVE")')
    ->execute([$spotId, safe_json([]), $colId]);
$riddleId2 = (int)$pdo->lastInsertId();

$resWrong2 = process_question_attempt($userId, $riddleId2, 'Wrong Answer', $colId, "attempt-wrong2-{$pfx}");
assert_true($resWrong2['fallback_offer'] === 'Collection-Fallback-Offer', 'Collection fallback offer was not used when question offer was null.');

// ==========================================
// TEST 4: Zero-Balance Negative Marking Guard
// ==========================================
echo "Running Test 4: Negative marking zero-balance guard...\n";
// Set wallet balance to 2.00
$pdo->prepare('UPDATE wallets SET balance = 0.00, bonus_balance = 2.00 WHERE user_id = ?')->execute([$userId]);
$resWrongCapped = process_question_attempt($userId, $riddleId2, 'Wrong Again', $colId, "attempt-wrong-cap-{$pfx}");
// Penalty was 5.00, but available was only 2.00, so penalty applied should be capped at 2.00
assert_true($resWrongCapped['penalty_applied'] === '2.00', "Penalty expected capped at 2.00, found {$resWrongCapped['penalty_applied']}");
$bonusAfterCap = (string)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
assert_true($bonusAfterCap === '0.00', "Bonus balance expected 0.00, found {$bonusAfterCap}");

// Now balance is 0.00. Another wrong answer should deduct 0.00 and NEVER go negative!
$resWrongZero = process_question_attempt($userId, $riddleId2, 'Wrong Again 2', $colId, "attempt-wrong-zero-{$pfx}");
assert_true($resWrongZero['penalty_applied'] === '0.00', "Penalty expected 0.00 when balance is zero, found {$resWrongZero['penalty_applied']}");
$bonusAfterZero = (string)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
$realAfterZero = (string)$pdo->query("SELECT balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
assert_true((float)$bonusAfterZero >= 0 && (float)$realAfterZero >= 0, 'Wallet balance went negative!');

// Restore wallet balance for next tests
$pdo->prepare('UPDATE wallets SET balance = 100.00, bonus_balance = 50.00 WHERE user_id = ?')->execute([$userId]);

// ==========================================
// TEST 5: Hunt Broadcast Messaging & Claims
// ==========================================
echo "Running Test 5: Hunt broadcast creation & customer claim flow...\n";
$pin = '4892';
$bc = create_hunt_broadcast([
    'campaign_id' => $spotId,
    'target_type' => 'PROMOTER',
    'target_id' => 'PROM_RED_DRESS_01',
    'message' => 'Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!',
    'reward_type' => 'COINS',
    'reward_value' => 200.00,
    'validity_window_minutes' => 60,
    'pin' => $pin,
], $adminId);

assert_true(!empty($bc['broadcast_code']), 'Broadcast code was not generated.');
assert_true(!empty($bc['qr_token']), 'Dynamic QR token was not generated.');
assert_true($bc['fcm_dispatched'] === true, 'FCM push was not dispatched.');

// Verify PIN hash stored, not plaintext
$storedPin = (string)$pdo->query("SELECT pin_hash FROM hunt_broadcasts WHERE id = {$bc['id']}")->fetchColumn();
assert_true($storedPin !== $pin, 'PIN was stored in plain text!');
assert_true(password_verify($pin, $storedPin), 'PIN hash does not verify.');

// Verify claim with wrong PIN throws exception
assert_throws(function() use ($userId, $bc) {
    verify_and_claim_broadcast($userId, $bc['qr_token'], '0000', 'claim-wrong-pin');
}, 'Invalid PIN was accepted.');

// Verify claim with correct PIN succeeds and credits wallet
$preClaimBonus = (float)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
$claimRes = verify_and_claim_broadcast($userId, $bc['qr_token'], $pin, "claim-valid-{$pfx}");
assert_true($claimRes['status'] === 'SUCCESS', 'Broadcast claim status is not SUCCESS.');
assert_true($claimRes['reward_amount'] === '200.00', 'Broadcast claim reward amount mismatch.');

$postClaimBonus = (float)$pdo->query("SELECT bonus_balance FROM wallets WHERE user_id = {$userId}")->fetchColumn();
assert_true($postClaimBonus === $preClaimBonus + 200.00, "Wallet bonus balance did not increase by 200.00.");

// Verify duplicate claim rejected
assert_throws(function() use ($userId, $bc, $pin, $pfx) {
    verify_and_claim_broadcast($userId, $bc['qr_token'], $pin, "claim-dupe-{$pfx}");
}, 'Duplicate broadcast claim was accepted.');

// ==========================================
// TEST 6: Targeted Bonus & Offers Priority Engine
// ==========================================
echo "Running Test 6: Targeted bonus & offers priority rules...\n";
// Create Category offer: 10% on CAT_ELEC (min cart 300, max limit 100)
$offerCat = create_targeted_offer([
    'offer_title' => '10% on Electronics',
    'target_type' => 'CATEGORY',
    'target_id' => 'CAT_ELEC',
    'bonus_type' => 'PERCENTAGE',
    'bonus_value' => 10.00,
    'shop_id' => 'ALL',
    'min_cart' => 300.00,
    'max_limit' => 100.00,
    'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
    'status' => 'ACTIVE',
], $adminId);

// Create Product offer: 15% on PROD_PHONE (min cart 500, max limit 250)
$offerProd = create_targeted_offer([
    'offer_title' => '15% on Phone',
    'target_type' => 'PRODUCT',
    'target_id' => 'PROD_PHONE',
    'bonus_type' => 'PERCENTAGE',
    'bonus_value' => 15.00,
    'shop_id' => 'ALL',
    'min_cart' => 500.00,
    'max_limit' => 250.00,
    'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
    'status' => 'ACTIVE',
], $adminId);

// Test Category match when only category passed
$matchCat = find_matching_targeted_offer($userId, null, 'CAT_ELEC', null, 500.00);
assert_true($matchCat !== null, 'Category offer was not matched.');
assert_true($matchCat['matched_priority'] === 'CATEGORY', 'Category priority failed.');
assert_true($matchCat['bonus_amount'] === '50.00', '10% of 500 should be 50.00.');

// Test Product priority over Category when both product and category passed
$matchProd = find_matching_targeted_offer($userId, 'PROD_PHONE', 'CAT_ELEC', null, 1000.00);
assert_true($matchProd !== null, 'Product offer was not matched.');
assert_true($matchProd['matched_priority'] === 'PRODUCT', 'Product offer should override Category offer.');
assert_true($matchProd['bonus_amount'] === '150.00', '15% of 1000 should be 150.00.');

// Test User-specific offer priority over Product
$offerUser = create_targeted_offer([
    'offer_title' => 'Special VIP User Offer',
    'target_type' => 'USER',
    'target_id' => (string)$userId,
    'bonus_type' => 'FIXED',
    'bonus_value' => 200.00,
    'shop_id' => 'ALL',
    'min_cart' => 200.00,
    'max_limit' => 200.00,
    'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
    'status' => 'ACTIVE',
], $adminId);

$matchUser = find_matching_targeted_offer($userId, 'PROD_PHONE', 'CAT_ELEC', null, 1000.00);
assert_true($matchUser !== null, 'User offer was not matched.');
assert_true($matchUser['matched_priority'] === 'USER', 'User offer must be highest priority (User > Product > Category > Vendor).');
assert_true($matchUser['bonus_amount'] === '200.00', 'Fixed bonus 200.00 expected.');

// Test min_cart rejection
$matchBelowMin = find_matching_targeted_offer($userId, null, 'CAT_ELEC', null, 100.00);
assert_true($matchBelowMin === null || $matchBelowMin['matched_priority'] === 'USER', 'Cart below min_cart 300 should not match Category offer.');

echo "ALL QUESTION MANAGER, COLLECTIONS, BROADCAST, AND TARGETED OFFERS TESTS PASSED!\n";
