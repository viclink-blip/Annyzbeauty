<?php
require_once __DIR__ . '/config.php';
$cartCount = getCartCount();
$flash = getFlashMessage();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="<?= SITE_NAME ?> - Your premier beauty destination in Kenya. Shop skincare, makeup, haircare, fragrances and more.">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-telephone me-1"></i> +254 758 556 523 &nbsp;|&nbsp; <i class="bi bi-envelope me-1"></i> hello@annyzbeauty.com</span>
            <span><i class="bi bi-truck me-1"></i> Free shipping on orders over <?= formatPrice(FREE_SHIPPING_THRESHOLD) ?></span>
        </div>
    </div>
</div>

<!-- Main Navbar -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>">
            <div class="brand-icon"><i class="bi bi-flower1"></i></div>
            <div>
                <span class="brand-name">Annyz</span><span class="brand-name brand-accent">beauty</span>
                <div class="brand-tagline">Glow. Bloom. Radiate.</div>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <!-- Search Bar -->
            <form class="d-flex mx-auto search-form" action="<?= SITE_URL ?>/products.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="search" placeholder="Search for products..." 
                           value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>">
                    <button class="btn btn-search" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <!-- Nav Links -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/products.php">Shop</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu">
                        <?php
                        $pdo = getDB();
                        $cats = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
                        foreach ($cats as $cat):
                        ?>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/products.php?category=<?= urlencode($cat['slug']) ?>">
                            <?= sanitize($cat['name']) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link cart-link" href="<?= SITE_URL ?>/cart.php">
                        <i class="bi bi-bag"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-lg-inline"><?= sanitize($_SESSION['user_name'] ?? 'Account') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-outline-pink btn-sm" href="<?= SITE_URL ?>/login.php">Login</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show" role="alert">
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
