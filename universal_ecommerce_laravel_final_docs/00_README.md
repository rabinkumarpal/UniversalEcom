# Construction Materials Ecommerce — Laravel Documentation Pack

## Purpose
This documentation pack defines a Laravel-based construction-materials ecommerce platform inspired by the information architecture and shopping experience observed on HomeRun, while being implemented as an independent product with its own branding, content, assets, business rules, and code.

Reference observations:
- Construction-material categories are grouped into areas such as Civil & Interiors, Electrical, Plumbing/Sanitary/Bath, Furniture/Architectural Hardware, and tools.
- The storefront emphasizes fast local delivery, pay-on-delivery, promotions, bulk pricing, product options, and cashback.
- The product listing experience supports category/brand/type/price/pack-size/grade/size filtering and sorting.
- The platform publishes price-list/knowledge/FAQ-style content alongside commerce.

## Recommended stack
- Laravel 13+
- PHP 8.4+
- MySQL 8+
- Redis for cache, queues, rate limits and optional realtime features
- Laravel Queue + Horizon in production
- Laravel Sanctum for first-party/API authentication
- Blade + Tailwind CSS + Alpine.js for the initial web application
- Vite for assets
- Object storage/CDN for product and media assets
- Payment gateway abstraction so Razorpay/Stripe/etc. can be swapped
- REST API versioning under `/api/v1`

## Documentation order
1. PRD
2. Scope & requirements
3. Roles & permissions
4. Information architecture
5. Domain architecture
6. Database specification
7. Ecommerce rules
8. Catalog and pricing specification
9. Order/delivery specification
10. API specification
11. Admin specification
12. UX/UI specification
13. SEO/content specification
14. Security specification
15. Testing/acceptance criteria
16. Implementation roadmap
17. AI Agent master instructions

## Important implementation principle
Do not copy proprietary logos, photos, exact marketing copy, customer testimonials, or brand identity from the reference site. Recreate the product/category/order workflow and visual patterns using original assets and content.

## Definition of done
A feature is not considered complete merely because the page renders. It must have:
- authorization
- validation
- database persistence
- auditability where relevant
- API support where relevant
- automated tests
- loading/empty/error states
- responsive UI
- SEO metadata where public
- observability/logging for important workflows
- documented acceptance criteria

## Portable Add-on Strategy

The platform is designed as a modular monolith with a small mandatory core and optional Composer-based add-ons. Vendor Marketplace, advanced promotions, loyalty, integrations, advanced search and similar capabilities can be packaged and sold independently.

Read:
- `22_PORTABLE_ADDON_ARCHITECTURE.md`
- `23_PLUGIN_DEVELOPER_SPEC.md`
- `24_AI_ADDON_ARCHITECTURE_AUDIT_PROMPT.md`

## Final architecture direction

This project is a Universal Ecommerce Platform. Construction materials are the first reference domain, not the core domain.

The architecture uses:
- Universal Core
- Portable Add-ons
- Provider Integrations
- Domain Packs

Read `25_UNIVERSAL_ECOMMERCE_PLATFORM_SPEC.md` and `26_FINAL_AI_AGENT_BUILD_PROMPT.md` before implementation.
