<?php
$pageTitle = 'Create Account';
require_once 'includes/shop.php';

if (isLoggedIn()) redirect(SITE_URL . '/profile.php');

$errors = [];
$data   = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $field => $_) {
        $data[$field] = trim($_POST[$field] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    $phone    = preg_replace('/[\s\-()]/', '', $data['phone']);

    if ($data['first_name'] === '') $errors[] = 'First name is required.';
    if ($data['last_name']  === '') $errors[] = 'Last name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^(\+?254|0)[17]\d{8}$/', $phone)) $errors[] = 'Enter a valid Kenyan phone number, e.g. 0712 345 678.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($_POST['agree']))  $errors[] = 'Please agree to the Terms & Conditions and Privacy Policy.';

    if (!$errors) {
        $pdo   = getDB();
        $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$data['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists. Please sign in instead.';
        } else {
            $pdo->prepare("INSERT INTO users (first_name,last_name,email,phone,password) VALUES (?,?,?,?,?)")
                ->execute([$data['first_name'], $data['last_name'], $data['email'], $phone,
                           password_hash($password, PASSWORD_BCRYPT)]);

            session_regenerate_id(true);
            $_SESSION['user_id']    = $pdo->lastInsertId();
            $_SESSION['user_name']  = $data['first_name'];
            $_SESSION['user_email'] = $data['email'];

            $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/index.php';
            unset($_SESSION['redirect_after_login']);
            flashMessage('success', 'Welcome to Annyzbeauty, ' . $data['first_name'] . '. Your account is ready.');
            redirect($redirect);
        }
    }
}
require_once 'includes/header.php';
?>

<div style="background:var(--pink-soft);min-height:calc(100vh - 200px);padding:50px 0;">
    <div class="container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto mb-3"><i class="bi bi-flower1"></i></div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-sub">Register to check out, pay with Paystack and track your orders.</p>
            </div>

            <?php if ($errors): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" autocomplete="given-name"
                               value="<?= sanitize($data['first_name']) ?>" placeholder="Jane" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" autocomplete="family-name"
                               value="<?= sanitize($data['last_name']) ?>" placeholder="Wanjiku" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" autocomplete="email"
                               value="<?= sanitize($data['email']) ?>" placeholder="jane@example.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" autocomplete="tel"
                               value="<?= sanitize($data['phone']) ?>" placeholder="0712 345 678" required>
                        <div class="form-text">Used for delivery updates.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="pwd">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="pwd" class="form-control" autocomplete="new-password"
                                   placeholder="At least 8 characters" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pwd','eyePwd')" aria-label="Show password">
                                <i id="eyePwd" class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="pwd2">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm" id="pwd2" class="form-control" autocomplete="new-password"
                                   placeholder="Re-enter password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('pwd2','eyePwd2')" aria-label="Show password">
                                <i id="eyePwd2" class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="agree" value="1" id="agree"
                                   <?= !empty($_POST['agree']) ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="agree" style="font-size:13px;">
                                I have read and agree to the
                                <a href="terms.php" target="_blank" style="color:var(--pink);">Terms &amp; Conditions</a>,
                                <a href="privacy.php" target="_blank" style="color:var(--pink);">Privacy Policy</a> and
                                <a href="returns.php" target="_blank" style="color:var(--pink);">Returns &amp; Refunds Policy</a>.
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-pink-lg w-100">Create My Account</button>
                    </div>
                </div>
            </form>

            <p class="text-center mt-4 mb-2" style="font-size:14px;">
                Already have an account? <a href="login.php" class="fw-600" style="color:var(--pink);">Sign In</a>
            </p>
            <p class="text-center mb-0" style="font-size:13px;color:var(--mid-grey);">
                Prefer not to register?
                <a href="<?= sanitize(whatsappLink('Hi Annyzbeauty, I would like to place an order.')) ?>"
                   target="_blank" rel="noopener" style="color:#25D366;font-weight:600;">
                    <i class="bi bi-whatsapp"></i> Order on WhatsApp
                </a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const show  = input.type === 'password';
    input.type     = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>

<?php require_once 'includes/footer.php'; ?>
