# Scope and Functional Requirements

## Requirement format
Each requirement has an ID and priority.

## Storefront
- ST-001 P0: User can browse categories without login.
- ST-002 P0: User can search by product name, SKU, brand, category and relevant attributes.
- ST-003 P0: User can filter by category, brand, price, unit, pack size, grade, size and availability.
- ST-004 P0: User can sort by relevance, price, newest and popularity.
- ST-005 P0: Product cards show price, discount, availability and relevant delivery badges.
- ST-006 P0: Product detail shows images, variants, specifications, pricing, quantity and delivery eligibility.
- ST-007 P0: User can add a selected variant and quantity to cart.
- ST-008 P0: Cart survives login/session changes safely.
- ST-009 P0: Checkout validates address and serviceability before order creation.

## Customer
- CU-001 P0: Registration/login via email/phone.
- CU-002 P0: Customer can maintain multiple addresses.
- CU-003 P0: Customer can view orders and invoices.
- CU-004 P1: Customer can save wishlist.
- CU-005 P1: Customer can reorder.
- CU-006 P1: Customer can maintain project/site addresses.
- CU-007 P1: Customer can access wallet/cashback ledger.

## Catalog
- CAT-001 P0: Admin can create category hierarchy.
- CAT-002 P0: Admin can create brands.
- CAT-003 P0: Admin can create products and variants.
- CAT-004 P0: Variant has SKU, price, stock unit, pack size and attributes.
- CAT-005 P0: Product supports multiple media items.
- CAT-006 P1: Product supports technical PDF/specification documents.
- CAT-007 P1: Product supports related and complementary products.

## Pricing
- PR-001 P0: Every sellable variant has a current selling price.
- PR-002 P0: Price changes are recorded.
- PR-003 P0: Quantity-tier pricing is supported.
- PR-004 P1: Customer-group pricing is supported.
- PR-005 P0: Coupons can have eligibility, expiry and usage rules.
- PR-006 P1: Wallet credits can be earned and redeemed through a ledger.

## Inventory
- INV-001 P0: Stock is maintained at warehouse/location level.
- INV-002 P0: Reservation occurs during order processing according to configured policy.
- INV-003 P0: Stock movement is immutable/auditable.
- INV-004 P0: Overselling is prevented.
- INV-005 P1: Low-stock alerts are configurable.

## Orders
- ORD-001 P0: Order has immutable order number.
- ORD-002 P0: Order stores price snapshots so historical orders do not change when catalog prices change.
- ORD-003 P0: Order supports line-item quantities and tax/discount snapshots.
- ORD-004 P0: Order has a controlled state machine.
- ORD-005 P0: Customer can cancel according to policy.
- ORD-006 P0: Admin can refund through authorized workflow.
- ORD-007 P1: Partial fulfillment is supported.

## Delivery
- DEL-001 P0: System validates pincode/zone serviceability.
- DEL-002 P0: Delivery fee can be calculated from configurable rules.
- DEL-003 P0: Delivery slot availability is checked at checkout.
- DEL-004 P1: Driver/agent assignment is supported.
- DEL-005 P1: Proof of delivery can be recorded.
- DEL-006 P1: Customer sees delivery status timeline.

## Admin
- ADM-001 P0: Role-based access control.
- ADM-002 P0: Admin dashboard shows operational KPIs.
- ADM-003 P0: Admin can manage catalog/order/inventory/customer data.
- ADM-004 P0: Sensitive actions are audited.
- ADM-005 P1: CSV import/export for catalog and pricing.


## Vendor / Marketplace Requirements
- VEN-001 P1: Admin can create/approve/suspend vendors.
- VEN-002 P1: Vendor can submit onboarding documents.
- VEN-003 P1: Admin can verify/reject documents.
- VEN-004 P1: Vendor has a public storefront/profile.
- VEN-005 P1: Vendor can submit offers for approved catalog products.
- VEN-006 P1: Vendor manages SKU, price and inventory.
- VEN-007 P1: Admin can approve/reject offers.
- VEN-008 P1: A customer order can contain multiple vendors.
- VEN-009 P1: Mixed orders split into vendor-specific fulfillment records.
- VEN-010 P1: Vendor users can access only their own records.
- VEN-011 P1: Platform calculates vendor commission.
- VEN-012 P1: Platform generates vendor payout statements.
- VEN-013 P1: Vendor can view payout history.
- VEN-014 P1: Suspended vendors cannot receive new orders.
- VEN-015 P2: Vendor-specific delivery/warehouse rules.
- VEN-016 P2: Vendor promotions and ratings.


## Promotion / Discount Requirements

- PROMO-001 P0: Support normal percentage/fixed discounts.
- PROMO-002 P0: Support quantity-based discounts.
- PROMO-003 P1: Support bundle discounts.
- PROMO-004 P1: Support Buy X Get Y.
- PROMO-005 P1: Support Frequently Bought Together merchandising.
- PROMO-006 P1: Support Mix and Match.
- PROMO-007 P1: Support loyalty/repeat-order rewards such as Double Order Plus.
- PROMO-008 P1: Support spending-goal/progress promotions.
- PROMO-009 P1: Support stock-scarcity merchandising based on real inventory.
- PROMO-010 P1: Support countdown campaign timers with server-side expiry.
- PROMO-011 P0: Support free-shipping promotions.
- PROMO-012 P1: Support Buy X Get Y bundles.
- PROMO-013 P1: Support next-order coupons/rewards.
- PROMO-014 P0: Centralize all promotion calculation in one server-side engine.
- PROMO-015 P0: Record applied promotions on order snapshots.
- PROMO-016 P1: Support vendor-specific and platform-funded/vendor-funded promotions.
