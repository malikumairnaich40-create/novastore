# NovaStore - Modern Multi-Category E-Commerce Platform

A production-ready, multi-category e-commerce web application built with **Vanilla PHP 8.x (OOP & Clean Architecture)**, **MySQL (Foreign Keys & Cascades)**, **Tailwind CSS 3 (via CDN)**, **FontAwesome 6**, and **AJAX / Vanilla JS**.

---

## 🚀 Key Features

### 🛍️ Storefront & Customer Experience
- **Responsive Homepage**: Hero banner with gradient highlights, trust badges, featured department tiles, flash sales with countdown timer, and trending products.
- **Product Catalog (`products.php`)**: Full-featured product filtering by category, price range (Min/Max), in-stock availability, and special deals. Dynamic sorting (Low to High, High to Low, Newest, Name) and pagination.
- **Product Details (`product.php`)**: Multi-image preview thumbnail switcher, live stock indicator, dynamic quantity stepper, and related products carousel.
- **Real-Time AJAX Cart (`api/cart.php` & `cart.php`)**:
  - Add to cart with interactive feedback & toast notifications without page reloads.
  - Live quantity adjustment & item removal with immediate subtotal & grand total recalculations.
  - Free shipping progress bar (Unlocks at \$100+).
  - Guest cart persisted in session and seamlessly migrated to user account upon sign-in.
- **Multi-Step Checkout (`checkout.php`)**:
  - Clean 2-column layout.
  - Delivery information collection with prefilled customer data.
  - Payment simulation: Cash on Delivery (COD) & Credit/Debit Card processing.
  - DB Transactions ensuring stock verification, deduction, and cart clearing.
- **Customer Dashboard (`account.php`)**:
  - Live visual timeline tracker (Order Placed ➔ Processing ➔ Shipped ➔ Delivered).
  - Order history with receipt printable view (`order-success.php`).
  - Personal profile & delivery address editor.
  - Password management.

### 🛡️ Admin Management Panel (`/admin`)
- **Executive Dashboard**: Key metric cards (Total Sales, Total Orders, Active Inventory, Registered Customers), interactive Monthly Revenue bar chart (Chart.js), order fulfillment distribution doughnut chart, and low-stock alerts.
- **Product Management (Full CRUD)**: Add, edit, delete, and toggle status. Secure file upload with MIME verification (`image/jpeg`, `image/png`, `image/webp`), size enforcement (5MB), and fallback URL support.
- **Category Management (Full CRUD)**: Create and edit categories with auto-slug generation, icon selection, and product assignment count checks.
- **Order Management & Fulfillment**: List orders with status filters (Pending, Processing, Shipped, Delivered, Cancelled), quick status changer, detailed item inspector, and **Printable PDF/Paper Invoice (`admin/order-invoice.php`)**.
- **Customer Directory**: View all registered users with their lifetime spending and order counts.

### 🔒 Security & Best Practices
- **100% Prepared Statements (PDO)**: Complete protection against SQL Injections.
- **XSS Prevention**: Strict HTML escaping via `e()` / `htmlspecialchars` across all views.
- **CSRF Token Protection**: Cryptographically secure token verification for all POST requests.
- **Password Security**: Modern `PASSWORD_BCRYPT` hashing and verification.
- **Session Fixation Prevention**: `session_regenerate_id(true)` upon authentication.

---

## 📂 Project Architecture

```
ecommerce_store/
├── database.sql               # Complete SQL schema & dummy seed data
├── README.md                  # Documentation and setup guide
├── .htaccess                  # Apache configuration & security headers
├── index.php                  # Modern Storefront Homepage
├── products.php               # Product Catalog with Filters & Pagination
├── product.php                # Product Details & Image Gallery
├── cart.php                   # Shopping Cart Page
├── checkout.php               # Secure Checkout & Order Transaction
├── order-success.php          # Order Confirmation & Receipt
├── account.php                # Customer Dashboard & Order Tracking
├── login.php                  # Authentication Login & 1-Click Demo Fills
├── register.php               # Customer Account Registration
├── logout.php                 # Secure Session Destroyer
├── config/
│   ├── db.php                 # PDO Database Singleton Connection
│   └── config.php             # App constants, dynamic URL resolution & helpers
├── includes/
│   ├── functions.php          # Security, Cart, Pricing & File Upload Helpers
│   ├── header.php             # Global Responsive Storefront Navigation
│   └── footer.php             # Global Storefront Footer & Scripts
├── api/
│   └── cart.php               # JSON API Endpoint for AJAX Cart Operations
├── assets/
│   ├── css/
│   │   └── custom.css         # Animations, Glassmorphism, Print Styles
│   ├── js/
│   │   └── main.js            # AJAX Cart Engine, Toasts, Quantity Steppers
│   └── uploads/               # Product & Category Uploads Storage
│       ├── products/
│       └── categories/
└── admin/
    ├── index.php              # Admin Dashboard with Chart.js Analytics
    ├── categories.php         # Category Full CRUD
    ├── products.php           # Products List & Inventory Filters
    ├── product-add.php        # Create Product Form with Secure Upload
    ├── product-edit.php       # Edit Product Form
    ├── product-delete.php     # Secure CSRF Product Delete
    ├── orders.php             # Orders Management & Status Filter
    ├── order-detail.php       # Order Line Items & Fulfillment Updater
    ├── order-invoice.php      # Printable Clean Invoice Template
    ├── customers.php          # Customer Accounts & Lifetime Value
    └── includes/
        ├── auth_check.php     # Admin Role Enforcement Middleware
        ├── header.php         # Admin Sidebar & Header Navigation
        └── footer.php         # Admin Scripts & Footer Layout
```

---

## ⚙️ Quick Installation & Setup Guide

### 1. Place in XAMPP `htdocs`
Copy or move the `ecommerce_store` directory into your XAMPP web root:
```
C:\xampp\htdocs\ecommerce_store
```

### 2. Start Apache & MySQL in XAMPP Control Panel
- Open **XAMPP Control Panel**
- Start **Apache**
- Start **MySQL**

### 3. Import Database
1. Open your browser and navigate to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar to create a database named `ecommerce_db` (or simply import directly)
3. Click the **Import** tab at the top
4. Choose the file `database.sql` located inside `ecommerce_store/`
5. Click **Go** / **Import**

### 4. Access the Platform
- **Front Storefront**: [http://localhost/ecommerce_store/](http://localhost/ecommerce_store/)
- **Admin Portal**: [http://localhost/ecommerce_store/admin/](http://localhost/ecommerce_store/admin/)

---

## 🔑 Demo Login Credentials

The database comes pre-seeded with sample users and full demo data:

| Role | Email | Password | Quick Action |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@ecommerce.com` | `admin123` | 1-Click Fill button on `login.php` |
| **Customer** | `customer@ecommerce.com` | `customer123` | 1-Click Fill button on `login.php` |
| **Customer 2** | `sarah@ecommerce.com` | `customer123` | Direct login |

---

## 🛠️ Testing via PHP Built-in Server (Alternative)

If you wish to test without Apache or in another directory, simply open PowerShell/Terminal in `ecommerce_store/` and run:
```bash
cd ecommerce_store
php -S localhost:8000
```
Then visit `http://localhost:8000` in your web browser.
