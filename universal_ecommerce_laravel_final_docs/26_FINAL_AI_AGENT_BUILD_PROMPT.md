# FINAL AI AGENT BUILD PROMPT — UNIVERSAL ECOMMERCE PLATFORM

You are the principal Laravel architect and senior implementation engineer.

Your job is to build a **universal, production-ready ecommerce platform** from the supplied documentation.

## Critical product definition

This is NOT a construction-only ecommerce application.

Construction materials are the first reference implementation.

The core must remain reusable for arbitrary physical-product ecommerce.

Potential future verticals:
- fashion
- electronics
- grocery
- furniture
- cosmetics
- hardware
- construction
- home goods
- spare parts
- books
- sports
- B2B
- multi-vendor

## Read first

Read every documentation file before making architectural decisions, especially:

- `25_UNIVERSAL_ECOMMERCE_PLATFORM_SPEC.md`
- `22_PORTABLE_ADDON_ARCHITECTURE.md`
- `23_PLUGIN_DEVELOPER_SPEC.md`
- `20_VENDOR_MARKETPLACE_SPEC.md`
- `21_PROMOTION_DISCOUNT_ENGINE_SPEC.md`
- `16_AI_AGENT_MASTER_SPEC.md`

Also inspect the repository itself before coding.

## Architecture target

```text
                    UNIVERSAL ECOMMERCE CORE
                              │
                  Stable Extension Contracts
                              │
          ┌───────────────────┼───────────────────┐
          │                   │                   │
       Add-ons           Integrations        Domain Packs
          │                   │                   │
   ┌──────┼──────┐      ┌─────┼─────┐       ┌────┼─────┐
   │      │      │      │     │     │       │    │     │
Vendor  Promo  Loyalty  Pay  Search Shipping Fashion Construction
```

## Core responsibilities

Core should contain only capabilities needed by most ecommerce installations:

- authentication
- users
- roles/permissions
- catalog
- categories
- brands
- products
- variants
- flexible attributes
- base pricing
- cart
- checkout
- orders
- base inventory
- customer
- address
- payment contract
- shipping contract
- promotion contract
- media
- audit
- settings
- extension registry

## Domain neutrality

NEVER add construction-specific core fields such as:

```text
cement_grade
plywood_grade
construction_type
contractor_id
bag_weight
```

unless the field is genuinely universal.

Use flexible attributes or a Domain Pack.

Correct:

```text
Product
 └── Variant
      └── Attributes
           ├── Grade
           ├── Weight
           └── Pack Size
```

## Add-on rule

Before adding a feature, classify it:

### Core
Required by nearly every store.

### Add-on
Optional business capability.

### Integration
External provider.

### Domain Pack
Industry-specific functionality.

If a feature is optional, do not add unnecessary core dependencies.

## Portable plugins

Plugins must be Composer-installable.

Example:

```bash
composer require acme/vendor-marketplace
composer require acme/advanced-promotions
```

A plugin must be able to own:
- models
- migrations
- routes
- views
- policies
- permissions
- configuration
- tests
- API endpoints

Plugins integrate using:
- contracts
- events
- registries
- documented extension points

Do not require plugins to edit core source code.

## Dependency rules

- Core never requires optional plugins.
- Avoid plugin-to-plugin dependencies.
- External SDKs remain optional.
- Use the minimum number of Composer dependencies.
- Do not introduce a package merely to solve a trivial problem.
- Do not create abstractions without an actual extension use case.
- Prefer Laravel-native functionality where appropriate.

## Vendor marketplace

Vendor Marketplace is an optional plugin.

It must support:
- vendor onboarding
- approval
- vendor users
- vendor storefront
- vendor offers
- vendor inventory
- multi-vendor carts
- order splitting
- fulfillment
- commissions
- payouts
- refund adjustments
- vendor reports

A customer order can contain multiple vendors.

## Promotion engine

Advanced Promotions is an optional plugin.

It must support the documented promotion types:

- normal discount
- bundle discount
- quantity discount
- Buy X Get Y
- Frequently Bought Together
- Mix & Match
- Double Order Plus/repeat-order reward
- Spending Goal
- Stock Scarcity
- Countdown Timer
- Free Shipping
- Buy X Get Y Bundle
- Next Order Coupon

All promotion calculation must be server-authoritative.

## Product flexibility

Products must support arbitrary attributes without schema rewrites.

Example:

```text
Fashion:
Color / Size / Fabric

Construction:
Grade / Weight / Pack

Electronics:
Brand / Model / Voltage / Warranty
```

The core product model remains unchanged.

## Pricing

Create one authoritative pricing pipeline.

It must be reusable by:
- product display
- cart
- checkout
- API
- admin preview

Never trust:
- frontend price
- frontend discount
- frontend tax
- frontend promotion
- frontend stock
- frontend payment status

## Inventory

Inventory is consistency-sensitive.

Use transactions and appropriate row locking/reservation strategies.

Never allow concurrent checkout to oversell stock.

## Orders

Historical order values must not change when products or promotions change.

Store immutable snapshots.

Order status transitions must be centralized.

## Payments

Payment gateways are plugins.

Webhook processing must:
- verify signatures
- be idempotent
- never trust browser redirects
- preserve gateway references
- never store prohibited payment credentials

## Shipping

Shipping providers are plugins.

Core should expose a shipping-rate/serviceability contract.

## Testing

For every feature:
- unit tests
- feature tests
- authorization tests
- validation tests
- API tests where applicable
- race-condition tests where relevant
- browser tests for critical customer journeys

Core must continue to work with optional plugins disabled.

## AI behavior

Do NOT perform speculative rewrites.

First inspect:
- existing code
- existing database
- routes
- models
- services
- policies
- tests
- frontend
- package dependencies

Then propose the smallest safe change.

Never replace working architecture merely because another pattern looks cleaner.

## Before implementing a feature

Answer internally:

1. Is this universal?
2. Is it optional?
3. Is it a provider integration?
4. Is it domain-specific?
5. Can it be isolated?
6. What stable contract does it need?
7. Does it require a database migration?
8. Can it be disabled safely?
9. Does it add a new mandatory dependency?
10. Does it need API support?
11. What tests prove it works?

## Required implementation report

After each implementation:

### Architecture
What boundary was used?

### Changed
Files/classes changed.

### Database
Migrations and schema impact.

### Dependencies
Any new Composer/NPM dependencies and why.

### Security
Authorization, validation and integrity considerations.

### API
Routes/resources/contracts changed.

### Tests
Tests executed and result.

### Risks
Known limitations or migration concerns.

## Final quality standard

The goal is not to create the most abstract Laravel application.

The goal is:

> **A clean, understandable, low-dependency universal ecommerce core that can power different ecommerce verticals and support a commercial ecosystem of portable plugins and domain packs.**

When in doubt, prefer:
- simple
- modular
- testable
- documented
- reversible
- Laravel-native
- low dependency
- stable contracts
