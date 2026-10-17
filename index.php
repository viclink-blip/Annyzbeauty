<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<style>
/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Hero Wrapper ── */
.hero-full {
    min-height: calc(100vh - 68px);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, #fff 0%, #fff5fb 40%, #fce4f3 100%);
}

/* ── Floating Background Circles ── */
.bg-circles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}
.bg-circle {
    position: absolute;
    border-radius: 50%;
    opacity: 0;
    animation: floatUp linear infinite;
}
.bg-circle:nth-child(1)  { width:320px; height:320px; background:radial-gradient(circle,rgba(233,30,140,0.08),transparent 70%); left:-80px;  top:10%;  animation-duration:18s; animation-delay:0s;   opacity:0; }
.bg-circle:nth-child(2)  { width:200px; height:200px; background:radial-gradient(circle,rgba(233,30,140,0.06),transparent 70%); right:5%;   top:20%;  animation-duration:22s; animation-delay:2s;   opacity:0; }
.bg-circle:nth-child(3)  { width:260px; height:260px; background:radial-gradient(circle,rgba(233,30,140,0.07),transparent 70%); left:30%;   bottom:5%; animation-duration:20s; animation-delay:4s;   opacity:0; }
.bg-circle:nth-child(4)  { width:150px; height:150px; background:radial-gradient(circle,rgba(233,30,140,0.05),transparent 70%); right:20%;  bottom:15%;animation-duration:25s; animation-delay:1s;   opacity:0; }
.bg-circle:nth-child(5)  { width:100px; height:100px; background:radial-gradient(circle,rgba(233,30,140,0.09),transparent 70%); left:55%;   top:8%;   animation-duration:16s; animation-delay:3s;   opacity:0; }

@keyframes floatUp {
    0%   { transform: translateY(60px) scale(0.85); opacity: 0; }
    15%  { opacity: 1; }
    85%  { opacity: 1; }
    100% { transform: translateY(-80px) scale(1.1); opacity: 0; }
}

/* ── Sparkles ── */
.sparkle {
    position: absolute;
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #E91E8C;
    opacity: 0;
    animation: sparkleAnim ease-in-out infinite;
    pointer-events: none;
}
.sparkle:nth-child(6)  { top:18%; left:12%;  animation-duration:3.2s; animation-delay:0.0s; }
.sparkle:nth-child(7)  { top:72%; left:80%;  animation-duration:2.8s; animation-delay:0.6s; }
.sparkle:nth-child(8)  { top:35%; left:88%;  animation-duration:3.6s; animation-delay:1.2s; }
.sparkle:nth-child(9)  { top:80%; left:22%;  animation-duration:2.5s; animation-delay:0.4s; }
.sparkle:nth-child(10) { top:12%; left:60%;  animation-duration:3.0s; animation-delay:1.8s; }
.sparkle:nth-child(11) { top:55%; left:5%;   animation-duration:2.7s; animation-delay:0.9s; }
.sparkle:nth-child(12) { top:25%; left:45%;  animation-duration:3.4s; animation-delay:2.1s; }

@keyframes sparkleAnim {
    0%,100% { transform: scale(0) rotate(0deg);   opacity: 0; }
    30%      { transform: scale(1.4) rotate(45deg); opacity: 0.7; }
    60%      { transform: scale(0.8) rotate(90deg); opacity: 0.4; }
}

/* ── Hero Content ── */
.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 40px 24px;
    max-width: 680px;
    width: 100%;
    margin: 0 auto;
}

/* ── Flower Icon ── */
.hero-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px; height: 72px;
    background: linear-gradient(135deg, #E91E8C, #C2176F);
    border-radius: 50%;
    font-size: 32px; color: #fff;
    box-shadow: 0 8px 28px rgba(233,30,140,0.35);
    margin-bottom: 28px;
    opacity: 0;
    animation: fadeSlideUp 0.8s ease 0.2s forwards;
}

