# Annyzbeauty Beauty Shop 🌸
**A complete PHP/MySQL e-commerce website for Annyzbeauty**

---

## 📁 Project Structure

```
annyzbeauty/
├── index.php                  ← Homepage
├── products.php               ← Product catalog with filters
├── product.php                ← Single product detail
├── cart.php                   ← Shopping cart
├── checkout.php               ← Checkout & order placement
├── orders.php                 ← Customer order history
├── profile.php                ← Customer profile management
├── login.php                  ← Customer login
├── logout.php                 ← Session logout
├── .htaccess                  ← Apache security config
│
├── ajax/
│   ├── cart_add.php           ← AJAX: Add to cart
│   └── cart_update.php        ← AJAX: Update cart quantity
│
├── admin/
│   ├── login.php              ← Admin login
│   ├── logout.php             ← Admin logout
│   ├── dashboard.php          ← Admin dashboard + stats
│   ├── products.php           ← Product list & delete
│   ├── product_add.php        ← Add new product
│   ├── product_edit.php       ← Edit existing product
│   ├── categories.php         ← Category CRUD
│   ├── orders.php             ← Order management
│   ├── customers.php          ← Customer management
│   └── includes/
│       ├── admin_header.php
│       └── admin_footer.php
│
├── includes/
│   ├── config.php             ← DB config + utility functions
│   ├── header.php             ← Site header + navbar
│   ├── footer.php             ← Site footer
│   └── product_card.php       ← Reusable product card
│
├── assets/
│   ├── css/
│   │   └── style.css          ← Main stylesheet (pink/white/black)
│   ├── js/
│   │   └── main.js            ← Main JavaScript
│   └── images/
│       ├── products/          ← Product image uploads go here
│       └── hero-beauty.jpg    ← Hero background image
│
└── database/
    └── annyzbeauty.sql        ← Complete MySQL schema + sample data
```

---

## 🚀 Deployment to InfinityFree Hosting

### Step 1: Create InfinityFree Account
1. Sign up at [infinityfree.com](https://infinityfree.com)
2. Create a new hosting account
3. Note your **subdomain** (e.g. `annyzbeauty.rf.gd`) or connect a custom domain

### Step 2: Create the MySQL Database
1. Log in to **VistaPanel** (InfinityFree control panel)
2. Click **MySQL Databases**
3. Create a new database — note the full name (e.g. `epiz_12345678_annyzbeauty`)
4. Create a DB user with a strong password
5. Assign the user to the database with **All Privileges**
6. Click **phpMyAdmin** → select your database → click **Import**
7. Upload `database/annyzbeauty.sql` → click **Go**

### Step 3: Configure Database Connection
Open `includes/config.php` and update:

```php
define('DB_HOST', 'sql200.infinityfree.com'); // check your VistaPanel for exact host
define('DB_USER', 'epiz_12345678');            // your DB username
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'epiz_12345678_annyzbeauty');

define('SITE_URL', 'https://annyzbeauty.rf.gd'); // your InfinityFree domain
```

### Step 4: Upload Files via FTP
1. In VistaPanel, note your **FTP hostname, username, and password**
2. Use **FileZilla** (free): File → Site Manager → New Site
   - Host: your FTP host
   - User/Pass: your FTP credentials
3. Upload the entire `annyzbeauty/` contents to `/htdocs/` on the server
   - Upload **all files and folders** into `/htdocs/` (not inside a subdirectory)

### Automatic Deploys (GitHub Actions)
`.github/workflows/deploy-infinityfree.yml` uploads the site to `/htdocs/` on every push to `main`
(or run it by hand from the **Actions** tab). Add these in GitHub → **Settings → Secrets and variables → Actions**:

| Secret         | Value (from VistaPanel → FTP Details) |
|----------------|---------------------------------------|
| `FTP_SERVER`   | e.g. `ftpupload.net`                  |
| `FTP_USERNAME` | e.g. `epiz_12345678`                  |
| `FTP_PASSWORD` | your FTP / hosting account password   |

`includes/config.php`, `database/` and uploaded product images are never overwritten, so set up
`config.php` on the server once (Step 3) and leave it there.

### Step 5: Create the Products Upload Folder
After uploading, ensure `/htdocs/assets/images/products/` exists and is **writable**.
In FileZilla: right-click the folder → File Permissions → set to `755`.

### Step 6: Test Your Site
- **Store:** `https://your-domain.com`
- **Admin:** `https://your-domain.com/admin/login.php`

---

## 🔑 Default Login Credentials

| Role     | Email                      | Password      |
|----------|----------------------------|---------------|
| Admin    | admin@annyzbeauty.com      | Admin@1234    |
| Customer | jane@example.com           | Admin@1234    |

**⚠️ Change all passwords immediately after first login!**

---

## 🎨 Branding Colors

| Name         | Hex       |
|--------------|-----------|
| Primary Pink | `#E91E8C` |
| Dark Pink    | `#C2176F` |
| Soft Pink    | `#FFF0F7` |
| Black        | `#1A1A1A` |
| White        | `#FFFFFF` |

---

## 💳 Payment Setup

The site uses a **manual payment confirmation** system:

1. Customer selects M-Pesa, PayPal, or Cash on Delivery at checkout
2. Order is placed with status **Pending / Unpaid**
3. Admin receives order notification (check `/admin/orders.php`)
4. Share payment details with customer via email/WhatsApp:
   - **M-Pesa:** Send to `+254 758 556 523` (Paybill/Till)
   - **PayPal:** `masilavincent32@gmail.com`
5. Customer pays and sends proof via WhatsApp
6. Admin updates order status to **Processing** and payment to **Paid**

---

## 🛡️ Security Features

- ✅ `password_hash()` with BCRYPT for all passwords
- ✅ PDO prepared statements (SQL injection prevention)
- ✅ `htmlspecialchars()` on all output (XSS prevention)
- ✅ Session-based authentication with role separation
- ✅ `.htaccess` blocks direct access to `/includes/` and `/database/`
- ✅ File type validation for image uploads
- ✅ Admin area completely separate from customer area

---

## 📦 Adding Product Images

Upload your product images to:  
`/assets/images/products/`

Image names should match the `image` column in the `products` table.  
Supported formats: JPG, PNG, WebP, GIF (max 2MB each).

You can also upload images directly through the admin panel:  
`/admin/product_add.php` or `/admin/product_edit.php`

---

## ⚙️ Customization Tips

- **Shop name:** Change `SITE_NAME` in `includes/config.php`
- **Currency:** Change `CURRENCY_SYMBOL` in `includes/config.php`
- **Shipping fee:** Change `SHIPPING_FEE` in `includes/config.php`
- **Free shipping threshold:** Change `FREE_SHIPPING_THRESHOLD`
- **WhatsApp number:** Update `+254758556523` in `includes/footer.php`
- **Colors:** Edit CSS variables in `assets/css/style.css` (`:root` block)

---

## 📞 Support

Built for **Annyzbeauty** · Nairobi, Kenya  
WhatsApp: +254 758 556 523  
Email: hello@annyzbeauty.com
