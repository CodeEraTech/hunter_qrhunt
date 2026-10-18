<?php
declare(strict_types=1);

require dirname(__DIR__) . '/_bootstrap.php';
require_post();

$user = api_user();
api_rate_limit($user, 'hunts.answer', 20, 600);
$d = json_input();

try {
    if (isset($d['riddle_id']) || (!isset($d['scan_id']) && isset($d['question_id']))) {
        // Question Manager & Question Collection Attempt
        $riddleId = (int)($d['riddle_id'] ?? $d['question_id'] ?? 0);
        $collectionId = isset($d['collection_id']) ? (int)$d['collection_id'] : null;
        $answer = (string)($d['answer'] ?? '');
        $idempotencyKey = trim((string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ($d['idempotency_key'] ?? '')));

        $result = process_question_attempt((int)$user['id'], $riddleId, $answer, $collectionId, $idempotencyKey);
        $msg = $result['is_correct'] ? 'Correct answer! Reward credited.' : 'Incorrect answer.';
        json_response(true, $msg, $result, 200);
    } else {
        // Legacy hunt scan location question challenge
        validate_question_challenge($user, (int)($d['scan_id'] ?? 0), (int)($d['question_id'] ?? 0), (string)($d['answer'] ?? ''));
        json_response(true, 'Answer accepted');
    }
} catch (InvalidArgumentException $e) {
    json_response(false, $e->getMessage(), null, 422);
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 422);
}
