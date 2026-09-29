# AI Agent Prompt — Audit and Convert the Project to Portable Add-on Architecture

You are the senior Laravel platform architect.

The repository contains a construction-material ecommerce platform specification. Read ALL documentation files before making architectural changes.

## Primary objective

Audit the current specification/code and ensure that the platform uses a **minimum-dependency, portable add-on architecture**.

The final system must support:

1. a lean ecommerce core;
2. optional feature modules;
3. installable Composer packages;
4. independent plugin development;
5. commercial plugin distribution;
6. plugin enable/disable lifecycle;
7. plugin version compatibility;
8. minimal coupling between plugins;
9. stable extension contracts;
10. vendor and promotion features without making every installation depend on them.

## First: audit, do not code

Inspect:
- docs
- repository structure
- composer.json
- routes
- migrations
- models
- services/actions
- events/listeners
- policies
- admin navigation
- tests
- configuration

Produce a table:

| Area | Current Design | Core or Add-on | Coupling Risk | Recommended Change |
|---|---|---|---|---|

Do not invent problems. Identify evidence from the repository.

## Target architecture

```text
                 Ecommerce Core
                       │
            Stable Extension Contracts
                       │
       ┌───────────────┼────────────────┐
       │               │                │
   Promotions       Vendors         Integrations
       │               │                │
  optional pkg     optional pkg     optional pkg
```

## Core must remain small

Mandatory:
- identity
- catalog
- variants
- base pricing
- cart
- checkout
- order
- payment abstraction
- base inventory
- delivery abstraction
- settings
- permissions
- audit
- extension registry

Everything else must be evaluated for add-on suitability.

## Required extension mechanisms

Use stable interfaces/contracts for:
- payment gateways
- shipping providers
- tax calculators
- search providers
- pricing adjustments
- promotion providers
- notification channels
- vendor/marketplace capabilities
- order actions
- dashboard widgets

Use events for lifecycle integration.

Avoid direct plugin-to-plugin coupling.

## Vendor requirement

Vendor Marketplace must be implementable as an optional package.

It may own:
- vendors
- vendor users
- vendor offers
- vendor inventory
- vendor orders
- commissions
- payouts

The core must not contain vendor-specific columns everywhere.

## Promotion requirement

Advanced Promotion Engine must be optional.

It may own:
- promotions
- conditions
- benefits
- bundles
- mix-match groups
- rewards
- usage records

Core checkout should consume a pricing-adjustment contract rather than know every promotion type.

## Dependency rules

Enforce:

- Core never requires optional plugins.
- Plugin depends on core contracts.
- Plugin-to-plugin dependency is prohibited unless unavoidable.
- Integration SDKs are optional.
- No unnecessary Composer packages.
- No undocumented internal-class dependencies.
- No direct edits to core source from plugins.

## Database rules

Plugin owns plugin tables.

Do not create:

```text
orders.vendor_id
products.promotion_id
products.plugin_x_id
```

just to support extensions.

Prefer extension tables, contracts, events, or plugin-owned models.

Historical commerce data must remain immutable.

## Commercial plugin requirement

Design so a developer can create:

```text
acme/advanced-promotions
acme/vendor-marketplace
acme/loyalty
acme/razorpay
acme/whatsapp
```

and distribute each package independently.

Each package must contain:
- Composer metadata
- service provider
- migrations
- config
- routes
- permissions
- policies
- tests
- documentation
- semantic versioning
- compatibility declaration

## License requirement

If commercial licensing is implemented:
- centralize license/entitlement logic
- do not put licensing checks throughout business logic
- do not block core orders because an external license server is temporarily unavailable unless explicitly required by the license model
- fail safely

## Required deliverables

After the audit, produce:

### A. Architecture map
Show Core vs Add-ons.

### B. Dependency graph
Show every dependency and identify unnecessary coupling.

### C. Extension contract list
Define each stable interface/event.

### D. Plugin boundary map
For every current feature, classify:
- Core
- Add-on
- Integration
- CMS/content
- Infrastructure

### E. Database ownership map
Show which tables belong to core and each plugin.

### F. Migration strategy
Explain how to move current tightly coupled features into packages without breaking data.

### G. Composer strategy
Define package names, version constraints and optional dependencies.

### H. Plugin developer guide
Explain how an external developer can create a plugin.

### I. Test strategy
Prove:
- core works with plugins disabled
- plugin works when enabled
- plugin isolation works
- upgrades are safe

### J. Implementation plan
Break work into small phases.

## Non-negotiable

Do not perform a large rewrite merely to make the architecture look clean.

Preserve working functionality.

Improve boundaries incrementally.

Prefer a boring, understandable modular monolith over microservices.

The goal is not maximum abstraction. The goal is **portable, sellable, low-dependency Laravel add-ons with stable contracts**.
