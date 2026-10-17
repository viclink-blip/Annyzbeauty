<?php
// $p = product row with cat_name
$imgSrc     = 'assets/images/products/' . ($p['image'] ?? 'placeholder.jpg');
$placeholder = 'https://placehold.co/300x300/FFF0F7/E91E8C?text=' . urlencode($p['name']);
$isSale      = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
$displayPrice = $isSale ? $p['sale_price'] : $p['price'];
?>
<div class="product-card h-100">
    <div class="product-image-wrap">
        <a href="product.php?id=<?= $p['id'] ?>">
            <img src="<?= $imgSrc ?>"
                 alt="<?= sanitize($p['name']) ?>"
                 onerror="this.src='<?= $placeholder ?>'">
        </a>
        <?php if ($p['is_featured']): ?>
        <span class="product-badge">Featured</span>
        <?php elseif ($isSale): ?>
        <span class="product-badge sale">Sale</span>
        <?php endif; ?>
        <div class="product-actions">
            <button class="btn-action primary btn-add-to-cart"
                    data-product-id="<?= $p['id'] ?>">
                <i class="bi bi-bag-plus me-1"></i> Add to Cart
            </button>
        </div>
    </div>
    <div class="product-body">
        <div class="product-category"><?= sanitize($p['cat_name'] ?? '') ?></div>
        <h3 class="product-name">
            <a href="product.php?id=<?= $p['id'] ?>"><?= sanitize($p['name']) ?></a>
        </h3>
        <div class="product-price">
            <span class="price-current"><?= formatPrice((float)$displayPrice) ?></span>
            <?php if ($isSale): ?>
            <span class="price-old"><?= formatPrice((float)$p['price']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
