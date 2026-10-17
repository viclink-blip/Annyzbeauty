<?php
// Shared opening for the policy pages. Set $pageTitle and $policyUpdated first.
require_once __DIR__ . '/header.php';
?>
<style>
.policy-wrap { background: linear-gradient(135deg,#fff 0%,#fff5fb 100%); padding: 40px 0 60px; }
.policy-card {
    background: #fff; border: 1px solid rgba(233,30,140,0.10); border-radius: 20px;
    box-shadow: 0 4px 24px rgba(233,30,140,0.07); padding: 36px; max-width: 860px; margin: 0 auto;
}
.policy-card h1 { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; margin-bottom: 4px; }
.policy-card h2 { font-size: 1.1rem; font-weight: 700; margin: 28px 0 10px; color: #1A1A1A; }
.policy-card p, .policy-card li { font-size: 14.5px; line-height: 1.8; color: #444; }
.policy-card a { color: #E91E8C; }
.policy-updated { font-size: 13px; color: #999; margin-bottom: 24px; }
.policy-nav { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
.policy-nav a {
    font-size: 12.5px; padding: 6px 14px; border-radius: 50px; text-decoration: none;
    border: 1px solid rgba(233,30,140,0.25); color: #E91E8C; background: #fff5fb;
}
.policy-nav a.active { background: #E91E8C; color: #fff; }
@media (max-width: 767px) { .policy-card { padding: 22px; } }
</style>

<div class="policy-wrap">
<div class="container">
<div class="policy-card">
    <nav class="policy-nav">
        <?php foreach ([
            'terms.php'    => 'Terms & Conditions',
            'privacy.php'  => 'Privacy Policy',
            'returns.php'  => 'Returns & Refunds',
            'shipping.php' => 'Shipping & Delivery',
        ] as $file => $label): ?>
        <a href="<?= SITE_URL ?>/<?= $file ?>" class="<?= $currentPage === $file ? 'active' : '' ?>"><?= sanitize($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <h1><?= sanitize($pageTitle) ?></h1>
    <div class="policy-updated">Last updated: <?= sanitize($policyUpdated) ?></div>
