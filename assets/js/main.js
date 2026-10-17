/* ============================================================
   Annyzbeauty - Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Sticky Navbar Shadow ----
    window.addEventListener('scroll', function () {
        const nav = document.getElementById('mainNav');
        if (nav) {
            nav.style.boxShadow = window.scrollY > 50
                ? '0 4px 20px rgba(233,30,140,0.12)'
                : '0 2px 12px rgba(233,30,140,0.06)';
        }
    });

    // ---- Quantity Controls ----
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.closest('.qty-control').querySelector('.qty-input');
            let val = parseInt(input.value) || 1;
            if (this.dataset.action === 'increase') val = Math.min(val + 1, 99);
            if (this.dataset.action === 'decrease') val = Math.max(val - 1, 1);
            input.value = val;
        });
    });

    // ---- Cart: Update Quantity via AJAX ----
    document.querySelectorAll('.cart-qty-update').forEach(input => {
        input.addEventListener('change', function () {
            const cartId = this.dataset.cartId;
            const qty    = parseInt(this.value);
            if (qty < 1 || !cartId) return;

            fetch('ajax/cart_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `cart_id=${cartId}&quantity=${qty}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Reload to recalculate totals
                    window.location.reload();
                } else {
                    showToast(data.message || 'Update failed', 'error');
                }
            });
        });
    });

    // ---- Add to Cart (product cards) ----
    document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const qty = document.querySelector(`#qty-${productId}`)?.value || 1;

            fetch('ajax/cart_add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=${qty}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Added to cart!', 'success');
                    updateCartBadge(data.cart_count);
                } else {
                    if (data.redirect) window.location.href = data.redirect;
                    else showToast(data.message || 'Could not add to cart', 'error');
                }
            })
            .catch(() => showToast('Something went wrong', 'error'));
        });
    });

    // ---- Toast Notification ----
    window.showToast = function (message, type = 'success') {
        const existing = document.getElementById('annyz-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'annyz-toast';
        toast.style.cssText = `
            position:fixed; bottom:24px; right:24px; z-index:9999;
            background:${type === 'success' ? '#E91E8C' : '#EF4444'};
            color:white; padding:14px 22px; border-radius:12px;
            font-size:14px; font-weight:500;
            box-shadow:0 6px 24px rgba(0,0,0,0.15);
            animation:slideIn .3s ease; max-width:320px;
        `;
        toast.innerHTML = `<span>${message}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut .3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // Toast animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn { from { opacity:0; transform:translateX(100px); } to { opacity:1; transform:translateX(0); } }
        @keyframes slideOut { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(100px); } }
    `;
    document.head.appendChild(style);

    // ---- Update Cart Badge ----
    window.updateCartBadge = function (count) {
        let badge = document.querySelector('.cart-badge');
        const cartLink = document.querySelector('.cart-link');
        if (!cartLink) return;
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'cart-badge';
                cartLink.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    };

    // ---- Promo Countdown Timer ----
    const countdownEl = document.getElementById('promoCountdown');
    if (countdownEl) {
        const endTime = new Date();
        endTime.setHours(23, 59, 59, 0);

        function updateCountdown() {
            const now  = new Date();
            const diff = endTime - now;
            if (diff <= 0) {
                countdownEl.innerHTML = '<span>Offer expired</span>';
                return;
            }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            countdownEl.querySelectorAll('.countdown-num')[0].textContent = String(h).padStart(2,'0');
            countdownEl.querySelectorAll('.countdown-num')[1].textContent = String(m).padStart(2,'0');
            countdownEl.querySelectorAll('.countdown-num')[2].textContent = String(s).padStart(2,'0');
        }
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // ---- Product Image Thumbnail Switch ----
    document.querySelectorAll('.thumb-img').forEach(thumb => {
        thumb.addEventListener('click', function () {
            const main = document.getElementById('mainProductImg');
            if (main) main.src = this.src;
            document.querySelectorAll('.thumb-img').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ---- Price Range Filter ----
    const priceRange = document.getElementById('priceRange');
    const priceLabel = document.getElementById('priceLabel');
    if (priceRange && priceLabel) {
        priceRange.addEventListener('input', function () {
            priceLabel.textContent = 'KSh ' + Number(this.value).toLocaleString();
        });
    }

    // ---- Admin Sidebar Toggle ----
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar  = document.getElementById('adminSidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('open');
        });
    }

    // ---- Confirm Delete ----
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ---- Auto-dismiss alerts ----
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // ---- Scroll Reveal ----
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('fade-up');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

});
