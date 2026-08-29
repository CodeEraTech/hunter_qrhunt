<?php declare(strict_types=1);require dirname(__DIR__).'/includes/bootstrap.php';header('Content-Type: application/json; charset=utf-8');
function api_user(): array
{
    $header=$_SERVER['HTTP_AUTHORIZATION']??'';if(!preg_match('/^Bearer\s+(.+)$/i',$header,$m))json_response(false,'Unauthorized request',null,401);
    $hash=hash('sha256',$m[1]);$stmt=db()->prepare('SELECT u.*,s.id session_id,s.device_id FROM user_sessions s JOIN users u ON u.id=s.user_id JOIN user_devices d ON d.user_id=s.user_id AND d.device_id=s.device_id AND d.status="ACTIVE" WHERE s.token_hash=? AND s.revoked_at IS NULL AND s.expires_at>NOW() AND u.status="ACTIVE" LIMIT 1');$stmt->execute([$hash]);$user=$stmt->fetch();if(!$user)json_response(false,'Unauthorized request',null,401);
    $device=trim((string)($_SERVER['HTTP_X_DEVICE_ID']??''));if($device===''||!hash_equals((string)$user['device_id'],$device))json_response(false,'Device validation failed',null,401);
    db()->prepare('UPDATE user_devices SET last_active_at=NOW(),ip_address=? WHERE user_id=? AND device_id=?')->execute([client_ip(),$user['id'],$user['device_id']]);return $user;
}
function json_input(): array { $data=json_decode(file_get_contents('php://input'),true);if(!is_array($data))json_response(false,'Invalid JSON body',null,400);return $data; }
function idempotent_response(int $userId,string $endpoint,string $key): ?array {$stmt=db()->prepare('SELECT response_code,response_body FROM api_idempotency WHERE user_id=? AND endpoint=? AND idempotency_key=?');$stmt->execute([$userId,$endpoint,$key]);$r=$stmt->fetch();return $r?:null;}
function api_rate_limit(array $user,string $endpoint,int $maximum,int $windowSeconds): void
{
    $windowSeconds=max(1,min(86400,$windowSeconds));$stmt=db()->prepare('SELECT COUNT(*) FROM api_request_logs WHERE user_id=? AND endpoint=? AND created_at>DATE_SUB(NOW(),INTERVAL '.$windowSeconds.' SECOND)');$stmt->execute([$user['id'],$endpoint]);if((int)$stmt->fetchColumn()>=$maximum)json_response(false,'Too many requests. Try again later.',null,429);
    db()->prepare('INSERT INTO api_request_logs(user_id,endpoint,method,ip_address,device_id) VALUES(?,?,?,?,?)')->execute([$user['id'],$endpoint,$_SERVER['REQUEST_METHOD']??'GET',client_ip(),$user['device_id']]);
}
