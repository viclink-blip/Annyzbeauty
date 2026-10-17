<?php
$pageTitle = 'Shop';
require_once 'includes/header.php';
$pdo = getDB();

// --- Filters ---
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$featured = isset($_GET['featured']);
$sort     = $_GET['sort'] ?? 'newest';
$maxPrice = intval($_GET['max_price'] ?? 20000);
$page     = max(1, intval($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// --- Build Query ---
$where  = ['p.is_active = 1'];
$params = [];

if ($search) {
    $where[]  = '(p.name LIKE ? OR p.short_description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $where[]  = 'c.slug = ?';
    $params[] = $category;
}
if ($featured) {
    $where[] = 'p.is_featured = 1';
}
if ($maxPrice < 20000) {
    $where[]  = 'COALESCE(p.sale_price, p.price) <= ?';
    $params[] = $maxPrice;
}

$whereStr = implode(' AND ', $where);

$orderMap = [
    'newest'    => 'p.created_at DESC',
    'price_asc' => 'COALESCE(p.sale_price, p.price) ASC',
    'price_desc'=> 'COALESCE(p.sale_price, p.price) DESC',
    'name_asc'  => 'p.name ASC',
];
$orderBy = $orderMap[$sort] ?? 'p.created_at DESC';

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id=c.id WHERE $whereStr");
$countStmt->execute($params);
$total     = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch products
$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// All categories for filter
$allCats = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();

$pageTitle = $search ? 'Search: ' . sanitize($search) : ($category ? ucwords(str_replace('-',' ',$category)) : 'Shop All');
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
                <li class="breadcrumb-item active">Shop</li>
                <?php if ($category): ?><li class="breadcrumb-item active"><?= sanitize(ucwords(str_replace('-',' ',$category))) ?></li><?php endif; ?>
            </ol>
        </nav>
        <h1><?= $pageTitle ?></h1>
        <?php if ($search): ?>
        <p class="text-muted mb-0"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<strong><?= sanitize($search) ?></strong>"</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/stats.php'; ?>

<div class="container py-5">
    <div class="row g-4">

        <!-- FILTER SIDEBAR -->
        <div class="col-lg-3">
            <form method="GET" id="filterForm">
                <?php if ($search): ?><input type="hidden" name="search" value="<?= sanitize($search) ?>"><?php endif; ?>

                <!-- Categories -->
                <div class="filter-card mb-3">
                    <div class="filter-title">Categories</div>
                    <div>
                        <div class="filter-item">
                            <input type="radio" name="category" value="" id="cat-all" <?= !$category ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label for="cat-all">All Categories</label>
                        </div>
                        <?php foreach ($allCats as $c): ?>
                        <div class="filter-item">
                            <input type="radio" name="category" value="<?= $c['slug'] ?>" id="cat-<?= $c['id'] ?>" <?= $category === $c['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label for="cat-<?= $c['id'] ?>"><?= sanitize($c['name']) ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Range -->
                <div class="filter-card mb-3">
                    <div class="filter-title">Max Price</div>
                    <input type="range" class="form-range" id="priceRange" name="max_price" min="500" max="20000" step="500" value="<?= $maxPrice ?>">
                    <div class="price-range-labels mt-1">
                        <span>KSh 500</span>
                        <span id="priceLabel">KSh <?= number_format($maxPrice) ?></span>
                    </div>
                    <button type="submit" class="btn btn-pink btn-sm w-100 mt-3">Apply Filter</button>
                </div>

                <!-- Featured -->
                <div class="filter-card">
                    <div class="filter-title">Filter</div>
                    <div class="filter-item">
                        <input type="checkbox" name="featured" value="1" id="chk-featured" <?= $featured ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label for="chk-featured">Featured Only</label>
                    </div>
                </div>
            </form>
        </div>

        <!-- PRODUCT GRID -->
        <div class="col-lg-9">
            <!-- Toolbar -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <p class="mb-0 text-muted" style="font-size:13px;">
                    Showing <strong><?= count($products) ?></strong> of <strong><?= $total ?></strong> products
                </p>
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted" style="font-size:13px;white-space:nowrap;">Sort by:</label>
                    <select class="form-select form-select-sm" style="width:180px;" onchange="window.location.href=this.value">
                        <?php
                        $baseUrl = '?category=' . urlencode($category) . ($search ? '&search='.urlencode($search) : '') . ($featured ? '&featured=1' : '') . '&max_price=' . $maxPrice . '&sort=';
                        $sorts = ['newest'=>'Newest First','price_asc'=>'Price: Low to High','price_desc'=>'Price: High to Low','name_asc'=>'Name A–Z'];
                        foreach ($sorts as $val => $label):
                        ?>
                        <option value="<?= $baseUrl . $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size:3rem;color:var(--pink-light);"></i>
                <h4 class="mt-3">No products found</h4>
                <p class="text-muted">Try adjusting your search or filters.</p>
                <a href="products.php" class="btn btn-pink">Browse All Products</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $p): ?>
                <div class="col-6 col-md-4">
                    <?php include 'includes/product_card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-5 d-flex justify-content-center">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
