# COPY/PASTE PROMPT FOR YOUR AI CODING AGENT

You are building a production-ready Laravel construction-materials ecommerce platform.

Read the complete documentation pack before coding. The project is an independent implementation inspired by the shopping workflow and information architecture of a construction-material ecommerce website. Do not copy third-party brand identity, copyrighted images, exact copy, testimonials or proprietary assets.

Tech:
- Laravel 13+
- PHP 8.4+
- MySQL 8+
- Blade + Tailwind + Alpine
- Vite
- Redis where useful
- REST API `/api/v1`

Your first task is NOT to code immediately.

First inspect the existing repository and produce:
1. architecture summary
2. existing modules
3. existing database tables
4. existing routes
5. existing authentication/authorization
6. existing frontend architecture
7. gaps against the documentation
8. migration risks
9. recommended implementation order

Then wait for/execute the requested implementation phase.

For every feature:
- implement database
- implement model/domain logic
- implement authorization
- implement validation
- implement UI
- implement API if relevant
- implement tests
- run formatter/tests
- report changed files and verification

Critical business rules:
- never trust client prices
- never trust client discounts/tax/stock/payment status
- quantity pricing must be server-side
- inventory reservation must be transaction-safe
- payment webhooks must be signature verified and idempotent
- historical orders must use snapshots
- sensitive admin actions must be audited
- all privileged actions require server-side authorization

Prioritize a modular monolith. Do not introduce microservices unless explicitly requested.

When the repository already contains a working implementation, extend it instead of replacing it.

The target experience includes:
- construction material categories
- search/filter/sort
- product variants
- bulk pricing
- cart
- checkout
- online payment + COD
- coupons
- wallet/cashback
- inventory
- local delivery zones
- delivery slots
- order tracking
- admin operations
- SEO/content
- API foundations
