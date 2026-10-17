<?php
$pageTitle = 'Checkout';
require_once 'includes/shop.php';
$pdo = getDB();
ensureCustomerSchema($pdo);

$items = cartItems($pdo);
if (empty($items)) { redirect(SITE_URL . '/cart.php'); }

$subtotal = 0;
foreach ($items as $item) {
    $price = (!empty($item['sale_price']) && $item['sale_price'] < $item['price'])
        ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
$total    = $subtotal + $shipping;

$countries = ['Kenya', 'Uganda', 'Tanzania'];

// Customers who saved their details (remembered device) get them filled in.
$user = null;
if (isLoggedIn()) {
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch() ?: null;
}

$form = [
    'first_name' => $user['first_name'] ?? '',
    'last_name'  => $user['last_name']  ?? '',
    'phone'      => $user['phone']      ?? '',
    'email'      => $user['email']      ?? '',
    'address'    => $user['address']    ?? '',
    'city'       => $user['city']       ?? '',
    'country'    => $user['country']    ?? 'Kenya',
    'notes'      => '',
];

$errors      = [];
$lookupError = '';
$saveDetails = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'lookup') {
    // "Ordered before?": phone number (or email) and password fill in saved details.
    $fails = $_SESSION['lookup_fails'] ?? ['count' => 0, 'until' => 0];
    if ($fails['until'] > time()) {
        $lookupError = 'Too many attempts. Please wait 15 minutes, or continue as a guest.';
    } elseif ($found = findCustomer($pdo, trim($_POST['login'] ?? ''), $_POST['password'] ?? '')) {
        unset($_SESSION['lookup_fails']);
        signInCustomer($found);
        rememberDevice($pdo, (int)$found['id']);
        flashMessage('success', 'Welcome back, ' . $found['first_name'] . '. Your details are filled in.');
        redirect(SITE_URL . '/checkout.php');
    } else {
        $fails['count']++;
        if ($fails['count'] >= 5) $fails = ['count' => 0, 'until' => time() + 900];
        $_SESSION['lookup_fails'] = $fails;
        $lookupError = 'We could not find saved details with that phone number and password.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $_) {
        $form[$field] = trim($_POST[$field] ?? '');
    }
    $phone       = normalizePhone($form['phone']);
    $saveDetails = !isLoggedIn() && !empty($_POST['save_details']);
    $password    = $_POST['password'] ?? '';

    if ($form['first_name'] === '') $errors[] = 'First name is required.';
    if ($form['last_name']  === '') $errors[] = 'Last name is required.';
    if ($phone === '')              $errors[] = 'Enter a valid Kenyan phone number, e.g. 0712 345 678.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address for your payment receipt.';
    if ($form['address'] === '')    $errors[] = 'Delivery address is required.';
    if ($form['city']    === '')    $errors[] = 'City is required.';
    if (!in_array($form['country'], $countries, true)) $form['country'] = 'Kenya';

    if ($saveDetails) {
        if (strlen($password) < 8)                       $errors[] = 'Choose a password of at least 8 characters to save your details.';
        if ($password !== ($_POST['confirm'] ?? ''))      $errors[] = 'The two passwords do not match.';
        if (!$errors) {
            $taken = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $taken->execute([$form['email']]);
            if ($taken->fetch() || findCustomerByPhone($pdo, $phone)) {
                $errors[] = 'Your details are already saved with us. Use "Ordered before?" above with your password, or untick "Save my details".';
            }
        }
    }

    if (empty($errors)) {
        if ($saveDetails) {
            $pdo->prepare("
                INSERT INTO users (first_name,last_name,email,phone,password,address,city,country)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $form['first_name'], $form['last_name'], $form['email'], $phone,
                password_hash($password, PASSWORD_BCRYPT), $form['address'], $form['city'], $form['country'],
            ]);
            $userId = (int)$pdo->lastInsertId();
            signInCustomer(['id' => $userId, 'first_name' => $form['first_name'], 'email' => $form['email']]);
            rememberDevice($pdo, $userId);
        } elseif (isLoggedIn()) {
            // Keep saved details current for the next order.
            $userId = (int)$_SESSION['user_id'];
            $pdo->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,address=?,city=?,country=? WHERE id=?")
                ->execute([$form['first_name'], $form['last_name'], $phone, $form['address'], $form['city'], $form['country'], $userId]);
        } else {
            $userId = null;
        }

        do {
            $orderNum = generateOrderNumber();
            $exists   = $pdo->prepare("SELECT id FROM orders WHERE order_number=?");
            $exists->execute([$orderNum]);
        } while ($exists->fetch());

        $pdo->prepare("
            INSERT INTO orders
            (order_number,user_id,customer_name,customer_phone,customer_email,
             subtotal,shipping_fee,total,payment_method,payment_status,status,
             shipping_address,shipping_city,shipping_country,notes)
            VALUES (?,?,?,?,?,?,?,?,'paystack','unpaid','pending',?,?,?,?)
        ")->execute([
            $orderNum, $userId, $form['first_name'] . ' ' . $form['last_name'], $phone, $form['email'],
            $subtotal, $shipping, $total,
            $form['address'], $form['city'], $form['country'], $form['notes'],
        ]);
        $orderId = (int)$pdo->lastInsertId();

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

        cartClear();
        rememberOrderInSession($orderId);
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

