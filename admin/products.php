<?php
$adminTitle = 'Products';
require_once 'includes/admin_header.php';
$pdo = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([intval($_GET['delete'])]);
    flashMessage('success', 'Product deleted.');
    redirect(SITE_URL . '/admin/products.php');
}

$search   = trim($_GET['search'] ?? '');
$catFilter= intval($_GET['cat'] ?? 0);
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 15; $offset = ($page-1)*$perPage;

$where = ['p.is_active=1'];
$params = [];
if ($search) { $where[] = 'p.name LIKE ?'; $params[] = "%$search%"; }
if ($catFilter) { $where[] = 'p.category_id=?'; $params[] = $catFilter; }
$whereStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereStr");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE $whereStr ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h5 class="fw-bold mb-1" style="font-family:var(--font-display);font-size:1.4rem;">All Products</h5>
        <p class="text-muted mb-0" style="font-size:13px;"><?= $total ?> products total</p>
    </div>
    <a href="product_add.php" class="btn btn-pink">
        <i class="bi bi-plus-lg me-1"></i>Add New Product
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-xl mb-4">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:260px;" placeholder="Search products..." value="<?= sanitize($search) ?>">
            <select name="cat" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
                <option value="0">All Categories</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-pink">Filter</button>
            <?php if ($search || $catFilter): ?><a href="products.php" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-xl">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr>
                    <th style="padding-left:20px;">Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No products found.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $p):
                    $img = SITE_URL . '/assets/images/products/' . ($p['image'] ?? 'placeholder.jpg');
                    $isSale = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
                    $displayPrice = $isSale ? $p['sale_price'] : $p['price'];
                ?>
                <tr>
                    <td style="padding-left:20px;">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $img ?>" style="width:46px;height:46px;object-fit:cover;border-radius:10px;background:var(--pink-soft);"
                                 onerror="this.src='https://placehold.co/46x46/FFF0F7/E91E8C?text=P'">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;"><?= sanitize($p['name']) ?></div>
                                <div style="font-size:11px;color:var(--mid-grey);">SKU: <?= sanitize($p['sku'] ?? '-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span style="font-size:12px;"><?= sanitize($p['cat_name']) ?></span></td>
                    <td>
                        <div style="font-weight:600;color:var(--pink);"><?= formatPrice((float)$displayPrice) ?></div>
                        <?php if ($isSale): ?><div style="font-size:11px;color:var(--mid-grey);text-decoration:line-through;"><?= formatPrice((float)$p['price']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $p['stock_quantity']==0?'bg-danger':($p['stock_quantity']<=5?'bg-warning text-dark':'bg-success') ?>">
                            <?= $p['stock_quantity'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($p['is_featured']): ?>
                        <i class="bi bi-star-fill" style="color:var(--pink);font-size:15px;" title="Featured"></i>
                        <?php else: ?>
                        <span style="color:var(--mid-grey);font-size:13px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                            <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once 'includes/admin_footer.php'; ?>
