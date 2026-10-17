<?php
require_once __DIR__ . '/shop.php';
$cartCount   = getCartCount();
$flash       = getFlashMessage();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="<?= SITE_NAME ?> - Your premier beauty destination in Kenya. Shop skincare, makeup, haircare, fragrances and more.">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/mobile-fix.css" rel="stylesheet">
    <style>
        #mainNav {
            background: #ffffff;
            border-bottom: 1px solid rgba(233,30,140,0.10);
            box-shadow: 0 2px 20px rgba(233,30,140,0.07);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: box-shadow 0.3s ease;
        }
        #mainNav.scrolled { box-shadow: 0 4px 28px rgba(233,30,140,0.13); }
        .navbar-inner {
            display: flex; align-items: center;
            justify-content: space-between;
            height: 68px; gap: 24px;
        }
        .navbar-brand-wrap {
            text-decoration: none; display: flex;
            align-items: center; flex-shrink: 0;
        }
        .navbar-logo {
            height: 50px;
            width: auto;
            display: block;
            object-fit: contain;
            transition: opacity 0.2s ease;
        }
        .navbar-logo:hover { opacity: 0.88; }
        .nav-links {
            display: flex; align-items: center;
            gap: 4px; list-style: none; margin: 0; padding: 0;
        }
        .nav-links .nav-item a {
            display: flex; align-items: center; gap: 5px;
            padding: 7px 14px; font-size: 13.5px; font-weight: 500;
            color: #333; border-radius: 8px; text-decoration: none;
            transition: all 0.22s ease; white-space: nowrap;
        }
        .nav-links .nav-item a:hover { color: #E91E8C; background: #FFF0F7; }
        .nav-links .nav-item a.active {
            color: #E91E8C; background: #FFF0F7; font-weight: 600;
        }
        .nav-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-nav-icon {
            background: none; border: none;
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #555; font-size: 18px; cursor: pointer;
            transition: all 0.22s ease; text-decoration: none; flex-shrink: 0;
        }
        .btn-nav-icon:hover { background: #FFF0F7; color: #E91E8C; }
        .btn-nav-cart { position: relative; }
        .cart-dot {
            position: absolute; top: 4px; right: 4px;
            width: 17px; height: 17px;
            background: #E91E8C; color: #fff;
            font-size: 9px; font-weight: 700; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'DM Sans', sans-serif; border: 2px solid #fff;
        }
        .btn-nav-login {
            padding: 7px 18px; font-size: 13px; font-weight: 500;
            color: #E91E8C; border: 1.5px solid #E91E8C;
            border-radius: 50px; background: transparent;
            text-decoration: none; transition: all 0.22s ease; white-space: nowrap;
        }
        .btn-nav-login:hover { background: #FFF0F7; color: #C2176F; border-color: #C2176F; }
        .btn-nav-register {
            padding: 7px 18px; font-size: 13px; font-weight: 600;
            color: #fff; background: linear-gradient(135deg,#E91E8C,#C2176F);
            border: none; border-radius: 50px; text-decoration: none;
            transition: all 0.22s ease; white-space: nowrap;
            box-shadow: 0 3px 12px rgba(233,30,140,0.25);
        }
        .btn-nav-register:hover {
            background: linear-gradient(135deg,#C2176F,#A0115C);
            color: #fff; transform: translateY(-1px);
            box-shadow: 0 5px 18px rgba(233,30,140,0.35);
        }
        .user-dropdown .dropdown-toggle {
            background: #FFF0F7; border: 1.5px solid rgba(233,30,140,0.2);
            border-radius: 50px; padding: 6px 14px 6px 8px;
            display: flex; align-items: center; gap: 7px;
            font-size: 13px; font-weight: 500; color: #1A1A1A;
            cursor: pointer; transition: all 0.22s ease; text-decoration: none;
        }
        .user-dropdown .dropdown-toggle:hover { border-color: #E91E8C; background: #FCE4F3; }
        .user-avatar {
            width: 26px; height: 26px;
            background: linear-gradient(135deg,#E91E8C,#C2176F);
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; color: #fff;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
        }
        .dropdown-menu {
            border: 1px solid rgba(233,30,140,0.12);
            box-shadow: 0 8px 32px rgba(233,30,140,0.12);
            border-radius: 14px; padding: 8px; min-width: 190px;
        }
        .dropdown-item {
            border-radius: 8px; font-size: 13.5px;
            padding: 9px 14px; display: flex;
            align-items: center; gap: 9px; color: #333;
        }
        .dropdown-item:hover { background: #FFF0F7; color: #E91E8C; }
        .dropdown-item.text-danger:hover { background: #FFF5F5; color: #dc3545; }
        .dropdown-divider { border-color: rgba(233,30,140,0.10); margin: 4px 0; }
        .search-bar-wrap {
            background: #fff;
            border-bottom: 1px solid rgba(233,30,140,0.10);
            overflow: hidden; max-height: 0;
            transition: max-height 0.35s ease, padding 0.35s ease;
            padding: 0;
        }
        .search-bar-wrap.open { max-height: 80px; padding: 12px 0; }
        .search-inner { display: flex; align-items: center; gap: 10px; }
        .search-inner input {
            flex: 1; border: 1.5px solid rgba(233,30,140,0.20);
            border-radius: 50px; padding: 9px 20px;
            font-size: 14px; outline: none; transition: border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .search-inner input:focus { border-color: #E91E8C; }
        .search-inner input::placeholder { color: #bbb; }
        .btn-search-go {
            background: linear-gradient(135deg,#E91E8C,#C2176F);
            border: none; color: #fff; border-radius: 50px;
            padding: 9px 22px; font-size: 13.5px; font-weight: 600;
            cursor: pointer; transition: all 0.22s ease; white-space: nowrap;
        }
        .btn-search-go:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-search-close {
            background: none; border: none; color: #aaa;
            font-size: 20px; cursor: pointer; padding: 0 4px; transition: color 0.2s;
        }
        .btn-search-close:hover { color: #E91E8C; }
        .hamburger-btn {
            background: none; border: none;
            width: 38px; height: 38px; border-radius: 8px;
            display: none; align-items: center; justify-content: center;
            color: #333; font-size: 22px; cursor: pointer;
            transition: all 0.2s; flex-shrink: 0;
        }
        .hamburger-btn:hover { background: #FFF0F7; color: #E91E8C; }
        .mobile-menu {
            background: #fff;
            border-bottom: 1px solid rgba(233,30,140,0.10);
            overflow: hidden; max-height: 0;
            transition: max-height 0.38s ease;
        }
        .mobile-menu.open { max-height: 400px; }
        .mobile-menu-inner { padding: 12px 16px 20px; }
        .mobile-nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px; font-size: 15px; font-weight: 500;
            color: #333; border-radius: 10px; text-decoration: none;
            transition: all 0.2s; margin-bottom: 4px;
        }
        .mobile-nav-link:hover, .mobile-nav-link.active { background: #FFF0F7; color: #E91E8C; }
        .mobile-nav-link i { font-size: 17px; width: 22px; }
        .mobile-divider { height: 1px; background: rgba(233,30,140,0.08); margin: 10px 0; }
        @media (max-width: 991px) {
            .nav-links { display: none !important; }
            .hamburger-btn { display: flex !important; }
            .btn-nav-login, .btn-nav-register { display: none !important; }
            .user-dropdown { display: none !important; }
            .navbar-inner { height: 60px; }
        }
        @media (max-width: 575px) {
            .navbar-logo { height: 40px; }
        }
    </style>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-QFV1BW7R8R"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-QFV1BW7R8R');
    </script>
</head>
<body>

<!-- NAVBAR -->
<nav id="mainNav">
    <div class="container">
        <div class="navbar-inner">

            <!-- Brand Logo -->
            <a href="<?= SITE_URL ?>" class="navbar-brand-wrap">
                <img src="<?= SITE_URL ?>/assets/images/logo.png"
                     alt="Annyzbeauty"
                     class="navbar-logo">
            </a>

            <!-- Desktop Nav Links -->
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="<?= SITE_URL ?>/index.php"
                       class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= SITE_URL ?>/products.php"
                       class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                        <i class="bi bi-bag"></i> Shop
                    </a>
                </li>
            </ul>

            <!-- Right Actions -->
            <div class="nav-actions">

                <!-- Search -->
                <button class="btn-nav-icon" id="searchToggle" title="Search">
                    <i class="bi bi-search"></i>
                </button>

                <!-- Cart -->
                <a href="<?= SITE_URL ?>/cart.php" class="btn-nav-icon btn-nav-cart" title="Cart">
                    <i class="bi bi-bag"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-dot"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()):
                    $initials = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
                ?>
                <!-- User Dropdown -->
                <div class="user-dropdown dropdown">
                    <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><?= $initials ?></div>
                        <span class="d-none d-lg-inline"><?= sanitize($_SESSION['user_name'] ?? 'Account') ?></span>
                        <i class="bi bi-chevron-down" style="font-size:10px;color:#aaa;"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/profile.php">
                                <i class="bi bi-person" style="color:#E91E8C;"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/orders.php">
                                <i class="bi bi-bag-check" style="color:#E91E8C;"></i> My Orders
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>

                <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn-nav-login">Login</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn-nav-register">Register</a>
                <?php endif; ?>

                <!-- Hamburger -->
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Menu">
                    <i class="bi bi-list" id="hamburgerIcon"></i>
                </button>

            </div>
        </div>
    </div>
</nav>

<!-- SEARCH BAR -->
<div class="search-bar-wrap" id="searchBar">
    <div class="container">
        <form class="search-inner" action="<?= SITE_URL ?>/products.php" method="GET">
            <input type="text" name="search" id="searchInput"
                   placeholder="Search for lipstick, serum, perfume..."
                   value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>">
            <button type="submit" class="btn-search-go">
                <i class="bi bi-search me-1"></i> Search
            </button>
            <button type="button" class="btn-search-close" id="searchClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </form>
    </div>
</div>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-inner">
        <a href="<?= SITE_URL ?>/index.php"
           class="mobile-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-house-fill"></i> Home
        </a>
        <a href="<?= SITE_URL ?>/products.php"
           class="mobile-nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">
            <i class="bi bi-bag-fill"></i> Shop
        </a>
        <div class="mobile-divider"></div>
        <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/profile.php" class="mobile-nav-link">
            <i class="bi bi-person-fill"></i> My Profile
        </a>
        <a href="<?= SITE_URL ?>/orders.php" class="mobile-nav-link">
            <i class="bi bi-bag-check-fill"></i> My Orders
        </a>
        <a href="<?= SITE_URL ?>/cart.php" class="mobile-nav-link">
            <i class="bi bi-cart-fill"></i> Cart
            <?php if ($cartCount > 0): ?>
            <span style="margin-left:auto;background:#E91E8C;color:#fff;font-size:11px;font-weight:700;border-radius:50px;padding:2px 8px;"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <div class="mobile-divider"></div>
        <a href="<?= SITE_URL ?>/logout.php" class="mobile-nav-link" style="color:#dc3545;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="mobile-nav-link">
            <i class="bi bi-person-circle"></i> Login
        </a>
        <a href="<?= SITE_URL ?>/register.php" class="mobile-nav-link" style="color:#E91E8C;font-weight:600;">
            <i class="bi bi-person-plus-fill"></i> Register
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- FLASH MESSAGES -->
<?php if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show"
         style="border-radius:12px;font-size:14px;" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2"></i>
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    window.addEventListener('scroll', function () {
        document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 40);
    });
    var searchToggle = document.getElementById('searchToggle');
    var searchBar    = document.getElementById('searchBar');
    var searchClose  = document.getElementById('searchClose');
    var searchInput  = document.getElementById('searchInput');
    searchToggle.addEventListener('click', function () {
        searchBar.classList.toggle('open');
        if (searchBar.classList.contains('open')) {
            setTimeout(function(){ searchInput && searchInput.focus(); }, 350);
        }
    });
    searchClose.addEventListener('click', function () {
        searchBar.classList.remove('open');
    });
    var hamburgerBtn  = document.getElementById('hamburgerBtn');
    var hamburgerIcon = document.getElementById('hamburgerIcon');
    var mobileMenu    = document.getElementById('mobileMenu');
    hamburgerBtn.addEventListener('click', function () {
        var open = mobileMenu.classList.toggle('open');
        hamburgerIcon.className = open ? 'bi bi-x-lg' : 'bi bi-list';
    });
    document.addEventListener('click', function (e) {
        if (!hamburgerBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
            mobileMenu.classList.remove('open');
            hamburgerIcon.className = 'bi bi-list';
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            searchBar.classList.remove('open');
            mobileMenu.classList.remove('open');
            hamburgerIcon.className = 'bi bi-list';
        }
    });
})();
</script>
