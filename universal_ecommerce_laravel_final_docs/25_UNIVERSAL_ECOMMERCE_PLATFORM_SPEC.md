# Universal Ecommerce Platform Specification

## 1. Vision

Build a reusable, domain-neutral Laravel ecommerce platform that can power many types of ecommerce businesses without rebuilding the core.

The construction-material storefront is the first reference implementation, not the definition of the core product.

The same platform should be able to support:
- fashion
- electronics
- grocery
- furniture
- hardware
- construction materials
- cosmetics
- home goods
- spare parts
- sports products
- books
- B2B catalogs
- multi-vendor marketplaces

## 2. Core principle

```text
Universal Ecommerce Core
        +
Stable Extension Contracts
        +
Optional Add-ons
        +
Domain Packs
        +
Provider Integrations
```

Do not make the core construction-specific.

## 3. Core vs domain

### Core understands

- products
- variants
- attributes
- categories
- brands
- prices
- taxes
- inventory
- warehouses
- customers
- addresses
- carts
- checkout
- orders
- payments through contracts
- shipping through contracts
- promotions through contracts
- media
- reviews
- notifications through contracts
- permissions
- settings
- audit
- extension registry

### Core must NOT hard-code

- cement
- plywood
- paint
- clothing
- shoe sizes
- grocery expiry rules
- construction grades
- fabric
- electronics wattage
- any industry-specific field

Industry-specific behavior belongs in a Domain Pack or Add-on.

## 4. Product modeling

Use a generic product model:

```text
Product
 ├── Category
 ├── Brand
 ├── Media
 ├── Documents
 └── Variants
       └── Attributes
```

Attributes should support:
- text
- number
- boolean
- select
- multi-select
- measurement
- date where appropriate

Example:

```text
T-Shirt
  Color = Black
  Size = XL
  Material = Cotton
```

```text
Cement
  Grade = PPC
  Weight = 50 KG
  Pack = Bag
```

Both use the same product system.

## 5. Attribute system

Recommended entities:

```text
attribute_definitions
attribute_values
product_attribute_values
variant_attribute_values
attribute_groups
```

Avoid adding domain-specific columns to `products`.

## 6. Units and measurements

Use an extensible measurement model:

```text
Unit
 ├── piece
 ├── kg
 ├── gram
 ├── litre
 ├── metre
 ├── square_metre
 └── custom configured units
```

A future measurement conversion service may support:

```text
1 tonne = 1000 kg
1 box = 12 pieces
```

Conversions must be explicitly configured, not guessed.

## 7. Inventory

Generic inventory supports:
- quantity
- reserved quantity
- available quantity
- warehouse
- stock movement
- reservation
- transfer
- adjustment

Domain packs may add specialized behavior such as:
- batch/lot tracking
- expiry
- serial numbers
- variants with complex measurement

These must be optional capabilities.

## 8. Orders

Orders remain domain-neutral.

```text
Order
 ├── Customer
 ├── Address Snapshot
 ├── Order Items
 ├── Payment
 ├── Shipment
 └── Adjustments
```

An order item stores historical snapshots:
- product name
- variant name
- SKU
- unit
- price
- quantity
- discount
- tax
- total

## 9. Marketplace

Marketplace is an optional add-on.

Core should support extension points for:
- vendor
- vendor offer
- order splitting
- vendor fulfillment
- commission
- payout

Do not force `vendor_id` into every core table.

## 10. Promotion engine

Advanced promotions are an optional capability.

Generic promotion types:
- percentage discount
- fixed discount
- quantity discount
- bundle
- Buy X Get Y
- Mix & Match
- free shipping
- spending goal
- countdown
- loyalty reward
- next-order coupon
- recommendations

Construction, fashion and grocery can all use the same promotion engine.

## 11. B2B

B2B is an add-on.

Potential capabilities:
- companies
- company users
- customer groups
- price lists
- negotiated prices
- purchase orders
- credit limits
- approval workflows
- tax/business fields

Do not pollute the basic customer model with B2B-only requirements.

## 12. Domain Packs

A Domain Pack is a reusable collection of configuration, attributes, workflows and optional extensions for an industry.

Example:

```text
Construction Pack
├── construction categories
├── grade attributes
├── construction units
├── technical specifications
├── project/site concepts
└── construction content templates
```

Fashion Pack:

```text
Fashion Pack
├── size
├── color
├── material
├── fit
├── gender/category taxonomy
└── apparel attributes
```

Electronics Pack:

```text
Electronics Pack
├── model
├── warranty
├── voltage
├── power
├── compatibility
└── technical specifications
```

A Domain Pack should use public extension contracts and configuration instead of modifying core code.

## 13. Integrations

