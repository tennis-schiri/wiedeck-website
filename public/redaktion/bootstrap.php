<?php
declare(strict_types=1);
/* MW / Restaurantredaktion. DB and pictures remain server-side, never in GitHub. */
if (PHP_SAPI !== 'cli' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')
    && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https')) {
    http_response_code(403);
    exit('Nur über HTTPS erreichbar.');
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Cache-Control: private, no-store');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self' data:; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('mw_redaktion');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/redaktion/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
function db(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    if (!extension_loaded('pdo_sqlite')) { http_response_code(503); exit('SQLite ist auf dem Server nicht verfügbar.'); }
    $dir = __DIR__ . '/private';
    if (!is_dir($dir) || !is_writable($dir)) { http_response_code(503); exit('Das geschützte Datenverzeichnis ist nicht beschreibbar.'); }
    $db = new PDO('sqlite:' . $dir . '/journal.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $db->exec('PRAGMA busy_timeout=5000; PRAGMA foreign_keys=ON;');
    $db->exec('CREATE TABLE IF NOT EXISTS admin (id INTEGER PRIMARY KEY CHECK(id=1), password_hash TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS attempts (ip TEXT NOT NULL, occurred INTEGER NOT NULL)');
    $db->exec('CREATE INDEX IF NOT EXISTS attempts_time ON attempts(occurred)');
    $db->exec('CREATE TABLE IF NOT EXISTS restaurants (
        id TEXT PRIMARY KEY, name TEXT NOT NULL, city TEXT NOT NULL, address TEXT NOT NULL DEFAULT "",
        website TEXT NOT NULL DEFAULT "", cuisine TEXT NOT NULL DEFAULT "", visit_date TEXT NOT NULL DEFAULT "",
        occasion TEXT NOT NULL DEFAULT "", companions TEXT NOT NULL DEFAULT "", price TEXT NOT NULL DEFAULT "",
        impression TEXT NOT NULL DEFAULT "", food_text TEXT NOT NULL DEFAULT "", ambience_text TEXT NOT NULL DEFAULT "",
        service_text TEXT NOT NULL DEFAULT "", value_text TEXT NOT NULL DEFAULT "", highlights TEXT NOT NULL DEFAULT "",
        shortcomings TEXT NOT NULL DEFAULT "", verdict TEXT NOT NULL DEFAULT "", revisit TEXT NOT NULL DEFAULT "",
        score_food INTEGER, score_ambience INTEGER, score_service INTEGER, score_value INTEGER,
        status TEXT NOT NULL DEFAULT "draft" CHECK(status IN ("draft","published")),
        published_at TEXT, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS photos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, restaurant_id TEXT NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
        filename TEXT NOT NULL, mime TEXT NOT NULL, caption TEXT NOT NULL DEFAULT "", created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    if ((int)$db->query('SELECT count(*) FROM restaurants')->fetchColumn() === 0) {
        $seed = json_decode((string)file_get_contents(__DIR__ . '/seed.json'), true);
        if (is_array($seed)) {
            $stmt = $db->prepare('INSERT OR IGNORE INTO restaurants (id,name,city,address,cuisine,website) VALUES (?,?,?,?,?,?)');
            foreach ($seed as $row) $stmt->execute([
                (string)$row['id'],(string)$row['name'],(string)$row['city'],
                (string)($row['address'] ?? ''),(string)($row['cuisine'] ?? ''),(string)($row['website'] ?? '')
            ]);
        }
    }
    return $db;
}
function esc(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function admins_exist(): bool { return (bool)db()->query('SELECT 1 FROM admin WHERE id=1')->fetchColumn(); }
function authenticated(): bool {
    if (empty($_SESSION['admin']) || empty($_SESSION['active_at'])) return false;
    if (time() - (int)$_SESSION['active_at'] > 21600) { unset($_SESSION['admin'], $_SESSION['active_at']); return false; }
    $_SESSION['active_at'] = time(); return true;
}
function guard(): void {
    if (!authenticated()) { header('Location: index.php'); exit; }
}
function csrf(): string {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Ungültige Anfrage.'); }
}
function redirect(string $target): never { header('Location: ' . $target, true, 303); exit; }
function blocked(): bool {
    $ip = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $stmt = db()->prepare('SELECT count(*) FROM attempts WHERE ip=? AND occurred>?');
    $stmt->execute([$ip, time()-900]);
    return (int)$stmt->fetchColumn() >= 6;
}
function register_failure(): void {
    $ip = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $pdo = db();
    $pdo->prepare('INSERT INTO attempts(ip,occurred) VALUES(?,?)')->execute([$ip,time()]);
    $pdo->prepare('DELETE FROM attempts WHERE occurred<?')->execute([time()-900]);
}
function clear_failures(): void {
    db()->prepare('DELETE FROM attempts WHERE ip=?')->execute([hash('sha256',(string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'))]);
}
function rating($v): ?int {
    if ($v === '' || $v === null || $v === 'na') return null;
    if (!is_string($v) && !is_int($v)) return null;
    if (!preg_match('/^(10|[0-9])$/', (string)$v)) return null;
    return (int)$v;
}
function json_output(array $data): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
