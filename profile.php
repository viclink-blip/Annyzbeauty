<?php
$pageTitle = 'My Profile';
require_once 'includes/config.php';
requireLogin();
$pdo = getDB();
$uid = $_SESSION['user_id'];

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$uid]);
$user = $user->fetch();

$errors = []; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first   = trim($_POST['first_name'] ?? '');
    $last    = trim($_POST['last_name']  ?? '');
    $phone   = trim($_POST['phone']      ?? '');
    $address = trim($_POST['address']    ?? '');
    $city    = trim($_POST['city']       ?? '');
    $country = trim($_POST['country']    ?? 'Kenya');
    $newPwd  = $_POST['new_password']    ?? '';
    $confirm = $_POST['confirm_password']?? '';

    if (empty($first)) $errors[] = 'First name is required.';
    if (!empty($newPwd)) {
        if (strlen($newPwd) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($newPwd !== $confirm)  $errors[] = 'Passwords do not match.';
        if (!password_verify($_POST['current_password'] ?? '', $user['password']))
            $errors[] = 'Current password is incorrect.';
    }

    if (empty($errors)) {
        if (!empty($newPwd)) {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,address=?,city=?,country=?,password=? WHERE id=?")
                ->execute([$first,$last,$phone,$address,$city,$country,$hash,$uid]);
        } else {
            $pdo->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,address=?,city=?,country=? WHERE id=?")
                ->execute([$first,$last,$phone,$address,$city,$country,$uid]);
        }
        $_SESSION['user_name'] = $first;
        $success = true;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
    }
}

// Stats
$orderCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$orderCount->execute([$uid]);
$orderCount = $orderCount->fetchColumn();

$totalSpent = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND payment_status='paid'");
$totalSpent->execute([$uid]);
$totalSpent = $totalSpent->fetchColumn();

// Unpaid orders
$unpaidStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? AND payment_status='unpaid' ORDER BY created_at DESC");
$unpaidStmt->execute([$uid]);
$unpaidOrders = $unpaidStmt->fetchAll();
$totalUnpaid  = array_sum(array_column($unpaidOrders, 'total'));

require_once 'includes/header.php';
?>

<style>
.profile-wrap {
    background: linear-gradient(135deg,#fff 0%,#fff5fb 100%);
    min-height: 80vh; padding: 40px 0 60px;
}

/* ── Payment Banner ── */
.payment-banner-trigger {
    position: relative;
    margin-bottom: 28px;
    cursor: pointer;
    user-select: none;
}

/* Arrow + Label animation */
.click-hint {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    animation: hintBounce 1.5s ease-in-out infinite;
}
@keyframes hintBounce {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-6px); }
}
.click-hint-text {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff;
    font-size: 13px; font-weight: 700;
    padding: 7px 18px;
    border-radius: 50px;
    letter-spacing: 0.3px;
    box-shadow: 0 4px 16px rgba(233,30,140,0.30);
    display: flex; align-items: center; gap: 8px;
}
.click-hint-arrow {
    font-size: 28px;
    color: #E91E8C;
    animation: arrowBounce 0.8s ease-in-out infinite alternate;
    line-height: 1;
}
@keyframes arrowBounce {
    from { transform: translateY(0) rotate(180deg); }
    to   { transform: translateY(8px) rotate(180deg); }
}

/* Pulsing Banner */
.payment-banner {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    border-radius: 20px;
    padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 14px;
    box-shadow: 0 6px 28px rgba(233,30,140,0.30);
    position: relative; overflow: hidden;
    animation: bannerPulse 2s ease-in-out infinite;
    transition: all 0.3s ease;
}
.payment-banner:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 36px rgba(233,30,140,0.40);
}
@keyframes bannerPulse {
    0%,100% { box-shadow: 0 6px 28px rgba(233,30,140,0.30); }
    50%      { box-shadow: 0 6px 48px rgba(233,30,140,0.55), 0 0 0 6px rgba(233,30,140,0.10); }
}
.payment-banner::before {
    content: '';
    position: absolute; top:-40px; right:-40px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,0.08);
    border-radius: 50%;
}
.payment-banner::after {
    content: '';
    position: absolute; bottom:-60px; right:60px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.banner-left { position: relative; z-index: 1; }
.banner-title {
    color: rgba(255,255,255,0.85);
    font-size: 12px; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase;
    margin-bottom: 4px;
}
.banner-amount {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem; font-weight: 600; color: #fff;
    line-height: 1;
}
.banner-sub { color: rgba(255,255,255,0.70); font-size: 12px; margin-top: 4px; }
.banner-right {
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: 10px;
}
.banner-cta {
    background: #fff; color: #E91E8C;
    border-radius: 50px; padding: 10px 20px;
    font-size: 13px; font-weight: 700;
    display: flex; align-items: center; gap: 6px;
    white-space: nowrap;
    animation: ctaPulse 1.5s ease-in-out infinite;
}
@keyframes ctaPulse {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.04); }
}

