<?php
// Reusable helper functions used across the whole application.
require_once __DIR__ . '/../config/database.php';

/* ---------- Output & URLs ---------- */
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

/* ---------- Logging (file handling) ---------- */
function log_error(string $msg): void {
    @file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
}
function log_activity(string $action, string $details): void {
    $file = dirname(LOG_FILE) . '/activity.log';
    @file_put_contents($file, '[' . date('Y-m-d H:i:s') . "] $action | $details" . PHP_EOL, FILE_APPEND);
}

/* ---------- Flash messages ---------- */
function flash(string $key, string $msg): void { $_SESSION['flash'][$key] = $msg; }
function take_flash(string $key): ?string {
    $m = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $m;
}

/* ---------- CSRF (extra security) ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400); die('Invalid request token. Please refresh and try again.');
    }
}

/* ---------- Input helpers ---------- */
function post_str(string $k): string { return trim($_POST[$k] ?? ''); }
function get_str(string $k): string  { return trim($_GET[$k]  ?? ''); }

/* ---------- Validation ---------- */
function validate_required(array $fields): array {
    $err = [];
    foreach ($fields as $key => $label) {
        if (trim($_POST[$key] ?? '') === '') $err[$key] = "$label is required.";
    }
    return $err;
}
function validate_hcdc_email(string $email): bool {
    return (bool)preg_match('/^[A-Za-z0-9._%+-]+@hcdc\.edu\.ph$/i', $email);
}
function validate_password(string $pwd): ?string {
    return strlen($pwd) < 8 ? 'Password must be at least 8 characters.' : null;
}
function validate_number($value, float $min = 0): ?string {
    if (!is_numeric($value)) return 'Must be a valid number.';
    if ((float)$value < $min) return "Must be at least $min.";
    return null;
}

/* ---------- Formatting ---------- */
function format_price($amount): string { return '₱' . number_format((float)$amount, 2); }
function format_datetime(?string $ts): string {
    return $ts ? date('M j, Y · g:i A', strtotime($ts)) : 'Pending';
}

/* ---------- Data accessors (with JOINs) ---------- */
function get_greeting_quotes(): array {
    return [
        'Rent smart, live better.',
        'Borrow what you need, share what you have.',
        'Your campus marketplace awaits.',
        'Save money. Reduce waste. Rent it.',
        'Trusted rentals within HCDC.',
    ];
}

function get_categories(PDO $pdo): array {
    try { return $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll(); }
    catch (PDOException $e) { log_error('get_categories: ' . $e->getMessage()); return []; }
}

function get_items(PDO $pdo, string $search = '', string $categorySlug = ''): array {
    $sql = 'SELECT i.*, c.name AS category_name, c.slug AS category_slug,
                   u.full_name AS owner_name, u.email AS owner_email
            FROM items i
            JOIN categories c ON c.id = i.category_id
            JOIN users u      ON u.id = i.owner_id
            WHERE 1=1';
    $params = [];
    if ($search !== '') {
        // NOTE: use distinct placeholders (native prepares don't allow reuse)
        $sql .= ' AND (i.name LIKE :s1 OR i.description LIKE :s2 OR c.name LIKE :s3)';
        $like = '%' . $search . '%';
        $params[':s1'] = $params[':s2'] = $params[':s3'] = $like;
    }
    if ($categorySlug !== '' && $categorySlug !== 'all') {
        $sql .= ' AND c.slug = :slug';
        $params[':slug'] = $categorySlug;
    }
    $sql .= ' ORDER BY i.created_at DESC';
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) { log_error('get_items: ' . $e->getMessage()); return []; }
}

/* ---------- File upload (file handling) ---------- */
function upload_image(array $file, string $prefix = 'item'): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Upload failed. Please try again.'];
    if ($file['size'] > 3 * 1024 * 1024)  return [null, 'Image must be under 3 MB.'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset($allowed[$info['mime']])) return [null, 'Only JPG, PNG, or WEBP allowed.'];
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $name = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        return [null, 'Could not save the uploaded image.'];
    }
    return ['uploads/' . $name, null];
}

/* ---------- Page helpers ---------- */
function current_page(): string { return basename($_SERVER['PHP_SELF'] ?? ''); }

/**
 * Automatically sends a system message to the chat when a transaction updates.
 */
function send_system_message(PDO $pdo, int $itemId, int $ownerId, int $renterId, int $senderId, string $title, array $details) {
    try {
        // 1. Find or create the conversation
        $stmt = $pdo->prepare('SELECT id FROM conversations WHERE item_id = :item AND owner_id = :o AND renter_id = :r');
        $stmt->execute([':item' => $itemId, ':o' => $ownerId, ':r' => $renterId]);
        $convId = $stmt->fetchColumn();

        if (!$convId) {
            $pdo->prepare('INSERT INTO conversations (item_id, owner_id, renter_id) VALUES (:i, :o, :r)')
                ->execute([':i' => $itemId, ':o' => $ownerId, ':r' => $renterId]);
            $convId = $pdo->lastInsertId();
        }

        // 2. Format the message exactly like the screenshot
        $body = "📢 " . strtoupper($title) . "\n\n";
        $body .= "Item: " . $details['item_name'] . "\n";
        if (isset($details['renter_email'])) $body .= "Renter: " . $details['renter_email'] . "\n";
        if (isset($details['owner_email']))  $body .= "Owner: " . $details['owner_email'] . "\n";
        $body .= "Date/Time: " . date('n/j/Y, g:i:s A') . "\n\n";
        $body .= "Check status in Transactions.";

        // 3. Insert the message
        $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (:c, :s, :t)')
            ->execute([':c' => $convId, ':s' => $senderId, ':t' => $body]);

        // 4. Bump the conversation to the top
        $pdo->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute([':id' => $convId]);

    } catch (PDOException $e) {
        log_error('system message failed: ' . $e->getMessage());
    }
}