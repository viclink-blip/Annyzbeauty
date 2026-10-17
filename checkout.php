<?php
$pageTitle = 'Checkout';
require_once 'includes/shop.php';
requireLogin();
$pdo = getDB();
$uid = $_SESSION['user_id'];

// Fetch cart
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.image, p.price, p.sale_price, p.stock_quantity
    FROM cart c JOIN products p ON c.product_id=p.id
    WHERE c.user_id=?
");
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

if (empty($items)) { redirect(SITE_URL . '/cart.php'); }

$subtotal = 0;
foreach ($items as $item) {
    $price = (!empty($item['sale_price']) && $item['sale_price'] < $item['price'])
        ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
$total    = $subtotal + $shipping;

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$userStmt->execute([$uid]);
$user = $userStmt->fetch();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city']    ?? '');
    $country = trim($_POST['country'] ?? 'Kenya');
    $payment = 'paystack';
    $notes   = trim($_POST['notes']   ?? '');

    if (empty($address)) $errors[] = 'Shipping address is required.';
    if (empty($city))    $errors[] = 'City is required.';

    if (empty($errors)) {
        ensurePaystackColumns($pdo);
        do {
            $orderNum = generateOrderNumber();
            $exists   = $pdo->prepare("SELECT id FROM orders WHERE order_number=?");
            $exists->execute([$orderNum]);
        } while ($exists->fetch());

        $ins = $pdo->prepare("
            INSERT INTO orders
            (order_number,user_id,subtotal,shipping_fee,total,
             payment_method,payment_status,status,
             shipping_address,shipping_city,shipping_country,notes)
            VALUES (?,?,?,?,?,?,'unpaid','pending',?,?,?,?)
        ");
        $ins->execute([
            $orderNum,$uid,$subtotal,$shipping,$total,
            $payment,$address,$city,$country,$notes
        ]);
        $orderId = $pdo->lastInsertId();

        $insItem = $pdo->prepare("
            INSERT INTO order_items
            (order_id,product_id,product_name,quantity,unit_price,total_price)
            VALUES (?,?,?,?,?,?)
        ");
        foreach ($items as $item) {
            $unitPrice = (!empty($item['sale_price']) && $item['sale_price'] < $item['price'])
                ? $item['sale_price'] : $item['price'];
            $insItem->execute([
                $orderId,$item['product_id'],$item['name'],
                $item['quantity'],$unitPrice,$unitPrice*$item['quantity']
            ]);
            $pdo->prepare("UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?")
                ->execute([$item['quantity'],$item['product_id']]);
        }

        $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$uid]);
        redirect(SITE_URL . '/paystack_pay.php?order=' . $orderId);
    }
}

require_once 'includes/header.php';
?>