/* ── Brand Name ── */
.hero-brand {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(3rem, 10vw, 5.8rem);
    font-weight: 600;
    line-height: 1.05;
    letter-spacing: -1px;
    color: #1A1A1A;
    opacity: 0;
    animation: fadeSlideUp 0.9s ease 0.4s forwards;
}
.hero-brand span {
    background: linear-gradient(135deg, #E91E8C, #C2176F, #E91E8C);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 3s linear 1.5s infinite;
}

@keyframes shimmer {
    0%   { background-position: 0% center; }
    100% { background-position: 200% center; }
}

/* ── Divider ── */
.hero-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 20px auto;
    opacity: 0;
    animation: fadeSlideUp 0.8s ease 0.6s forwards;
}
.hero-divider-line {
    width: 50px; height: 1.5px;
    background: linear-gradient(90deg, transparent, #E91E8C);
}
.hero-divider-line.right {
    background: linear-gradient(90deg, #E91E8C, transparent);
}
.hero-divider-dot {
    width: 6px; height: 6px;
    background: #E91E8C; border-radius: 50%;
}

/* ── Slogan ── */
.hero-slogan {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.1rem, 3.5vw, 1.5rem);
    font-weight: 400;
    font-style: italic;
    color: #E91E8C;
    letter-spacing: 3px;
    text-transform: uppercase;
    opacity: 0;
    animation: fadeSlideUp 0.8s ease 0.8s forwards;
}

/* ── Subtitle ── */
.hero-subtitle {
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: clamp(0.9rem, 2.5vw, 1.05rem);
    font-weight: 400;
    color: #888;
    line-height: 1.7;
    margin: 16px auto 0;
    max-width: 400px;
    opacity: 0;
    animation: fadeSlideUp 0.8s ease 1.0s forwards;
}

/* ── Buttons ── */
.hero-buttons {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 36px;
    opacity: 0;
    animation: fadeSlideUp 0.9s ease 1.2s forwards;
}

.btn-hero-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 34px;
    background: linear-gradient(135deg, #E91E8C, #C2176F);
    color: #fff !important;
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    letter-spacing: 0.3px;
    box-shadow: 0 6px 24px rgba(233,30,140,0.30);
    transition: all 0.28s ease;
    border: none;
}
.btn-hero-primary:hover {
    background: linear-gradient(135deg, #C2176F, #A0115C);
    color: #fff !important;
    box-shadow: 0 10px 32px rgba(233,30,140,0.42);
    transform: translateY(-2px);
}

.btn-hero-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 32px;
    background: transparent;
    color: #E91E8C !important;
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    letter-spacing: 0.3px;
    border: 2px solid #E91E8C;
    transition: all 0.28s ease;
}
.btn-hero-secondary:hover {
    background: #FFF0F7;
    color: #C2176F !important;
    border-color: #C2176F;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(233,30,140,0.15);
}

/* ── Trust Pills ── */
.hero-trust {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 40px;
    opacity: 0;
    animation: fadeIn 1s ease 1.6s forwards;
}
.trust-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: 11.5px;
    font-weight: 500;
    color: #999;
    letter-spacing: 0.3px;
}
.trust-pill i { color: #E91E8C; font-size: 13px; }

/* ── Core Animations ── */
@keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

/* ── Responsive ── */
@media (max-width: 576px) {
    .hero-full { min-height: calc(100vh - 60px); }
    .hero-icon { width: 58px; height: 58px; font-size: 26px; margin-bottom: 20px; }
    .hero-buttons { gap: 10px; margin-top: 28px; }
    .btn-hero-primary, .btn-hero-secondary { padding: 12px 26px; font-size: 13.5px; }
    .hero-trust { gap: 14px; margin-top: 28px; }
    .hero-slogan { letter-spacing: 2px; }
    .hero-content { padding: 28px 20px; }
}
@media (max-width: 360px) {
    .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
    .hero-buttons { flex-direction: column; width: 100%; }
}
</style>

<!-- HERO SECTION -->
<section class="hero-full">

    <!-- Floating Background -->
    <div class="bg-circles">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
    </div>

    <!-- Content -->
    <div class="hero-content">

        <!-- Flower Icon -->
        <div class="hero-icon">
            <i class="bi bi-flower1"></i>
        </div>

        <!-- Brand Name -->
        <h1 class="hero-brand">
            Annyz<span>beauty</span>
        </h1>

        <!-- Divider -->
        <div class="hero-divider">
            <div class="hero-divider-line"></div>
            <div class="hero-divider-dot"></div>
            <div class="hero-divider-line right"></div>
        </div>

        <!-- Slogan -->
        <p class="hero-slogan">Glow. Bloom. Radiate.</p>

        <!-- Subtitle -->
        <p class="hero-subtitle">
            Premium beauty products for every skin tone, delivered across Kenya.
        </p>

        <!-- Buttons -->
        <div class="hero-buttons">
            <a href="<?= SITE_URL ?>/index.php" class="btn-hero-secondary">
                <i class="bi bi-house-fill"></i> Home
            </a>
            <a href="<?= SITE_URL ?>/products.php" class="btn-hero-primary">
                <i class="bi bi-bag-fill"></i> Shop Now
            </a>
        </div>

        <!-- Trust Pills -->
        <div class="hero-trust">
            <span class="trust-pill">
                <i class="bi bi-shield-check-fill"></i> 100% Authentic
            </span>
            <span class="trust-pill">
                <i class="bi bi-truck"></i> Kenya Delivery
            </span>
            <span class="trust-pill">
                <i class="bi bi-phone-fill"></i> M-Pesa Accepted
            </span>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
