# Recommended Laravel Project Structure

```text
app/
  Actions/
    Cart/
    Checkout/
    Orders/
    Payments/
    Inventory/
    Delivery/
  Domain/
    Catalog/
    Pricing/
    Inventory/
    Orders/
    Delivery/
  Http/
    Controllers/
      Web/
      Api/V1/
      Admin/
    Requests/
    Resources/
  Models/
  Policies/
  Jobs/
  Events/
  Listeners/
  Notifications/
  Support/

database/
  migrations/
  seeders/
  factories/

resources/
  views/
    layouts/
    components/
    storefront/
    checkout/
    account/
    admin/
  js/
  css/

routes/
  web.php
  api.php
  admin.php
```

Do not force every class into this structure if Laravel conventions already provide a better location. The purpose is separation of concerns, not ceremony.
