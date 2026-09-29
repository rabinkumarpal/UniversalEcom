# Promotion, Discount & Merchandising Engine Specification

## Purpose

The ecommerce platform must support a flexible promotion engine covering normal ecommerce discounts and construction-specific bulk/bundle purchasing behavior.

The reference feature set includes:
- Normal Discount
- Bundle Discount
- Quantity Discount
- Buy X Get Y
- Frequently Bought Together
- Mix and Match
- Double Order Plus
- Spending Goal
- Stock Scarcity
- Countdown Timer
- Free Shipping
- Buy X Get Y Bundle
- Next Order Coupon

These are modeled as configurable promotion types, not hard-coded page-specific logic.

## Promotion architecture

Use a central promotion engine:

```text
Promotion
   ↓
Eligibility Rules
   ↓
Condition Evaluation
   ↓
Benefit Calculation
   ↓
Cart/Checkout Adjustment
   ↓
Promotion Application Record
```

The same engine must be used by:
- product pages
- cart
- checkout
- API
- admin previews
- order pricing

Never calculate discounts independently in Blade, JavaScript, and checkout controllers.

## Core promotion types

### 1. Normal Discount

Simple product/category/brand/customer-group discount.

Examples:
- 10% off
- ₹500 off
- sale price
- campaign price

Rules:
- start/end date
- minimum spend
- maximum discount
- eligible products/categories/brands
- customer eligibility
- usage limits

### 2. Quantity Discount

Discount changes according to quantity.

Example:

```text
1–9 bags    ₹450/bag
10–29 bags  ₹435/bag
30+ bags    ₹420/bag
```

Must work with product variants and vendors.

### 3. Bundle Discount

A predefined group of products gets a discount.

Example:

```text
Cement + Sand + Waterproofing
Normal: ₹8,000
Bundle: ₹7,400
```

Support:
- fixed bundle price
- percentage discount
- fixed amount discount
- required quantities
- optional quantities
- validity period

### 4. Buy X Get Y

Example:

```text
Buy 10 → Get 1 free
Buy 2 → Get 1 at 50% off
```

Support:
- same product
- different qualifying/reward product
- maximum rewards
- eligibility conditions

The reward line must be explicitly represented in the pricing calculation.

### 5. Frequently Bought Together

Merchandising recommendation rather than necessarily a discount.

Example:

```text
Cement
+ Trowel
+ Waterproofing
+ Gloves
```

Can optionally include:
- bundle discount
- one-click add all
- vendor compatibility rules

### 6. Mix and Match

Customer chooses items from a defined group.

Example:

```text
Choose any 3 from:
- Paint
- Primer
- Roller
- Brush

Get 10% off.
```

Support:
- eligible product/category pool
- minimum quantity
- maximum quantity
- fixed bundle price
- percentage discount

### 7. Double Order Plus

Configurable loyalty/repeat-order incentive.

Possible behavior:

```text
Place qualifying order
→ earn additional benefit
→ benefit available on next qualifying order
```

Do not hard-code the business meaning. Model the reward as a promotion/loyalty rule with configurable trigger and benefit.

### 8. Spending Goal

Progress-based promotion.

Example:

```text
Spend ₹4,000 more to unlock:
FREE DELIVERY
```

The cart UI should show:
- current eligible spend
- target
- remaining amount
- unlocked reward

Rules must define whether discounts/coupons count toward the target.

### 9. Stock Scarcity

Merchandising signal such as:

```text
Only 7 left
Limited stock
Selling fast
```

This is not automatically a discount.

The system must only display scarcity based on actual inventory/configured rules. Never generate fake stock counts.

### 10. Countdown Timer

Time-limited campaign display.

Example:

```text
Offer ends in
02 : 14 : 33
```

Server-side validity determines whether the promotion applies. Client countdown is presentation only.

Do not trust the browser timer.

### 11. Free Shipping

Promotion that removes or reduces delivery fees.

Conditions may include:
- minimum order
- zone
- pincode
- customer group
- products/categories
- vendor
- campaign period

Delivery eligibility must still be checked.

### 12. Buy X Get Y Bundle

Bundle-oriented Buy X Get Y promotion.

Example:

```text
Buy:
2 Cement
1 Waterproofing

Get:
1 Trowel Free
```

