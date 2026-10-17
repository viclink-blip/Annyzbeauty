<?php
$adminTitle = 'Orders';
require_once 'includes/admin_header.php';
$pdo = getDB();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $pdo->prepare("UPDATE orders SET status=?,payment_status=? WHERE id=?")
        ->execute([$_POST['status'],$_POST['payment_status'],intval($_POST['order_id'])]);
    flashMessage('success', 'Order updated.');
    redirect(SITE_URL . '/admin/orders.php' . (isset($_GET['view']) ? '?view='.$_GET['view'] : ''));
}

$statusClass = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];

// Single order view
if (isset($_GET['view'])) {
    $order = $pdo->prepare("SELECT o.*, CONCAT(u.first_name,' ',u.last_name) as customer, u.email, u.phone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $order->execute([intval($_GET['view'])]);
    $order = $order->fetch();
    if ($order) {
        $orderItems = $pdo->prepare("SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $orderItems->execute([$order['id']]);
        $orderItems = $orderItems->fetchAll();
    }
}

// Filter
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$perPage = 15; $offset = ($page-1)*$perPage;

$where = ['1=1']; $params = [];
if ($status) { $where[] = 'o.status=?'; $params[] = $status; }
if ($search) { $where[] = '(o.order_number LIKE ? OR CONCAT(u.first_name," ",u.last_name) LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id=u.id WHERE $whereStr");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage);

$orders = $pdo->prepare("SELECT o.*, CONCAT(u.first_name,' ',u.last_name) as customer FROM orders o JOIN users u ON o.user_id=u.id WHERE $whereStr ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$orders->execute($params);
$orders = $orders->fetchAll();
?>

<?php if (isset($order) && $order): ?>
<!-- ORDER DETAIL VIEW -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0" style="font-family:var(--font-display);font-size:1.4rem;">
        Order: <?= sanitize($order['order_number']) ?>
    </h5>
    <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-xl mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Order Items</h6>
                <table class="table" style="font-size:14px;">
                    <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($orderItems as $item): $img = SITE_URL.'/assets/images/products/'.($item['image']??''); ?>
                    <tr>
                        <td class="d-flex align-items-center gap-2">
                            <img src="<?= $img ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;"
                                 onerror="this.src='https://placehold.co/40x40/FFF0F7/E91E8C?text=P'">
                            <?= sanitize($item['product_name']) ?>
                        </td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= formatPrice((float)$item['unit_price']) ?></td>
                        <td><?= formatPrice((float)$item['total_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end fw-bold">Subtotal</td><td><?= formatPrice((float)$order['subtotal']) ?></td></tr>
                        <tr><td colspan="3" class="text-end fw-bold">Shipping</td><td><?= formatPrice((float)$order['shipping_fee']) ?></td></tr>
                        <tr><td colspan="3" class="text-end fw-bold" style="color:var(--pink);">Total</td><td style="font-weight:700;color:var(--pink);"><?= formatPrice((float)$order['total']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 d-flex flex-column gap-4">
        <!-- Customer Info -->
        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Customer</h6>
                <p class="mb-1"><strong><?= sanitize($order['customer']) ?></strong></p>
                <p class="mb-1" style="font-size:13px;"><?= sanitize($order['email']) ?></p>
                <p class="mb-1" style="font-size:13px;"><?= sanitize($order['phone'] ?? '') ?></p>
                <hr style="border-color:var(--border);">
                <p class="mb-0" style="font-size:13px;"><strong>Ship to:</strong><br>
                <?= sanitize($order['shipping_address']) ?><br>
                <?= sanitize($order['shipping_city']) ?>, <?= sanitize($order['shipping_country']) ?></p>
                <hr style="border-color:var(--border);">
                <p class="mb-0" style="font-size:13px;"><strong>Payment:</strong>
                <?= sanitize(ucwords(str_replace('_', ' ', $order['payment_method'] ?? ''))) ?>
                <?php if (!empty($order['payment_reference'])): ?>
                <br>Paystack ref: <code><?= sanitize($order['payment_reference']) ?></code>
                <?php endif; ?></p>
            </div>
        </div>

        <!-- Update Status -->
        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Update Order</h6>
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Order Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <?php foreach (['unpaid','paid','refunded'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-pink w-100">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ORDER LIST -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h5 class="fw-bold mb-1" style="font-family:var(--font-display);font-size:1.4rem;">All Orders</h5>
        <p class="text-muted mb-0" style="font-size:13px;"><?= $total ?> orders total</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-xl mb-4">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:220px;" placeholder="Search order / customer..." value="<?= sanitize($search) ?>">
            <select name="status" class="form-select form-select-sm" style="max-width:160px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-pink">Filter</button>
            <?php if ($search || $status): ?><a href="orders.php" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-xl">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr>
                    <th style="padding-left:20px;">Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No orders found.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td style="padding-left:20px;"><strong><?= sanitize($o['order_number']) ?></strong></td>
                    <td><?= sanitize($o['customer']) ?></td>
                    <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                    <td style="font-weight:700;color:var(--pink);"><?= formatPrice((float)$o['total']) ?></td>
                    <td><span class="status-badge <?= $o['payment_status']==='paid'?'status-paid':'status-unpaid' ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                    <td><span class="status-badge <?= $statusClass[$o['status']] ?? '' ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td>
                        <a href="orders.php?view=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
        <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/admin_footer.php'; ?>