<style>
.checkout-wrap {
    background: linear-gradient(135deg,#fff 0%,#fff5fb 100%);
    min-height: 80vh; padding: 40px 0 60px;
}
.checkout-card {
    background: #fff;
    border: 1px solid rgba(233,30,140,0.10);
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(233,30,140,0.07);
    overflow: hidden; margin-bottom: 20px;
}
.checkout-card-header {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    padding: 16px 22px;
    display: flex; align-items: center; gap: 10px;
}
.checkout-card-header h5 {
    color:#fff; margin:0;
    font-family:'Cormorant Garamond',serif;
    font-size:1.15rem; font-weight:600;
}
.checkout-card-header i { color:rgba(255,255,255,0.85); font-size:17px; }
.checkout-card-body { padding: 22px; }
.form-label { font-size:13px; font-weight:600; color:#333; margin-bottom:5px; }
.form-control, .form-select {
    border: 1.5px solid rgba(233,30,140,0.15);
    border-radius: 10px; padding: 10px 14px;
    font-size: 14px; transition: all 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: #E91E8C;
    box-shadow: 0 0 0 3px rgba(233,30,140,0.10);
}

/* Payment Method Cards */
.payment-option {
    border: 1.5px solid rgba(233,30,140,0.15);
    border-radius: 12px; padding: 14px 16px;
    cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 10px;
}
.payment-option:hover { border-color: #E91E8C; background: #fff5fb; }
.payment-option.selected { border-color: #E91E8C; background: #fff5fb; }
.payment-option input[type=radio] { accent-color: #E91E8C; width:16px; height:16px; }
.payment-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.payment-icon.paystack { background:#e6f7f4; color:#0ba4db; }
.payment-label { font-size:13.5px; font-weight:600; color:#1A1A1A; }
.payment-sub   { font-size:11.5px; color:#999; }

/* Pay instructions box */
.pay-instructions {
    background: #fff5fb;
    border: 1px solid rgba(233,30,140,0.15);
    border-radius: 12px; padding: 16px;
    margin-top: 16px; display: none;
}
.pay-instructions.show { display: block; }
.pay-instructions h6 {
    font-size: 13px; font-weight: 700;
    color: #1A1A1A; margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.pay-instructions p { font-size: 13px; color: #555; margin: 0; line-height: 1.7; }
.pay-detail {
    background: white; border-radius: 8px;
    padding: 8px 12px; margin: 8px 0;
    font-size: 13px; font-weight: 600;
    color: #1A1A1A; border: 1px solid rgba(233,30,140,0.10);
    display: flex; align-items: center; gap: 8px;
}
.pay-detail i { color: #E91E8C; }

/* Summary */
.summary-card {
    background: #fff;
    border: 1px solid rgba(233,30,140,0.10);
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(233,30,140,0.07);
    overflow: hidden; position: sticky; top: 90px;
}
.summary-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(233,30,140,0.07);
}
.summary-item:last-child { border-bottom: none; }
.summary-item-img {
    width: 48px; height: 48px; border-radius: 10px;
    object-fit: cover; flex-shrink: 0; background: #fff5fb;
}
.summary-item-name { font-size:13px; font-weight:600; color:#1A1A1A; }
.summary-item-qty  { font-size:11px; color:#999; }
.summary-item-price { margin-left:auto; font-size:13px; font-weight:700; color:#E91E8C; white-space:nowrap; }
.summary-row { display:flex; justify-content:space-between; font-size:13.5px; padding:5px 0; color:#555; }
.summary-total {
    font-size:1.1rem; font-weight:700; color:#E91E8C;
    padding-top:10px; border-top:2px solid rgba(233,30,140,0.12); margin-top:6px;
}

/* Place Order Button */
.btn-place-order {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; border: none; border-radius: 50px;
    padding: 15px 30px; font-size: 15px; font-weight: 700;
    width: 100%; cursor: pointer; transition: all 0.28s ease;
    box-shadow: 0 6px 24px rgba(233,30,140,0.30);
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-family: 'DM Sans', sans-serif; letter-spacing: 0.3px;
}
.btn-place-order:hover {
    background: linear-gradient(135deg,#C2176F,#A0115C);
    transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(233,30,140,0.40);
}
.secure-badge {
    display: flex; align-items: center; justify-content: center;
    gap: 6px; font-size: 11.5px; color: #bbb; margin-top: 12px;
}
.secure-badge i { color: #22C55E; }

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

@media (max-width: 767px) {
    .checkout-wrap { padding: 20px 0 40px; }
    .checkout-card-body { padding: 16px; }
    .summary-card { position: static; }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/cart.php">Cart</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </nav>
        <h1>Checkout</h1>
    </div>
</div>

<div class="checkout-wrap">
<div class="container">

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius:12px;">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?>
        <li><?= sanitize($e) ?></li>
    <?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
<div class="row g-4">

    <!-- LEFT -->
    <div class="col-lg-7">

        <!-- Shipping -->
        <div class="checkout-card">
            <div class="checkout-card-header">
                <i class="bi bi-geo-alt-fill"></i>
                <h5>Shipping Information</h5>
            </div>
            <div class="checkout-card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['first_name']) ?>" readonly style="background:#fafafa;">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['last_name']) ?>" readonly style="background:#fafafa;">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly style="background:#fafafa;">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Shipping Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control"
                               placeholder="e.g. 123 Moi Avenue, Westlands"
                               value="<?= sanitize($user['address'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control"
                               placeholder="Nairobi"
                               value="<?= sanitize($user['city'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Country</label>
                        <select name="country" class="form-select">
                            <option value="Kenya" selected>Kenya</option>
                            <option value="Uganda">Uganda</option>
                            <option value="Tanzania">Tanzania</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Order Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Special delivery instructions..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="checkout-card">
            <div class="checkout-card-header">
                <i class="bi bi-credit-card-fill"></i>
                <h5>Payment Method</h5>
            </div>
            <div class="checkout-card-body">

                <div class="payment-option selected">
                    <div class="payment-icon paystack"><i class="bi bi-shield-lock-fill"></i></div>
                    <div>
                        <div class="payment-label">Paystack</div>
                        <div class="payment-sub">Pay with M-Pesa or card on Paystack's secure page</div>
                    </div>
                </div>

                <div class="pay-instructions show">
                    <p>When you place your order you will be taken to Paystack to pay <strong><?= formatPrice($total) ?></strong>.
                    Your order is confirmed automatically as soon as the payment goes through.</p>
                </div>

            </div>
        </div>

    </div>

    <!-- RIGHT: Summary -->
    <div class="col-lg-5">
        <div class="summary-card">
            <div class="checkout-card-header">
                <i class="bi bi-bag-check-fill"></i>
                <h5>Order Summary</h5>
            </div>
            <div class="checkout-card-body">

                <?php foreach ($items as $item):
                    $unitPrice = (!empty($item['sale_price']) && $item['sale_price'] < $item['price'])
                        ? $item['sale_price'] : $item['price'];
                    $img = SITE_URL . '/assets/images/products/' . ($item['image'] ?? '');
                ?>
                <div class="summary-item">
                    <img src="<?= $img ?>" class="summary-item-img"
                         alt="<?= sanitize($item['name']) ?>"
                         onerror="this.src='https://placehold.co/48x48/FFF0F7/E91E8C?text=P'">
                    <div style="flex:1;min-width:0;">
                        <div class="summary-item-name"><?= sanitize($item['name']) ?></div>
                        <div class="summary-item-qty">Qty: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="summary-item-price"><?= formatPrice((float)($unitPrice * $item['quantity'])) ?></div>
                </div>
                <?php endforeach; ?>

                <div class="mt-3">
                    <div class="summary-row">
                        <span>Subtotal</span><span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?= $shipping === 0.0
                            ? '<span style="color:#22C55E;font-weight:600;">Free</span>'
                            : formatPrice($shipping) ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total</span><span><?= formatPrice($total) ?></span>
                    </div>
                </div>

                <button type="submit" class="btn-place-order mt-4">
                    <i class="bi bi-lock-fill"></i>
                    Pay <?= formatPrice($total) ?> with Paystack
                </button>

                <div class="secure-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                    Safe & Secure Checkout
                </div>

                <?php if ($subtotal < FREE_SHIPPING_THRESHOLD): ?>
                <div style="margin-top:12px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:10px 14px;font-size:12px;color:#92400e;">
                    <i class="bi bi-truck me-1"></i>
                    Add <?= formatPrice(FREE_SHIPPING_THRESHOLD - $subtotal) ?> more for <strong>free shipping!</strong>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>
</form>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
