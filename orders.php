<?php
$pageTitle = 'My Orders';
require_once 'includes/shop.php';
$pdo = getDB();
ensureCustomerSchema($pdo);

// Account holders (remembered device) see all their orders; guests see the
// orders they placed in this browser session.
$uid      = isLoggedIn() ? (int)$_SESSION['user_id'] : 0;
$guestIds = sessionOrderIds();

function loadOwnOrder(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    return ($order && canAccessOrder($order)) ? $order : null;
}

// Single order detail view
$viewOrder = isset($_GET['view']) ? loadOwnOrder($pdo, intval($_GET['view'])) : null;
if ($viewOrder) {
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.image FROM order_items oi
        LEFT JOIN products p ON oi.product_id=p.id
        WHERE oi.order_id=?
    ");
    $itemsStmt->execute([$viewOrder['id']]);
    $viewOrder['items'] = $itemsStmt->fetchAll();
}

// New order just placed
$newOrder = isset($_GET['new']) ? loadOwnOrder($pdo, intval($_GET['new'])) : null;

// All orders list
$conditions = [];
$params     = [];
if ($uid) { $conditions[] = 'o.user_id=?'; $params[] = $uid; }
if ($guestIds) {
    $conditions[] = 'o.id IN (' . implode(',', array_fill(0, count($guestIds), '?')) . ')';
    $params = array_merge($params, $guestIds);
}
$orders = [];
if ($conditions) {
    $stmt = $pdo->prepare("
        SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) as item_count
        FROM orders o WHERE " . implode(' OR ', $conditions) . " ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
}

$statusClass = [
    'pending'    => 'status-pending',
    'processing' => 'status-processing',
    'shipped'    => 'status-shipped',
    'delivered'  => 'status-delivered',
    'cancelled'  => 'status-cancelled',
];

require_once 'includes/header.php';
?>

