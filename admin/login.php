<?php
require_once '../includes/config.php';

if (isAdminLoggedIn()) redirect(SITE_URL . '/admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $pdo      = getDB();
    $stmt     = $pdo->prepare("SELECT * FROM admins WHERE email=? AND is_active=1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        $pdo->prepare("UPDATE admins SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        $error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Annyzbeauty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=DM+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="shortcut icon" type="image/png" href="../assets/images/logo.png">
</head>
<body style="background:linear-gradient(135deg,#1A1A1A,#2D1520);min-height:100vh;display:flex;align-items:center;">
<div class="container">
    <div style="max-width:420px;margin:0 auto;">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="Annyzbeauty"
                 style="height:80px;width:auto;object-fit:contain;margin-bottom:14px;filter:drop-shadow(0 4px 12px rgba(233,30,140,0.3));">
            <h2 style="font-family:var(--font-display);color:white;font-size:1.8rem;">Admin Panel</h2>
            <p style="color:rgba(255,255,255,0.5);font-size:13px;">Sign in to manage Annyzbeauty</p>
        </div>

        <div class="auth-card">
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@annyzbeauty.com"
                           value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Your password" required>
                </div>
                <button type="submit" class="btn btn-pink-lg w-100">
                    <i class="bi bi-shield-lock me-2"></i>Sign In to Admin
                </button>
            </form>
            <div class="text-center mt-3" style="font-size:12px;color:var(--mid-grey);">
                Demo: admin@annyzbeauty.com / Admin@1234
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="<?= SITE_URL ?>" style="color:rgba(255,255,255,0.5);font-size:13px;">
                <i class="bi bi-arrow-left me-1"></i>Back to Store
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
