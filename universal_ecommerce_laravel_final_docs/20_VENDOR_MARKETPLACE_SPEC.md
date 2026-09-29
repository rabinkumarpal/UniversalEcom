# Vendor Marketplace Specification

## Purpose
Add optional multi-vendor marketplace capability without rebuilding the first-party ecommerce core.

## Vendor lifecycle
```text
Draft → Application Submitted → Under Review → Documents Verified → Approved → Active
                                                                    ↓
                                                              Suspended/Blocked
```

## Vendor capabilities
- Business/vendor profile
- Onboarding and approval
- KYC/business documents
- Vendor users and roles
- Vendor storefront
- Vendor product offers
- Vendor-specific SKU, price and inventory
- Vendor orders and fulfillment
- Vendor commissions
- Vendor payout statements
- Vendor reports
- Vendor suspension
- Vendor ratings/reviews
- Vendor API access

## Recommended product model
Keep the platform catalog normalized:

```text
products
  ↓
product_variants
  ↓
vendor_product_offers
```

A vendor normally sells an existing platform product/variant rather than creating an isolated duplicate product.

Example:

```text
Platform Product: UltraTech PPC Cement
Variant: 50 Kg Bag

Vendor A → SKU A-001 → ₹430 → Stock 500
Vendor B → SKU B-009 → ₹425 → Stock 100
```

## Multi-vendor cart
A single customer cart may contain products from several vendors.

```text
Cart
├── Vendor A
│   ├── Cement × 20
│   └── Paint × 5
└── Vendor B
    └── Pipes × 10
```

The checkout remains one customer checkout.

## Order splitting
Customer sees one platform order:

```text
ORD-100001
```

Internally:

```text
ORD-100001
├── Vendor Order VO-100001
└── Vendor Order VO-100002
```

This allows independent fulfillment and payout while preserving one customer-facing order.

## Vendor inventory
Vendor inventory must be isolated by:

```text
vendor_id
warehouse_id
product_variant_id
```

Never use one shared stock record for multiple vendors.

Inventory reservation must remain transaction-safe.

## Vendor pricing
Vendor offers can have:
- selling price
- MRP
- quantity tiers
- vendor promotions
- customer-group price if enabled

All final prices must pass through the central pricing engine. Never trust frontend-submitted prices.

## Commission
Support:
- percentage
- fixed fee
- category-based commission
- vendor-specific commission
- product-specific override

Commission must be calculated from immutable order snapshots.

## Payouts
Recommended lifecycle:

```text
Pending → Eligible → Statement Generated → Approved → Processing → Paid
```

A payout statement should contain:
- order
- order item
- gross amount
- discounts
- returns
- commission
- platform fees
- shipping adjustments
- net payable

Use a ledger/reconciliation model rather than only a mutable vendor balance.

## Returns/refunds
Customers interact with the platform for returns/refunds.

When a refund occurs:
- customer refund is recorded
- vendor commission is reversed where applicable
- vendor payout is adjusted if already paid
- all financial changes remain auditable

## Vendor dashboard
```text
Dashboard
Catalog
  Products
  Offers
  Import
Inventory
Orders
Fulfillment
Returns
Payouts
Reports
Storefront
Documents
Settings
```

## Admin marketplace
```text
Vendors
  All Vendors
  Applications
  Pending Approval
  Active
  Suspended
  Documents

Marketplace
  Vendor Offers
  Product Approvals
  Commissions
  Payouts
  Disputes
```

## Vendor isolation
Every vendor query must be server-side scoped by vendor ownership and protected by Policies/permissions.

A vendor user must never be able to access another vendor's:
- products
- inventory
- orders
- customers' private information
- payouts
- reports
- documents

## Vendor permissions
- vendors.view
- vendors.create
- vendors.approve
- vendors.suspend
- vendors.update
- vendor_users.manage
- vendor_products.view
- vendor_products.create
- vendor_products.update
- vendor_products.submit
- vendor_products.publish
- vendor_inventory.view
- vendor_inventory.update
- vendor_orders.view
- vendor_orders.fulfill
- vendor_payouts.view
- vendor_payouts.request
- vendor_reports.view

## Rollout
### Phase 1
Vendor schema, roles, onboarding, approval, profile.

### Phase 2
Vendor offers, inventory, multi-vendor cart, order splitting.

### Phase 3
Commission, payout statements, refund adjustments.

### Phase 4
Vendor analytics, ratings, promotions, advanced fulfillment.

## Definition of done
Vendor functionality requires automated tests for:
- vendor isolation
- authorization
- mixed-vendor cart
- order splitting
- vendor stock reservation
- commission calculation
- refund/commission reversal
- payout calculation
- vendor suspension
