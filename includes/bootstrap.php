<?php
/**
 * Shared bootstrap: sessions, database, security and data helpers.
 * Every page starts with:  require_once __DIR__ . '/includes/bootstrap.php';
 *
 * Expects config/db.php (your existing file) to create a mysqli object in $conn.
 */

const COMPLAINT_STATUSES = ['Pending', 'In Progress', 'Resolved'];
const OVERDUE_HOURS      = 72;   // pending longer than this is flagged to officers
const PER_PAGE           = 10;
const WASTE_TYPES        = ['Organic', 'Recyclable', 'Hazardous', 'E-waste', 'Mixed'];

/* ---------- mbstring fallbacks (the official php:apache image ships mbstring; this is insurance) ---------- */
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return preg_match_all('/./us', (string) $s) ?: 0; }
}
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null) {
        $chars = preg_split('//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode('', array_slice($chars, $start, $len));
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($s) { return strtoupper((string) $s); }
}
if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth($s, $start, $width, $trim = '') {
        $s = (string) $s;
        return mb_strlen($s) <= $width ? $s : mb_substr($s, $start, $width - mb_strlen($trim)) . $trim;
    }
}

/* ---------- Errors: log details, show a friendly page ---------- */
set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Something went wrong</title>'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<style>body{font-family:system-ui,sans-serif;background:#f1f5ef;display:grid;place-items:center;min-height:100vh;margin:0;color:#1c2a23}'
       . '.b{background:#fff;padding:32px;border-radius:14px;max-width:420px;box-shadow:0 5px 20px #173b1d0d}a{color:#1d6b42;font-weight:bold}</style></head>'
       . '<body><div class="b"><h2>Something went wrong</h2><p>We could not complete your request. Please try again in a moment.</p>'
       . '<a href="index.php">Back to home</a></div></body></html>';
    exit;
});

/* ---------- Session (Render terminates TLS at its proxy) ---------- */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

/* ---------- Time zone: DB stores UTC, people see local time ---------- */
function app_timezone(): string
{
    return getenv('APP_TIMEZONE') ?: 'Asia/Kolkata';
}
date_default_timezone_set(app_timezone());

/* ---------- Database ---------- */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException('config/db.php must create a mysqli connection named $conn.');
}
$conn->set_charset('utf8mb4');
try {
    $conn->query("SET time_zone = '+00:00'");
} catch (mysqli_sql_exception $e) {
    error_log('Could not set session time zone: ' . $e->getMessage());
}

/**
 * Does complaints.waste_type exist? Older databases were created without it, and
 * CREATE TABLE IF NOT EXISTS never adds columns. If it is missing we add it (same
 * definition as config/db.php); if the DB user may not alter tables, the app simply
 * hides waste type instead of failing.
 */
function has_waste_type(): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    if (!empty($_SESSION['has_waste_type'])) {
        return $has = true;
    }
    global $conn;
    try {
        $exists = $conn->query("SHOW COLUMNS FROM complaints LIKE 'waste_type'")->num_rows > 0;
        if (!$exists) {
            $conn->query("ALTER TABLE complaints ADD COLUMN waste_type VARCHAR(100) NOT NULL DEFAULT 'Organic' AFTER location");
            error_log('Added missing complaints.waste_type column.');
            $exists = true;
        }
    } catch (mysqli_sql_exception $e) {
        error_log('waste_type column unavailable: ' . $e->getMessage());
        $exists = false;
    }
    if ($exists) {
        $_SESSION['has_waste_type'] = true;
    }
    return $has = $exists;
}

/** Run a prepared statement and return it. */
function db_query(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_query($sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    return db_all($sql, $types, $params)[0] ?? null;
}

/* ---------- Basics ---------- */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to)
{
    header('Location: ' . $to);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function take_flashes(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/** Remember submitted form values so they survive a redirect. */
function remember_input(array $data): void
{
    $_SESSION['old'] = $data;
}

function old(string $key): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
    }
    return (string) ($cache[$key] ?? '');
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/* ---------- Auth ---------- */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'   => (int) $_SESSION['user_id'],
        'name' => (string) ($_SESSION['user_name'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? ''),
    ];
}

function is_officer(): bool
{
    return ($_SESSION['role'] ?? '') === 'officer';
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('index.php');
    }
    return $user;
}

function require_officer(): array
{
    $user = require_login();
    if (!is_officer()) {
        flash('error', 'That page is only for municipal officers.');
        redirect('dashboard.php');
    }
    return $user;
}

/* ---------- Formatting ---------- */
/** Show a UTC database timestamp in the app's local time zone. */
function fmt_dt(?string $utc, string $format = 'd M Y, g:i a'): string
{
    if (!$utc) {
        return '–';
    }
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone(app_timezone()));
    return $d->format($format);
}

