# Annyzbeauty Beauty Shop
**A complete PHP/MySQL e-commerce website for Annyzbeauty**

---

## Project Structure

```
annyzbeauty/
├── index.php                  ← Homepage
├── products.php               ← Product catalog with filters
├── product.php                ← Single product detail
├── cart.php                   ← Shopping cart
├── checkout.php               ← Guest checkout; optional "save my details" (the only sign-up)
├── orders.php                 ← Customer order history
├── profile.php                ← Customer profile management
├── paystack_pay.php           ← Starts a Paystack payment for an order
├── paystack_callback.php      ← Confirms the Paystack payment
├── terms.php / privacy.php    ← Terms & Conditions, Privacy Policy
├── returns.php / shipping.php ← Returns & Refunds, Shipping & Delivery
├── logout.php                 ← "Not you? Sign out": forgets the remembered device
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
│   ├── shop.php               ← WhatsApp number + Paystack helpers
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

## Deployment to InfinityFree Hosting

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
`.github/workflows/deploy-infinityfree.yml` uploads the site to `/annyzbeauty.co.ke/htdocs/` (the folder
InfinityFree serves for the custom domain) on every push to `main`, or by hand from the **Actions** tab.
Add these in GitHub → **Settings → Secrets and variables → Actions**:

| Secret                | Value                                                    |
|-----------------------|----------------------------------------------------------|
| `FTP_SERVER`          | from VistaPanel → FTP Details, e.g. `ftpupload.net`      |
| `FTP_USERNAME`        | e.g. `if0_12345678`                                      |
| `FTP_PASSWORD`        | your FTP / hosting account password                      |
| `PAYSTACK_SECRET_KEY` | Paystack Dashboard → Settings → API Keys (`sk_live_...`) |

`includes/config.php` is never uploaded: the live copy on the server keeps the database settings, so edit
it there (Step 3). Without `PAYSTACK_SECRET_KEY` the site still deploys but Paystack payments are off
(customers see a friendly message); after adding the secret, re-run the workflow from the **Actions** tab.
A malformed key stops the deploy before anything is uploaded.

### Step 5: Create the Products Upload Folder
After uploading, ensure `/htdocs/assets/images/products/` exists and is **writable**.
In FileZilla: right-click the folder → File Permissions → set to `755`.

### Step 6: Test Your Site
- **Store:** `https://your-domain.com`
- **Admin:** `https://your-domain.com/admin/login.php`

---

## Default Login Credentials

| Role     | Email                      | Password      |
|----------|----------------------------|---------------|
| Admin    | admin@annyzbeauty.com      | Admin@1234    |
| Customer | jane@example.com           | Admin@1234    |

**Change all passwords immediately after first login!**

---

## Branding Colors

| Name         | Hex       |
|--------------|-----------|
| Primary Pink | `#E91E8C` |
| Dark Pink    | `#C2176F` |
| Soft Pink    | `#FFF0F7` |
| Black        | `#1A1A1A` |
| White        | `#FFFFFF` |

---

## Checkout and Saved Details

There is no login or register page. Customers shop as guests; the cart lives in the session.

- **Checkout** asks for name, phone, email (Paystack needs it for the receipt) and delivery address.
- **"Save my details for faster checkout next time"** plus a password is the only sign-up. The device is then
  remembered for one year (`remember_tokens` table, hashed tokens), so the details fill in automatically.
- **"Ordered before?"** at the top of checkout fills in saved details on a new device with phone number and password
  (5 wrong attempts lock it for 15 minutes).
- **My Orders** shows a remembered customer's orders, or a guest's orders from the current browser session.
- Orders store `customer_name`, `customer_phone` and `customer_email`, so guest orders show fully in admin.
  The `orders` table is updated automatically the first time (no manual SQL needed).

## Payment Setup

Payments go through **Paystack** only (customers pay with M-Pesa or card on Paystack's page):

1. The customer checks out; the order is saved as **Pending / Unpaid**.
2. They are sent to Paystack to pay (`paystack_pay.php`).
3. Paystack returns them to `paystack_callback.php`, which confirms the payment with Paystack's API
   (amount, currency and reference must match) and marks the order **Paid / Processing**.
4. Unpaid orders show a **Pay with Paystack** button on *My Orders* and on the profile page, so customers can retry.

The Paystack secret key comes from the `PAYSTACK_SECRET_KEY` repository secret; the deploy writes it to
`includes/paystack_secret.php` on the server. It is never committed. The first checkout updates the `orders`
table automatically (adds `payment_reference` and `paid_at`).

Customers who don't want an account can order on **WhatsApp** (+254 708 433 754) from the floating button,
or the product page. Send them a Paystack payment link from your Paystack dashboard.

---

## Security Features

- `password_hash()` with BCRYPT for all passwords
- PDO prepared statements (SQL injection prevention)
- `htmlspecialchars()` on all output (XSS prevention)
- Session-based authentication with role separation
- `.htaccess` blocks direct access to `/includes/` and `/database/`
- File type validation for image uploads
- Admin area completely separate from customer area

---

## Adding Product Images

Upload your product images to:  
`/assets/images/products/`

Image names should match the `image` column in the `products` table.  
Supported formats: JPG, PNG, WebP, GIF (max 2MB each).

You can also upload images directly through the admin panel:  
`/admin/product_add.php` or `/admin/product_edit.php`

---

## Customization Tips

- **Shop name:** Change `SITE_NAME` in `includes/config.php`
- **Currency:** Change `CURRENCY_SYMBOL` in `includes/config.php`
- **Shipping fee:** Change `SHIPPING_FEE` in `includes/config.php`
- **Free shipping threshold:** Change `FREE_SHIPPING_THRESHOLD`
- **WhatsApp number:** Update `+254758556523` in `includes/footer.php`
- **Colors:** Edit CSS variables in `assets/css/style.css` (`:root` block)

---

## Support

Built for **Annyzbeauty** · Nairobi, Kenya  
WhatsApp: +254 758 556 523  
Email: hello@annyzbeauty.com
