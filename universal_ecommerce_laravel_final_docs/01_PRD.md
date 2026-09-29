# PRD — Construction Materials Ecommerce Platform

## 1. Product vision
Build a high-performance ecommerce platform for construction, renovation, electrical, plumbing, sanitary, hardware, tools and related materials. Customers should be able to discover products, compare variants, obtain quantity/bulk pricing, place orders, select delivery options, track fulfillment, and manage repeat purchases.

The system must work for:
- individual homeowners
- contractors
- builders
- interior designers
- site engineers
- procurement teams
- small businesses

## 2. Primary goals
1. Make construction-material purchasing as easy as mainstream ecommerce.
2. Support construction-specific product data and units.
3. Support bulk/quantity pricing.
4. Support local delivery zones and delivery slots.
5. Support B2C and lightweight B2B purchasing.
6. Provide a robust admin/operations system.
7. Be API-first enough for a future mobile application.
8. Be SEO-friendly for category, brand, product and educational pages.
9. Provide reliable inventory, pricing and order state management.

## 3. Non-goals for MVP
- Full marketplace with independent sellers
- Complex accounting/ERP replacement
- Route optimization using advanced ML
- Multi-country tax engine
- Warehouse robotics
- Enterprise procurement contracts

These can be future phases.

## 4. Personas
### Customer
Browses products, compares options, adds quantities, checks delivery, pays, tracks order.

### Contractor / Builder
Needs bulk quantities, repeat orders, project/site addresses, invoices, quick reorder and negotiated pricing.

### Store/Operations staff
Manages inventory, picking, packing, dispatch and delivery exceptions.

### Catalog manager
Creates products, variants, brands, categories, attributes, images and pricing.

### Admin
Controls configuration, users, promotions, orders, inventory, delivery zones, CMS and reporting.

## 5. Core customer journey
Home → location → category/search → filters → product → variant/quantity → cart → address → delivery slot → coupon/wallet → payment/COD → order confirmation → fulfillment tracking → delivery → invoice → review/reorder.

## 6. MVP features
### Storefront
- Homepage
- Header/search/location selector
- Mega navigation
- Category pages
- Product listing
- Product detail
- Brand pages
- Search
- Filters
- Sort
- Cart
- Checkout
- Account
- Orders
- Wishlist
- Reorder
- Coupons
- Wallet/credits
- Reviews
- FAQ/content pages
- Contact

### Catalog
- Categories
- Brands
- Products
- Product variants
- Attributes
- Units
- Pack sizes
- Specifications
- Images
- Documents
- Product options
- Related products
- Frequently bought together

### Pricing
- MRP
- Selling price
- Sale price
- Customer-group price
- Quantity tiers
- Promotional price
- Coupon
- Delivery charge
- Tax
- Wallet redemption
- Price history

### Inventory
- Warehouses
- Stock by variant
- Reserved stock
- Available stock
- Reorder threshold
- Stock adjustment
- Stock movement ledger
- Low-stock alerts

### Orders
- Order creation
- Payment
- COD
- Order state machine
- Invoice
- Packing
- Dispatch
- Delivery
- Cancellation
- Refund
- Return
- Replacement
- Partial fulfillment

### Delivery
- Serviceable pincodes
- Delivery zones
- Delivery charges
- Delivery slots
- ASAP delivery
- Scheduled delivery
- Next-day delivery
- Driver assignment
- Proof of delivery
- Delivery status timeline

### Admin
- Dashboard
- Orders
- Products
- Categories
- Brands
- Customers
- Inventory
- Coupons
- Promotions
- Delivery
- Payments/refunds
- Reviews
- CMS
- Reports
- Settings
- Audit logs

## 7. Success metrics
- Search-to-product-view rate
- Product-view-to-cart rate
- Cart-to-checkout rate
- Checkout completion rate
- Repeat purchase rate
- Average order value
- Gross merchandise value
- Cancellation rate
- Return/refund rate
- On-time delivery rate
- Inventory stockout rate
- Search zero-result rate
- Page performance/Core Web Vitals

## 8. Business assumptions
The initial operating model is single-company inventory rather than a marketplace. The architecture should not prevent future multi-vendor support.

## 9. Functional priority
P0 = required for launch
P1 = important after launch
P2 = future enhancement

P0:
catalog, search, cart, checkout, payment/COD, inventory, orders, delivery zones, admin, coupons, invoices, customer accounts, responsive storefront, API foundations.

P1:
wallet/cashback, reviews, wishlist, project/site management, bulk customer pricing, scheduled delivery, advanced reports.

P2:
vendor marketplace, procurement workflows, advanced route optimization, subscriptions, AI recommendations.
