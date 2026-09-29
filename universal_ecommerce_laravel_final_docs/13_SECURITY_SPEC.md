# Security Specification

## Authentication
- secure password hashing
- email/phone verification
- session protection
- optional 2FA for admins
- login rate limiting
- password reset protections

## Authorization
Use Laravel Policies/Gates and granular permissions.

Never rely on:
- hidden buttons
- URL obscurity
- frontend role checks

## Input security
- validate all input
- whitelist sortable/filterable fields
- escape output
- protect against mass assignment
- validate file uploads
- restrict upload MIME types and size

## Payments
- verify gateway webhook signatures
- idempotency for webhook processing
- never trust client payment status
- never store raw card data
- log gateway references safely

## Inventory/order race conditions
Use transactions and row locking where required.

Example:
- lock inventory row
- verify available quantity
- reserve stock
- commit

## Admin
- strong authentication
- role/permission checks
- audit sensitive actions
- CSRF protection for web
- API authentication for API
- rate limits

## Files
- store private documents privately
- generate temporary signed URLs
- virus/malware scanning can be added for user uploads
- do not trust original filenames

## Secrets
Never commit `.env`, API keys, payment secrets or credentials.

## Audit
Audit:
- price changes
- stock adjustments
- refunds
- order cancellations by staff
- role changes
- permission changes
- settings changes
- customer data exports

## Privacy
Minimize PII, define retention rules and provide customer data access/deletion workflows where legally required.

## Security testing
Include:
- authorization tests
- IDOR tests
- webhook signature tests
- mass-assignment tests
- rate-limit tests
- upload validation tests
- checkout tampering tests
- price manipulation tests
- stock race tests
