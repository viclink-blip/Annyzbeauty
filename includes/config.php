<?php
// ============================================================
// Annyzbeauty - Database & App Configuration
// ============================================================

// --- Database Settings ---
// Update these values to match your InfinityFree/cPanel DB credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');   // e.g. epiz_12345678
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'annyzbeauty');        // e.g. epiz_12345678_annyzbeauty

// --- App Settings ---
define('SITE_NAME', 'Annyzbeauty');
define('SITE_URL', 'http://localhost/annyzbeauty'); // Change to your domain on InfinityFree
define('CURRENCY', 'KES');
define('CURRENCY_SYMBOL', 'KSh');
define('SHIPPING_FEE', 300.00);
define('FREE_SHIPPING_THRESHOLD', 5000.00);

// --- Session & Security ---
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('UPLOAD_DIR', __DIR__ . '/../assets/images/products/');
define('UPLOAD_URL', SITE_URL . '/assets/images/products/');
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// --- Start Session Securely ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// --- Error Reporting (turn off on production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// Database Connection (PDO)
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
                    <h2 style="color:#d63384;">Database Connection Failed</h2>
                    <p>Please check your database credentials in <code>includes/config.php</code></p>
                    <small>' . htmlspecialchars($e->getMessage()) . '</small>
                 </div>');
        }
    }
    return $pdo;
}

// ============================================================
// Utility Functions
// ============================================================

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $amount): string {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(SITE_URL . '/login.php');
    }
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        redirect(SITE_URL . '/admin/login.php');
    }
}

function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateOrderNumber(): string {
    return 'ANN-' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    return floor($diff/86400) . ' days ago';
}
