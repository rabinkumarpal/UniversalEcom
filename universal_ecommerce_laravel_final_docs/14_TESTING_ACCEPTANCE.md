# Testing and Acceptance Criteria

## Test layers
### Unit
Pricing, quantity tiers, coupon rules, tax, delivery fee, state transitions.

### Feature
Catalog, cart, checkout, orders, payment webhook, inventory, delivery, admin.

### Browser/E2E
Critical customer journeys:
1. Search → product → cart → checkout.
2. Login → saved address → checkout.
3. Quantity pricing.
4. Coupon.
5. COD.
6. Online payment.
7. Order tracking.
8. Cancellation/refund.
9. Admin product creation.
10. Admin inventory adjustment.

## Critical acceptance tests

### Price integrity
Given a product price of 500, if a malicious client submits 1, the server must use the authoritative price.

### Quantity tier
Given tiers:
1–9 = 500
10–29 = 475
30+ = 450
a quantity of 30 must calculate at 450/unit.

### Stock
If available stock is 5, a checkout for 6 must fail safely.

### Concurrent checkout
Two simultaneous checkouts cannot reserve more stock than exists.

### Coupon
Expired or unauthorized coupons must not apply.

### Payment webhook
The same webhook delivered twice must not create two payments/orders/refunds.

### Order snapshot
Changing a product title after an order is placed must not change the historical order item name.

### Authorization
A catalog manager cannot issue a refund unless the refund permission exists.

### Delivery
An unsupported pincode cannot proceed to a delivery slot.

## Quality gates
Before merging:
- tests pass
- formatter/linter passes
- no debug statements
- migrations reversible where practical
- authorization covered
- validation covered
- feature documented
- API contract updated
- performance impact considered
