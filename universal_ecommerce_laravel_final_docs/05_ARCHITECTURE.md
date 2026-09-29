# Laravel Architecture

## Architecture style
Use a modular monolith first. Do not start with microservices.

Suggested bounded modules:
- Identity
- Catalog
- Pricing
- Inventory
- Cart
- Checkout
- Orders
- Payments
- Delivery
- Customers
- Promotions
- Reviews
- CMS
- Search
- Reporting
- Notifications

Each module should have:
- Models/entities
- Actions/services
- Policies
- Requests/validators
- Events/listeners
- Jobs
- Controllers/API resources
- Tests

## Request flow
HTTP/API
→ Form Request
→ Authorization
→ Application Action
→ Domain/service logic
→ Repository/query where useful
→ Transaction
→ Domain event
→ Queue side effects
→ Response/resource

## Transaction boundaries
Use DB transactions for:
- order creation
- inventory reservation
- payment state updates
- refund state updates
- wallet ledger changes
- stock adjustments

## Events
Examples:
- ProductPublished
- PriceChanged
- StockAdjusted
- CartCheckedOut
- OrderPlaced
- PaymentCaptured
- PaymentFailed
- OrderPacked
- OrderDispatched
- OrderDelivered
- OrderCancelled
- RefundCreated
- WalletCredited

## Queue jobs
- SendOrderConfirmation
- GenerateInvoice
- SendPaymentNotification
- SyncSearchIndex
- ProcessCatalogImport
- LowStockNotification
- DeliveryReminder
- GenerateReport

## Caching
Cache:
- category trees
- active navigation
- serviceability rules where safe
- product aggregates
- frequently requested configuration

Never cache mutable stock in a way that can cause overselling.

## Search
MVP can use MySQL full-text/search tables if scale is low. Design a search abstraction so Meilisearch/Typesense/Elasticsearch can be introduced later.

## Storage
Store media in object storage or a dedicated storage abstraction. Never expose filesystem paths directly.

## API
Version from day one:
`/api/v1/...`

Use API Resources and consistent error responses.

## Observability
- structured application logs
- failed-job monitoring
- slow-query monitoring
- audit logs
- payment webhook logs
- order state transition logs


## Marketplace architecture
Keep marketplace concerns modular:
- Vendor
- VendorOffer
- VendorInventory
- VendorOrder
- Commission
- Payout

The platform Order remains customer-facing. VendorOrder represents each vendor's operational and financial slice.

Do not couple the entire order to a single vendor because mixed-vendor carts are supported.
