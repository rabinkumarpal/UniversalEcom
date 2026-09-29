# Plugin Developer Specification

## Goal

Allow an independent developer to build an add-on and distribute it as a Composer package with minimal knowledge of the platform internals.

## Package checklist

```text
my-addon/
├── composer.json
├── README.md
├── CHANGELOG.md
├── LICENSE
├── src/
│   ├── MyAddonServiceProvider.php
│   ├── MyAddon.php
│   ├── Contracts/
│   ├── Actions/
│   ├── Models/
│   ├── Policies/
│   ├── Http/
│   ├── Events/
│   ├── Listeners/
│   └── Support/
├── config/
├── database/
│   └── migrations/
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   ├── views/
│   ├── lang/
│   └── assets/
└── tests/
```

## Plugin must not

- edit vendor application code
- edit core controllers directly
- edit core Blade files directly
- require undocumented internal classes
- assume a specific database driver unless documented
- hard-code platform URLs
- store secrets in source code
- use global mutable state
- make external network requests during every checkout
- depend on another commercial plugin unless explicitly documented

## Plugin should

- use contracts
- use events
- use service providers
- use configuration
- use policies
- use migrations
- use namespaced classes
- provide tests
- provide upgrade notes
- provide uninstall/disable behavior
- document dependencies

## Admin integration

A plugin may register:

```text
Admin menu item
Permission
Dashboard widget
Settings page
```

Example:

```text
Extensions
 ├── Vendor Marketplace
 ├── Promotions
 ├── Loyalty
 └── Advanced Search
```

## Plugin settings

Settings should be namespaced:

```text
addon.vendor_marketplace.enabled
addon.vendor_marketplace.commission.default_rate
```

Prefer a settings service over scattering configuration reads.

## Plugin API

Public plugin APIs must be versioned and documented.

Example:

```text
/api/v1/vendor/...
```

Do not expose internal implementation classes as public APIs.

## Plugin testing

Every plugin should test:
- installation
- migrations
- service provider registration
- authorization
- main feature behavior
- API
- disabled state
- upgrade path
- failure behavior

## Example commercial products

Potential sellable packages:

1. Vendor Marketplace
2. Advanced Promotion Engine
3. Loyalty & Wallet
4. B2B/Contractor Pricing
5. Advanced Delivery
6. Driver/POD
7. GST/Invoice Integration
8. WhatsApp Notifications
9. SMS Gateway Pack
10. Meilisearch Integration
11. AI Product Enrichment
12. AI Recommendation Engine
13. POS Integration
14. ERP Connector
15. Advanced Analytics

Each can be developed and sold independently if its contract boundaries are respected.
