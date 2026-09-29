<h1 align="center">🛒 UniversalEcom</h1>

<p align="center">
  A modular, production-ready Laravel e-commerce platform with multi-vendor marketplace, B2B commerce, logistics, promotions, and a portable add-on architecture.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-red?logo=laravel" />
  <img src="https://img.shields.io/badge/PHP-8.3%2B-blue?logo=php" />
  <img src="https://img.shields.io/badge/License-MIT-green" />
  <img src="https://img.shields.io/badge/API-RESTful%20v1-orange" />
</p>

---

## 📖 Table of Contents

- [Overview](#-overview)
- [System Architecture](#-system-architecture)
- [Core Modules](#-core-modules)
- [Add-on Packages](#-add-on-packages)
- [API Reference](#-api-reference)
- [Admin Panel](#-admin-panel)
- [Storefront](#-storefront)
- [Database & Migrations](#-database--migrations)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [Testing](#-testing)
- [Project Structure](#-project-structure)

---

## 🌐 Overview

**UniversalEcom** is a full-stack Laravel e-commerce platform designed for scale and flexibility. It supports B2C storefronts, B2B corporate purchasing, multi-vendor marketplaces, and is extensible through a portable add-on/package architecture.

### Key Highlights

| Feature | Details |
|---|---|
| **Architecture** | Domain-Driven + Portable Add-on Packages |
| **API** | RESTful API v1 with Laravel Sanctum auth |
| **Admin Panel** | Full-featured web dashboard (RBAC) |
| **Storefront** | Blade-rendered customer-facing UI |
| **Multi-vendor** | Vendor portal, payouts, commission |
| **B2B** | Company accounts, contract price lists, PO management |
| **Payments** | Razorpay, COD (extensible gateway interface) |
| **Logistics** | Delivery zones, slots, driver app, POD |
| **Promotions** | Coupons, bundles, mix-and-match, tiered pricing |
| **Notifications** | WhatsApp notifications (Meta/WABA) |
| **GST/Tax** | Indian GST-compliant invoicing |
| **Loyalty** | Wallet & loyalty points system |

---

## 🏗 System Architecture

```
┌─────────────────────────────────────────────────────┐
│                    HTTP Layer                        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ │
│  │  Storefront │  │  Admin Web  │  │  REST API   │ │
│  │ (Blade/CSS) │  │  (Blade)    │  │   (v1)      │ │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘ │
└─────────┼────────────────┼────────────────┼─────────┘
          │                │                │
┌─────────▼────────────────▼────────────────▼─────────┐
│                   Domain Layer                       │
│  Cart │ Catalog │ Checkout │ Delivery │ Inventory    │
│  Orders │ Payments │ Pricing                         │
└─────────────────────────┬───────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────┐
│               Portable Add-on Packages               │
│  vendor-marketplace │ promotion-engine │ b2b-commerce│
│  loyalty-wallet │ driver-logistics │ invoice-gst     │
│  payment-gateways │ whatsapp-notifications           │
└─────────────────────────────────────────────────────┘
```

---

## 🧩 Core Modules

### 🛍 Catalog
- Products with **variants**, **attributes**, and **media gallery**
- **Categories** (nested), **Brands**, **Tags**
- Bulk CSV import/export
- SEO slugs & sitemap generation
- Product documents (PDFs, spec sheets)

### 🛒 Cart & Checkout
- Session-based guest cart + authenticated cart merge
- Coupon / promo code application
- Delivery slot selection & address validation
- Order placement with stock reservation

### 📦 Orders & Fulfillment
- Full order lifecycle: Pending → Confirmed → Packed → Shipped → Delivered
- Order status history with audit trail
- Packing slip generation
- Shipment tracking

### 📍 Delivery & Logistics
- **Delivery zones** with pincode mapping
- **Delivery slots** (time-window scheduling)
- Serviceability check by pincode
- Delivery exceptions management

### 🏭 Inventory
- Multi-**warehouse** inventory tracking
- **Inventory movements** (in/out/adjustment)
- **Stock reservations** on checkout
- Low-stock alerts

### 💰 Pricing
- Base price, sale price, MRP
- **Quantity price tiers** (B2B volume discounts)
- GST-inclusive/exclusive pricing
- Promotion-adjusted pricing pipeline

### 🔐 RBAC & Security
- **Roles & Permissions** (DB-driven, not hardcoded)
- Admin login with brute-force throttle
- Centralized **AuditLog** — captures actor, IP, before/after state for every sensitive action
- API rate limiting per endpoint group

---

## 📦 Add-on Packages

Each package lives under `packages/` and is autoloaded as a PSR-4 namespace. They follow a **portable add-on architecture** and can be enabled/disabled independently.

### `vendor-marketplace`
> **Namespace:** `Packages\VendorMarketplace`

- Vendor onboarding, profile management
- Vendor product listings & offers
- Commission tracking & **payout management**
- Vendor portal web UI (separate layout)
- Vendor order fulfillment flow

### `promotion-engine`
> **Namespace:** `Packages\PromotionEngine`

- **Coupon codes** (flat, percentage, free shipping)
- **Bundle discounts** (buy X get Y)
- **Mix-and-match** promotions
- `PromotionCalculationService` implementing `PricingAdjustmentContract`

### `b2b-commerce`
> **Namespace:** `Packages\B2BCommerce`

- **Company accounts** with company-user relationships
- **Contract price lists** (per-company negotiated pricing)
- **Purchase order (PO) management**
- Admin panel for B2B company & PO management
- Customer B2B self-service portal

### `loyalty-wallet`
> **Namespace:** `Packages\LoyaltyWallet`

- Customer loyalty points accumulation
- **Digital wallet** for store credits
- Redeem points at checkout
- Customer account dashboard

### `driver-logistics`
> **Namespace:** `Packages\DriverLogistics`

- Driver onboarding & authentication
- **Delivery assignment** & route management
- **Proof of delivery (POD)** capture (photo upload)
- Driver mobile-friendly UI
- Admin logistics dashboard

### `invoice-gst`
> **Namespace:** `Packages\InvoiceGst`

- **GST-compliant invoices** (GSTIN, HSN/SAC codes)
- Tax breakdowns (CGST, SGST, IGST)
- Invoice PDF generation
- Admin invoice management

### `payment-gateways`
> **Namespace:** `Packages\PaymentGateways`

- **Razorpay** integration (card, UPI, netbanking)
- **Cash on Delivery (COD)**
- `PaymentGatewayContract` interface — drop in new gateways easily
- Webhook handling & payment status reconciliation
- Admin payment management

### `whatsapp-notifications`
> **Namespace:** `Packages\WhatsAppNotifications`

- Meta / WABA WhatsApp Business API integration
- Transactional notifications: order confirmation, shipment update, OTP
- Admin template management
- Delivery status tracking for messages

---

## 🔌 API Reference

**Base URL:** `/api/v1`  
**Auth:** Laravel Sanctum (Bearer token)

| Group | Endpoints | Auth |
|---|---|---|
| Health | `GET /health` | Public |
| Auth | `POST /auth/register`, `POST /auth/login`, `POST /auth/logout` | Public / Bearer |
| Catalog | `GET /categories`, `GET /products`, `GET /products/{slug}`, `GET /search` | Public |
| Cart | `GET /cart`, `POST /cart/items`, `PATCH /cart/items/{id}`, `DELETE /cart/items/{id}` | Session/Bearer |
| Promotions | `POST /cart/coupon`, `DELETE /cart/coupon` | Session/Bearer |
| Delivery | `GET /delivery/serviceability`, `GET /delivery/slots` | Public |
| Checkout | `POST /checkout/quote`, `POST /checkout/validate`, `POST /orders` | Bearer |
| Orders | `GET /orders`, `GET /orders/{id}` | Bearer |
| Payments | `POST /payments/initiate`, `POST /payments/webhook` | Bearer / Signed |
| Reviews | `GET /products/{id}/reviews`, `POST /products/{id}/reviews` | Public / Bearer |
| Wishlist | `GET /wishlist`, `POST /wishlist`, `DELETE /wishlist/{id}` | Bearer |
| Addresses | `GET /addresses`, `POST /addresses`, `PUT /addresses/{id}` | Bearer |
| Vendor Portal | `GET /vendor/orders`, `POST /vendor/orders/{id}/fulfill` | Bearer (Vendor) |
| Admin Catalog | `POST /admin/products`, `PUT /admin/products/{id}` | Bearer (Admin) |
| Admin Orders | `GET /admin/orders`, `PATCH /admin/orders/{id}/status` | Bearer (Admin) |
| Admin Inventory | `GET /admin/inventory`, `POST /admin/inventory/adjust` | Bearer (Admin) |

---

## 🖥 Admin Panel

Accessible at `/admin` — role-based access control applied to all routes.

| Section | Features |
|---|---|
| **Dashboard** | KPIs, recent orders, revenue summary |
| **Catalog → Products** | Create/edit products, variants, images, documents |
| **Catalog → Categories** | Nested category management |
| **Catalog → Brands & Tags** | Brand & tag management |
| **Catalog → Attributes** | Custom attribute definitions |
| **Bulk Import** | CSV upload for mass product creation |
| **Media Library** | Central media asset management |
| **Orders** | Order list, detail view, status updates, packing slips |
| **Shipments** | Shipment creation & tracking |
| **Inventory** | Stock levels per warehouse, adjustments |
| **Promotions** | Coupon & promotion rule management |
| **Marketplace** | Vendor management, payout processing |
| **B2B** | Company accounts, price lists, purchase orders |
| **Logistics** | Driver management, delivery zones |
| **Users** | Customer & staff user management |
| **Roles** | Role & permission configuration |
| **Settings** | System-wide configuration (key-value store) |
| **Audit Log** | Tamper-evident activity log |

---

## 🏪 Storefront

Customer-facing Blade UI at the root URL `/`.

| Page | Route |
|---|---|
| Home | `/` |
| Catalog / Browse | `/catalog` |
| Product Detail | `/product/{slug}` |
| Cart | `/cart` |
| Checkout | `/checkout` |
| Order Confirmation | `/order-confirmation/{orderNumber}` |
| Customer Account | `/account/*` |
| B2B Portal | `/b2b/*` |
| About / FAQ / Contact | `/about`, `/faq`, `/contact` |
| Calculators | `/calculators` |
| Knowledge Base | `/knowledge/*` |
| Sitemap | `/sitemap.xml` |

---

## 🗄 Database & Migrations

| Migration Group | Tables |
|---|---|
| Core Identity & RBAC | `users`, `roles`, `permissions`, `role_user` |
| Core Catalog | `categories`, `brands`, `products`, `product_variants`, `product_media`, `product_documents` |
| Pricing & Inventory | `inventory_items`, `inventory_movements`, `warehouses`, `stock_reservations`, `quantity_price_tiers`, `tax_classes` |
| Delivery | `delivery_zones`, `delivery_zone_pincodes`, `delivery_slots` |
| Cart & Orders | `carts`, `cart_items`, `orders`, `order_items`, `order_status_histories`, `shipments`, `payments`, `invoices` |
| Delivery Operations | `delivery_exceptions` |
| System | `system_settings`, `audit_logs`, `media_assets`, `tags` |
| Packages | Each add-on package carries its own migrations under `packages/*/database/migrations/` |

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Framework** | Laravel 13.x |
| **Language** | PHP 8.3+ |
| **Auth** | Laravel Sanctum (API token + session) |
| **Frontend** | Blade templates, Tailwind CSS, Vite |
| **Database** | MySQL / MariaDB (configurable) |
| **Queue** | Laravel Queue (database driver by default) |
| **Storage** | Laravel Filesystem (local / S3-compatible) |
| **Testing** | PHPUnit 12.x |
| **Code Style** | Laravel Pint |
| **Payments** | Razorpay SDK |
| **Notifications** | WhatsApp Business API (Meta) |

---

## 🚀 Installation

### Prerequisites
- PHP 8.3+
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8+ or MariaDB

### Quick Setup

```bash
# 1. Clone the repository
git clone https://github.com/rabinkumarpal/UniversalEcom.git
cd UniversalEcom

# 2. Run the automated setup script
composer run setup

# This will:
#  - Install PHP dependencies
#  - Copy .env.example to .env
#  - Generate application key
#  - Run all migrations
#  - Install JS dependencies
#  - Build frontend assets
```

### Manual Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Development Server

```bash
composer run dev
# or
npm run dev   # for frontend hot reload
```

### Environment Variables

Key values to configure in `.env`:

```env
APP_NAME=UniversalEcom
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=universalecom
DB_USERNAME=root
DB_PASSWORD=

# Razorpay
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=

# WhatsApp (Meta WABA)
WHATSAPP_API_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost
```

---

## 🧪 Testing

```bash
# Run all tests
composer run test

# Run a specific test file
php artisan test tests/Feature/CheckoutFlowTest.php

# Run by filter
php artisan test --filter=CheckoutFlowTest --compact
```

### Test Coverage

| Test Suite | File |
|---|---|
| API v1 (full) | `ApiV1Test.php`, `RestApiV1ExtendedTest.php` |
| Storefront flow | `StorefrontWebFlowTest.php` |
| Checkout flow | `CheckoutFlowTest.php` |
| Admin features | `AdminCoreFeaturesTest.php`, `AdminAuditWebTest.php` |
| Auth & RBAC | `AuthorizationRbacTest.php` |
| Vendor marketplace | `VendorMarketplaceTest.php`, `VendorPortalWebTest.php` |
| B2B commerce | `B2BCommerceTest.php` |
| Promotion engine | `PromotionEngineExtendedTest.php` |
| Delivery operations | `DeliveryOperationsTest.php`, `DriverLogisticsTest.php` |
| GST invoicing | `GstInvoiceTest.php` |
| Loyalty wallet | `LoyaltyWalletAddonTest.php` |
| Payment gateways | `PaymentGatewaysTest.php` |
| WhatsApp | `WhatsAppNotificationsTest.php` |
| Security | `SecurityHardeningTest.php` |
| SEO & Sitemap | `SeoAndSitemapTest.php` |
| Bulk catalog CSV | `BulkCatalogCsvTest.php` |

---

## 📁 Project Structure

```
UniversalEcom/
├── app/
│   ├── Console/Commands/       # Artisan commands (MakeAddonCommand)
│   ├── Core/
│   │   ├── Contracts/          # Core interfaces (PricingAdjustmentContract, etc.)
│   │   ├── Events/             # Domain events
│   │   ├── Registry/           # Add-on / package registry
│   │   └── Services/           # AuditService, SettingService
│   ├── Domain/                 # Domain logic (Cart, Catalog, Checkout, Delivery,
│   │   │                       #   Inventory, Orders, Payments, Pricing)
│   ├── Http/
│   │   ├── Controllers/Api/V1/ # REST API controllers
│   │   ├── Controllers/Web/    # Admin & storefront web controllers
│   │   ├── Middleware/         # Custom middleware
│   │   └── Resources/Api/V1/  # API resource transformers
│   ├── Models/                 # Eloquent models
│   └── Providers/              # Service providers
│
├── packages/                   # Portable add-on packages
│   ├── b2b-commerce/
│   ├── domain-construction/
│   ├── driver-logistics/
│   ├── invoice-gst/
│   ├── loyalty-wallet/
│   ├── payment-gateways/
│   ├── promotion-engine/
│   ├── vendor-marketplace/
│   └── whatsapp-notifications/
│
├── database/
│   ├── factories/              # Model factories for testing
│   ├── migrations/             # Core database migrations
│   └── seeders/                # Database seeders
│
├── resources/
│   ├── views/
│   │   ├── admin/              # Admin panel Blade views
│   │   ├── storefront/         # Customer storefront views
│   │   ├── layouts/            # Admin & storefront layout templates
│   │   └── content/            # Static content pages
│   ├── css/                    # Application styles
│   └── js/                     # Application JS
│
├── routes/
│   ├── web.php                 # Web routes (storefront + admin)
│   ├── api.php                 # API v1 routes
│   └── console.php             # Scheduled commands
│
└── tests/
    ├── Feature/                # Feature tests (33 test files)
    └── Unit/                   # Unit tests (Inventory, Pricing, Promotions)
```

---

## 📄 License

This project is open-sourced under the [MIT license](LICENSE).

---

<p align="center">Built with ❤️ using <a href="https://laravel.com">Laravel</a></p>
