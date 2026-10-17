<?php
$pageTitle = 'My Cart';
require_once 'includes/config.php';
requireLogin();
$pdo = getDB();
$uid = $_SESSION['user_id'];

// Handle remove
if (isset($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
    $stmt->execute([intval($_GET['remove']), $uid]);
    flashMessage('success', 'Item removed from cart.');
    redirect(SITE_URL . '/cart.php');
}

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.image,
           p.price, p.sale_price, p.stock_quantity, cat.name as cat_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ?
");
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

$subtotal = 0;
foreach ($items as $item) {
    $price = !empty($item['sale_price']) && $item['sale_price'] < $item['price'] ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
$total    = $subtotal + $shipping;

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Shopping Cart</li>
        </ol></nav>
        <h1>Shopping Cart</h1>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($items)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bag" style="font-size:4rem;color:var(--pink-light);"></i>
        <h3 class="mt-3">Your cart is empty</h3>
        <p class="text-muted">Looks like you haven't added anything yet.</p>
        <a href="products.php" class="btn btn-pink-lg mt-2">Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-body p-0">
                    <table class="table cart-table mb-0">
                        <thead>
                            <tr>
                                <th style="padding-left:20px;">Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item):
                            $unitPrice = (!empty($item['sale_price']) && $item['sale_price'] < $item['price']) ? $item['sale_price'] : $item['price'];
                            $lineTotal = $unitPrice * $item['quantity'];
                            $img = 'assets/images/products/' . ($item['image'] ?? 'placeholder.jpg');
                        ?>
                        <tr>
                            <td style="padding-left:20px;">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= $img ?>" alt="<?= sanitize($item['name']) ?>"
                                         class="cart-product-img"
                                         onerror="this.src='https://placehold.co/70x70/FFF0F7/E91E8C?text=IMG'">
                                    <div>
                                        <div class="cart-product-name"><?= sanitize($item['name']) ?></div>
                                        <div style="font-size:12px;color:var(--mid-grey);"><?= sanitize($item['cat_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= formatPrice((float)$unitPrice) ?></td>
                            <td>
                                <div class="qty-control" style="width:fit-content;">
                                    <button class="qty-btn" data-action="decrease" onclick="changeQty(<?= $item['cart_id'] ?>, -1)">−</button>
                                    <input type="number" class="qty-input cart-qty-update"
                                           data-cart-id="<?= $item['cart_id'] ?>"
                                           value="<?= $item['quantity'] ?>" min="1"
                                           max="<?= $item['stock_quantity'] ?>">
                                    <button class="qty-btn" data-action="increase" onclick="changeQty(<?= $item['cart_id'] ?>, 1)">+</button>
                                </div>
                            </td>
                            <td class="fw-bold" style="color:var(--pink);"><?= formatPrice((float)$lineTotal) ?></td>
                            <td>
                                <a href="cart.php?remove=<?= $item['cart_id'] ?>"
                                   class="btn btn-sm btn-outline-danger rounded-circle"
                                   onclick="return confirm('Remove this item?')"
                                   style="width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-x"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <a href="products.php" class="btn btn-outline-pink"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="order-summary-card">
                <h5 class="fw-bold mb-4" style="font-family:var(--font-display);font-size:1.4rem;">Order Summary</h5>
                <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?= $shipping === 0 ? '<span class="text-success">Free</span>' : formatPrice($shipping) ?></span>
                </div>
                <?php if ($subtotal < FREE_SHIPPING_THRESHOLD): ?>
                <div style="font-size:12px;color:var(--mid-grey);margin-top:4px;padding:8px;background:white;border-radius:8px;">
                    <i class="bi bi-info-circle text-pink-color me-1"></i>
                    Add <?= formatPrice(FREE_SHIPPING_THRESHOLD - $subtotal) ?> more for free shipping!
                </div>
                <?php endif; ?>
                <div class="summary-row summary-total">
                    <span class="fw-bold">Total</span>
                    <span style="color:var(--pink);"><?= formatPrice($total) ?></span>
                </div>

                <!-- Payment Methods -->
                <div class="mt-3 mb-4">
                    <div style="font-size:12px;color:var(--mid-grey);margin-bottom:8px;">We accept:</div>
                    <div class="payment-badges" style="justify-content:flex-start;">
                        <span class="payment-badge" style="background:var(--pink-soft);color:var(--black);"><i class="bi bi-shield-lock-fill text-pink-color"></i> Paystack (M-Pesa or card)</span>
                    </div>
                </div>

                <a href="checkout.php" class="btn btn-pink-lg w-100">
                    Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeQty(cartId, delta) {
    const input = document.querySelector(`.cart-qty-update[data-cart-id="${cartId}"]`);
    if (!input) return;
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(val, parseInt(input.max)));
    input.value = val;
    input.dispatchEvent(new Event('change'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
