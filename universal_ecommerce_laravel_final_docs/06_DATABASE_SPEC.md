# Database Specification

## Identity
users
- id
- name
- email
- phone
- password
- status
- email_verified_at
- phone_verified_at
- timestamps

roles
permissions
role_user
permission_role

addresses
- id
- user_id
- label
- recipient_name
- phone
- address_line_1
- address_line_2
- landmark
- city
- state
- country
- pincode
- latitude
- longitude
- is_default

## Catalog
categories
- id
- parent_id nullable
- name
- slug
- description
- image_id nullable
- status
- sort_order
- seo_title
- seo_description

brands
- id
- name
- slug
- logo_id
- status

products
- id
- brand_id
- primary_category_id
- name
- slug
- short_description
- description
- sku nullable if variants own SKU
- status
- published_at
- seo_title
- seo_description

product_variants
- id
- product_id
- sku
- barcode nullable
- name
- unit
- pack_size
- weight nullable
- dimensions JSON nullable
- attributes JSON
- mrp
- selling_price
- tax_class_id
- status

product_categories
product_media
product_documents
product_attributes
attribute_definitions
attribute_values
product_related

## Pricing
prices
- id
- product_variant_id
- price_type
- amount
- currency
- starts_at
- ends_at
- priority

quantity_price_tiers
- id
- product_variant_id
- min_quantity
- max_quantity nullable
- unit_price

customer_price_lists
customer_price_list_items

coupons
coupon_rules
coupon_usages
promotions
promotion_items

## Inventory
warehouses
warehouse_locations
inventory_items
- id
- warehouse_id
- product_variant_id
- on_hand
- reserved
- available
- reorder_level

inventory_movements
stock_reservations
stock_transfers

## Cart
carts
cart_items
saved_carts

## Orders
orders
- id
- order_number
- user_id nullable
- status
- payment_status
- fulfillment_status
- currency
- subtotal
- discount_total
- tax_total
- delivery_fee
- wallet_amount
- grand_total
- billing_address_snapshot JSON
- shipping_address_snapshot JSON
- placed_at
- timestamps

order_items
- id
- order_id
- product_variant_id
- sku_snapshot
- product_name_snapshot
- variant_name_snapshot
- unit_price
- quantity
- discount
- tax
- line_total
- metadata JSON

order_status_history
shipments
shipment_items
invoices
invoice_items
returns
return_items
refunds

## Payments
payments
payment_transactions
payment_webhooks
refund_transactions

Never rely solely on a browser redirect to determine payment success. Verify gateway signatures/webhooks server-side.

## Delivery
delivery_zones
delivery_zone_pincodes
delivery_slots
delivery_slot_capacity
delivery_assignments
drivers
proof_of_delivery

## Customer engagement
wishlists
wishlist_items
reviews
review_media
wallet_accounts
wallet_ledger_entries
notifications

## CMS
pages
page_sections
banners
faqs
articles
article_categories
seo_redirects

## Audit
audit_logs

## Database rules
- money values use integer minor units or DECIMAL consistently; choose one approach globally.
- all important foreign keys indexed.
- use unique constraints for SKU, slug within required scope and order_number.
- soft delete only where business history requires it.
- historical order data must be immutable snapshots.
- stock movement records are append-only.


## Vendors / Marketplace

vendors
- id
- legal_name
- display_name
- slug
- email
- phone
- status
- approval_status
- commission_plan_id nullable
- tax_number nullable
- business_registration_number nullable
- onboarding_completed_at nullable
- approved_at nullable
- timestamps

vendor_users
- id
- vendor_id
- user_id
- role
- status

vendor_documents
- id
- vendor_id
- document_type
- file_id
- verification_status
- verified_at
- rejection_reason

vendor_products
- id
- vendor_id
- product_id
- approval_status
- vendor_status

vendor_product_variants
- id
- vendor_product_id
- product_variant_id
- vendor_sku
- vendor_price
- vendor_mrp
- status

vendor_inventory
- id
- vendor_id
- warehouse_id nullable
- product_variant_id
- on_hand
- reserved
- available

vendor_orders
- id
- vendor_id
- order_id
- status
- subtotal
- commission
- vendor_payout

vendor_order_items
- id
- vendor_order_id
- order_item_id
- quantity
- unit_price
- commission
- payout_amount

vendor_commissions
- id
- vendor_id
- order_id nullable
- order_item_id nullable
- commission_type
- commission_rate
- commission_amount
- status

vendor_payouts
- id
- vendor_id
- payout_number
- amount
- status
- period_start
- period_end
- paid_at
- payment_reference

vendor_payout_items
- id
- vendor_payout_id
- vendor_order_id
- amount

vendor_reviews
- id
- vendor_id
- customer_id
- order_id
- rating
- comment
- status

Marketplace rules:
- Customer orders can contain multiple vendors.
- Split mixed orders into vendor orders.
- Vendor users are server-side scoped by vendor_id.
- Vendor prices use the central pricing engine.
- Vendor inventory uses transaction-safe reservation.
- Commission/payout calculations use immutable order snapshots.
- Vendor suspension blocks new sales but preserves historical records.
