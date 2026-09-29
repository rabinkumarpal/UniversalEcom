# Roles and Permissions

## Roles
### Super Admin
Full system control.

### Catalog Manager
Products, variants, categories, brands, attributes, media and catalog imports.

### Pricing Manager
Prices, quantity tiers, promotions and coupons.

### Inventory Manager
Warehouses, stock adjustments, reservations, stock transfers and inventory reports.

### Order Manager
Orders, cancellations, refunds, invoices and fulfillment.

### Delivery Manager
Zones, slots, drivers, assignments and proof of delivery.

### Customer Support
Customer profiles, order support, returns and communication. No unrestricted financial configuration.

### Content Manager
Homepage sections, banners, pages, FAQs, blogs and SEO metadata.

### Customer
Own account, addresses, orders, wishlist, wallet and profile.

## Permission naming convention
Use granular permissions:
- products.view
- products.create
- products.update
- products.delete
- products.publish
- prices.view
- prices.update
- inventory.view
- inventory.adjust
- orders.view
- orders.update
- orders.cancel
- refunds.create
- delivery.manage
- customers.view
- customers.update
- reports.view
- settings.manage
- audit.view

## Security principle
Never authorize based only on frontend visibility. Every protected action must be authorized server-side.


## Vendor Roles
### Vendor Owner
Manages the vendor profile, users, offers, inventory, orders, fulfillment and payout reports.

### Vendor Staff
Performs only the vendor operations granted by assigned permissions.

Vendor users are always scoped to their own vendor.