/* ── Payment Details Card (hidden by default) ── */
.payment-details-card {
    background: #fff;
    border: 2px solid #E91E8C;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(233,30,140,0.15);
    margin-bottom: 24px;
    display: none;
    animation: slideDown 0.4s ease;
}
.payment-details-card.show { display: block; }
@keyframes slideDown {
    from { opacity:0; transform:translateY(-16px); }
    to   { opacity:1; transform:translateY(0); }
}
.pdc-header {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.pdc-header h5 {
    color: #fff; margin: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
}
.pdc-close {
    background: rgba(255,255,255,0.20); border: none;
    color: #fff; border-radius: 50%;
    width: 30px; height: 30px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 16px; transition: all 0.2s;
}
.pdc-close:hover { background: rgba(255,255,255,0.35); }
.pdc-body { padding: 22px; }

/* Copy Row */
.copy-row {
    background: #fff5fb;
    border: 1.5px solid rgba(233,30,140,0.15);
    border-radius: 14px; padding: 16px 18px;
    margin-bottom: 14px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.copy-row-left {}
.copy-label { font-size: 11px; font-weight: 700; color: #999; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 4px; }
.copy-value { font-size: 1.3rem; font-weight: 700; color: #1A1A1A; font-family: 'Cormorant Garamond', serif; }
.copy-value.pink { color: #E91E8C; }
.btn-copy {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; border: none;
    border-radius: 50px; padding: 9px 20px;
    font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
    display: flex; align-items: center; gap: 6px;
    white-space: nowrap;
}
.btn-copy:hover { opacity: 0.88; transform: translateY(-1px); }
.btn-copy.copied {
    background: #22C55E !important;
}

/* Steps inside card */
.pay-steps-mini { margin-top: 16px; }
.pay-step-mini {
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 13px; color: #555; padding: 5px 0;
}
.step-dot {
    width: 20px; height: 20px; border-radius: 50%;
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 2px;
}

/* WhatsApp button */
.btn-whatsapp {
    background: #25D366; color: #fff;
    border: none; border-radius: 50px;
    padding: 11px 22px; font-size: 13.5px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 8px;
    text-decoration: none; transition: all 0.2s;
    box-shadow: 0 4px 16px rgba(37,211,102,0.30);
    width: 100%; justify-content: center; margin-top: 14px;
}
.btn-whatsapp:hover { background: #1ebe5b; color: #fff; transform: translateY(-1px); }

/* Unpaid order pills */
.unpaid-pill {
    background: #FEF3C7; color: #92400E;
    border-radius: 8px; padding: 6px 12px;
    font-size: 12px; font-weight: 600;
    margin-bottom: 6px; display: flex;
    align-items: center; justify-content: space-between; gap: 8px;
}

/* Profile Cards */
.profile-card {
    background: #fff;
    border: 1px solid rgba(233,30,140,0.10);
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(233,30,140,0.07);
    overflow: hidden; margin-bottom: 20px;
}
.profile-card-header {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    padding: 14px 20px;
    display: flex; align-items: center; gap: 8px;
}
.profile-card-header h6 {
    color: #fff; margin: 0;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem; font-weight: 600;
}
.profile-card-header i { color: rgba(255,255,255,0.85); }
.profile-card-body { padding: 20px; }

/* Avatar */
.profile-avatar {
    width: 72px; height: 72px;
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    border-radius: 50%; margin: 0 auto 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-family: 'Cormorant Garamond', serif;
    font-size: 26px; font-weight: 600;
    box-shadow: 0 4px 16px rgba(233,30,140,0.30);
}

/* Form */
.form-label { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 5px; }
.form-control, .form-select {
    border: 1.5px solid rgba(233,30,140,0.15);
    border-radius: 10px; padding: 10px 14px;
    font-size: 14px; transition: all 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: #E91E8C;
    box-shadow: 0 0 0 3px rgba(233,30,140,0.10);
}

.btn-save {
    background: linear-gradient(135deg,#E91E8C,#C2176F);
    color: #fff; border: none; border-radius: 50px;
    padding: 12px 32px; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: all 0.28s ease;
    box-shadow: 0 4px 16px rgba(233,30,140,0.25);
}
.btn-save:hover { opacity:0.88; transform:translateY(-1px); }

.stat-mini {
    text-align: center; padding: 12px 8px;
    background: #fff5fb; border-radius: 12px;
}
.stat-mini-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem; font-weight: 600; color: #E91E8C;
}
.stat-mini-label { font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px; }

/* Page Header */
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
    .profile-wrap { padding: 20px 0 40px; }
    .profile-card-body { padding: 16px; }
    .copy-value { font-size: 1.1rem; }
    .banner-amount { font-size: 1.4rem; }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
                <li class="breadcrumb-item active">My Profile</li>
            </ol>
        </nav>
        <h1>My Profile</h1>
    </div>
</div>

<div class="profile-wrap">
<div class="container">

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i> Profile updated successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius:12px;">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════
     PAYMENT BANNER (only if unpaid orders)
══════════════════════════════════════════ -->
<?php if (!empty($unpaidOrders)): ?>

<!-- Animated hint arrow -->
<div class="click-hint" id="clickHint">
    <div class="click-hint-text">
        <i class="bi bi-credit-card-fill"></i>
        Click here to pay for your orders
    </div>
    <div class="click-hint-arrow">&darr;</div>
</div>

<!-- Pulsing Banner -->
<div class="payment-banner-trigger" id="paymentBannerTrigger" onclick="togglePaymentDetails()">
    <div class="payment-banner">
        <div class="banner-left">
            <div class="banner-title">Payment Pending</div>
            <div class="banner-amount"><?= formatPrice((float)$totalUnpaid) ?></div>
            <div class="banner-sub">
                <?= count($unpaidOrders) ?> unpaid order<?= count($unpaidOrders)>1?'s':'' ?> &middot; Tap to pay
            </div>
        </div>
        <div class="banner-right">
            <div class="banner-cta">
                <i class="bi bi-lock-fill"></i>
                Pay with Paystack
            </div>
        </div>
    </div>
</div>

<!-- Payment Details (hidden until clicked) -->
<div class="payment-details-card" id="paymentDetailsCard">
    <div class="pdc-header">
        <h5><i class="bi bi-credit-card-fill"></i> Pay for Your Orders</h5>
        <button class="pdc-close" onclick="togglePaymentDetails()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="pdc-body">
        <p style="font-size:13px;color:#555;margin-bottom:14px;">
            Each order is paid separately on Paystack's secure page, using M-Pesa or your card.
            Your order is confirmed automatically once the payment goes through.
        </p>

        <?php foreach ($unpaidOrders as $uo): ?>
        <div class="unpaid-pill" style="flex-wrap:wrap;gap:10px;">
            <span><i class="bi bi-receipt me-1"></i><?= sanitize($uo['order_number']) ?></span>
            <span style="display:flex;align-items:center;gap:12px;">
                <span style="color:#E91E8C;font-weight:700;"><?= formatPrice((float)$uo['total']) ?></span>
                <a href="<?= SITE_URL ?>/paystack_pay.php?order=<?= (int)$uo['id'] ?>" class="btn-copy" style="text-decoration:none;">
                    <i class="bi bi-lock-fill"></i> Pay now
                </a>
            </span>
        </div>
        <?php endforeach; ?>

        <!-- WhatsApp help -->
        <a href="<?= sanitize(whatsappLink('Hi Annyzbeauty, I need help paying for my order.')) ?>"
           target="_blank" rel="noopener" class="btn-whatsapp">
            <i class="bi bi-whatsapp"></i>
            Need help? Chat on WhatsApp
        </a>

    </div>
</div>

<?php endif; ?>

<!-- ══════════════════════════════════════════
     MAIN PROFILE LAYOUT
══════════════════════════════════════════ -->
<div class="row g-4">

    <!-- Left: Info Card -->
    <div class="col-lg-3">
        <div class="profile-card text-center">
            <div class="profile-card-body">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
                </div>
                <h5 class="fw-bold mb-1" style="font-family:'Cormorant Garamond',serif;font-size:1.3rem;">
                    <?= sanitize($user['first_name'].' '.$user['last_name']) ?>
                </h5>
                <p style="font-size:12px;color:#999;margin-bottom:12px;"><?= sanitize($user['email']) ?></p>
                <p style="font-size:11px;color:#bbb;margin-bottom:16px;">
                    Member since <?= date('M Y', strtotime($user['created_at'])) ?>
                </p>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="stat-mini">
                            <div class="stat-mini-num"><?= $orderCount ?></div>
                            <div class="stat-mini-label">Orders</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-mini">
                            <div class="stat-mini-num" style="font-size:1.1rem;"><?= formatPrice((float)$totalSpent) ?></div>
                            <div class="stat-mini-label">Spent</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <a href="orders.php" style="background:#fff5fb;color:#E91E8C;border:1.5px solid rgba(233,30,140,0.20);border-radius:50px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;text-align:center;">
                        <i class="bi bi-bag-check me-1"></i>My Orders
                    </a>
                    <a href="logout.php" style="background:#fff5f5;color:#dc3545;border:1.5px solid rgba(220,53,69,0.20);border-radius:50px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;text-align:center;">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Edit Form -->
    <div class="col-lg-9">

        <form method="POST">

            <!-- Personal Info -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-person-fill"></i>
                    <h6>Personal Information</h6>
                </div>
                <div class="profile-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= sanitize($user['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= sanitize($user['last_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly style="background:#fafafa;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="+254 7XX XXX XXX">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-geo-alt-fill"></i>
                    <h6>Delivery Address</h6>
                </div>
                <div class="profile-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="address" class="form-control" value="<?= sanitize($user['address'] ?? '') ?>" placeholder="123 Moi Avenue">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?= sanitize($user['city'] ?? '') ?>" placeholder="Nairobi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <select name="country" class="form-select">
                                <option <?= ($user['country']??'Kenya')==='Kenya'?'selected':'' ?>>Kenya</option>
                                <option <?= ($user['country']??'')==='Uganda'?'selected':'' ?>>Uganda</option>
                                <option <?= ($user['country']??'')==='Tanzania'?'selected':'' ?>>Tanzania</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-lock-fill"></i>
                    <h6>Change Password</h6>
                </div>
                <div class="profile-card-body">
                    <p style="font-size:13px;color:#999;margin-bottom:16px;">Leave blank to keep your current password.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min. 8 chars">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">
                <i class="bi bi-check-circle-fill me-2"></i>Save Changes
            </button>

        </form>
    </div>
</div>

</div>
</div>

<script>
// Toggle payment details card
function togglePaymentDetails() {
    var card  = document.getElementById('paymentDetailsCard');
    var hint  = document.getElementById('clickHint');
    var isOpen = card.classList.contains('show');

    if (isOpen) {
        card.classList.remove('show');
        if (hint) hint.style.display = 'flex';
    } else {
        card.classList.add('show');
        if (hint) hint.style.display = 'none';
        // Smooth scroll to it
        setTimeout(function() {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
}

// Copy text to clipboard
function copyText(elementId, btn) {
    var text = document.getElementById(elementId).innerText.trim();
    navigator.clipboard.writeText(text).then(function() {
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
        btn.classList.add('copied');
        setTimeout(function() {
            btn.innerHTML = original;
            btn.classList.remove('copied');
        }, 2500);
    }).catch(function() {
        // Fallback for older browsers
        var el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
        btn.classList.add('copied');
        setTimeout(function() {
            btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
            btn.classList.remove('copied');
        }, 2500);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
