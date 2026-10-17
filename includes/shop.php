<?php
// ============================================================
// Annyzbeauty - Shop settings, WhatsApp ordering and Paystack
// ============================================================
require_once __DIR__ . '/config.php';

// Written by the deploy workflow from the PAYSTACK_SECRET_KEY repository
// secret; it is never committed.
if (is_file(__DIR__ . '/paystack_secret.php')) {
    require_once __DIR__ . '/paystack_secret.php';
}
defined('PAYSTACK_SECRET_KEY') || define('PAYSTACK_SECRET_KEY', '');
defined('PAYSTACK_CURRENCY')   || define('PAYSTACK_CURRENCY', 'KES');

defined('WHATSAPP_NUMBER')  || define('WHATSAPP_NUMBER', '254708433754');
defined('WHATSAPP_DISPLAY') || define('WHATSAPP_DISPLAY', '+254 708 433 754');

function whatsappLink(string $message = ''): string {
    $url = 'https://wa.me/' . WHATSAPP_NUMBER;
    return $message === '' ? $url : $url . '?text=' . rawurlencode($message);
}

// ============================================================
// Paystack
// ============================================================

// Paystack expects amounts in the currency's subunit (cents for KES).
function paystackAmount(float $amount): int {
    return (int) round($amount * 100);
}

