<?php
$pageTitle = 'Sign In';
require_once 'includes/config.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['first_name'];
            $_SESSION['user_email'] = $user['email'];
            $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/index.php';
            unset($_SESSION['redirect_after_login']);
            flashMessage('success', 'Welcome back, ' . $user['first_name'] . '!');
            redirect($redirect);
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
require_once 'includes/header.php';
?>

<div style="background:var(--pink-soft);min-height:calc(100vh - 200px);padding:60px 0;">
    <div class="container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto mb-3"><i class="bi bi-flower1"></i></div>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-sub">Sign in to your Annyzbeauty account</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= sanitize($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="jane@example.com"
                           value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label d-flex justify-content-between">
                        Password <a href="#" style="font-size:12px;color:var(--pink);">Forgot password?</a>
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="pwd" class="form-control" placeholder="Your password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()">
                            <i id="eyeIcon" class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember" style="font-size:13px;">Remember me</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-pink-lg w-100">Sign In</button>
            </form>

            <div class="text-center mt-4" style="font-size:13px;color:var(--mid-grey);">
                <p class="mb-1">Demo: <strong>jane@example.com</strong> / <strong>Admin@1234</strong></p>
            </div>

            <p class="text-center mt-3 mb-0" style="font-size:14px;">
                Don't have an account? <a href="register.php" class="fw-600" style="color:var(--pink);">Create one free</a>
            </p>
            <p class="text-center mt-2 mb-0" style="font-size:13px;color:var(--mid-grey);">
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
function togglePwd() {
    const i = document.getElementById('pwd');
    const e = document.getElementById('eyeIcon');
    i.type = i.type === 'password' ? 'text' : 'password';
    e.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php require_once 'includes/footer.php'; ?>
