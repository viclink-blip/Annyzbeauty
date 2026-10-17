<?php
$adminTitle = 'Dashboard';
require_once 'includes/admin_header.php';
$pdo = getDB();

// Stats
$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$totalOrders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
$pendingOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$todayOrders    = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, COALESCE(NULLIF(o.customer_name,''), CONCAT(u.first_name,' ',u.last_name)) as customer FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();

// Low stock
$lowStock = $pdo->query("SELECT * FROM products WHERE stock_quantity <= 5 AND is_active=1 ORDER BY stock_quantity ASC LIMIT 6")->fetchAll();

// Top products (by order count)
$topProducts = $pdo->query("SELECT p.name, p.image, SUM(oi.quantity) as sold, SUM(oi.total_price) as revenue FROM order_items oi JOIN products p ON oi.product_id=p.id GROUP BY oi.product_id ORDER BY sold DESC LIMIT 5")->fetchAll();

$statusClass = ['pending'=>'status-pending','processing'=>'status-processing','shipped'=>'status-shipped','delivered'=>'status-delivered','cancelled'=>'status-cancelled'];
?>

<!-- Stat Cards Row -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon pink"><i class="bi bi-bag-fill"></i></div>
            <div>
                <div class="stat-num"><?= $totalProducts ?></div>
                <div class="stat-label">Products</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-num"><?= $totalCustomers ?></div>
                <div class="stat-label">Customers</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="stat-num"><?= $totalOrders ?></div>
                <div class="stat-label">Total Orders</div>
                <?php if ($pendingOrders): ?>
                <div class="stat-change" style="color:var(--pink);font-size:11px;"><?= $pendingOrders ?> pending</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon green"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="stat-num" style="font-size:1.4rem;"><?= formatPrice((float)$totalRevenue) ?></div>
                <div class="stat-label">Revenue</div>
                <div class="stat-change up"><i class="bi bi-arrow-up"></i> <?= $todayOrders ?> today</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-0 px-4">
                <h6 class="fw-bold mb-0" style="font-family:var(--font-display);font-size:1.1rem;">Recent Orders</h6>
                <a href="orders.php" class="btn btn-outline-pink btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr>
                            <th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><strong><?= sanitize($o['order_number']) ?></strong></td>
                            <td><?= sanitize($o['customer'] ?? '') ?></td>
                            <td><?= formatPrice((float)$o['total']) ?></td>
                            <td><span class="status-badge <?= $o['payment_status']==='paid'?'status-paid':'status-unpaid' ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                            <td><span class="status-badge <?= $statusClass[$o['status']] ?? '' ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td><a href="order_detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4 d-flex flex-column gap-4">

        <!-- Low Stock -->
        <?php if ($lowStock): ?>
        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                <h6 class="fw-bold mb-0" style="font-family:var(--font-display);font-size:1.1rem;">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Low Stock
                </h6>
            </div>
            <div class="card-body pt-2">
                <?php foreach ($lowStock as $ls): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--border)!important;">
                    <span style="font-size:13px;"><?= sanitize($ls['name']) ?></span>
                    <span class="badge <?= $ls['stock_quantity']==0?'bg-danger':'bg-warning text-dark' ?>">
                        <?= $ls['stock_quantity'] ?> left
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Products -->
        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                <h6 class="fw-bold mb-0" style="font-family:var(--font-display);font-size:1.1rem;">Top Products</h6>
            </div>
            <div class="card-body pt-2">
                <?php foreach ($topProducts as $i => $tp): ?>
                <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color:var(--border)!important;">
                    <span style="font-size:11px;font-weight:700;color:var(--pink);min-width:16px;">#<?= $i+1 ?></span>
                    <div class="flex-grow-1">
                        <div style="font-size:12.5px;font-weight:600;"><?= sanitize($tp['name']) ?></div>
                        <div style="font-size:11px;color:var(--mid-grey);"><?= $tp['sold'] ?> sold · <?= formatPrice((float)$tp['revenue']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