<?php if (!isLoggedIn()): ?>
<!-- Ordered before? Fill in saved details with phone + password -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="checkout-card mb-0">
            <div class="checkout-card-body">
                <a href="#lookupForm" data-bs-toggle="collapse" class="d-flex align-items-center gap-2 text-decoration-none"
                   style="color:#1A1A1A;font-weight:600;font-size:14px;" aria-expanded="<?= $lookupError ? 'true' : 'false' ?>">
                    <i class="bi bi-person-check-fill" style="color:#E91E8C;font-size:18px;"></i>
                    Ordered before? Fill in your saved details
                    <i class="bi bi-chevron-down ms-auto" style="font-size:12px;color:#999;"></i>
                </a>
                <div class="collapse <?= $lookupError ? 'show' : '' ?>" id="lookupForm">
                    <form method="POST" class="row g-2 mt-2">
                        <input type="hidden" name="action" value="lookup">
                        <?php if ($lookupError): ?>
                        <div class="col-12"><div class="alert alert-danger py-2 mb-1" style="font-size:13px;"><?= sanitize($lookupError) ?></div></div>
                        <?php endif; ?>
                        <div class="col-sm-5">
                            <input type="text" name="login" class="form-control" placeholder="Phone number" autocomplete="tel"
                                   value="<?= sanitize($_POST['login'] ?? '') ?>" required>
                        </div>
                        <div class="col-sm-4">
                            <input type="password" name="password" class="form-control" placeholder="Password" autocomplete="current-password" required>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-pink w-100" style="border-radius:10px;padding:10px;">Fill in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
                <?php if ($user): ?>
                <div style="background:#fff5fb;border-radius:10px;padding:10px 14px;font-size:13px;color:#555;margin-bottom:16px;">
                    <i class="bi bi-check-circle-fill me-1" style="color:#22C55E;"></i>
                    Welcome back, <strong><?= sanitize($user['first_name']) ?></strong>. Your saved details are filled in; check them before you pay.
                    <a href="<?= SITE_URL ?>/logout.php" style="color:#E91E8C;white-space:nowrap;">Not you?</a>
                </div>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="form-control" autocomplete="given-name"
                               value="<?= sanitize($form['first_name']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="last_name" class="form-control" autocomplete="family-name"
                               value="<?= sanitize($form['last_name']) ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="phone">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" id="phone" class="form-control" autocomplete="tel"
                               placeholder="0712 345 678" value="<?= sanitize($form['phone']) ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" autocomplete="email"
                               placeholder="For your payment receipt" value="<?= sanitize($form['email']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Delivery Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" id="address" class="form-control" autocomplete="street-address"
                               placeholder="e.g. 123 Moi Avenue, Westlands"
                               value="<?= sanitize($form['address']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="city">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" id="city" class="form-control" autocomplete="address-level2"
                               placeholder="Nairobi" value="<?= sanitize($form['city']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="country">Country</label>
                        <select name="country" id="country" class="form-select">
                            <?php foreach ($countries as $c): ?>
                            <option value="<?= $c ?>" <?= $form['country'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Order Notes (optional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"
                                  placeholder="Special delivery instructions..."><?= sanitize($form['notes']) ?></textarea>
                    </div>

                    <?php if (!$user): ?>
                    <!-- Save details: this is how customers register -->
                    <div class="col-12">
                        <div style="background:#fff5fb;border:1px solid rgba(233,30,140,0.15);border-radius:12px;padding:14px 16px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="save_details" value="1" id="saveDetails"
                                       <?= $saveDetails ? 'checked' : '' ?>>
                                <label class="form-check-label" for="saveDetails" style="font-size:13.5px;font-weight:600;">
                                    Save my details for faster checkout next time
                                </label>
                            </div>
                            <div style="font-size:12px;color:#888;margin:4px 0 0 24px;">
                                We will remember this device for a year, so you won't need to type your details again.
                            </div>
                            <div class="row g-2 mt-1" id="savePasswords" style="<?= $saveDetails ? '' : 'display:none;' ?>">
                                <div class="col-sm-6">
                                    <input type="password" name="password" class="form-control" placeholder="Choose a password (8+ characters)" autocomplete="new-password">
                                </div>
                                <div class="col-sm-6">
                                    <input type="password" name="confirm" class="form-control" placeholder="Repeat password" autocomplete="new-password">
                                </div>
                                <div class="col-12" style="font-size:12px;color:#888;">
                                    You only need this password if you order from a new phone or computer.
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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

                <p style="font-size:11.5px;color:#999;text-align:center;margin:10px 0 0;">
                    By placing your order you agree to our <a href="terms.php" style="color:#E91E8C;">Terms</a>,
                    <a href="privacy.php" style="color:#E91E8C;">Privacy Policy</a> and
                    <a href="returns.php" style="color:#E91E8C;">Returns Policy</a>.
                </p>

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

<script>
(function () {
    var box = document.getElementById('saveDetails');
    var pw  = document.getElementById('savePasswords');
    if (!box || !pw) return;
    box.addEventListener('change', function () { pw.style.display = box.checked ? '' : 'none'; });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
