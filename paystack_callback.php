<?php
// Paystack sends the customer back here; the payment is confirmed with
// Paystack directly before the order is marked paid.
require_once 'includes/shop.php';
$pdo = getDB();
ensurePaystackColumns($pdo);

$reference = (string)($_GET['reference'] ?? $_GET['trxref'] ?? '');
if (!preg_match('/^[A-Za-z0-9\-]{6,100}$/', $reference)) {
    redirect(SITE_URL . '/orders.php');
}

$response = paystackRequest('GET', '/transaction/verify/' . rawurlencode($reference));
$tx       = $response['data'] ?? null;

// Find the order from the metadata sent at checkout, falling back to the
// reference saved on the order.
$orderId = (int)($tx['metadata']['order_id'] ?? 0);
if ($orderId) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$orderId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_reference=?");
    $stmt->execute([$reference]);
}
$order = $stmt->fetch();

if (!$order || !is_array($tx)) {
    flashMessage('error', 'We could not confirm your payment. If money was deducted, please contact us on WhatsApp with your order number.');
    redirect(SITE_URL . '/orders.php');
}

$paid = !empty($response['status'])
    && ($tx['status'] ?? '') === 'success'
    && (int)($tx['amount'] ?? 0) === paystackAmount((float)$order['total'])
    && strtoupper((string)($tx['currency'] ?? '')) === PAYSTACK_CURRENCY
    && strpos($reference, $order['order_number'] . '-') === 0;

if ($paid) {
    markOrderPaid($pdo, (int)$order['id'], $reference);
    flashMessage('success', 'Payment received. Thank you, order #' . $order['order_number'] . ' is now being processed.');
} else {
    flashMessage('error', 'Your payment was not completed. You can try again from this page.');
}

redirect(SITE_URL . '/orders.php?view=' . $order['id']);
