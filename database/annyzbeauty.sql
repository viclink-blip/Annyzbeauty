-- ============================================================
-- Annyzbeauty Beauty Shop - Complete Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS annyzbeauty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE annyzbeauty;

-- --------------------------------------------------------
-- Table: users (customers)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Kenya',
    profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(300),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE,
    image VARCHAR(255),
    gallery TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: cart
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NULL,
    customer_name VARCHAR(200) NULL,
    customer_email VARCHAR(150) NULL,
    customer_phone VARCHAR(20) NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    payment_method VARCHAR(30) NOT NULL DEFAULT 'paystack',
    payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    payment_reference VARCHAR(100) NULL,
    paid_at DATETIME NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100),
    shipping_country VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payment_reference (payment_reference),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: remember_tokens (devices remembered for saved customers)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: order_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: reviews
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(150),
    body TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin account (password: Admin@1234)
INSERT INTO admins (name, email, password, role) VALUES
('Anny Admin', 'admin@annyzbeauty.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Categories
INSERT INTO categories (name, slug, description, image, sort_order) VALUES
('Skincare', 'skincare', 'Cleansers, moisturizers, serums and more for glowing skin.', 'cat-skincare.jpg', 1),
('Makeup', 'makeup', 'Foundations, lipsticks, eyeshadows and everything beauty.', 'cat-makeup.jpg', 2),
('Hair Care', 'hair-care', 'Shampoos, conditioners, oils and treatments for healthy hair.', 'cat-haircare.jpg', 3),
('Fragrances', 'fragrances', 'Luxury perfumes and body mists for every occasion.', 'cat-fragrance.jpg', 4),
('Nail Care', 'nail-care', 'Nail polishes, treatments, and tools for perfect nails.', 'cat-nailcare.jpg', 5),
('Body Care', 'body-care', 'Lotions, scrubs, and oils for silky smooth skin.', 'cat-bodycare.jpg', 6);

-- Products
INSERT INTO products (category_id, name, slug, description, short_description, price, sale_price, stock_quantity, sku, image, is_featured) VALUES
(1, 'Rose Glow Vitamin C Serum', 'rose-glow-vitamin-c-serum', 'A powerful brightening serum infused with Vitamin C and rose extracts. Reduces dark spots, evens skin tone, and leaves skin visibly radiant. Suitable for all skin types.', 'Brightening Vitamin C serum with rose extracts for radiant skin.', 2800.00, 2200.00, 45, 'SKC-001', 'serum-vitc.jpg', 1),
(1, 'Hydra Boost Moisturizer', 'hydra-boost-moisturizer', 'A lightweight yet deeply hydrating moisturizer with hyaluronic acid and aloe vera. Keeps skin plump and moisturized for 24 hours without clogging pores.', '24-hour hydration with hyaluronic acid and aloe vera.', 1950.00, NULL, 60, 'SKC-002', 'moisturizer-hydra.jpg', 1),
(1, 'Gentle Foam Cleanser', 'gentle-foam-cleanser', 'A soft, pH-balanced foam cleanser that removes makeup, dirt and excess oil without stripping the skin. Contains green tea and chamomile extracts.', 'pH-balanced foam cleanser with green tea and chamomile.', 1200.00, NULL, 80, 'SKC-003', 'cleanser-foam.jpg', 0),
(1, 'Retinol Night Repair Cream', 'retinol-night-repair-cream', 'Advanced anti-aging night cream with retinol and peptides. Visibly reduces fine lines and wrinkles while you sleep, revealing firmer, younger-looking skin.', 'Anti-aging night cream with retinol and peptides.', 3500.00, 2900.00, 30, 'SKC-004', 'night-cream.jpg', 1),
(2, 'Velvet Matte Lipstick - Rosewood', 'velvet-matte-lipstick-rosewood', 'Long-lasting matte lipstick in a gorgeous rosewood shade. Creamy formula that doesnt dry out lips. Lasts up to 8 hours with a rich pigment payoff.', 'Long-lasting matte lipstick in gorgeous rosewood.', 850.00, NULL, 120, 'MKP-001', 'lipstick-rosewood.jpg', 1),
(2, 'Flawless Foundation SPF 30', 'flawless-foundation-spf30', 'Medium-to-full coverage liquid foundation with built-in SPF 30. Blurs pores and imperfections for a natural, skin-like finish. Available in 20 shades.', 'Medium-full coverage foundation with SPF 30 protection.', 2100.00, 1750.00, 55, 'MKP-002', 'foundation.jpg', 1),
(2, '12-Pan Eyeshadow Palette - Nudes', 'eyeshadow-palette-nudes', 'A curated palette of 12 nude eyeshadow shades ranging from light champagne to deep espresso. Highly pigmented formula with matte, shimmer, and glitter finishes.', '12 nude eyeshadow shades with matte, shimmer & glitter finishes.', 1800.00, NULL, 40, 'MKP-003', 'eyeshadow-palette.jpg', 1),
(2, 'Volumizing Mascara', 'volumizing-mascara', 'Buildable formula mascara that adds dramatic volume and length. Smudge-proof and long-lasting for up to 12 hours. Nourishing vitamin E formula conditions lashes.', 'Dramatic volume & length mascara, smudge-proof 12 hours.', 980.00, NULL, 70, 'MKP-004', 'mascara.jpg', 0),
(3, 'Argan Oil Hair Serum', 'argan-oil-hair-serum', 'Lightweight Moroccan argan oil serum that tames frizz, adds shine, and protects hair from heat damage. Suitable for all hair types, especially dry and damaged hair.', 'Moroccan argan oil serum for frizz-free, shiny hair.', 1650.00, 1300.00, 50, 'HRC-001', 'hair-serum.jpg', 1),
(3, 'Keratin Deep Repair Mask', 'keratin-deep-repair-mask', 'Intensive keratin-infused hair mask that restores strength, elasticity, and shine to damaged, over-processed hair. Use weekly for best results.', 'Intensive keratin mask to restore strength and shine.', 1400.00, NULL, 45, 'HRC-002', 'hair-mask.jpg', 0),
(3, 'Scalp Nourishing Shampoo', 'scalp-nourishing-shampoo', 'Gentle sulfate-free shampoo enriched with tea tree oil and biotin. Cleanses the scalp, reduces dandruff, and promotes healthy hair growth.', 'Sulfate-free shampoo with tea tree oil and biotin.', 1100.00, NULL, 90, 'HRC-003', 'shampoo.jpg', 0),
(4, 'Pink Blossom Eau de Parfum', 'pink-blossom-eau-de-parfum', 'A feminine floral fragrance with top notes of peony and raspberry, a heart of rose and jasmine, and a warm base of sandalwood and musk. 50ml bottle.', 'Feminine floral fragrance with peony, rose & sandalwood.', 4500.00, 3800.00, 25, 'FRG-001', 'perfume-pink.jpg', 1),
(4, 'Midnight Velvet Body Mist', 'midnight-velvet-body-mist', 'A sensual and long-lasting body mist with notes of vanilla, black orchid, and amber. Leaves a soft, luxurious scent on skin all day. 200ml.', 'Sensual body mist with vanilla, black orchid & amber.', 1200.00, NULL, 60, 'FRG-002', 'body-mist.jpg', 0),
(5, 'Gel Nail Polish Set - Pink Collection', 'gel-nail-polish-set-pink', 'A set of 6 long-lasting gel nail polishes in beautiful pink shades. Chip-resistant formula that lasts up to 3 weeks without chipping.', '6 gel nail polishes in beautiful long-lasting pink shades.', 1500.00, 1200.00, 35, 'NLC-001', 'nail-polish-set.jpg', 1),
(5, 'Nail Strengthening Base Coat', 'nail-strengthening-base-coat', 'Fortifying base coat with calcium and keratin that strengthens weak, brittle nails. Promotes healthy nail growth and prevents breakage.', 'Strengthening base coat with calcium and keratin.', 600.00, NULL, 80, 'NLC-002', 'base-coat.jpg', 0),
(6, 'Shea Butter Body Lotion', 'shea-butter-body-lotion', 'Ultra-rich body lotion with raw shea butter and vitamin E. Deeply nourishes and softens even the driest skin. Non-greasy formula with a light floral scent. 400ml.', 'Ultra-rich shea butter lotion for deeply soft skin.', 1300.00, NULL, 75, 'BDC-001', 'body-lotion.jpg', 1),
(6, 'Coffee Sugar Body Scrub', 'coffee-sugar-body-scrub', 'An invigorating coffee and brown sugar scrub that exfoliates dead skin cells, boosts circulation, and leaves skin incredibly smooth and glowing. 300g.', 'Invigorating coffee & sugar scrub for smooth, glowing skin.', 1100.00, 900.00, 50, 'BDC-002', 'body-scrub.jpg', 0);

-- Sample customer (password: Customer@1234)
INSERT INTO users (first_name, last_name, email, phone, password, address, city, country) VALUES
('Jane', 'Wanjiku', 'jane@example.com', '+254712345678', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Moi Avenue', 'Nairobi', 'Kenya'),
('Amina', 'Hassan', 'amina@example.com', '+254723456789', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '45 Tom Mboya St', 'Mombasa', 'Kenya');

-- Sample orders
INSERT INTO orders (order_number, user_id, subtotal, shipping_fee, total, status, payment_method, payment_status, shipping_address, shipping_city, shipping_country) VALUES
('ANN-20260001', 1, 5950.00, 300.00, 6250.00, 'delivered', 'paystack', 'paid', '123 Moi Avenue', 'Nairobi', 'Kenya'),
('ANN-20260002', 2, 2800.00, 300.00, 3100.00, 'processing', 'paystack', 'paid', '45 Tom Mboya St', 'Mombasa', 'Kenya');

INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES
(1, 1, 'Rose Glow Vitamin C Serum', 1, 2200.00, 2200.00),
(1, 5, 'Velvet Matte Lipstick - Rosewood', 2, 850.00, 1700.00),
(1, 9, 'Argan Oil Hair Serum', 1, 1300.00, 1300.00),
(1, 16, 'Shea Butter Body Lotion', 1, 1300.00, 1300.00),
(2, 12, 'Pink Blossom Eau de Parfum', 1, 3800.00, 3800.00);