// Calls the Paystack API; returns the decoded response, or null when the
// key is missing or the request fails.
function paystackRequest(string $method, string $path, ?array $body = null): ?array {
    if (PAYSTACK_SECRET_KEY === '' || !function_exists('curl_init')) return null;

    $ch = curl_init('https://api.paystack.co' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    curl_close($ch);

    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

// Older databases only allow mpesa/paypal/cash_on_delivery as payment
// methods; widen the column and add the Paystack fields the first time.
function ensurePaystackColumns(PDO $pdo): void {
    if ($pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_reference'")->fetch()) return;
    $pdo->exec("
        ALTER TABLE orders
            MODIFY payment_method VARCHAR(30) NOT NULL DEFAULT 'paystack',
            ADD COLUMN payment_reference VARCHAR(100) NULL,
            ADD COLUMN paid_at DATETIME NULL,
            ADD INDEX idx_payment_reference (payment_reference)
    ");
}

// Marks an order paid after Paystack has confirmed it. Safe to call twice.
function markOrderPaid(PDO $pdo, int $orderId, string $reference): void {
    $pdo->prepare("
        UPDATE orders
        SET payment_status='paid', payment_method='paystack', payment_reference=?, paid_at=NOW(),
            status=IF(status='pending','processing',status)
        WHERE id=? AND payment_status='unpaid'
    ")->execute([$reference, $orderId]);
}

// ============================================================
// Cart (kept in the session, so guests can shop)
// ============================================================

// product_id => quantity
function cartQuantities(): array {
    return array_map('intval', $_SESSION['cart'] ?? []);
}

function cartSet(int $productId, int $quantity): void {
    if ($quantity > 0) {
        $_SESSION['cart'][$productId] = $quantity;
    } else {
        unset($_SESSION['cart'][$productId]);
    }
}

function cartClear(): void {
    unset($_SESSION['cart']);
}

function cartCount(): int {
    return array_sum(cartQuantities());
}

// Cart lines with product details. Quantities are capped at the stock left,
// and products that are inactive or sold out drop out of the cart.
function cartItems(PDO $pdo): array {
    $quantities = cartQuantities();
    if (!$quantities) return [];

    $marks = implode(',', array_fill(0, count($quantities), '?'));
    $stmt  = $pdo->prepare("
        SELECT p.id AS product_id, p.id AS cart_id, p.name, p.image, p.price, p.sale_price,
               p.stock_quantity, c.name AS cat_name
        FROM products p JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1 AND p.id IN ($marks)
    ");
    $stmt->execute(array_keys($quantities));

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $qty = min($quantities[$row['product_id']], (int)$row['stock_quantity']);
        if ($qty < 1) continue;
        $row['quantity'] = $qty;
        $items[] = $row;
    }
    $_SESSION['cart'] = array_column($items, 'quantity', 'product_id');
    return $items;
}

// ============================================================
// Customers: guest checkout, saved details and remembered devices
// ============================================================
defined('REMEMBER_COOKIE') || define('REMEMBER_COOKIE', 'annyz_device');
defined('REMEMBER_DAYS')   || define('REMEMBER_DAYS', 365);

// Kenyan mobile number in local form (0712345678), or '' if invalid.
function normalizePhone(string $phone): string {
    $digits = preg_replace('/[\s\-().]/', '', $phone);
    return preg_match('/^(?:\+?254|0)([17]\d{8})$/', $digits, $m) ? '0' . $m[1] : '';
}

// Orders from guests have no user; widen the table the first time.
function ensureCustomerSchema(PDO $pdo): void {
    ensurePaystackColumns($pdo);
    if (!$pdo->query("SHOW COLUMNS FROM orders LIKE 'customer_phone'")->fetch()) {
        $pdo->exec("
            ALTER TABLE orders
                MODIFY user_id INT NULL,
                ADD COLUMN customer_name VARCHAR(200) NULL,
                ADD COLUMN customer_email VARCHAR(150) NULL,
                ADD COLUMN customer_phone VARCHAR(20) NULL
        ");
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            selector CHAR(24) NOT NULL UNIQUE,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
}

function signInCustomer(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['first_name'];
    $_SESSION['user_email'] = $user['email'];
}

function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function setDeviceCookie(string $value, int $expires): void {
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Remember this browser for a year so the customer's details fill in by
// themselves. Only a hash of the token is stored.
function rememberDevice(PDO $pdo, int $userId): void {
    ensureCustomerSchema($pdo);
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $pdo->prepare("
        INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL " . (int)REMEMBER_DAYS . " DAY))
    ")->execute([$userId, $selector, hash('sha256', $validator)]);
    setDeviceCookie($selector . ':' . $validator, time() + REMEMBER_DAYS * 86400);
}

function forgetDevice(PDO $pdo): void {
    $selector = explode(':', (string)($_COOKIE[REMEMBER_COOKIE] ?? ''))[0];
    if ($selector !== '') {
        ensureCustomerSchema($pdo);
        $pdo->prepare("DELETE FROM remember_tokens WHERE selector=?")->execute([$selector]);
    }
    setDeviceCookie('', time() - 3600);
}

// Signs the customer back in when this browser was remembered.
function restoreRememberedCustomer(): void {
    $cookie = (string)($_COOKIE[REMEMBER_COOKIE] ?? '');
    if (isLoggedIn() || $cookie === '') return;

    $pdo = getDB();
    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) {
        setDeviceCookie('', time() - 3600);
        return;
    }
    ensureCustomerSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT t.token_hash, u.id, u.first_name, u.email
        FROM remember_tokens t JOIN users u ON u.id = t.user_id
        WHERE t.selector = ? AND t.expires_at > NOW() AND u.is_active = 1
    ");
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();

    if ($row && hash_equals($row['token_hash'], hash('sha256', $m[2]))) {
        signInCustomer($row);
    } else {
        setDeviceCookie('', time() - 3600);
    }
}

// Saved customers with this phone number. Older accounts stored phones in
// different formats, so match on the last 9 digits and compare normalised.
function customersByPhone(PDO $pdo, string $phone): array {
    $phone = normalizePhone($phone);
    if ($phone === '') return [];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone LIKE ? AND is_active=1");
    $stmt->execute(['%' . substr($phone, -9)]);
    return array_values(array_filter($stmt->fetchAll(), function ($u) use ($phone) {
        return normalizePhone((string)$u['phone']) === $phone;
    }));
}

function findCustomerByPhone(PDO $pdo, string $phone): ?array {
    return customersByPhone($pdo, $phone)[0] ?? null;
}

// Finds a saved customer by phone number or email and checks the password.
function findCustomer(PDO $pdo, string $login, string $password): ?array {
    if (strpos($login, '@') !== false) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
        $stmt->execute([trim($login)]);
        $candidates = $stmt->fetchAll();
    } else {
        $candidates = customersByPhone($pdo, $login);
    }
    foreach ($candidates as $user) {
        if (password_verify($password, $user['password'])) return $user;
    }
    return null;
}

// ============================================================
// Order access: account holders see their orders; guests see the
// orders placed in this browser session.
// ============================================================
function rememberOrderInSession(int $orderId): void {
    $_SESSION['my_orders'][] = $orderId;
    $_SESSION['my_orders']   = array_values(array_unique(array_map('intval', $_SESSION['my_orders'])));
}

function sessionOrderIds(): array {
    return array_map('intval', $_SESSION['my_orders'] ?? []);
}

function canAccessOrder(array $order): bool {
    if (!empty($order['user_id']) && isLoggedIn() && (int)$order['user_id'] === (int)$_SESSION['user_id']) {
        return true;
    }
    return in_array((int)$order['id'], sessionOrderIds(), true);
}

restoreRememberedCustomer();