<style>
.orders-wrap {
    background: linear-gradient(135deg,#fff 0%,#fff5fb 100%);
    min-height: 80vh; padding: 40px 0 60px;
}

/* Payment Alert Box */
.payment-alert {
    background: #fff;
    border: 2px solid #E91E8C;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(233,30,140,0.15);
    margin-bottom: 32px;
    animation: fadeSlideDown 0.5s ease;
}
@keyframes fadeSlideDown {
    from { opacity:0; transform:translateY(-20px); }
    to   { opacity:1; transform:translateY(0); }
}
.payment-alert-header {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    padding: 18px 24px;
    display: flex; align-items: center; gap: 12px;
}
.payment-alert-header h5 {
    color: #fff; margin: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem; font-weight: 600;
}
.payment-alert-body { padding: 24px; }
.payment-alert-body .order-ref {
    font-size: 13px; color: #888; margin-bottom: 16px;
}
.payment-alert-body .order-ref strong { color: #E91E8C; font-size: 15px; }

/* Payment Method Cards */
.pay-method-card {
    border: 1.5px solid rgba(233,30,140,0.15);
    border-radius: 14px; padding: 18px;
    margin-bottom: 14px; background: #fff5fb;
}
.pay-method-title {
    font-size: 14px; font-weight: 700; color: #1A1A1A;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 8px;
}
.pay-detail-row {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border-radius: 10px;
    padding: 10px 14px; margin-bottom: 8px;
    border: 1px solid rgba(233,30,140,0.10);
    font-size: 13.5px;
}
.pay-detail-row i { color: #E91E8C; font-size: 16px; flex-shrink: 0; }
.pay-detail-row .label { color: #999; font-size: 12px; min-width: 80px; }
.pay-detail-row .value { font-weight: 700; color: #1A1A1A; }

/* Amount Highlight */
.amount-highlight {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; border-radius: 12px;
    padding: 16px 20px; margin-bottom: 16px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
}
.amount-highlight .label { font-size: 13px; opacity: 0.85; }
.amount-highlight .amount {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem; font-weight: 600;
}

/* WhatsApp Button */
.btn-whatsapp {
    background: #25D366; color: #fff;
    border: none; border-radius: 50px;
    padding: 12px 24px; font-size: 14px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
    text-decoration: none; transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(37,211,102,0.30);
}
.btn-whatsapp:hover {
    background: #1ebe5b; color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,211,102,0.40);
}

/* Paystack Button */
.btn-paystack {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; border: none; border-radius: 50px;
    padding: 14px 30px; font-size: 15px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 10px;
    text-decoration: none; transition: all 0.2s;
    box-shadow: 0 6px 24px rgba(233,30,140,0.30);
}
.btn-paystack:hover {
    color: #fff; transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(233,30,140,0.40);
}

/* Steps */
.pay-steps { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }
.pay-step {
    display: flex; align-items: flex-start; gap: 12px;
    font-size: 13px; color: #555;
}
.step-badge {
    width: 24px; height: 24px; border-radius: 50%;
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
}

/* Orders Table */
.orders-card {
    background: #fff;
    border: 1px solid rgba(233,30,140,0.10);
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(233,30,140,0.07);
    overflow: hidden;
}
.orders-card-header {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.orders-card-header h5 {
    color: #fff; margin: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.15rem; font-weight: 600;
}
table { width: 100%; border-collapse: collapse; }
thead th {
    background: #fafafa;
    padding: 12px 16px;
    font-size: 11px; font-weight: 700;
    color: #999; letter-spacing: 1px;
    text-transform: uppercase;
    border-bottom: 1px solid rgba(233,30,140,0.08);
    text-align: left;
}
tbody td {
    padding: 14px 16px;
    font-size: 13.5px;
    border-bottom: 1px solid rgba(233,30,140,0.06);
    vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #fff5fb; }

.status-badge {
    padding: 4px 12px; border-radius: 50px;
    font-size: 11px; font-weight: 700;
    letter-spacing: 0.5px; text-transform: uppercase;
    display: inline-block;
}
.status-pending    { background:#FEF3C7; color:#92400E; }
.status-processing { background:#EFF6FF; color:#1E40AF; }
.status-shipped    { background:#F3E8FF; color:#6B21A8; }
.status-delivered  { background:#F0FDF4; color:#166534; }
.status-cancelled  { background:#FEF2F2; color:#991B1B; }
.status-paid       { background:#F0FDF4; color:#166534; }
.status-unpaid     { background:#FEF2F2; color:#991B1B; }

.btn-view {
    background: #fff5fb; color: #E91E8C;
    border: 1.5px solid rgba(233,30,140,0.20);
    border-radius: 50px; padding: 5px 14px;
    font-size: 12px; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
    display: inline-block;
}
.btn-view:hover { background: #E91E8C; color: #fff; border-color: #E91E8C; }

/* Page Header */
.page-header {
    background: linear-gradient(135deg,#fff0f7,#fce4f3);
    padding: 28px 0 22px;
    border-bottom: 1px solid rgba(233,30,140,0.10);
}
.page-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.9rem; font-weight: 600;
}
.breadcrumb-item a { color: #E91E8C; }

/* Order Detail */
.detail-card {
    background: #fff;
    border: 1px solid rgba(233,30,140,0.10);
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(233,30,140,0.07);
    overflow: hidden; margin-bottom: 20px;
}
.detail-card-header {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.detail-card-header h5 {
    color: #fff; margin: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem; font-weight: 600;
}
.detail-card-body { padding: 22px; }
.detail-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid rgba(233,30,140,0.07);
}
.detail-item:last-child { border-bottom: none; }
.detail-item-img {
    width: 46px; height: 46px; border-radius: 10px;
    object-fit: cover; background: #fff5fb; flex-shrink: 0;
}
.detail-info-row {
    display: flex; justify-content: space-between;
    font-size: 13.5px; padding: 6px 0;
    border-bottom: 1px solid rgba(233,30,140,0.06);
}
.detail-info-row:last-child { border-bottom: none; }
.detail-info-row .key { color: #888; }
.detail-info-row .val { font-weight: 600; color: #1A1A1A; }

@media (max-width: 767px) {
    .orders-wrap { padding: 20px 0 40px; }
    .payment-alert-body { padding: 16px; }
    .amount-highlight { flex-direction: column; gap: 4px; }
    .amount-highlight .amount { font-size: 1.5rem; }
    table { font-size: 12px; }
    thead th, tbody td { padding: 10px 10px; }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
                <li class="breadcrumb-item active">My Orders</li>
            </ol>
        </nav>
        <h1>My Orders</h1>
    </div>
</div>

<div class="orders-wrap">
<div class="container">

<?php if ($viewOrder): ?>
<!-- ═══ ORDER DETAIL VIEW ═══ -->
<div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="fw-bold mb-0" style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;">
        Order #<?= sanitize($viewOrder['order_number']) ?>
    </h5>
    <a href="orders.php" class="btn-view"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
</div>

<?php if ($viewOrder['payment_status'] === 'unpaid' && $viewOrder['status'] !== 'cancelled'):
    $payOrder   = $viewOrder;
    $payHeading = 'Payment Required to Confirm Your Order';
    include 'includes/paystack_pay_box.php';
endif; ?>

<!-- Order Detail -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="detail-card">
            <div class="detail-card-header">
                <h5>Order Items</h5>
                <span class="status-badge <?= $statusClass[$viewOrder['status']] ?? '' ?>"><?= ucfirst($viewOrder['status']) ?></span>
            </div>
            <div class="detail-card-body">
                <?php foreach ($viewOrder['items'] as $item):
                    $img = SITE_URL . '/assets/images/products/' . ($item['image'] ?? '');
                ?>
                <div class="detail-item">
                    <img src="<?= $img ?>" class="detail-item-img"
                         onerror="this.src='https://placehold.co/46x46/FFF0F7/E91E8C?text=P'">
                    <div style="flex:1;">
                        <div style="font-size:13.5px;font-weight:600;"><?= sanitize($item['product_name']) ?></div>
                        <div style="font-size:12px;color:#999;">Qty: <?= $item['quantity'] ?></div>
                    </div>
                    <div style="font-weight:700;color:#E91E8C;"><?= formatPrice((float)$item['total_price']) ?></div>
                </div>
                <?php endforeach; ?>
                <div class="mt-3 pt-2" style="border-top:1px solid rgba(233,30,140,0.08);">
                    <div class="detail-info-row"><span class="key">Subtotal</span><span class="val"><?= formatPrice((float)$viewOrder['subtotal']) ?></span></div>
                    <div class="detail-info-row"><span class="key">Shipping</span><span class="val"><?= formatPrice((float)$viewOrder['shipping_fee']) ?></span></div>
                    <div class="detail-info-row"><span class="key" style="color:#E91E8C;font-weight:700;">Total</span><span class="val" style="color:#E91E8C;font-size:1.1rem;"><?= formatPrice((float)$viewOrder['total']) ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="detail-card">
            <div class="detail-card-header">
                <h5>Order Info</h5>
                <span class="status-badge <?= $viewOrder['payment_status']==='paid'?'status-paid':'status-unpaid' ?>"><?= ucfirst($viewOrder['payment_status']) ?></span>
            </div>
            <div class="detail-card-body">
                <div class="detail-info-row"><span class="key">Order #</span><span class="val"><?= sanitize($viewOrder['order_number']) ?></span></div>
                <div class="detail-info-row"><span class="key">Date</span><span class="val"><?= date('d M Y', strtotime($viewOrder['created_at'])) ?></span></div>
                <div class="detail-info-row"><span class="key">Payment</span><span class="val"><?= ucwords(str_replace('_',' ',$viewOrder['payment_method'])) ?></span></div>
                <div class="detail-info-row"><span class="key">Ship to</span><span class="val"><?= sanitize($viewOrder['shipping_address']) ?>, <?= sanitize($viewOrder['shipping_city']) ?></span></div>
                <?php if ($viewOrder['notes']): ?>
                <div class="detail-info-row"><span class="key">Notes</span><span class="val"><?= sanitize($viewOrder['notes']) ?></span></div>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="https://wa.me/254708433754?text=Hi+Annyzbeauty,+I+need+help+with+Order+<?= urlencode($viewOrder['order_number']) ?>"
                       target="_blank" class="btn-whatsapp w-100 justify-content-center">
                        <i class="bi bi-whatsapp"></i> WhatsApp Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ═══ ORDERS LIST ═══ -->

<?php if ($newOrder && $newOrder['payment_status'] === 'unpaid'):
    $payOrder   = $newOrder;
    $payHeading = 'Order Placed. Complete Your Payment';
    include 'includes/paystack_pay_box.php';
endif; ?>

<!-- Orders Table -->
<?php if (empty($orders)): ?>
<div class="text-center py-5">
    <i class="bi bi-bag" style="font-size:4rem;color:rgba(233,30,140,0.2);"></i>
    <h4 class="mt-3" style="font-family:'Cormorant Garamond',serif;">No orders on this device yet</h4>
    <p class="text-muted">Orders show here on the phone or computer you ordered from.
    Looking for an earlier order? <a href="<?= sanitize(whatsappLink('Hi Annyzbeauty, I would like an update on my order.')) ?>" target="_blank" rel="noopener" style="color:#25D366;font-weight:600;">Ask us on WhatsApp</a> with your order number.</p>
    <a href="<?= SITE_URL ?>/products.php" style="background:linear-gradient(135deg,#E91E8C,#C2176F);color:#fff;border:none;border-radius:50px;padding:12px 30px;font-size:14px;font-weight:600;text-decoration:none;display:inline-block;">
        Shop Now
    </a>
</div>
<?php else: ?>
<div class="orders-card">
    <div class="orders-card-header">
        <h5>All Orders (<?= count($orders) ?>)</h5>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><strong style="color:#E91E8C;"><?= sanitize($o['order_number']) ?></strong></td>
                <td style="color:#888;font-size:12px;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td><?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?></td>
                <td><strong><?= formatPrice((float)$o['total']) ?></strong></td>
                <td>
                    <span class="status-badge <?= $o['payment_status']==='paid'?'status-paid':'status-unpaid' ?>">
                        <?= ucfirst($o['payment_status']) ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge <?= $statusClass[$o['status']] ?? '' ?>">
                        <?= ucfirst($o['status']) ?>
                    </span>
                </td>
                <td>
                    <a href="orders.php?view=<?= $o['id'] ?>" class="btn-view">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