External providers should be packaged independently:

```text
Payment
├── Razorpay
├── Stripe
├── PayPal
└── Other providers

Shipping
├── Provider A
├── Provider B
└── Custom carrier

Search
├── MySQL
├── Meilisearch
└── Typesense

Notifications
├── Email
├── SMS
├── WhatsApp
└── Push
```

Only the selected provider package should be installed where possible.

## 14. Portable plugin architecture

Every optional plugin should be installable as a Composer package.

Example:

```bash
composer require acme/vendor-marketplace
composer require acme/advanced-promotions
composer require acme/loyalty
composer require acme/razorpay
```

Plugins should have:
- service provider
- contracts
- migrations
- configuration
- permissions
- policies
- routes
- tests
- documentation
- version compatibility

## 15. Plugin isolation

A plugin owns its tables.

Example:

```text
Core
  products
  product_variants
  orders
  order_items

Vendor plugin
  vendors
  vendor_offers
  vendor_inventory
  vendor_orders
  vendor_payouts

Promotion plugin
  promotions
  promotion_rules
  promotion_rewards
  promotion_usages
```

Avoid dozens of nullable plugin fields in core tables.

## 16. Extension mechanisms

Provide:
- events
- contracts/interfaces
- registries
- policies
- configuration
- dashboard extension points
- menu extension points
- API extension points

Prefer events/contracts over direct core modification.

## 17. Dependency philosophy

### Core dependencies
Keep minimal and stable.

### Plugin dependencies
A plugin should bring only what it needs.

### Integration dependencies
Provider SDKs should be optional.

### Plugin-to-plugin dependencies
Avoid unless unavoidable.

If integration is needed, depend on a stable contract rather than another plugin's private implementation.

## 18. Future-proofing rule

Future-proof does NOT mean supporting every possible feature today.

It means:

> New functionality can be added without redesigning the core data model or breaking existing commerce behavior.

Do not over-engineer the core with speculative abstractions.

## 19. Multi-store possibility

The architecture should leave room for future multi-store support.

Potential future model:

```text
Platform
 ├── Store A
 │    ├── Catalog
 │    ├── Pricing
 │    └── Orders
 └── Store B
      ├── Catalog
      ├── Pricing
      └── Orders
```

This should be introduced only if required. Do not force multi-tenancy into the initial core without a product requirement.

## 20. SaaS possibility

The platform can later become SaaS:

```text
SaaS Platform
 ├── Store/Tenant
 │     ├── Users
 │     ├── Catalog
 │     ├── Orders
 │     └── Plugins
 └── Platform Admin
```

Design IDs and ownership boundaries carefully so tenant scoping can be introduced without unsafe global queries.

## 21. API-first compatibility

Every core domain should have a service/application layer that can be consumed by:
- Blade controllers
- API controllers
- jobs
- CLI commands
- future mobile applications

Do not duplicate business rules between web and API.

## 22. Testing strategy

Core tests must pass when all optional plugins are disabled.

Each plugin must test:
- installation
- compatibility
- authorization
- runtime behavior
- API
- migrations
- disable behavior
- upgrade behavior

Integration tests should verify contracts rather than private implementation details.

## 23. AI-agent architecture rule

When an AI coding agent encounters a new feature:

```text
Is it required for every ecommerce installation?
        │
       YES
        ↓
      CORE

Is it optional business functionality?
        │
       YES
        ↓
      ADD-ON

Is it a third-party provider?
        │
       YES
        ↓
   INTEGRATION PLUGIN

Is it industry-specific?
        │
       YES
        ↓
    DOMAIN PACK
```

The AI must not turn a domain-specific feature into a core field or workflow without justification.

## 24. Reference implementation

Construction ecommerce is the first reference domain.

It should demonstrate:
- generic catalog
- flexible attributes
- construction domain attributes
- vendor marketplace
- quantity pricing
- promotions
- local delivery
- technical product documents
- project/site features

The reference implementation proves the core can handle a specialized ecommerce vertical without changing its fundamental architecture.

## 25. Productization strategy

The resulting platform can be distributed as:

```text
Universal Ecommerce Core
        +
Plugin Marketplace
        +
Domain Packs
        +
Provider Integrations
```

Possible commercial products:
- Vendor Marketplace
- Advanced Promotions
- B2B Commerce
- Loyalty
- Advanced Delivery
- Driver Management
- WhatsApp
- Payment gateways
- Search integrations
- AI tools
- Fashion Pack
- Construction Pack
- Electronics Pack

## 26. Final architecture principle

Do not build a "construction ecommerce application that can maybe become generic."

Build:

> **A universal ecommerce platform whose first reference implementation happens to be construction materials.**
