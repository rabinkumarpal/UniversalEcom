# Portable Add-on / Plugin Architecture

## Objective

The platform must support optional, portable add-ons so that:
1. the core ecommerce application remains lean;
2. optional features can be installed/enabled independently;
3. internal teams can build add-ons without modifying core code unnecessarily;
4. third-party developers can build and sell compatible Laravel packages;
5. add-ons can be distributed privately, publicly, or commercially;
6. an add-on can be removed/disabled without corrupting core commerce data.

The architecture should favor **minimum dependencies and stable contracts**.

## Core principle

Use:

```text
Core Platform
    ↓
Stable Extension Contracts
    ↓
Plugin/Add-on
    ↓
Optional Dependencies
```

Do NOT use:

```text
Core
  ↓
Every feature hard-coded into every controller/model
  ↓
One giant application
```

## What belongs in Core

Only capabilities required for the base ecommerce product should be mandatory:

- identity/authentication
- users/roles/permissions
- catalog
- product variants
- core pricing
- cart
- checkout
- orders
- payments abstraction
- basic inventory
- basic delivery/serviceability abstraction
- notifications abstraction
- core admin
- settings
- audit/logging
- extension registry/contracts

## What should normally be Add-ons

Examples:

- Advanced Promotions
- Vendor Marketplace
- Wallet/Cashback
- Loyalty
- Advanced Search
- Meilisearch/Typesense integration
- Advanced Delivery
- Driver Management
- Route Optimization
- GST/invoicing integrations
- Razorpay integration
- Stripe integration
- WhatsApp notifications
- SMS provider
- Email provider
- Product Reviews
- Wishlist
- Subscription/Repeat Orders
- B2B pricing
- Purchase Orders
- Advanced Reports
- AI recommendations
- AI product enrichment
- Import/export packs
- POS integration
- ERP integrations
- Marketplace connectors

A feature may start as core during initial development and later be extracted into an add-on when the contract is stable.

## Extension contract

Create a small stable extension layer.

Example concepts:

```php
interface EcommerceAddon
{
    public function id(): string;

    public function name(): string;

    public function version(): string;

    public function boot(AddonContext $context): void;
}
```

The exact implementation may differ, but the contract must remain intentionally small.

## Add-on manifest

Each add-on should declare metadata:

```json
{
  "id": "vendor-marketplace",
  "name": "Vendor Marketplace",
  "version": "1.0.0",
  "requires": {
    "platform": "^1.0",
    "php": "^8.4",
    "laravel": "^13.0"
  },
  "optional_dependencies": [],
  "permissions": [],
  "routes": true,
  "migrations": true,
  "settings": true,
  "api": true
}
```

Use Composer package metadata as the primary technical dependency mechanism. A separate application-level manifest may be used for runtime capabilities.

## Dependency policy

### Required
An add-on may require only:
- PHP version range
- Laravel version range
- core platform contract/version
- absolutely necessary Composer packages

### Optional
Integrations should remain optional.

Example:

```text
Core
 └── PaymentContract

Razorpay Add-on
 └── requires Razorpay SDK

Stripe Add-on
 └── requires Stripe SDK
```

Do not install every payment SDK into the base application.

## Dependency rules

1. Core must never require an optional add-on.
2. Add-ons may depend on core contracts.
3. Avoid add-on-to-add-on dependencies.
4. If an add-on must integrate with another add-on, use a contract/capability interface.
5. Never depend on private classes of another add-on.
6. Avoid modifying core migrations.
7. Avoid editing core controllers.
8. Avoid replacing core models when an event/contract extension is possible.

## Composer packaging

Recommended package:

```text
packages/
  vendor/
    ecommerce-addon-name/
      composer.json
      src/
      config/
      database/
      routes/
      resources/
      lang/
      tests/
      README.md
      CHANGELOG.md
      LICENSE
```

For commercial distribution, the package can live in a private Composer repository.

Example:

```json
{
  "name": "acme/vendor-marketplace",
  "type": "library",
  "autoload": {
    "psr-4": {
      "Acme\\VendorMarketplace\\": "src/"
    }
  }
}
```

The package should use a Laravel service provider and Laravel package auto-discovery where appropriate.

## Service provider responsibilities

A plugin provider may:
- register bindings
- merge config
- register routes
- register migrations
- register translations
- register views
- register commands
- register event listeners
- register policies
- register API resources

Keep provider boot logic lightweight.

## Extension points

Core should expose extension points through:

### Events
Examples:
- ProductCreated
- ProductUpdated
- CartCalculated
- CheckoutValidated
- OrderPlaced
- PaymentCaptured
- OrderDelivered
- OrderCancelled
- RefundCreated

