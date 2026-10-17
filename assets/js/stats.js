/* ============================================================
   Annyzbeauty - Stats Section JavaScript
   File: assets/js/stats.js
   Counter animation + Intersection Observer for fade-in
   ============================================================ */

(function () {
    'use strict';

    /**
     * Animate a number from 0 to target over duration ms
     * Supports decimals (e.g. 4.9) and suffixes (e.g. "3000+")
     */
    function animateCounter(el, target, duration, isDecimal, suffix) {
        var start     = 0;
        var startTime = null;
        var decimals  = isDecimal ? 1 : 0;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var elapsed  = timestamp - startTime;
            var progress = Math.min(elapsed / duration, 1);

            // Ease out cubic
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = start + (target - start) * eased;

            el.textContent = current.toFixed(decimals) + suffix;

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target.toFixed(decimals) + suffix;
            }
        }

        requestAnimationFrame(step);
    }

    /**
     * Trigger animations when stats section enters viewport
     */
    function initStats() {
        var section = document.getElementById('statsSection');
        if (!section) return;

        var cards   = section.querySelectorAll('.stat-card-beauty');
        var numbers = section.querySelectorAll('.stat-counter');
        var animated = false;

        function runAnimations() {
            if (animated) return;
            animated = true;

            // Fade-up cards
            cards.forEach(function (card) {
                card.classList.add('animated');
            });

            // Counter animation
            numbers.forEach(function (el) {
                var target   = parseFloat(el.dataset.target);
                var suffix   = el.dataset.suffix   || '';
                var decimal  = el.dataset.decimal === 'true';
                var duration = parseInt(el.dataset.duration) || 2000;
                animateCounter(el, target, duration, decimal, suffix);
            });
        }

        // Use IntersectionObserver if available
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        runAnimations();
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.2 });

            observer.observe(section);
        } else {
            // Fallback: run immediately
            runAnimations();
        }
    }

    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStats);
    } else {
        initStats();
    }

})();
