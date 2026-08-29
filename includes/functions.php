<?php
declare(strict_types=1);

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function app_url(string $path = ''): string { global $config; return ($config['url'] ?: '') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . app_url($path)); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function input(string $key, mixed $default = ''): mixed { return $_POST[$key] ?? $_GET[$key] ?? $default; }
function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45); }
function user_agent(): string { return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500); }
function money(mixed $amount): string { return '₹' . number_format((float) $amount, 2); }
function flash(string $type, string $message): void { $_SESSION['_flash'][] = compact('type', 'message'); }
function take_flashes(): array { $items = $_SESSION['_flash'] ?? []; unset($_SESSION['_flash']); return $items; }
function json_response(bool $status, string $message, mixed $data = null, int $code = 200): never {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(compact('status', 'message', 'data'), JSON_UNESCAPED_SLASHES); exit;
}
function require_post(): void { if (!is_post()) json_response(false, 'Method not allowed', null, 405); }
function require_get(): void { if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') json_response(false, 'Method not allowed', null, 405); }
function page_int(string $key = 'page'): int { return max(1, filter_var(input($key, 1), FILTER_VALIDATE_INT) ?: 1); }
function pagination(int $page, int $perPage = 20): array { return [$perPage, ($page - 1) * $perPage]; }
function mask_account(?string $value): string { if (!$value) return '—'; $last = substr($value, -4); return 'XXXX XXXX ' . e($last); }
function transaction_code(string $prefix): string { return $prefix . '-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))); }
function idempotency_key(string $scope): string { return $scope . ':' . bin2hex(random_bytes(24)); }
function valid_date(string $date): bool { $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);return $parsed!==false&&$parsed->format('Y-m-d')===$date; }
function safe_json(mixed $value): string { return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR); }
function payment_account_mask(string $value): string { $clean=preg_replace('/\s+/','',trim($value))??'';$last=substr($clean,-4);return $last===''?'XXXX':'XXXX XXXX '.$last; }
function csv_row(array $row): array { return array_map(static function(mixed $value): string {$text=(string)$value;return preg_match('/^[=+\-@\t\r]/',$text)?"'".$text:$text;},array_values($row)); }
function decimal_to_cents(string $value): int { [$whole,$fraction]=array_pad(explode('.',$value,2),2,''); return ((int)$whole*100)+(int)str_pad(substr($fraction,0,2),2,'0'); }
function cents_to_decimal(int $cents): string { $sign=$cents<0?'-':'';$absolute=abs($cents);return $sign.sprintf('%d.%02d',intdiv($absolute,100),$absolute%100); }
function decimal_add(string $a,string $b): string { return cents_to_decimal(decimal_to_cents($a)+decimal_to_cents($b)); }
function decimal_subtract(string $a,string $b): string { return cents_to_decimal(decimal_to_cents($a)-decimal_to_cents($b)); }
function decimal_compare(string $a,string $b): int { return decimal_to_cents($a)<=>decimal_to_cents($b); }

/** Read a system setting safely. Secret settings are decrypted only when requested. */
function setting_value(string $key, ?string $default = null, bool $secret = false): ?string
{
    static $cache = [];
    $cacheKey = ($secret ? 'secret:' : 'plain:') . $key;
    if (array_key_exists($cacheKey, $cache)) return $cache[$cacheKey];
    try {
        $stmt = db()->prepare('SELECT `value`, is_secret FROM system_settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return $cache[$cacheKey] = $default;
        if ($secret && (int)$row['is_secret'] === 1) return $cache[$cacheKey] = decrypt_secret((string)$row['value']);
        return $cache[$cacheKey] = (string)$row['value'];
    } catch (Throwable $e) {
        return $cache[$cacheKey] = $default;
    }
}
function checked(mixed $value, mixed $expected = '1'): string { return (string)$value === (string)$expected ? 'checked' : ''; }
function selected(mixed $value, mixed $expected): string { return (string)$value === (string)$expected ? 'selected' : ''; }
function store_image_upload(string $field, string $prefix = 'image'): ?string { if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null; $f=$_FILES[$field]; if(($f['error']??1)!==UPLOAD_ERR_OK || (int)$f['size']>5*1024*1024) throw new RuntimeException('Image must be smaller than 5MB.'); $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $allowed=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','image/gif'=>'gif']; if(!isset($allowed[$mime])) throw new RuntimeException('Unsupported image type.'); $dir=APP_ROOT.'/uploads/media'; if(!is_dir($dir)&&!mkdir($dir,0750,true)) throw new RuntimeException('Upload directory unavailable.'); $name=$prefix.'-'.bin2hex(random_bytes(12)).'.'.$allowed[$mime]; if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('Could not save image.'); return '/uploads/media/'.$name; }

function clear_setting_cache(): void { /* settings are cached per request only */ }
