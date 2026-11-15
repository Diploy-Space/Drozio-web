# Drozio-web — Multi-Vendor eCommerce Platform

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel 12 Framework">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%20--%208.5-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/React-19.x-61DAFB?style=for-the-badge&logo=react&logoColor=black" alt="React 19">
  <img src="https://img.shields.io/badge/Vite-6.x-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite 6">
  <img src="https://img.shields.io/badge/Flutter%20SDK-3.47+-02569B?style=for-the-badge&logo=flutter&logoColor=white" alt="Flutter SDK">
  <img src="https://img.shields.io/badge/Status-Production%20Ready-success?style=for-the-badge" alt="Status">
</p>

---

## 📖 Overview

**Drozio-web** is an enterprise-grade multi-vendor eCommerce ecosystem engineered on **Laravel 12**, **Inertia.js + React 19**, and modern asynchronous queue architecture. It provides an all-in-one digital marketplace infrastructure connecting platform administrators, independent vendors/sellers, customers, and delivery personnel.

The platform is fully decoupled and supports multi-theme web storefronts alongside dedicated cross-platform mobile apps for Android and iOS built with **Flutter 3.47+**.

---

## ✨ Key Platform Features & Modules

### 🏢 1. Multi-Vendor Commerce Architecture
- **Super Admin Dashboard:** Granular control over platform commissions, payouts, multi-currency exchange rates, and store verifications.
- **Dedicated Vendor Panel:** Product management, wholesale pricing configuration, dynamic coupons, delivery management, and real-time sales analytics.
- **Customer Storefront:** Fast, responsive multi-theme user interface (Default, Aster, and Fashion themes) powered by React 19 & Inertia.js.

### 📦 2. Wholesale (B2B) Module
- Tiered quantity-based pricing and minimum order quantities (MOQ).
- Dedicated wholesale cart, quotation workflows, and bulk product management.
- Multi-tier commission rules for wholesale vendors.

### 🔨 3. Auction & Real-Time Bidding
- Scheduled product auctions with reserve pricing and automated bid incrementing.
- Live countdowns, bid activity tracking, and fair winner auto-selection.
- Integrated escrow settlement upon auction conclusion.

### 🤖 4. AI Assistant Module
- Integrated AI-driven catalog generation and smart product descriptions.
- Customer AI chat sessions with token and rate-limit guardrails.
- Intelligent search suggestions and semantic product discovery.

### 🚚 5. Logistics & Courier Service
- Integrated multi-provider courier configuration (Steadfast, Pathao, RedX, Paperfly, etc.).
- Automated consignment booking, dispatch manifests, tracking supersede logic, and real-time status webhooks.

### 💳 6. Payment & Tax Infrastructure
- Multi-gateway support (Stripe, PayPal, SSLCommerz, Razorpay, Paystack, BKash, Nagad, etc.).
- Flexible tax configurations: Inclusive/Exclusive taxation, multi-tier VAT, and vendor-wise tax reporting.

---

## 🛠️ System Requirements

Ensure your hosting environment meets the following specifications:

| Component | Minimum Required | Recommended |
| :--- | :--- | :--- |
| **PHP** | `^8.2` | `8.2.x` / `8.3.x` / `8.5.x` |
| **Database** | MySQL `8.0+` or MariaDB `10.4+` | MySQL `8.0+` |
| **Node.js** | `18.x` | `20.x LTS` (npm 9+) |
| **Web Server** | Nginx / Apache 2.4 | Nginx with PHP-FPM |
| **Composer** | `2.x` | `2.7+` |

### Required PHP Extensions:
`bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `json`, `libxml`, `mbstring`, `mysqli`, `openssl`, `pdo_mysql`, `zip`

---

## 🚀 Quick Start / Installation Guide

### 1. Clone the Repository
```bash
git clone https://github.com/Diploy-Space/Drozio-web.git
cd Drozio-web
```

### 2. Install Backend Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Environment Configuration
Duplicate the example environment file and generate a unique application encryption key:
```bash
cp .env.example .env
php artisan key:generate
```

Configure your database and application URL in `.env`:
```ini
APP_NAME=Drozio
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_password

