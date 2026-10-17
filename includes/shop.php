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
