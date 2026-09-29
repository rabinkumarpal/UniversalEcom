# Admin and Operations Specification

## Dashboard
Widgets:
- today's orders
- revenue
- pending payments
- orders to pick
- packed orders
- out-for-delivery
- delivered
- refunds
- low-stock products
- top products
- failed payments
- delivery exceptions

## Catalog management
Product editor:
- basic information
- category
- brand
- variants
- attributes
- pricing
- quantity tiers
- inventory
- media
- documents
- SEO
- related products
- status/publishing

## Bulk import
CSV import should support:
- SKU
- product
- variant
- category
- brand
- price
- MRP
- tax class
- stock
- attributes

Import flow:
upload → parse → validate → preview → commit → report.

Never partially mutate data without a clear transaction/recovery strategy.

## Order management
Order detail should show:
- customer
- addresses
- items
- price breakdown
- payment
- fulfillment
- shipment
- status history
- notes
- audit history

Admin actions must be permission controlled.

## Inventory
Inventory page:
- warehouse
- SKU
- on hand
- reserved
- available
- reorder level
- last movement

Stock adjustment requires:
- reason
- quantity
- reference
- authorized user

## CMS
Homepage should be component-driven:
- hero banner
- category grid
- deal carousel
- product collection
- promotional banner
- trust/guarantee section
- testimonials/video
- brand strip
- content/FAQ

Avoid hard-coding homepage content in Blade.

## Settings
- business details
- tax
- currency
- order rules
- delivery rules
- payment gateways
- notification providers
- SEO defaults
- storage
- feature flags