QUEUE_CONNECTION=database
```

### 4. Database Setup & Migrations
Execute the database schema migrations:
```bash
php artisan migrate
```

*(Optional: If setting up a fresh demonstration database with sample data)*
```bash
php artisan db:seed
```

### 5. Link Public Storage
```bash
php artisan storage:link
```

### 6. Install & Compile Frontend Assets
```bash
npm install
npm run build
```

---

## ⚡ Deployment: Queues & Scheduler (Production Critical)

> [!IMPORTANT]
> Order confirmations, registration emails, and Firebase Cloud Messaging (FCM) notifications execute asynchronously on **background queues**. Production environments **must** run an active queue worker.

### 1. Running the Queue Worker
Run the worker across all prioritized application queues:
```bash
php -d memory_limit=512M artisan queue:work database \
    --queue=exports,high,default,auctions,auction_notifications \
    --tries=3 --timeout=900 --max-time=3600 --sleep=3
```

#### Recommended: Supervisor Configuration (`/etc/supervisor/conf.d/drozio-worker.conf`)
```ini
[program:drozio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/Drozio-web/artisan queue:work database --queue=exports,high,default,auctions,auction_notifications --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/Drozio-web/storage/logs/worker.log
```

### 2. Task Scheduler (Cron Job)
Configure a single cron entry to drive scheduled promotions, cache invalidation, and recurring jobs:
```bash
* * * * * cd /path/to/Drozio-web && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📁 Repository Directory Structure

```text
Drozio-web/
├── app/                      # Core MVC: Controllers, Middleware, Models, Services
│   ├── Http/Controllers/     # Admin, Vendor, and REST API v6 Controllers
│   ├── Library/              # Payment gateways, helper libraries, constants
│   ├── Models/               # Eloquent domain models
│   ├── Providers/            # Service providers (RouteServiceProvider, etc.)
│   └── Services/             # Business logic service abstractions
├── Modules/                  # Modular Architecture extensions
│   ├── AI/                   # Generative AI tools & limits
│   ├── Blog/                 # Content marketing & publication engine
│   ├── Courier/              # Automated shipping, dispatch & tracking
│   └── TaxModule/            # Enterprise multi-tier tax computations
├── config/                   # System, gateway, and database configurations
├── database/                 # Schema migrations, seeders, and factories
│   └── migrations/           # 340+ incremental migrations
├── public/                   # Web root (Vite compiled build assets, themes)
├── resources/                # Blade views, React/Inertia components, and CSS
├── routes/                   # Routing declarations (web, api, admin, vendor)
└── tests/                    # Feature and Unit test suites
```

---

## 📱 Mobile Applications (Flutter)

Drozio-web powers native mobile clients via versioned REST APIs (`/api/v6/`):
- **User App:** Customer shopping application (`flutter_sixvalley_ecommerce`) supporting real-time push notifications, payment gateways, live bidding, and order tracking.
- **Vendor App:** Dedicated merchant portal (`sixvalley_vendor_app`) for catalog updates, POS invoice printing via Bluetooth thermal printers, and live order alerts.

Compatible with **Flutter SDK 3.47.7+** and **Dart 3.6.2+**.

---

## 🧹 Maintenance & Diagnostic Commands

| Command | Description |
| :--- | :--- |
| `php artisan optimize:clear` | Flush configuration, routing, view, and application caches. |
| `php artisan config:cache` | Pre-compile environment configurations for maximum production throughput. |
| `php artisan route:cache` | Pre-compile route map for near-instant route resolution. |
| `php artisan file:permission` | Automatically correct storage and bootstrap directory ownership. |
| `php artisan queue:failed` | Inspect failed background jobs. |
| `php artisan queue:retry all` | Re-queue all previously failed background tasks. |

---

## 🔒 Security & Contribution Guidelines

- If you identify security vulnerabilities within this repository, please notify the technical lead directly.
- Ensure all pull requests follow project styling rules, include appropriate database migrations, and pass local syntax checks prior to merging.

---

## 📄 License & Attribution

This software repository is customized and maintained by the **Drozio Engineering Team**.  
All rights reserved &copy; 2026.
