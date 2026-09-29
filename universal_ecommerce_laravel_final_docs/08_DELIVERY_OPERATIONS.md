# Delivery and Operations

## Delivery zones
Each zone may define:
- name
- pincodes
- minimum order
- delivery fee
- free-delivery threshold
- available delivery modes
- operating hours

## Delivery modes
- ASAP
- scheduled
- next-day
- pickup, if enabled

## Slot model
A slot has:
- start
- end
- capacity
- current allocation
- blackout status

Checkout must reserve capacity safely to avoid double booking.

## Fulfillment workflow
1. Order confirmed.
2. Warehouse selected.
3. Pick list generated.
4. Items picked.
5. Quantities verified.
6. Packed.
7. Shipment created.
8. Driver assigned.
9. Out for delivery.
10. Delivered.
11. POD recorded.
12. Customer notified.

## Partial fulfillment
Construction orders may contain items from multiple stock locations. The system should eventually support multiple shipments for one order.

## Proof of delivery
POD may include:
- recipient name
- signature
- OTP
- photo
- timestamp
- GPS metadata where legally/operationally appropriate

## Delivery exception codes
- CUSTOMER_UNAVAILABLE
- WRONG_ADDRESS
- PINCODE_NOT_SERVICEABLE
- STOCK_SHORTAGE
- VEHICLE_ISSUE
- WEATHER
- CUSTOMER_CANCELLED
- OTHER

Every exception should be visible to support staff and included in the order timeline.
