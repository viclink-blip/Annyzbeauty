<!-- Footer -->
<footer class="site-footer">
    <div class="footer-top">
        <div class="container">
            <div class="row g-4">
                <!-- Brand -->
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="brand-icon brand-icon-sm"><i class="bi bi-flower1"></i></div>
                        <span class="fw-bold fs-5 text-white">Annyz<span class="brand-accent-light">beauty</span></span>
                    </div>
                    <p class="footer-text">Your premier beauty destination in Kenya. We bring you the finest skincare, makeup, haircare, and fragrances to help you glow every day.</p>
                    <div class="social-links">
                        <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-btn"><i class="bi bi-tiktok"></i></a>
                        <a href="<?= whatsappLink() ?>" class="social-btn" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/products.php">Shop All</a></li>
                        <li><a href="<?= SITE_URL ?>/products.php?featured=1">Featured</a></li>
                        <li><a href="<?= SITE_URL ?>/cart.php">My Cart</a></li>
                        <li><a href="<?= SITE_URL ?>/orders.php">My Orders</a></li>
                        <li><a href="<?= SITE_URL ?>/register.php">Create Account</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Categories</h6>
                    <ul class="footer-links">
                        <?php
                        $pdo = getDB();
                        $cats = $pdo->query("SELECT name, slug FROM categories WHERE is_active=1 LIMIT 6")->fetchAll();
                        foreach ($cats as $c):
                        ?>
                        <li><a href="<?= SITE_URL ?>/products.php?category=<?= urlencode($c['slug']) ?>"><?= sanitize($c['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Policies -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Policies</h6>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/terms.php">Terms &amp; Conditions</a></li>
                        <li><a href="<?= SITE_URL ?>/privacy.php">Privacy Policy</a></li>
                        <li><a href="<?= SITE_URL ?>/returns.php">Returns &amp; Refunds</a></li>
                        <li><a href="<?= SITE_URL ?>/shipping.php">Shipping &amp; Delivery</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="footer-heading">Contact Us</h6>
                    <ul class="footer-contact">
                        <li><i class="bi bi-geo-alt-fill"></i> Nairobi, Kenya</li>
                        <li><i class="bi bi-whatsapp"></i> <?= WHATSAPP_DISPLAY ?></li>
                        <li><i class="bi bi-envelope-fill"></i> masilavincent32@gmail.com</li>
                        <li><i class="bi bi-clock-fill"></i> Mon–Sat: 8am – 6pm</li>
                    </ul>
                    <!-- Payment Methods -->
                    <div class="payment-badges mt-3">
                        <span class="payment-badge"><i class="bi bi-shield-lock-fill"></i> Secure payments by Paystack</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                <p class="mb-0">Made with <i class="bi bi-heart-fill text-pink"></i> in Nairobi, Kenya</p>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp order button -->
<a href="<?= sanitize(whatsappLink('Hi Annyzbeauty, I would like to place an order.')) ?>"
   target="_blank" rel="noopener" aria-label="Order on WhatsApp"
   style="position:fixed;right:18px;bottom:18px;z-index:1050;width:56px;height:56px;border-radius:50%;
          background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;
          font-size:28px;box-shadow:0 6px 20px rgba(37,211,102,0.45);text-decoration:none;">
    <i class="bi bi-whatsapp"></i>
</a>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
