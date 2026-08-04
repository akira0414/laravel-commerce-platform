# API Examples

Base URL: `http://localhost:8000/api/v1`

## Create order

`POST /orders`

```json
{
  "customer_email": "buyer@example.com",
  "currency": "TWD",
  "shipping_fee": 6000,
  "shipping_address": {
    "recipient": "王小明",
    "phone": "0912345678",
    "address": "台北市信義區"
  },
  "items": [{ "product_id": 1, "quantity": 2 }]
}
```

Returns `201`. Use `GET /orders/{id}` for its items, payments, and shipments; use `POST /orders/{id}/cancel` before payment.

## Payment webhook

`POST /webhooks/payments/{provider}`

Headers:

```text
Content-Type: application/json
X-Webhook-Timestamp: 1770000000
X-Webhook-Signature: <hex hmac-sha256>
```

Body:

```json
{
  "id": "evt_pay_001",
  "type": "payment.succeeded",
  "data": {
    "order_number": "ORD-20260101-ABCDEFGH",
    "payment_id": "pay_001",
    "amount": 164000,
    "currency": "TWD"
  }
}
```

Generate a local signature:

```bash
BODY='{"id":"evt_pay_001","type":"payment.succeeded","data":{"order_number":"ORD-20260101-ABCDEFGH","payment_id":"pay_001","amount":164000,"currency":"TWD"}}'
TIMESTAMP=$(date +%s)
SIGNATURE=$(printf '%s' "${TIMESTAMP}.${BODY}" | openssl dgst -sha256 -hmac 'local-payment-secret' -hex | sed 's/^.* //')
curl -X POST http://localhost:8000/api/v1/webhooks/payments/demo \
  -H 'Content-Type: application/json' \
  -H "X-Webhook-Timestamp: ${TIMESTAMP}" \
  -H "X-Webhook-Signature: ${SIGNATURE}" \
  -d "$BODY"
```

Supported topics: `payment.succeeded`, `payment.failed`.

## Shipping webhook

`POST /webhooks/shipping/{provider}` uses the same signature protocol with `SHIPPING_WEBHOOK_SECRET`.

```json
{
  "id": "evt_ship_001",
  "type": "shipment.in_transit",
  "data": {
    "order_number": "ORD-20260101-ABCDEFGH",
    "shipment_id": "ship_001",
    "tracking_number": "TW123456789"
  }
}
```

Supported topics: `shipment.ready`, `shipment.in_transit`, `shipment.delivered`, `shipment.exception`.

All valid webhook requests return `202 {"received":true}`. Processing happens asynchronously; provider retries are safe when the same event ID is used.