### Contracts
Examples:
- PricingAdjustmentContract
- PaymentGatewayContract
- ShippingRateContract
- SearchProviderContract
- TaxCalculatorContract
- NotificationChannelContract
- OrderActionContract

### Registries
Examples:
- PaymentGatewayRegistry
- ShippingProviderRegistry
- PricingAdjustmentRegistry
- SearchProviderRegistry
- AddonRegistry

### Policies/capabilities
An add-on may register:
- permissions
- admin menu entries
- dashboard widgets
- settings pages
- API routes
- customer account sections

## Promotion add-on example

The advanced promotion engine should be able to work as:

```text
Core Pricing
     ↓
Promotion Contract
     ↓
Promotion Add-on
     ├── Normal Discount
     ├── Quantity Discount
     ├── Bundle
     ├── Buy X Get Y
     ├── Mix & Match
     ├── Spending Goal
     ├── Free Shipping
     └── Next Order Coupon
```

Core checkout should not know every promotion type.

It should ask the registered promotion capability for applicable adjustments.

## Vendor add-on example

```text
Core Catalog
     ↓
Vendor Contract
     ↓
Vendor Marketplace Add-on
     ├── Vendors
     ├── Vendor Offers
     ├── Vendor Inventory
     ├── Vendor Orders
     ├── Commissions
     └── Payouts
```

Core order infrastructure should expose hooks/contracts for order splitting and fulfillment without hard-coding every vendor rule.

## Database isolation

An add-on owns its own tables.

Example:

```text
Core:
orders
order_items
products
product_variants

Vendor Add-on:
vendors
vendor_users
vendor_offers
vendor_inventory
vendor_orders
vendor_commissions
vendor_payouts

Promotion Add-on:
promotions
promotion_rules
promotion_benefits
promotion_usages
promotion_rewards
```

Do not casually add dozens of nullable plugin columns to core tables.

If an add-on needs a core relationship, prefer:
- separate relation table
- extension metadata table
- documented contract
- event-driven projection

## Add-on migrations

Each add-on owns and publishes its migrations.

Rules:
- migrations must be namespaced by package ownership
- never alter core tables destructively without a documented migration contract
- installation must be repeatable
- migrations must be tested
- uninstall must not silently delete business history

## Install lifecycle

```text
Discover
  ↓
Compatibility Check
  ↓
Dependency Check
  ↓
Install
  ↓
Migrate
  ↓
Register
  ↓
Configure
  ↓
Enable
```

## Disable lifecycle

```text
Disable
  ↓
Stop new behavior
  ↓
Preserve data
```

Disabling an add-on must normally preserve its database records.

## Uninstall lifecycle

Uninstall should be explicit and potentially destructive.

Require:
- admin confirmation
- dependency check
- data/export warning
- documented data retention behavior

Never automatically delete financial/order history.

## Version compatibility

Use semantic versioning.

Example:

```text
Addon 2.1.0
requires:
platform ^1.4
php ^8.4
laravel ^13.0
```

Breaking core extension contracts require a major platform version.

## Commercial plugin model

The architecture should allow:
- free plugins
- paid plugins
- private plugins
- customer-specific plugins
- licensed plugins
- subscription plugins

A plugin can expose:

```text
License
  ↓
Compatibility
  ↓
Activation
  ↓
Feature Entitlement
```

Do not place license checks throughout business logic. Centralize licensing/entitlement checks.

## Licensing security

Commercial licensing can verify:
- license key
- package ID
- platform version
- expiry
- activation count
- allowed domain/installation

The core application should continue to function safely if a licensing server is temporarily unavailable according to the chosen license policy.

Never make payment/order data depend on a live licensing API call.

## Marketplace readiness

A future plugin marketplace can provide:
- plugin catalog
- versions
- compatibility
- screenshots
- documentation
- changelog
- license
- pricing
- installation instructions

Do not make the ecommerce application depend on the marketplace to run.

## AI-agent rule

When implementing a new feature, first decide:

```text
Is this mandatory to run the base ecommerce product?
        │
       yes → Core
        │
        no
        ↓
Can it be isolated behind a stable contract?
        │
       yes → Add-on
        │
       no
        ↓
Improve the extension boundary before implementation.
```

## Definition of done

The add-on architecture is acceptable when:
- optional features do not create mandatory dependencies
- add-ons have clear ownership boundaries
- core contracts are small
- plugin tables are isolated
- plugin authorization is isolated
- install/disable lifecycle is defined
- plugin versions are compatible/versioned
- automated tests cover install and runtime behavior
- core tests pass with the plugin disabled
- commercial licensing can be added without polluting domain logic
