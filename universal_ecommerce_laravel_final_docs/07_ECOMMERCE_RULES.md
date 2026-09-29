# Ecommerce Business Rules

## Product/variant
A product is a commercial concept; a variant is the sellable unit.

Example:
Product: UltraTech PPC Cement
Variants:
- 50 Kg Bag
- different pack/grade if applicable

Every cart item references a variant.

## Quantity pricing
Example:
1–9 units: ₹X
10–29: ₹Y
30–49: ₹Z
50+: ₹W

The pricing engine must select the applicable tier based on quantity and customer eligibility.

Never trust a client-submitted price. Recalculate server-side.

## Cart
At cart mutation:
1. Validate variant exists and is active.
2. Validate purchasable quantity.
3. Calculate current price.
4. Calculate applicable quantity tier.
5. Calculate tax.
6. Validate stock.
7. Save cart.

At checkout repeat all important validation.

## Coupon
Coupon may include:
- percentage/fixed discount
- minimum subtotal
- maximum discount
- category/product/brand restrictions
- customer restrictions
- first-order rule
- usage limit
- per-customer usage
- date range
- stacking policy

## Wallet
Use a ledger, not a mutable balance-only field.

Ledger entries:
- credit
- debit
- reversal
- expiry

Balance = sum(valid ledger entries), optionally materialized for performance with reconciliation.

## Order state machine
Suggested:
draft
→ pending_payment
→ paid
→ confirmed
→ picking
→ packed
→ dispatched
→ out_for_delivery
→ delivered

Alternative terminal states:
cancelled
failed
returned
refunded

Transitions must be explicit and policy-controlled.

## Payment
Payment states:
pending
authorized
captured
failed
cancelled
refunded
partially_refunded

Gateway webhook processing must be idempotent.

## COD
COD order can be:
pending_verification → confirmed → fulfillment.

Optional COD risk rules:
- maximum order amount
- pincode eligibility
- customer history
- manual verification

## Returns
Return eligibility depends on:
- product category
- delivered date
- condition
- return policy
- quantity
- customer history

Do not hard-code policy in controllers; use policy/configuration services.

## Inventory
Reserve stock before final fulfillment according to the configured strategy.

Available:
`on_hand - reserved`

Every reservation must have expiry/release behavior.

## Delivery fee
Possible rule dimensions:
- zone
- pincode
- order subtotal
- weight
- volume
- urgency
- scheduled slot

## Tax
Tax should be calculated from the variant/product tax class and applicable destination rules. Preserve the tax rate and amount snapshot on the order item.

## Price snapshots
Order line must retain:
- product name
- variant name
- SKU
- unit price
- quantity
- discount
- tax
- total

Catalog changes must never rewrite historical orders.


## Promotion engine

All promotions must be evaluated by one authoritative promotion service.

Supported promotion families:
- normal discount
- quantity discount
- bundle discount
- Buy X Get Y
- Frequently Bought Together
- Mix and Match
- spending goal
- stock scarcity messaging
- countdown campaigns
- free shipping
- Buy X Get Y bundles
- next-order coupons
- repeat-order/loyalty rewards

The browser may display promotion progress, but only the server determines eligibility and final discount.

For vendor commerce, distinguish platform-funded and vendor-funded discounts so payout calculations remain correct.