function age_text(int $hours): string
{
    if ($hours < 1)  { return 'Just now'; }
    if ($hours < 24) { return $hours . ' h ago'; }
    $days = intdiv($hours, 24);
    return $days . ($days === 1 ? ' day ago' : ' days ago');
}

/** Citizens see "Asha K."; officers see full names. */
function display_name(?string $name, bool $full): string
{
    $name = trim((string) $name);
    if ($name === '') {
        return 'Citizen';
    }
    if ($full) {
        return $name;
    }
    $parts = preg_split('/\s+/', $name);
    $out = $parts[0];
    if (count($parts) > 1) {
        $out .= ' ' . mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1)) . '.';
    }
    return $out;
}

function status_class(string $status): string
{
    switch ($status) {
        case 'Pending':     return 'pending';
        case 'In Progress': return 'progress';
        case 'Resolved':    return 'resolved';
        default:            return 'other';
    }
}

function waste_tag(?string $type): string
{
    $type = (string) $type;
    $cls  = in_array($type, WASTE_TYPES, true) ? 'w-' . strtolower(str_replace('-', '', $type)) : 'w-other';
    return $type === '' ? '' : '<span class="waste-tag ' . $cls . '">' . h($type) . '</span>';
}

function status_badge(string $status): string
{
    return '<span class="status ' . status_class($status) . '">' . h($status) . '</span>';
}

/* ---------- Complaint queries (shared by dashboard + officer panel) ---------- */

/** Read and sanitise ?status=&q=&page= from GET or POST data. */
function list_params(array $src): array
{
    $status = $src['status'] ?? '';
    if (!in_array($status, COMPLAINT_STATUSES, true)) {
        $status = '';
    }
    $type = $src['type'] ?? '';
    if (!in_array($type, WASTE_TYPES, true)) {
        $type = '';
    }
    return [
        'status' => $status,
        'type'   => $type,
        'q'      => mb_substr(trim((string) ($src['q'] ?? '')), 0, 100),
        'page'   => max(1, (int) ($src['page'] ?? 1)),
    ];
}

function complaint_where(?int $userId, string $status, string $q, string $type = ''): array
{
    $where = ['1=1'];
    $types = '';
    $args  = [];

    if ($userId !== null) {
        $where[] = 'c.user_id = ?';
        $types  .= 'i';
        $args[]  = $userId;
    }
    if ($status !== '') {
        $where[] = 'c.status = ?';
        $types  .= 's';
        $args[]  = $status;
    }
    if ($type !== '' && has_waste_type()) {
        $where[] = 'c.waste_type = ?';
        $types  .= 's';
        $args[]  = $type;
    }
    if ($q !== '') {
        $like    = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(c.location LIKE ? OR c.description LIKE ?)';
        $types  .= 'ss';
        $args[]  = $like;
        $args[]  = $like;
    }
    return [implode(' AND ', $where), $types, $args];
}

function fetch_complaints(?int $userId, string $status, string $q, int $page, int $perPage = PER_PAGE, string $type = ''): array
{
    [$where, $types, $args] = complaint_where($userId, $status, $q, $type);

    $total = (int) (db_one("SELECT COUNT(*) AS n FROM complaints c WHERE $where", $types, $args)['n'] ?? 0);
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $typeCol = has_waste_type() ? 'c.waste_type' : "'' AS waste_type";
    $rows = db_all(
        "SELECT c.id, c.location, $typeCol, c.description, COALESCE(c.status, 'Pending') AS status, c.created_at, u.name,
                TIMESTAMPDIFF(HOUR, c.created_at, NOW()) AS age_hours
         FROM complaints c
         LEFT JOIN users u ON u.id = c.user_id
         WHERE $where
         ORDER BY c.created_at DESC, c.id DESC
         LIMIT ? OFFSET ?",
        $types . 'ii',
        array_merge($args, [$perPage, $offset])
    );

    return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function complaint_stats(?int $userId): array
{
    $where = $userId !== null ? 'WHERE user_id = ?' : '';
    $rows  = db_all(
        "SELECT status, COUNT(*) AS n FROM complaints $where GROUP BY status",
        $userId !== null ? 'i' : '',
        $userId !== null ? [$userId] : []
    );

    $stats = ['total' => 0, 'Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
    foreach ($rows as $r) {
        $stats['total'] += (int) $r['n'];
        if (isset($stats[$r['status']])) {
            $stats[$r['status']] = (int) $r['n'];
        }
    }
    $stats['rate'] = $stats['total'] > 0 ? round(($stats['Resolved'] / $stats['total']) * 100) : 0;
    return $stats;
}

function overdue_count(): int
{
    return (int) (db_one(
        "SELECT COUNT(*) AS n FROM complaints
         WHERE status = 'Pending' AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= ?",
        'i',
        [OVERDUE_HOURS]
    )['n'] ?? 0);
}