Support multiple qualifying lines and reward lines.

### 13. Next Order Coupon

After a qualifying completed order:
- generate coupon/reward
- assign it to customer
- define expiry
- define minimum spend
- define eligible categories/products
- define maximum discount

Coupon creation must happen after the qualifying business event, not before successful order completion.

## Promotion data model

Recommended tables:

```text
promotions
promotion_rules
promotion_conditions
promotion_benefits
promotion_products
promotion_categories
promotion_brands
promotion_customer_groups
promotion_usages
promotion_rewards
promotion_bundles
promotion_bundle_items
promotion_mix_match_groups
promotion_mix_match_items
promotion_progress
```

Suggested `promotions` fields:

- id
- name
- code nullable
- slug
- type
- status
- priority
- stackable
- starts_at
- ends_at
- usage_limit
- usage_limit_per_customer
- configuration JSON
- created_by
- timestamps

Use normalized tables for relationships and JSON only for type-specific configuration that does not need relational querying.

## Promotion priority

When multiple promotions apply, use deterministic rules:

1. Validate eligibility.
2. Apply explicit priority.
3. Respect stackable/non-stackable setting.
4. Apply configured combination rules.
5. Prevent discounting the same line twice unless explicitly allowed.
6. Recalculate after cart changes.

Never let frontend ordering decide which discount wins.

## Vendor compatibility

Promotions must support scope:

```text
Platform-wide
Vendor-specific
Product-specific
Category-specific
Brand-specific
Customer-group-specific
```

For a vendor marketplace:
- vendor-funded promotion
- platform-funded promotion
- shared-funded promotion

should be distinguishable.

Financial responsibility must be recorded so vendor payouts can correctly account for vendor-funded discounts.

## Promotion application snapshot

When an order is placed, record:

- promotion ID
- promotion name snapshot
- promotion type
- qualifying items
- reward items
- discount amount
- funding source
- rule/condition snapshot
- coupon code if used

Changing a promotion later must not change historical order calculations.

## Admin UI

```text
Promotions
├── All Promotions
├── Active
├── Scheduled
├── Expired
├── Draft
├── Create Promotion
├── Coupons
├── Bundles
├── Mix & Match
├── Loyalty Rewards
└── Usage Reports
```

Promotion builder:

```text
Basic Information
→ Promotion Type
→ Eligibility
→ Conditions
→ Benefits
→ Product/Category Scope
→ Customer Scope
→ Vendor Scope
→ Usage Limits
→ Schedule
→ Stacking
→ Preview
→ Publish
```

## Customer UI

Promotion presentation can appear on:
- homepage
- category pages
- product pages
- cart
- checkout
- order confirmation
- account/rewards

Important:
The frontend may display an estimated promotion, but the server is authoritative.

## Construction ecommerce examples

### Cement quantity pricing

```text
1–9       ₹450
10–49     ₹435
50+       ₹420
```

### Contractor bundle

```text
50 Cement Bags
+ 10 Waterproofing
+ 5 Trowels
= Bundle Price
```

### Project spending goal

```text
Cart: ₹42,000
Goal: ₹50,000

₹8,000 more to unlock free delivery.
```

### Next order reward

```text
Order delivered
→ ₹300 coupon generated
→ valid for 30 days
→ minimum order ₹5,000
```

## Security and integrity

- Never trust submitted discount values.
- Never trust client countdown timers.
- Never trust client eligibility.
- Never trust client stock-scarcity values.
- Recalculate promotions on checkout.
- Record promotion application snapshots.
- Use idempotency for reward generation.
- Prevent duplicate coupon/reward creation.
- Ensure refunded items reverse relevant promotion benefits according to policy.

## Testing requirements

Automated tests must cover:
- normal discount
- quantity discount
- bundle discount
- Buy X Get Y
- Mix and Match
- free shipping
- spending goal
- countdown expiry
- next-order coupon generation
- promotion stacking
- promotion priority
- customer usage limits
- vendor-specific promotion
- vendor-funded discount
- refund promotion reversal
- concurrent checkout
- client price/discount tampering

## Definition of done

The promotion engine is complete only when all promotion calculations are centralized, server-authoritative, auditable, tested and reusable across storefront, cart, checkout, API and admin preview.
