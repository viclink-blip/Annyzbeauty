<?php
require_once 'includes/config.php';
$pdo = getDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: products.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p JOIN categories c ON p.category_id=c.id WHERE p.id=? AND p.is_active=1");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header('Location: products.php'); exit; }

// Related products
$related = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id=c.id WHERE p.category_id=? AND p.id!=? AND p.is_active=1 LIMIT 4");
$related->execute([$p['category_id'], $id]);
$relatedProducts = $related->fetchAll();

$isSale = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
$displayPrice = $isSale ? $p['sale_price'] : $p['price'];
$imgSrc = 'assets/images/products/' . ($p['image'] ?? 'placeholder.jpg');
$placeholder = 'https://placehold.co/500x500/FFF0F7/E91E8C?text=' . urlencode($p['name']);

$pageTitle = $p['name'];
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Shop</a></li>
                <li class="breadcrumb-item"><a href="products.php?category=<?= $p['cat_slug'] ?>"><?= sanitize($p['cat_name']) ?></a></li>
                <li class="breadcrumb-item active"><?= sanitize($p['name']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">

        <!-- Product Image -->
        <div class="col-lg-5">
            <img id="mainProductImg" src="<?= $imgSrc ?>"
                 alt="<?= sanitize($p['name']) ?>"
                 class="product-detail-img mb-3"
                 onerror="this.src='<?= $placeholder ?>'">
        </div>

        <!-- Product Info -->
        <div class="col-lg-7">
            <span class="section-eyebrow"><?= sanitize($p['cat_name']) ?></span>
            <h1 class="product-detail-title"><?= sanitize($p['name']) ?></h1>

            <!-- Price -->
            <div class="product-price mb-3" style="font-size:1.5rem;">
                <span class="price-current"><?= formatPrice((float)$displayPrice) ?></span>
                <?php if ($isSale): ?>
                <span class="price-old ms-2"><?= formatPrice((float)$p['price']) ?></span>
                <span class="badge ms-2" style="background:var(--pink);font-size:12px;">
                    <?= round((1 - $p['sale_price']/$p['price'])*100) ?>% OFF
                </span>
                <?php endif; ?>
            </div>

            <!-- Short description -->
            <?php if ($p['short_description']): ?>
            <p class="text-muted mb-4"><?= sanitize($p['short_description']) ?></p>
            <?php endif; ?>

            <!-- Stock -->
            <div class="mb-4">
                <?php if ($p['stock_quantity'] > 0): ?>
                <span class="text-success fw-600"><i class="bi bi-check-circle-fill me-1"></i>
                    <?= $p['stock_quantity'] > 5 ? 'In Stock' : "Only {$p['stock_quantity']} left!" ?>
                </span>
                <?php else: ?>
                <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Add to Cart -->
            <?php if ($p['stock_quantity'] > 0): ?>
            <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                <div class="qty-control">
                    <button class="qty-btn" data-action="decrease">−</button>
                    <input type="number" class="qty-input" id="qty-<?= $p['id'] ?>" value="1" min="1" max="<?= $p['stock_quantity'] ?>">
                    <button class="qty-btn" data-action="increase">+</button>
                </div>
                <button class="btn btn-pink-lg btn-add-to-cart" data-product-id="<?= $p['id'] ?>" style="flex:1;max-width:260px;">
                    <i class="bi bi-bag-plus me-2"></i>Add to Cart
                </button>
            </div>
            <?php endif; ?>

            <!-- Order on WhatsApp (no account needed) -->
            <a href="<?= sanitize(whatsappLink('Hi Annyzbeauty, I would like to order: ' . $p['name'] . ' (' . formatPrice((float)$displayPrice) . '). ' . SITE_URL . '/product.php?id=' . $p['id'])) ?>"
               target="_blank" rel="noopener" class="btn w-100 mb-4"
               style="max-width:360px;background:#25D366;color:#fff;border-radius:50px;padding:12px 20px;font-weight:600;">
                <i class="bi bi-whatsapp me-2"></i>Order on WhatsApp
            </a>
            <div style="font-size:12px;color:var(--mid-grey);margin:-14px 0 20px;">No account needed. We will confirm your order on WhatsApp.</div>

            <!-- Meta -->
            <div class="border-top pt-3" style="font-size:13px;color:var(--mid-grey);">
                <?php if ($p['sku']): ?><div class="mb-1"><strong>SKU:</strong> <?= sanitize($p['sku']) ?></div><?php endif; ?>
                <div><strong>Category:</strong> <a href="products.php?category=<?= $p['cat_slug'] ?>"><?= sanitize($p['cat_name']) ?></a></div>
            </div>

            <!-- Trust badges -->
            <div class="d-flex gap-3 mt-4 flex-wrap">
                <div style="font-size:12px;color:var(--mid-grey);display:flex;align-items:center;gap:5px;">
                    <i class="bi bi-truck text-pink-color"></i> Fast Kenya Delivery
                </div>
                <div style="font-size:12px;color:var(--mid-grey);display:flex;align-items:center;gap:5px;">
                    <i class="bi bi-shield-check text-pink-color"></i> 100% Authentic
                </div>
                <div style="font-size:12px;color:var(--mid-grey);display:flex;align-items:center;gap:5px;">
                    <i class="bi bi-arrow-counterclockwise text-pink-color"></i> 7-Day Returns
                </div>
            </div>
        </div>
    </div>

    <!-- Full Description Tabs -->
    <div class="mt-5">
        <ul class="nav nav-tabs" style="border-bottom:2px solid var(--border);">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc"
                        style="font-weight:600;color:var(--black);border:none;padding:12px 20px;">Description</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shipping"
                        style="font-weight:600;color:var(--mid-grey);border:none;padding:12px 20px;">Shipping & Returns</button>
            </li>
        </ul>
        <div class="tab-content pt-4">
            <div class="tab-pane fade show active" id="tab-desc">
                <p style="line-height:1.8;color:#444;"><?= nl2br(sanitize($p['description'])) ?></p>
            </div>
            <div class="tab-pane fade" id="tab-shipping">
                <ul style="line-height:2;color:#555;font-size:14px;">
                    <li>Standard delivery: <strong>2–4 business days</strong> (Nairobi), 4–7 days (nationwide).</li>
                    <li>Shipping fee: <strong><?= formatPrice(SHIPPING_FEE) ?></strong> · Free on orders over <?= formatPrice(FREE_SHIPPING_THRESHOLD) ?>.</li>
                    <li>Secure payment with <strong>Paystack</strong> (M-Pesa or card).</li>
                    <li>Returns accepted within <strong>7 days</strong> of delivery for unused, sealed items. See our <a href="returns.php">Returns &amp; Refunds Policy</a>.</li>
                    <li>Contact us on WhatsApp: <strong><?= WHATSAPP_DISPLAY ?></strong></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($relatedProducts): ?>
    <div class="mt-5">
        <h3 class="mb-4" style="font-family:var(--font-display);font-size:1.8rem;">You May Also Like</h3>
        <div class="row g-4">
            <?php foreach ($relatedProducts as $p): ?>
            <div class="col-6 col-md-3">
                <?php include 'includes/product_card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
