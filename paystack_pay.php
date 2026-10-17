<?php
// Starts a Paystack payment for one of the customer's unpaid orders.
require_once 'includes/shop.php';
$pdo = getDB();
ensureCustomerSchema($pdo);

$stmt = $pdo->prepare("
    SELECT o.*, COALESCE(NULLIF(o.customer_email,''), u.email) AS email
    FROM orders o LEFT JOIN users u ON u.id=o.user_id
    WHERE o.id=?
");
$stmt->execute([intval($_GET['order'] ?? 0)]);
$order = $stmt->fetch();

if (!$order || !canAccessOrder($order)) redirect(SITE_URL . '/orders.php');

$orderUrl = SITE_URL . '/orders.php?view=' . $order['id'];
if ($order['payment_status'] !== 'unpaid' || $order['status'] === 'cancelled') {
    redirect($orderUrl);
}

// A fresh reference per attempt, so a customer can retry a failed payment.
$reference = $order['order_number'] . '-' . bin2hex(random_bytes(4));

$response = paystackRequest('POST', '/transaction/initialize', [
    'email'        => $order['email'],
    'amount'       => paystackAmount((float)$order['total']),
    'currency'     => PAYSTACK_CURRENCY,
    'reference'    => $reference,
    'callback_url' => SITE_URL . '/paystack_callback.php',
    'metadata'     => [
        'order_id'     => (int)$order['id'],
        'order_number' => $order['order_number'],
    ],
]);

$authUrl = $response['data']['authorization_url'] ?? '';
if (empty($response['status']) || $authUrl === '') {
    flashMessage('error', 'We could not start the payment right now. Please try again in a moment, or contact us on WhatsApp.');
    redirect($orderUrl);
}

$pdo->prepare("UPDATE orders SET payment_method='paystack', payment_reference=? WHERE id=?")
    ->execute([$reference, $order['id']]);

header('Location: ' . $authUrl);
exit;
