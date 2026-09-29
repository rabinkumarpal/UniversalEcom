# API Specification — `/api/v1`

## Response envelope
Success:
```json
{
  "data": {},
  "meta": {}
}
```

Error:
```json
{
  "message": "Validation failed.",
  "errors": {
    "field": ["The field is required."]
  }
}
```

## Auth
POST `/auth/register`
POST `/auth/login`
POST `/auth/logout`
GET `/me`

## Catalog
GET `/categories`
GET `/categories/{slug}`
GET `/brands`
GET `/products`
GET `/products/{slug}`
GET `/products/{slug}/related`
GET `/search?q=...`

Filters:
- category
- brand
- price_min
- price_max
- unit
- pack_size
- grade
- size
- availability
- sort
- page

## Cart
GET `/cart`
POST `/cart/items`
PATCH `/cart/items/{id}`
DELETE `/cart/items/{id}`
POST `/cart/coupon`
DELETE `/cart/coupon`

## Checkout
POST `/checkout/validate`
POST `/checkout/quote`
POST `/orders`

## Orders
GET `/orders`
GET `/orders/{orderNumber}`
POST `/orders/{orderNumber}/cancel`
POST `/orders/{orderNumber}/reorder`

## Payments
POST `/payments/create`
POST `/payments/{id}/verify`
POST `/payments/webhook/{gateway}`

Webhook endpoints must verify signatures and be idempotent.

## Addresses
GET `/addresses`
POST `/addresses`
PATCH `/addresses/{id}`
DELETE `/addresses/{id}`

## Wishlist
GET `/wishlist`
POST `/wishlist/items`
DELETE `/wishlist/items/{id}`

## Reviews
POST `/products/{product}/reviews`
GET `/products/{product}/reviews`

## Delivery
GET `/delivery/serviceability?pincode=...`
GET `/delivery/slots?address_id=...`

## Admin API
Admin endpoints should be separate and permission-protected:
`/api/v1/admin/...`

## API rules
- Use Form Requests.
- Use Policies.
- Paginate collections.
- Never return internal secrets.
- Rate-limit authentication, search and checkout-sensitive endpoints.
- Use idempotency keys for order/payment-sensitive POST requests where appropriate.
- Version breaking changes.
