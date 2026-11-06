# Courier Module — App API Reference (Vendor v3 · User v1)

Audience: mobile app developers (vendor app, user app) and the backend developer wiring the endpoints.

**Read this first.** The vendor app can now configure delivery partners, estimate, dispatch and track over `api/v3/seller/courier/*` — bearer token, `seller_api_auth`, JSON in and JSON out. Every one of those endpoints is **unnamed on purpose**: `HostCourierOwnerResolver` reads the `Seller` model that `seller_api_auth` puts on the request, so the owner comes from the token and never from a route name or a request parameter.

This document has six parts:

- §0 — the endpoint map: every endpoint in the flow, grouped, with the providers that actually use each one.
- §1–§3 — order-detail reads, for both apps, plus the public tracking response (§3.1).
- §4 — every endpoint documented one by one: configuration, providers, the four location lookups, estimate, dispatch and switching, tracking, the web-panel twins, the inbound carrier webhook and the revise action (§4.11).
- §5 — exactly what each app has to change, including where the tracking URL comes from.
- §6–§7 — the shipment status vocabulary, and the known gaps.
- §8 — the per-provider dynamic-API map: what each carrier's setup and booking screens call, what they must call before a booking can succeed, and what they never call.

**What changed most recently.** A booked order is no longer frozen: it can be **switched** to a different delivery partner (§4.6, `replace_existing`) and its delivery information can be **revised** (§4.11). Neither action contacts the previous or current carrier — read §1.2 before building either screen, because the conflict that creates is the merchant's to resolve and the confirmation copy is the only thing standing in front of it. `courier_shipment` gained `can_manage` and `dispatch_details` (§4.2); the public tracking response gained `courier_tracking_url` and nothing else (§3.1); the authenticated customer payload (§3) is unchanged.

---

## 0. Endpoint map

Everything below is grouped by what it does. "Providers" names the drivers for which the endpoint returns real data — every other registered provider answers the documented "not supported" shape rather than an error (§0.3). Detail for each endpoint is in the section named in the last column.

### 0.1 Group-wise endpoint summary

**Group A — Configuration** (per-owner credentials; prerequisite for every other group)

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `/api/v3/seller/courier/config` | `GET` | Full provider catalog: credential-field contract, stored values, country/environment lists, per-owner webhook URL | All 10 | §4.1 |
| `/api/v3/seller/courier/config` | `PUT` | Save one provider — credentials, country, environment, enable flag | All 10 | §4.1 |
| `admin/courier/config` · `vendor/courier/config` | `GET` `PUT` | The same two operations behind the web panels' setup screens (HTML in, redirect + toast out) | All 10 | §4.9 |

**Group B — Provider catalog for booking**

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `/api/v3/seller/courier/providers` | `GET` | The **enabled** partners only, each with the form contract the booking screen renders from (`address_mode`, `levels`, `required_fields`, `delivery_types`) | Enabled subset | §4.3 |

**Group C — Dynamic location lookups** *(the provider-specific dropdowns — this is where "Pathao's Pickup Store list" lives)*

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `courier/locations/stores?provider=<id>` | `GET` | Pickup-store / warehouse dropdown | `pathao`, `pathao_nepal`, `redx` | §4.4.1 |
| `courier/locations/cities?provider=<id>` | `GET` | Top destination level (city · district · branch list) | `pathao`, `pathao_nepal`, `tcs`, `garuda_express`, (`redx` synthetic) | §4.4.2 |
| `courier/locations/zones/{cityId}?provider=<id>` | `GET` | Second destination level (zone · area code) | `pathao`, `pathao_nepal`, `tcs`, (`redx` synthetic) | §4.4.3 |
| `courier/locations/areas/{zoneId}?provider=<id>` | `GET` | Third destination level (area · block code); RedX calls it with `all` | `pathao`, `pathao_nepal`, `redx`, `tcs` | §4.4.4 |

The same four paths exist three times — `api/v3/seller/courier/…` (bearer token), `admin/courier/…` and `vendor/courier/…` (session guard). One controller serves all three; only the auth and the resolved owner differ.

**Group D — Quote and booking**

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `/api/v3/seller/courier/estimate` | `POST` | Delivery-charge quote before booking | `pathao`, `pathao_nepal`, `redx`, `dhl`, `lalamove`, `delhivery`, `shiprocket` | §4.5 |
| `/api/v3/seller/courier/dispatch` | `POST` | Book the shipment with the carrier — first booking, or a **switch** to another partner with `replace_existing` | All 10 | §4.6 |
| `/api/v3/seller/courier/revise` | `POST` | Correct the delivery information held for an already-booked order. **Local only — no carrier call** | All 10 | §4.11 |

**Group E — Tracking**

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `/api/v3/seller/courier/track/{consignmentId}` | `GET` | Tracking-event history for a booked consignment | `redx`, `dhl`, `lalamove`, `delhivery`, `aramex`, `shiprocket`, `tcs`, `garuda_express` | §4.7 |

**Group F — Inbound carrier callback** (the carrier calls you, not the other way round)

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `courier/webhook/{provider}/{owner?}` | `POST` | Carrier pushes a status change; updates the shipment and the host order | `pathao`, `pathao_nepal`, `redx`, `lalamove`, `delhivery`, `shiprocket` (slug `logistics-in`) | §4.10 |

**Group G — Host order reads** (not part of the courier module, but where the apps read the result)

| Endpoint | Method | Purpose | Providers | Detail |
|---|---|---|---|---|
| `/api/v3/seller/orders/{order_id}` | `GET` | Vendor order details — carries `delivery_partner_available` + `courier_shipment` | n/a | §2, §4.2 |
| `/api/v1/customer/order/details?order_id=` | `GET` | Customer order details — carries the trimmed `courier_shipment` | n/a | §3 |
| `/api/v1/order/track-order` | `POST` | Public order tracking (guest-reachable) — carries **only** `courier_tracking_url` | n/a | §3.1 |

### 0.2 Provider × dynamic API matrix

✅ = the flow calls it and the booking cannot be completed correctly without it · ○ = called, optional (a stored default covers it) · — = never called; the endpoint answers `supported: false` and the field must be hidden.

| Provider (`id`) | `address_mode` | stores | cities | zones | areas | estimate | dispatch | track | webhook |
|---|---|---|---|---|---|---|---|---|---|
| Pathao BD (`pathao`) | catalog | ○ | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ |
| Pathao Nepal (`pathao_nepal`) | catalog | ○ | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅ |
| RedX (`redx`) | catalog | ○ | — ¹ | — ¹ | ✅ ² | ✅ ³ | ✅ | ✅ | ✅ |
| DHL (`dhl`) | postal | — | — | — | — | ✅ | ✅ | ✅ | — |
| Lalamove (`lalamove`) | geo | — | — | — | — | ✅ | ✅ | ✅ | ✅ |
| Delhivery (`delhivery`) | postal | — | — | — | — | ✅ | ✅ | ✅ | ✅ |
| Aramex (`aramex`) | postal | — | — | — | — | — | ✅ | ✅ | — |
| Shiprocket (`shiprocket`) | postal | — | — | — | — | ✅ | ✅ | ✅ | ✅ |
| TCS (`tcs`) | catalog | — | ✅ | ✅ | ✅ | — | ✅ | ✅ | — |
| Garuda Express (`garuda_express`) | catalog | — | ✅ ⁴ | — ⁵ | — ⁵ | — | ✅ | ✅ | — |

1. RedX declares `levels: ["area"]`, so the form never asks for city or zone. If you call them anyway they answer `supported: true` with one synthetic option (`{"id": "all", "name": "Bangladesh"}`) — a placeholder, not a picker.
2. Called as `locations/areas/all?provider=redx` — the literal string `all` in place of a zone id.
3. Fails with a merchant-readable message until `default_pickup_area_id` is set in configuration.
4. Served from the module's own district table (`resources/data/garuda-districts.php`), so it answers instantly and works before the API key is valid. Still a real request the app must make — never hardcode the 77 districts.
5. `ResolvesLocations` is implemented but both methods return `[]`, and `levels: ["city"]` means the form never asks. Calling them yields `supported: true, options: []`, which the UI would report as a carrier fault.

**Switch and revise are not in the matrix because they do not vary by provider.** A switch (§4.6, `replace_existing`) is a normal `dispatch` against the *new* partner — so it needs whatever that partner's column already demands — plus a local supersede of the old shipment; the **old** partner is never called, whatever it supports. A revise (§4.11) calls no carrier at all, so it works identically for all ten, including the four with no webhook and the three with no estimate.

### 0.3 What "not supported" looks like, per group

None of these are errors. Every one answers HTTP `200`.

| Group | Shape when the provider does not implement it | What the UI must do |
|---|---|---|
| Locations (C) | `{"supported": false, "options": []}` | Hide the field — do not render an empty dropdown |
| Estimate (D) | `{"ok": true, "supported": false}` | Hide the estimate control — do not show `0` |
| Tracking (E) | `can_track: false` on the shipment object (§4.2); the endpoint itself is only meaningful when that flag is true | Hide the Track action |
| Dispatch (D) | n/a — every registered driver implements `CreatesOrders` | — |
| Webhook (F) | The route accepts the POST, then raises `UnsupportedCourierOperationException` (`500`) | Do not register the callback URL for a provider outside Group F — see §7.7 |

### 0.4 Example calls — every endpoint, with real values

Host is `https://shop.example.com` throughout, order `100143`, consignment `CN-88`, seller token on every `api/v3/seller/*` call. Every URL below is exactly what goes on the wire — the query parameters are shown filled in, not as placeholders.

**Group A — Configuration**

```
GET  https://shop.example.com/api/v3/seller/courier/config
PUT  https://shop.example.com/api/v3/seller/courier/config
```

**Group B — Provider catalog**

```
GET  https://shop.example.com/api/v3/seller/courier/providers
```

**Group C — Dynamic location lookups** *(`provider` is required on all four)*

```
GET  https://shop.example.com/api/v3/seller/courier/locations/stores?provider=pathao
GET  https://shop.example.com/api/v3/seller/courier/locations/stores?provider=redx
GET  https://shop.example.com/api/v3/seller/courier/locations/cities?provider=pathao
GET  https://shop.example.com/api/v3/seller/courier/locations/cities?provider=tcs
GET  https://shop.example.com/api/v3/seller/courier/locations/cities?provider=garuda_express
GET  https://shop.example.com/api/v3/seller/courier/locations/zones/1?provider=pathao
GET  https://shop.example.com/api/v3/seller/courier/locations/zones/KHI?provider=tcs
GET  https://shop.example.com/api/v3/seller/courier/locations/areas/298?provider=pathao
GET  https://shop.example.com/api/v3/seller/courier/locations/areas/all?provider=redx
```

**Group D — Quote and booking**

```
POST https://shop.example.com/api/v3/seller/courier/estimate
POST https://shop.example.com/api/v3/seller/courier/dispatch     # first booking, or a switch with replace_existing: true
POST https://shop.example.com/api/v3/seller/courier/revise        # correct the details of an already-booked order
```

**Group E — Tracking**

```
GET  https://shop.example.com/api/v3/seller/courier/track/CN-88
```

**Group F — Inbound carrier webhook** *(the carrier calls these; `vendor-12` = seller 12, drop it for platform-owned)*

```
POST https://shop.example.com/courier/webhook/pathao/vendor-12
POST https://shop.example.com/courier/webhook/redx/vendor-12?token=s3cr3t-token
POST https://shop.example.com/courier/webhook/logistics-in/vendor-12
POST https://shop.example.com/courier/webhook/lalamove
```

**Group G — Host order reads**

```
GET  https://shop.example.com/api/v3/seller/orders/100143
GET  https://shop.example.com/api/v1/customer/order/details?order_id=100143
POST https://shop.example.com/api/v1/order/track-order            # body: order_id + phone_number
```

**The web-panel twins** (§4.9) — same paths under a different prefix, session cookie instead of a token:

```
GET  https://shop.example.com/vendor/courier/locations/cities?provider=pathao
GET  https://shop.example.com/vendor/courier/locations/zones/1?provider=pathao
GET  https://shop.example.com/vendor/courier/locations/areas/298?provider=pathao
GET  https://shop.example.com/vendor/courier/locations/stores?provider=pathao
GET  https://shop.example.com/vendor/courier/track/CN-88
POST https://shop.example.com/vendor/courier/estimate
POST https://shop.example.com/vendor/courier/dispatch
POST https://shop.example.com/vendor/courier/revise
GET  https://shop.example.com/admin/courier/locations/cities?provider=tcs
GET  https://shop.example.com/admin/courier/locations/areas/all?provider=redx
GET  https://shop.example.com/admin/courier/track/CN-88
POST https://shop.example.com/admin/courier/estimate
POST https://shop.example.com/admin/courier/dispatch
POST https://shop.example.com/admin/courier/revise
```

---

## 1. Current state at a glance

| Capability | Web (admin) | Web (vendor) | Vendor API v3 | User API v1 |
|---|---|---|---|---|
| Configure own delivery partners | ✅ | ✅ | ✅ §4.1 | n/a |
| List enabled delivery partners | ✅ | ✅ | ✅ §4.3 | n/a |
| Location cascade (city/zone/area/store) | ✅ | ✅ | ✅ §4.4 | n/a |
| Estimate delivery charge | ✅ | ✅ | ✅ §4.5 | n/a |
| Send to courier (book shipment) | ✅ | ✅ | ✅ §4.6 | n/a |
| **Switch to another delivery partner after booking** | ✅ | ✅ | ✅ §4.6 | n/a |
| **Revise delivery info after booking** (local only) | ✅ | ✅ | ✅ §4.11 | n/a |
| Read shipment (partner + tracking no. + status) | ✅ | ✅ | ✅ §4.2 | ✅ |
| Tracking history timeline | ✅ | ✅ | ✅ §4.7 | ❌ |
| Public tracking URL | ✅ | ✅ | ✅ §4.2 | ✅ |
| Manual 3rd-party assign (legacy, no carrier API) | ❌ removed | ❌ removed | ❌ `403` | n/a |

Two distinct flows share the `third_party_delivery` value in `orders.delivery_type`:

- **Integrated** — booked through the Courier module against a real carrier API. Produces a `courier_shipments` row.
- **Manual (legacy, no longer writable)** — an operator typed a courier name and tracking number by hand. No carrier call, no shipment row. Every write path is closed: both panels' modal, `POST /api/v{2,3}/seller/orders/assign-third-party-delivery` and the `delivery_type=third_party_delivery` branch of `POST /api/v3/seller/orders/order-detail-info-update` all refuse. Orders assigned this way before the change stay readable, read-only.

Both write `delivery_service_name` and `third_party_delivery_tracking_id` onto the order, so a client that only reads those two columns works for either flow. A successful integrated dispatch writes them too (via the `ShipmentDispatched` → `CourierShipmentDispatchedListener` write-back).

**A pre-existing manual assignment can only ever show partner + tracking number** — there is no shipment row, so shipment status, delivery fee, dispatch time, public tracking URL and tracking history do not exist for it, on the web or in either API. Both order-details screens and both APIs render the two flows through one shape (`_delivery-partner-selection.blade.php` / the `courier_shipment` object), the manual one simply carrying nulls, and the panels offer the partner picker underneath it so the order can be re-booked against a real carrier.

### 1.1 Configuration is owned per panel — this is the change that matters most

Delivery-partner credentials and enable toggles live on `courier_provider_settings` keyed by **owner** (`platform`, or `vendor:<seller id>`). **There is no inheritance.** A vendor that has not configured RedX has RedX unconfigured and disabled — the platform's RedX row is never consulted.

Consequences for the apps:

- "Enabled partners" is **per seller**, not global. Any provider list the vendor app renders must come from the authenticated seller's own configuration.
- On the seller API the owner is resolved from the bearer token: `seller_api_auth` puts the `Seller` model on the request and `HostCourierOwnerResolver` reads it. So every courier read on `api/v3/seller/*` is already scoped to that vendor without any extra parameter.
- Everything a vendor does on their own carrier accounts is gated by the business setting `vendor_delivery_partner_setup` (default on) — config, the provider list, the location lookups, estimate, dispatch and revise all abort `404` while it is off. Only `track` stays reachable, so a shipment booked before the switch went off is still visible. When it is off, vendors use only what the admin books for them.

### 1.2 One shipment is active; earlier ones are superseded, not deleted

An order has **at most one active shipment**. Switching partners (§4.6) marks the old row `superseded_at` and inserts a new one; nothing is deleted. Everything that reads "the order's shipment" — `courier_shipment` in both APIs, the panels, the storefront tracking link — reads the **active** one only.

Three consequences the apps must hold:

- **`courier_shipment` after a switch describes the new partner**, and the old consignment id disappears from it. Re-fetch order details after a switch instead of merging into cached state.
- **The old consignment stays trackable by id.** `GET courier/track/{consignmentId}` still resolves a superseded consignment, and its carrier's webhooks still land on that row — so a stale carrier update can never overwrite the active shipment's status. Nothing in either app should present a superseded consignment as current.
- **The previous partner is never cancelled for you.** A switch makes no request to the old carrier at all, so two live bookings can exist for one parcel. The merchant must cancel with the old partner themselves, and the app **must** say so before switching (§5.2).

`can_manage` on the shipment object (§4.2) tells the caller whether *its own* delivery-partner account holds the active shipment. A vendor looking at an order the admin booked on the platform's account sees `can_manage: false` — read-only, no switch, no revise. Both write paths enforce it server-side, so hiding the buttons is a UX nicety, not the security boundary.

---

## 2. Vendor API v3 — order details (existing)

```
GET /api/v3/seller/orders/{order_id}
Authorization: Bearer <seller auth_token>
```

Example: `GET https://shop.example.com/api/v3/seller/orders/100143` — the order id is a path segment, no query parameters.

Returns a **flat array of order-detail rows** (not an order object). Each row carries the parent order under `order`.

### Current response (trimmed)

```json
[
  {
    "id": 512,
    "order_id": 100143,
    "product_id": 88,
    "qty": 1,
    "price": 4500,
    "variation": [{ "key": "Color", "value": "Red" }],
    "modified_variation": [{ "key": "Color", "value": "Red" }],
    "current_stock": 12,
    "current_price": 4500,
    "product_details": { "id": 88, "name": "…", "thumbnail_full_url": "…" },
    "edit_order_payment_histories": [],
    "delivery_partner_available": true,
    "order": {
      "id": 100143,
      "order_status": "confirmed",
      "payment_status": "paid",
      "order_amount": 4800,
      "shipping_responsibility": "sellerwise_shipping",
      "delivery_type": "third_party_delivery",
      "delivery_service_name": "RedX (Bangladesh)",
      "third_party_delivery_tracking_id": "TRK-88",
      "delivery_man_id": null,
      "deliveryman_charge": 0,
      "expected_delivery_date": null
    }
  }
]
```

**What the vendor app can already show without any backend change:** `order.delivery_service_name` as the partner and `order.third_party_delivery_tracking_id` as the tracking number. These are populated for integrated bookings as well as manual ones.

### `delivery_partner_available`

Boolean, **identical on every row** — it describes the order, not the line item. Computed by `deliveryPartnerAvailable($order->shipping_responsibility, 'vendor')` and true only when **all three** hold:

1. business setting `third_party_delivery_service` is on;
2. the order's `shipping_responsibility` is `sellerwise_shipping` — i.e. this vendor owes the shipping, not the admin (`inhouse_shipping` orders belong to the admin panel, and the flag is `false` for the vendor even though the vendor can see the order);
3. the **authenticated seller** has at least one enabled delivery partner of their own (§1.1).

**The app must hide its "third party delivery" option when this is false**, exactly as the vendor order-details screen does. An order that is already on `third_party_delivery` keeps showing its assignment either way. The write endpoints below are unchanged and still accept the value, so an app build that has not adopted the flag keeps working.

Live shipment status, status tone, consignment id, delivery fee and the public tracking URL come from the `courier_shipment` object this endpoint now carries — see §4.2.

### Manual assign — removed

```
POST /api/v3/seller/orders/assign-third-party-delivery      → 403, always
POST /api/v2/seller/orders/assign-third-party-delivery      → 403, always
```

```json
{ "success": 0, "message": "Manual third party delivery assignment is no longer available. Send the order to a delivery partner instead." }
```

Third-party delivery is **integrated only**. The routes stay registered so an old app build gets an explanatory `403` rather than a `404`; there is no request body that makes them succeed. Book through §4.6 `POST /api/v3/seller/courier/dispatch` instead.

`POST /api/v3/seller/orders/order-detail-info-update` answers the same `403` when it receives `delivery_type=third_party_delivery` for an order with no `courier_shipments` row. For an order already booked through a carrier the value is accepted and ignored — dispatch already set `delivery_type` and both tracking columns — so an app that echoes the current delivery type while updating other fields keeps working. `delivery_service_name` and `third_party_delivery_tracking_id` are no longer read from any request.

`PUT /api/v3/seller/orders/assign-delivery-man` still reverses an assignment — it sets `delivery_type = 'self_delivery'` and nulls both third-party columns. It does not cancel a booked carrier shipment; the `courier_shipments` row survives, so re-fetch order details after the call rather than trusting local state.

The admin and vendor web panels lost the same capability: the "Update third party delivery info" modal and its `orders/update-deliver-info` route are gone from both panels.

---

## 3. User API v1 — order details

```
GET /api/v1/customer/order/details?order_id=100143
Authorization: Bearer <customer token>
```

Example: `GET https://shop.example.com/api/v1/customer/order/details?order_id=100143` — `order_id` is the only query parameter.

Returns a flat array of order-detail rows. A **`courier_shipment` object is present on every row** (identical on each row — it describes the order, not the line item).

### The field

```json
{
  "id": 512,
  "order_id": 100143,
  "product_details": { "…": "…" },
  "reviewData": null,

  "courier_shipment": {
    "delivery_partner": "RedX (Bangladesh)",
    "tracking_number": "TRK-88",
    "shipment_status": "in_transit",
    "shipment_status_label": "In Transit",
    "tracking_url": "https://redx.com.bd/track/TRK-88"
  }
}
```

### `courier_shipment` contract

| Field | Type | Notes |
|---|---|---|
| `delivery_partner` | `string` | Carrier label, e.g. `Pathao (Bangladesh)`. Falls back to `delivery_service_name` for manual assigns. |
| `tracking_number` | `string` | Carrier tracking code, falling back to the consignment id. Falls back to `third_party_delivery_tracking_id` for manual assigns. |
| `shipment_status` | `string \| null` | One of the values in §6. **`null` for manual assigns** — there is no carrier to ask. |
| `shipment_status_label` | `string \| null` | The same status in display wording, already translated — **render this, never `shipment_status`** (§6). `null` whenever `shipment_status` is. |
| `tracking_url` | `string \| null` | Public tracking page, taken from the carrier's own booking payload (`shareLink` / `tracking_url`). **`null` unless the carrier supplied one.** |

`courier_shipment` itself is **`null`** when the order has neither a courier shipment nor manual delivery info. It is also `null` when the Courier module is uninstalled or disabled — the endpoint still answers `200`.

Nothing vendor-only is exposed here: no `provider` id, no `consignment_id`, no `delivery_fee`. Keep it that way.

### Resolution order (backend behaviour, for your reference)

1. **Active** `courier_shipments` row for the order → full object, `shipment_status` populated. A shipment superseded by a partner switch (§1.2) is never used here.
2. No shipment, but `delivery_service_name` / `third_party_delivery_tracking_id` set → partner + tracking number, `shipment_status` and `tracking_url` `null`.
3. Neither → `null`.

**The shape of this payload is unchanged.** Switch, revise and supersede added no key here and removed none. The only behavioural difference is step 1 reading the active shipment rather than the latest row, which matters only for an order whose partner was switched — the customer then sees the new partner, as they should.

> **Note on placement.** `courier_shipment` repeats on every row because this endpoint has no order-level envelope. Read it from the first element. If you would rather it were not duplicated, that is a breaking response reshape — raise it before it ships to production apps.

### 3.1 User API v1 — public order tracking

```
POST /api/v1/order/track-order
```

```json
{ "order_id": "100143", "phone_number": "+15551112222" }
```

This is the API twin of the storefront `/track-order` page, and it is **reachable without a customer token** — a guest tracks an order with the order id and the phone number on it. It returns the same flat array of order-detail rows, and it carries exactly **one** courier field:

```json
{
  "id": 512,
  "order_id": 100143,
  "product_details": { "…": "…" },

  "courier_tracking_url": "https://share.sandbox.lalamove.com?BD100260…&sign=435a11…"
}
```

| Field | Type | Notes |
|---|---|---|
| `courier_tracking_url` | `string \| null` | The active shipment's public tracking page, when the carrier supplied one. `null` otherwise — including when the order is booked with a partner that publishes no tracking URL |

**No courier-module data beyond that URL is added here, by design.** No `courier_shipment` object, no consignment id, no shipment status, no status tone, no delivery fee, no dispatch time, no tracking history — none of what §3 gives an authenticated customer. Whoever holds an order id and a phone number reaches this response, so it carries a link the customer could have been emailed and nothing more.

**One honest caveat.** The nested `order` object on each row still carries the host columns `delivery_type`, `delivery_service_name` and `third_party_delivery_tracking_id` — so the partner's *name* and the tracking *number* are readable here. That is **pre-existing**: those are plain `orders` columns, written by manual assignment long before the Courier module existed, and this endpoint has always serialized the whole order. Nothing in the courier work added them, and removing them is a breaking response reshape for every app build in the field — see §7.14. The storefront `/track-order` page, which renders fields explicitly rather than dumping the model, shows neither.

**What the app must do:** show a *Track with Delivery Partner* button when `courier_tracking_url` is non-`null` and open it in a browser; show nothing at all when it is `null`. Do **not** dress the screen up with `order.delivery_service_name` or `order.third_party_delivery_tracking_id` just because they happen to be in the payload — match the web page, which shows the link alone. The signed-in order-details screen (§3) is where partner and tracking number belong.

Like `courier_shipment`, the value repeats identically on every row; read it from the first element. It is `null` when the Courier module is uninstalled, and the endpoint still answers `200`.

---

## 4. The vendor courier API

Ordered by dependency, starting with configuration (§4.1) — without it a vendor has no enabled partner and every list below is empty.

Auth for the whole group: `seller_api_auth` (bearer token). That middleware is also what makes the owner resolution in §1.1 work, so the seller automatically sees only their own providers and their own shipments — no `seller_id` parameter anywhere. Routes live in `Modules/Courier/routes/seller_api.php`.

Every endpoint in this section forces `Accept: application/json`, so validation failures, the `404` gate in §4.1 — which covers every endpoint here except `track` — and the `401` from an expired token all answer JSON regardless of what the client sends.

### 4.1 Vendor courier configuration *(prerequisite for everything else)*

Both panels share one service and one FormRequest — `CourierConfigService::save()` and `SaveCourierProviderRequest` — and the API adds a third controller over the same two, so there is exactly one write path for `courier_provider_settings`.

#### Read the catalog

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/config` |
| **Method** | `GET` |
| **Purpose** | The whole setup screen in one response — every registered provider, its credential-field contract, its stored values, its country/environment lists and its per-owner callback URL |
| **Required parameters** | None. The owner comes from the bearer token |
| **When it is called** | On opening the Courier Configuration screen, and again after every successful save and after any country change |
| **Providers** | All 10 — enabled or not, configured or not |
| **Mandatory?** | **Mandatory.** Nothing else in the flow can be rendered without it; `fields` is the only source of a carrier's credential set |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/config` — no query parameters |

```
GET /api/v3/seller/courier/config
Authorization: Bearer <seller auth_token>
```

```json
{
  "providers": [
    {
      "id": "redx",
      "label": "RedX (Bangladesh)",
      "is_enabled": true,
      "is_configured": true,
      "environment": "sandbox",
      "environments": ["sandbox", "live"],
      "base_urls": { "sandbox": "https://sandbox.redx.com.bd", "live": "https://openapi.redx.com.bd" },
      "country": "BD",
      "countries": [{ "id": "BD", "label": "Bangladesh" }],
      "webhook_url": "https://shop.example.com/courier/webhook/redx/vendor-12",
      "fields": [
        { "key": "api_access_token", "label": "API Access Token", "type": "password", "required": true,
          "help": "Issued by RedX for your merchant account." },
        { "key": "default_pickup_area_id", "label": "Default Pickup Area ID", "type": "text", "required": false,
          "help": "…Without it RedX cannot estimate a delivery charge." }
      ],
      "credentials": { "api_access_token": "…", "default_pickup_area_id": "1" }
    }
  ]
}
```

Backend: `app(ProviderRegistry::class)->catalog()` returns this array verbatim, already scoped to the authenticated seller.

| Field | Notes |
|---|---|
| `fields` | **The form contract.** Render from this array only — never hardcode a carrier's credential set. `type` is `text`, `password` or `select`; a `select` carries `options: [{id, label}]` and `translatable_options: false` when the options are data (district lists, station codes) rather than vocabulary. `required` drives the asterisk **and** the save validation. |
| `country` / `countries` | Empty `country` means nothing chosen yet. Changing it re-scopes both `fields` (country-dependent selects) and `delivery_types` — re-read the catalog after saving a country change. |
| `environment` / `environments` | Usually `sandbox` / `live`. Some drivers simulate sandbox and never hit the network. |
| `base_urls` | Informational; a couple of drivers expose overridable hosts as credential fields instead. |
| `is_configured` | Every `required` field filled **and** the driver's own `credentialsComplete()` rule satisfied (TCS accepts either credential pair). |
| `webhook_url` | **Per owner.** A vendor's URL carries the `vendor-<id>` segment. Show it as copyable text — the vendor must register it in the carrier's own merchant panel or status webhooks never arrive. |
| `credentials` | Current stored values, **including secrets in plaintext** (see §7). |

Unlike §4.4, this endpoint lists **every registered provider**, enabled or not — that is the point of a setup screen.

#### Save one provider

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/config` |
| **Method** | `PUT` |
| **Purpose** | Write one provider's credentials, operating country, environment and enable flag for the authenticated seller |
| **Required parameters** | `provider`; then every `fields[].key` the catalog marks `required: true`, under `credentials` — unless `credentials` is omitted entirely (status-only toggle) |
| **When it is called** | On submitting a provider card, and on flipping its enable switch |
| **Providers** | All 10; the credential rules are derived per provider **and per submitted country** |
| **Mandatory?** | **Mandatory** — this is the only write path to `courier_provider_settings`. No provider is usable until it is saved and enabled |
| **Example** | `PUT https://shop.example.com/api/v3/seller/courier/config` — no query parameters; the provider is named in the JSON body, never in the URL |

```
PUT /api/v3/seller/courier/config
Authorization: Bearer <seller auth_token>
Content-Type: application/json
```

##### Envelope — types and rules

| Field | JSON type | Required | Rule (`SaveCourierProviderRequest`) |
|---|---|---|---|
| `provider` | `string` | ✅ | `required` · `string` · `in:<registered provider ids>` — `pathao`, `pathao_nepal`, `redx`, `dhl`, `lalamove`, `delhivery`, `aramex`, `shiprocket`, `tcs`, `garuda_express` |
| `is_enabled` | `boolean` | ❌ | `nullable` · `boolean`. Accepts `true`/`false`/`1`/`0`/`"1"`/`"0"`. **Absent counts as `false`** — the save always writes `is_active`, so a payload that omits it disables the provider |
| `environment` | `string` | ❌ | `nullable` · `string` · `in:sandbox,live` — every driver exposes exactly these two |
| `country` | `string` | ❌ | `nullable` · `string` · `in:<driver supportedCountries>`. ISO-3166 alpha-2, trimmed and **uppercased server-side** (`"bd"` → `"BD"`) |
| `credentials` | `object` | ❌ | `nullable` · `array`. Flat map, **string keys → string values**, one entry per `fields[].key` |
| `credentials.<key>` | `string` | per field | `required` or `nullable` exactly as `fields[].required` declares, then `string`, `max:1000`; a `select` field also gets `in:<that country's option ids>` |

**The three type traps** — all three come from the rules being written for an HTML form post, where every value arrives as a string:

1. **Every credential value is a `string`, including numeric and boolean-looking ones.** `default_package_length` is declared `type: "number"` for the *keyboard*, not the wire — its rule is still `string`. `{"default_package_length": 30}` fails with *must be a string*; send `"30"`. Yes/no selects are the strings `"0"` / `"1"`, never `false` / `true`.
2. **Empty string means absent.** Global `TrimStrings` + `ConvertEmptyStringsToNull` run on API routes too, so `""` becomes `null` — fine for a `nullable` field, a hard `422` for a `required` one. Send `null` or omit the key; never `""` as a placeholder.
3. **`credentials` is all-or-nothing, not a patch.** Present → the whole stored map is replaced (`$setting->credentials = (array) $request->input('credentials')`). Any key you leave out is wiped, secrets included.

##### The four behaviours the app must respect

1. **One provider per request.** The payload configures the provider it names; other rows are untouched. There is no bulk save.
2. **Omit `credentials` entirely for a status-only toggle.** With the key absent the request carries *no* credential rules at all — that is how the enable switch saves without re-sending secrets, and the only way to toggle a provider whose stored set is incomplete.
3. **Send `country` together with `credentials`.** Select options validate against the *submitted* country, so a value chosen for the previous country is rejected on a country change. That is intended, not a bug to work around: re-read the catalog for the new country, then save both together.
4. **A dishonest `required` flag now blocks the save.** `fields[].required` is not decoration — it becomes a `required` rule. If a field renders with an asterisk, the save will refuse without it.

##### Status-only toggle

```json
{
  "provider": "redx",
  "is_enabled": false
}
```

##### Single-country, minimal credential set — RedX (BD)

```json
{
  "provider": "redx",
  "is_enabled": true,
  "environment": "live",
  "country": "BD",
  "credentials": {
    "api_access_token": "eyJhbGciOi…",
    "default_pickup_store_id": "884",
    "default_pickup_area_id": "1",
    "webhook_token": "s3cr3t-token"
  }
}
```

Only `api_access_token` is required. `country` is optional here — a single-country driver falls back to `BD` on its own.

##### Multinational with country-scoped selects — Lalamove (HK)

```json
{
  "provider": "lalamove",
  "is_enabled": true,
  "environment": "sandbox",
  "country": "HK",
  "credentials": {
    "api_key": "pk_test_…",
    "api_secret": "sk_test_…",
    "language": "zh_HK",
    "default_service_type": "MOTORCYCLE",
    "sender_name": "Acme Warehouse",
    "sender_phone": "+85212345678",
    "origin_address": "1 Harbour Rd, Wan Chai",
    "origin_latitude": "22.2833",
    "origin_longitude": "114.1722",
    "payment_method": "CREDIT",
    "is_pod_enabled": "1"
  }
}
```

`language`, `default_service_type` and the country list all come from that market's profile — read them out of the catalog for the selected `country` rather than hardcoding. Note the coordinates are **strings**.

##### Postal mode with numeric-typed fields — DHL (US)

```json
{
  "provider": "dhl",
  "is_enabled": true,
  "environment": "sandbox",
  "country": "US",
  "credentials": {
    "api_username": "apiuser",
    "api_password": "…",
    "account_number": "123456789",
    "origin_company_name": "Acme Ltd",
    "origin_full_name": "Alex Doe",
    "origin_phone": "+12125550100",
    "origin_email": "ship@acme.test",
    "origin_address_line1": "350 5th Ave",
    "origin_city_name": "New York",
    "origin_postal_code": "10118",
    "default_package_length": "30",
    "default_package_width": "20",
    "default_package_height": "10"
  }
}
```

DHL declares every ISO country as supported, so `country` is a real choice here and it drives the currency and product codes.

##### Credential keys per provider

Required (✅) vs optional (—), with the wire type. All values are JSON strings; `select` shows its accepted ids.

| Provider (`country` enum) | Key | Type | Req |
|---|---|---|---|
| **pathao** (`BD`) · **pathao_nepal** (`NP`) | `client_id` | text | ✅ |
| | `client_secret` | password | ✅ |
| | `username` | text | ✅ |
| | `password` | password | ✅ |
| | `store_id` | text | — |
| | `webhook_secret` | password | — |
| | *(pathao_nepal only)* `sandbox_base_url`, `live_base_url` | text | — |
| **redx** (`BD`) | `api_access_token` | password | ✅ |
| | `default_pickup_store_id`, `default_pickup_area_id`, `webhook_token` | text / text / password | — |
| **dhl** (any ISO country) | `api_username`, `account_number`, `origin_company_name`, `origin_full_name`, `origin_phone`, `origin_address_line1`, `origin_city_name`, `origin_postal_code` | text | ✅ |
| | `api_password` | password | ✅ |
| | `default_package_length`, `default_package_width`, `default_package_height` | number *(send as string)* | ✅ |
| | `origin_email` | text | — |
| **lalamove** (12 markets) | `api_key`, `sender_name`, `sender_phone`, `origin_address`, `origin_latitude`, `origin_longitude` | text | ✅ |
| | `api_secret` | password | ✅ |
| | `language` | select — market language keys | ✅ |
| | `default_service_type` | select — market vehicles (`MOTORCYCLE`, `CAR`, `WALKER`, …) | ✅ |
| | `payment_method` | select — `CREDIT` / `CASH` / `POSTPAID` | — |
| | `is_pod_enabled` | select — `"0"` / `"1"` | — |
| **delhivery** (`IN`) | `api_token` | password | ✅ |
| | `pickup_location`, `origin_pincode` | text | ✅ |
| | `seller_name`, `seller_address` | text | — |
| | `default_shipping_mode` | select — `Surface` / `Express` | — |
| | `default_weight_grams` | number *(send as string)* | — |
| | `webhook_token` | password | — |
| **aramex** (contract markets) | `username`, `account_number`, `origin_company_name`, `origin_full_name`, `origin_phone`, `origin_line1`, `origin_city` | text | ✅ |
| | `password`, `account_pin` | password | ✅ |
| | `account_entity` | select — station codes for the selected country | ✅ |
| | `default_product_type` | select — `PPX` / `PDX` / `EPX` / `OND` | — |
| | `payment_type` | select — `P` / `C` / `3` | — |
| | `origin_email`, `origin_state`, `origin_postal_code` | text | — |
| **shiprocket** (`IN`) | `email`, `pickup_location`, `origin_pincode` | text | ✅ |
| | `password` | password | ✅ |
| | `auto_request_pickup` | select — `"0"` / `"1"` | — |
| | `default_weight_kg`, `default_length_cm`, `default_breadth_cm`, `default_height_cm` | number *(send as string)* | — |
| | `webhook_token` | password | — |
| **tcs** (`PK`) | `tcs_account`, `cost_center_code`, `shipper_name`, `shipper_mobile`, `shipper_address`, `shipper_city_name` | text | ✅ |
| | `client_id`, `username`, `shipper_city_code`, `shipper_zip` | text | — |
| | `client_secret`, `password` | password | — |
| | `service_code` | select — `O` / `C` / `V` | — |
| | `enable_simulation` | select — `"0"` / `"1"` | — |
| **garuda_express** (`NP`) | `api_key` | password | ✅ |
| | `sender_name`, `sender_mobile`, `sender_address` | text | ✅ |
| | `sender_district` | select — 77 district ids | ✅ |
| | `amount_semantics` | select — `cod` / `declared` | — |
| | `sender_email`, `sender_pin_code`, `pickup_note` | text | — |

**TCS is the one provider where the asterisks do not tell the whole story.** All four auth fields are `required: false`, but `credentialsComplete()` demands **either** `client_id` + `client_secret` **or** `username` + `password`. A save with neither pair succeeds and the provider reports `is_configured: false` — validate the pair client-side and show why the card is still incomplete.

##### Responses

```json
{ "ok": true, "message": "Delivery partner settings updated successfully" }
```

```json
{
  "message": "The credentials.api_access_token field is required.",
  "errors": {
    "credentials.api_access_token": ["The credentials.api_access_token field is required."],
    "credentials.default_service_type": ["The selected credentials.default_service_type is invalid."],
    "country": ["The selected country is invalid."]
  }
}
```

The web action answers with a redirect + toast; the API must answer JSON. Validation failures are standard `422` field errors keyed `credentials.<key>` — map them back onto the dynamic form by key, since the app never knew the field names at build time.

#### Gating

`vendorDeliveryPartnerSetupAvailable()` — business setting `third_party_delivery_service` on, `vendor_delivery_partner_setup` on (default), module bound — gates the API group exactly as the `vendor_delivery_partner_setup` middleware gates the web routes. It aborts `404`. **The app must hide the whole courier-setup screen on `404`**, not show an error: it means the platform has reserved delivery-partner setup for the admin, and the vendor's orders are dispatched on the platform's account.

The gate is not limited to this section. It wraps every endpoint that books on the vendor's own carrier account — `providers`, the four `locations/*` lookups, `estimate`, `dispatch` and `revise` — because a vendor who may not hold carrier credentials may not ship on them either. `track/{consignmentId}` is deliberately outside it, so a shipment booked before the admin switched the setting off stays visible on the order.

`GET /api/v1/config` carries `vendor_delivery_partner_setup_status` (`1`/`0`), the same boolean this helper returns. Read it at launch to decide whether to render the courier-setup screen and the send-to-partner action at all, rather than discovering the gate through a `404`.

### 4.2 Shipment data on vendor order details

`GET /api/v3/seller/orders/{id}` carries a `courier_shipment` object on every row — identical on each row, like `delivery_partner_available`. Read it from the first element.

```json
"courier_shipment": {
  "provider": "redx",
  "delivery_partner": "RedX (Bangladesh)",
  "consignment_id": "CN-88",
  "tracking_code": "TRK-88",
  "tracking_number": "TRK-88",
  "shipment_status": "in_transit",
  "shipment_status_label": "In Transit",
  "status_tone": "info",
  "delivery_fee": 60,
  "tracking_url": "https://redx.com.bd/track/TRK-88",
  "dispatched_at": "29 Jul 2026, 10:14 AM",
  "can_track": true,
  "can_manage": true,
  "dispatch_details": {
    "store_id": null,
    "recipient_name": "Taylor",
    "recipient_phone": "01885576624",
    "recipient_address": "20 Rd No. 14A, Dhaka 1209, Bangladesh",
    "city_id": null, "zone_id": null, "area_id": "1",
    "country_code": null, "postal_code": null, "city_name": null, "state_province": null,
    "latitude": null, "longitude": null,
    "source_branch": null, "source_branch_id": null,
    "destination_branch": null, "destination_branch_id": null,
    "weight": 0.5, "quantity": 1, "cod_amount": 0, "order_value": 4800,
    "item_description": "Cotton t-shirts", "delivery_type": null, "note": null
  }
}
```

Backend: `deliveryPartnerShipmentDetails($order->id)` (in `app/Utils/module-helper.php`) → `CourierService::shipmentDetailsFor()`, guarded for module absence. That one method is also what the admin and vendor order-details blades render, so web and API can never drift.

`provider`, `consignment_id`, `delivery_fee`, `can_manage` and `dispatch_details` are **vendor/admin-only** — do not add them to the v1 customer payload (§3), and never to the public tracking response (§3.1).

`status_tone` is one of `success` / `danger` / `warning` / `info` / `neutral` — use it to colour the badge instead of hardcoding a colour per status.

#### `can_manage`

`true` when the active shipment was booked on **the caller's own** delivery-partner account — the platform's for admin, this seller's for the vendor panel and the seller token. Only then may the caller switch the partner (§4.6) or revise the details (§4.11); both endpoints refuse otherwise, so treat it as the flag that shows or hides those two actions, not as the security boundary. It is `false` on a manual assign, which has no carrier account behind it at all.

#### `dispatch_details`

The delivery information that was submitted when the shipment was booked, keyed exactly as the §4.6 dispatch payload — **this is what you prefill the revise form with** (§4.11). Two cases return `{}` rather than a populated map, and neither is an error:

- a manual assign, which was never dispatched through a carrier;
- a shipment booked before this field existed. Fall back to the order's own address and recipient, exactly as a first dispatch does.

`dispatch_details` is what the *merchant typed*, not what the carrier stored. After a revise the two deliberately disagree — that is the whole point of the warning in §4.11.

`courier_shipment` falls back to the **manual assign** when the order has no active `courier_shipments` row but does carry `order.delivery_service_name` / `order.third_party_delivery_tracking_id` — the same two values the vendor order-details blade prints for a manual assign. The key set is unchanged; everything the carrier integration would have supplied is `null`, `can_track` / `can_manage` are `false` and `dispatch_details` is `{}`, so a row with `provider: null` means "typed in by hand, no carrier behind it". It is `null` only when the order has no delivery-partner assignment at all. This mirrors the v1 customer payload (§3), which already resolved in that order.

Only the **active** shipment is read (§1.2). An order whose partner was switched reports the new booking here; the superseded one is reachable only by its own consignment id.

### 4.3 Enabled partner list

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/providers` |
| **Method** | `GET` |
| **Purpose** | The seller's **enabled** partners, each carrying the contract the booking form is built from: `address_mode`, `levels`, `level_labels`, `required_fields`, `optional_fields`, `delivery_types` |
| **Required parameters** | None — owner-scoped by the token |
| **When it is called** | On opening an order that can be dispatched, before the partner picker is drawn. Fetch per order/session, never cache globally |
| **Providers** | Whatever the seller has enabled; `[]` is a normal state |
| **Mandatory?** | **Mandatory** for the booking flow — it decides which of the Group C lookups are called at all |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/providers` — no query parameters |

```
GET /api/v3/seller/courier/providers
```

```json
{
  "providers": [
    {
      "id": "redx",
      "label": "RedX (Bangladesh)",
      "address_mode": "catalog",
      "levels": ["area"],
      "level_labels": {},
      "address_fields": [],
      "required_fields": ["recipient_name","recipient_phone","recipient_address","weight","cod_amount","area_id"],
      "optional_fields": ["store_id","quantity","item_description","note","order_value"],
      "delivery_types": []
    },
    {
      "id": "lalamove",
      "label": "Lalamove (Bangladesh)",
      "address_mode": "geo",
      "levels": [],
      "level_labels": {},
      "address_fields": ["recipient_address","latitude","longitude"],
      "required_fields": ["recipient_name","recipient_phone","recipient_address","weight","cod_amount","latitude","longitude"],
      "optional_fields": ["delivery_type","quantity","item_description","note","order_value"],
      "delivery_types": [{ "id": "MOTORCYCLE", "label": "Motorcycle" }]
    }
  ]
}
```

Backend: `app(ProviderRegistry::class)->enabledOptions()` returns this array verbatim (each entry already carries `id`, `label`, `levels`, `level_labels`, `address_fields`, `address_mode`, `required_fields`, `optional_fields`, `delivery_types`). The registry is owner-scoped, so the response is **this seller's** enabled partners — an empty array is a normal state, not an error, and means the vendor has configured nothing (or the admin turned `vendor_delivery_partner_setup` off and booked nothing). Render the same "no delivery partner is enabled" empty state the web panel shows.

**`required_fields` is the contract for the app's form.** Render the asterisk and validate client-side from this array only — never hardcode which fields are required, because it varies per carrier and per selected country. The server builds its validation rules from the same array (`SendToCourierRequest::presence()`), so the two can never disagree.

**`required_fields` + `optional_fields` is the whole form.** Together they are exactly the input set the web offcanvas renders for that provider (`_delivery-partner-fields.blade.php`), so an app that renders both screens shows the merchant the same fields the web panel does. `optional_fields` is derived per driver, not hand-listed:

| Optional field | Present when |
|---|---|
| `store_id` | the driver implements `ProvidesStores` (Pathao, Pathao Nepal, RedX) — the pickup-store picker the web hides for everyone else |
| `state_province` | `address_mode` is `postal` |
| `source_branch_id`, `destination_branch_id` | `address_mode` is `branch` — the carrier's branch ids behind the branch names |
| `delivery_type` | `delivery_types` is non-empty |
| `quantity`, `item_description`, `note`, `order_value` | always |

Send them on `POST /courier/dispatch` exactly as named here; every one of them is `nullable` server-side (§4.6).

**`address_mode` decides which address UI to show:**

| Mode | Show | Carriers |
|---|---|---|
| `catalog` | city/zone/area cascade, per `levels`, relabelled per `level_labels` | Pathao, Pathao Nepal, RedX (`area` only), TCS, Garuda Express (`city` only, labelled "District") |
| `postal` | country, postal code, city, state/province | DHL, Aramex, Shiprocket, Delhivery |
| `geo` | latitude/longitude map picker | Lalamove |
| `branch` | source/destination branch pickers | (no registered driver uses it today) |

`level_labels` overrides the level captions where the carrier's vocabulary differs (Garuda's `{"city": "District"}`); translate the value before rendering. `delivery_types` is country-scoped — render the picker only when the selected provider returns a non-empty array.

### 4.4 Location cascade — the four dynamic lookups

These four are the provider-specific dropdowns. They are the reason the booking form cannot be hardcoded: which of them are called, and what they mean, is decided entirely by the chosen provider's `address_mode` and `levels` from §4.3.

**One envelope for all four**, always HTTP `200`:

```json
{ "supported": true, "options": [{ "id": "1", "name": "Dhaka" }] }
{ "supported": true, "options": [], "message": "The user credentials were incorrect" }
{ "supported": false, "options": [] }
```

| Field | Type | Meaning |
|---|---|---|
| `supported` | `boolean` | `false` → the provider is not enabled for this owner, **or** the driver does not implement this lookup. Hide the field |
| `options` | `array` | `{id, name}` pairs. `id` is the carrier's own identifier and is what the dispatch payload carries — **always a string**, never coerce to int |
| `message` | `string?` | Present only on a carrier fault. **Show it to the vendor.** An empty picker with no explanation is the single biggest usability trap in this flow |

`provider` is a **required** query parameter on all four; omitting it yields `supported: false` rather than a validation error:

```
GET https://shop.example.com/api/v3/seller/courier/locations/stores?provider=pathao
GET https://shop.example.com/api/v3/seller/courier/locations/cities?provider=pathao
GET https://shop.example.com/api/v3/seller/courier/locations/zones/1?provider=pathao
GET https://shop.example.com/api/v3/seller/courier/locations/areas/298?provider=pathao
```

Backend for all four: `Admin\CourierLocationController` — panel-agnostic and already JSON, so the seller API differs only in its auth middleware.

---

#### 4.4.1 Pickup stores

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/locations/stores` |
| **Method** | `GET` |
| **Purpose** | Populate the **Pickup Store** dropdown — the merchant's own warehouses/stores registered inside the carrier's merchant panel, one of which the rider collects from |
| **Required parameters** | `provider` (query) |
| **Response** | `options[].id` = carrier store id → goes to `store_id` on estimate/dispatch. `options[].name` = `"<store name> — <store address>"`, composed server-side |
| **When it is called** | The moment a `catalog`-mode provider is selected in the booking form, before the merchant fills anything |
| **Providers** | `pathao` (carrier `GET /aladdin/api/v1/stores`), `pathao_nepal` (same, Nepal host), `redx` (carrier `GET /v1.0.0-beta/pickup/stores`). Everyone else → `supported: false` |
| **Mandatory?** | **Optional.** `store_id` is in `optional_fields`; leaving it empty falls back to the provider's configured default (`store_id` for Pathao, `default_pickup_store_id` for RedX). Treat `supported: false` and an empty `options` list the same way — hide the field, do not warn |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/locations/stores?provider=pathao`<br>`GET https://shop.example.com/api/v3/seller/courier/locations/stores?provider=redx` |

**Injection-probe stores are filtered out server-side.** A carrier's store list is merchant-authored, and shared sandbox accounts accumulate junk records left behind by other integrators — Pathao's sandbox returns stores literally named `{{7*7}}`, `{{config}}`, `test$(id)` and ``test`id``. `options` therefore omits any store whose name contains template or shell syntax (`{{`, `}}`, `${`, `$(`, backtick, `|`, `<`, `>`). The app does not need its own filter, and a merchant whose real store carries one of those characters will not see it in the picker — they can still book against it through the configured default store id.

This is the endpoint behind "Pathao Bangladesh has a Pickup Store dropdown that loads from an API". It is **not** part of the configuration screen: there, the default store is a plain text field the merchant copies out of the carrier's panel. The dropdown exists only on the per-order booking form, and only for the three drivers that implement `ProvidesStores`.

---

#### 4.4.2 Cities — the top destination level

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/locations/cities` |
| **Method** | `GET` |
| **Purpose** | First level of the destination cascade. What "city" means is per carrier — relabel it from `level_labels` |
| **Required parameters** | `provider` (query) |
| **Response** | `options[].id` → `city_id` on estimate/dispatch, and the parent id for §4.4.3 |
| **When it is called** | Immediately after a `catalog` provider is selected, when its `levels` contains `city` |
| **Providers** | `pathao` / `pathao_nepal` (carrier city list), `tcs` (`/ecom/api/setup/citylistbycountry`, scoped to the configured country), `garuda_express` (77 Nepali **districts** from the module's own table — `level_labels: {"city": "District"}`), `redx` (synthetic single option, never asked for) |
| **Mandatory?** | **Mandatory** for Pathao, Pathao Nepal, TCS and Garuda Express — `city_id` is in their `required_fields`. Not applicable to every `postal` and `geo` provider |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/locations/cities?provider=pathao`<br>`GET https://shop.example.com/api/v3/seller/courier/locations/cities?provider=tcs`<br>`GET https://shop.example.com/api/v3/seller/courier/locations/cities?provider=garuda_express` |

`branch` mode (no registered driver uses it today) reuses this same endpoint as its branch list: `name` goes into `source_branch` / `destination_branch`, `id` into the `*_branch_id` companions.

---

#### 4.4.3 Zones — the second destination level

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/locations/zones/{cityId}` |
| **Method** | `GET` |
| **Purpose** | Second level, scoped to the chosen city |
| **Required parameters** | `cityId` (path — an `id` from §4.4.2), `provider` (query) |
| **Response** | `options[].id` → `zone_id`, and the parent id for §4.4.4 |
| **When it is called** | On every change of the city select. Reset zone **and** area first — a stale area under a new city is a booking to the wrong place |
| **Providers** | `pathao` / `pathao_nepal` (carrier zone list for that city), `tcs` (`/ecom/api/setup/areacode`, TCS's *area* mapped onto the zone level), `redx` (synthetic, never asked for) |
| **Mandatory?** | **Mandatory** for Pathao, Pathao Nepal and TCS. Not applicable elsewhere — Garuda Express implements the method but returns `[]`, and its `levels` never asks for it |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/locations/zones/1?provider=pathao` — `1` = the Dhaka city id from §4.4.2<br>`GET https://shop.example.com/api/v3/seller/courier/locations/zones/KHI?provider=tcs` |

---

#### 4.4.4 Areas — the third destination level

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/locations/areas/{zoneId}` |
| **Method** | `GET` |
| **Purpose** | Final destination level — the value most carriers actually route on |
| **Required parameters** | `zoneId` (path — an `id` from §4.4.3, **or the literal `all`** for RedX), `provider` (query) |
| **Response** | `options[].id` → `area_id` |
| **When it is called** | On every change of the zone select. For RedX there is no cascade at all: the form loads `locations/areas/all?provider=redx` once, as soon as the provider is picked |
| **Providers** | `pathao` / `pathao_nepal` (carrier area list for that zone), `redx` (carrier `GET /v1.0.0-beta/areas`, nationwide flat list), `tcs` (`/ecom/api/setup/blockcode`, TCS's *block* mapped onto the area level) |
| **Mandatory?** | **Mandatory** for Pathao, Pathao Nepal, RedX and TCS — `area_id` is in all four `required_fields`. RedX additionally re-reads this list **server-side** during dispatch to resolve the area's name, so a wrong `area_id` fails at booking time, not at form time |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/locations/areas/298?provider=pathao` — `298` = the zone id from §4.4.3<br>`GET https://shop.example.com/api/v3/seller/courier/locations/areas/all?provider=redx` — the literal `all`, no cascade |

### 4.5 Estimate delivery charge

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/estimate` |
| **Method** | `POST` |
| **Purpose** | Ask the carrier what this parcel will cost, before committing to a booking |
| **Required parameters** | `provider` (must be enabled for this seller) and `weight`. Everything else is `nullable` — but each carrier needs its own subset filled or the quote is meaningless (§8) |
| **When it is called** | On the merchant tapping *Estimate* in the booking form — never automatically, and never after dispatch |
| **Providers** | `pathao`, `pathao_nepal` (price-plan), `redx`, `dhl`, `lalamove`, `delhivery`, `shiprocket`. **Not** `aramex`, `tcs`, `garuda_express` |
| **Mandatory?** | **Optional everywhere.** It is a convenience quote; dispatch never requires it, and a provider that answers `supported: false` must simply not show the control |
| **Example** | `POST https://shop.example.com/api/v3/seller/courier/estimate` — no query parameters; every value goes in the JSON body below |

```
POST /api/v3/seller/courier/estimate
```

```json
{
  "provider": "redx",
  "store_id": null,
  "weight": 0.5,
  "cod_amount": 0,
  "city_id": null,
  "zone_id": null,
  "area_id": "1",
  "country_code": null,
  "postal_code": null,
  "city_name": null,
  "latitude": null,
  "longitude": null,
  "source_branch": null,
  "source_branch_id": null,
  "destination_branch": null,
  "destination_branch_id": null,
  "recipient_address": null,
  "delivery_type": null
}
```

Only `provider` (must be enabled for this seller) and `weight` are required; everything else is nullable and follows the provider's `address_mode`. `delivery_type` is a **string** from `delivery_types`.

```json
{ "ok": true, "supported": true, "total": 110, "currency": "BDT" }
{ "ok": true, "supported": false }
{ "ok": false, "message": "Set \"Default Pickup Area ID\" for RedX in Courier Configuration — a delivery charge cannot be estimated without the pickup area." }
```

`supported: false` means the carrier exposes no rate API. Hide the estimate control rather than showing a zero. Today: **Pathao / Pathao Nepal** (price plan), **RedX, DHL, Lalamove, Delhivery, Shiprocket** support it; **Aramex, TCS, Garuda Express** do not.

Backend: `SellerApi\CourierDispatchController::estimate()`, over the same `EstimateCourierChargeRequest` and `CourierService::estimate()` as the web panels.

### 4.6 Send to courier

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/dispatch` |
| **Method** | `POST` |
| **Purpose** | Book the shipment with the carrier, persist a `courier_shipments` row and write the partner + tracking number back onto the host order. With `replace_existing: true`, **switch** an already-booked order to a different partner |
| **Required parameters** | `provider`, `host_order_reference`, `cod_amount` — always. Everything else follows that provider's `required_fields` from §4.3 (table below) |
| **When it is called** | On submitting the booking form. Without `replace_existing` a second call for the same order is rejected; with it, the call is a switch and requires an active shipment to replace |
| **Providers** | All 10 — every registered driver implements `CreatesOrders` |
| **Mandatory?** | **Mandatory.** Since manual third-party assignment was removed, this is the *only* way to put an order on a delivery partner |
| **Example** | `POST https://shop.example.com/api/v3/seller/courier/dispatch` — no query parameters; the order is named by `host_order_reference` in the body, never in the URL |

```
POST /api/v3/seller/courier/dispatch
```

Full payload — field presence follows `required_fields` from §4.3:

```json
{
  "provider": "redx",
  "host_order_reference": "100143",

  "recipient_name": "Taylor",
  "recipient_phone": "01885576624",
  "recipient_address": "20 Rd No. 14A, Dhaka 1209, Bangladesh",

  "store_id": null,

  "city_id": null,
  "zone_id": null,
  "area_id": "1",

  "country_code": null,
  "postal_code": null,
  "city_name": null,
  "state_province": null,

  "latitude": null,
  "longitude": null,

  "source_branch": null,
  "source_branch_id": null,
  "destination_branch": null,
  "destination_branch_id": null,

  "weight": 0.5,
  "quantity": 1,
  "cod_amount": 0,
  "order_value": 4800,
  "item_description": "Order #100143",
  "delivery_type": null,
  "note": null
}
```

Validation, exactly as `SendToCourierRequest` declares it:

| Field | Rule |
|---|---|
| `provider` | required; must be a provider **this seller** has enabled |
| `replace_existing` | optional; boolean, default `false`. `true` turns the call into a **switch** (below) |
| `host_order_reference` | required; string; the order id |
| `recipient_name` / `_phone` / `_address` | per `required_fields`; max 100 / 20 / 220 |
| `weight` | per `required_fields`; numeric ≥ 0, **kg** |
| `cod_amount` | **always required**, numeric ≥ 0. **Send `0` when `payment_status` is `paid`** — nothing to collect |
| `city_id` / `zone_id` / `area_id` | per `required_fields`; **strings**, not integers |
| `country_code` | per `required_fields`; 2-letter ISO, `postal` mode |
| `postal_code` / `city_name` | per `required_fields`; max 20 / 100 |
| `state_province` | always optional; max 100 |
| `latitude` / `longitude` | per `required_fields`; `-90..90` / `-180..180` |
| `source_branch` / `destination_branch` | per `required_fields`; max 100. The `*_branch_id` companions are always optional |
| `quantity` | optional; integer ≥ 1, defaults to 1 |
| `delivery_type` | optional; **string**, provider vocabulary (`48`, `MOTORCYCLE`, `PPX`) — never coerce to int |
| `store_id` | optional; omit to use the configured default pickup store |
| `order_value` | optional; declared value for insurance/COD-fee maths |
| `item_description` | optional; printed on the label |
| `note` | optional; max 500; rider instructions |

Response (the web flow answers with a redirect + toast; the API answers JSON, `200` either way — branch on `ok`, not on the status code):

```json
{
  "ok": true,
  "message": "Sent to courier. Consignment: CN-88",
  "courier_shipment": { "…": "as in §4.2" }
}
```

```json
{ "ok": false, "message": "This order has already been sent to a courier." }
```

Errors surface as `CourierException` messages — already merchant-readable. Pass them through verbatim. The invariants that produce them: the provider must be enabled for the resolved owner, the driver must implement `CreatesOrders`, and the order's shipment state must match the intent (`replace_existing` absent → no active shipment may exist; present → one must, on your own account, with a different partner).

**Side effect the app must account for:** on success the module dispatches `ShipmentDispatched`, and the host listener writes `delivery_type = 'third_party_delivery'`, `delivery_service_name`, `third_party_delivery_tracking_id`, and clears `delivery_man_id`, `deliveryman_charge` and `expected_delivery_date`. Warn the vendor before dispatching an order that already has a delivery man assigned — the web form shows exactly that warning.

#### Switching to a different partner — `replace_existing`

Send the identical payload you would send for a first booking, naming the **new** partner and filling **its** `required_fields`, plus:

```json
{ "replace_existing": true }
```

Carrier-specific ids do not survive a switch. `city_id`, `zone_id`, `area_id`, `store_id` and `delivery_type` in `dispatch_details` belong to the *old* carrier's catalogue — a Pathao city id means nothing to Lalamove. Prefill only the carrier-neutral values (recipient, address, weight, quantity, COD, contents, note, coordinates, postal fields) and re-run the §4.4 lookups for the new partner. The web offcanvas does exactly this.

What the server does, in order: validate → call the **new** carrier → on success, mark the old shipment superseded and insert the new one, both in one transaction. So:

- **A carrier rejection changes nothing.** `{ "ok": false }` with the old shipment still active and no new row written. Safe to retry.
- **The old carrier is never contacted.** No cancellation, no update, whatever that driver supports. Two live bookings for one parcel is the real-world outcome, and the merchant must cancel the old one themselves.
- **Ownership is enforced.** A seller token cannot supersede a shipment the admin booked on the platform account (`can_manage: false` in §4.2).

Four refusals, all `{ "ok": false }` with a merchant-readable message:

```json
{ "ok": false, "message": "This order has already been sent to a courier." }
{ "ok": false, "message": "This order is not assigned to a courier, so there is nothing to switch." }
{ "ok": false, "message": "This order is already assigned to the selected delivery partner." }
{ "ok": false, "message": "This order is assigned to a courier account you do not have access to." }
```

The first is the flag missing when it was needed; the second is the flag sent when there was nothing to replace; the third means you offered the current partner as a switch target — filter it out of the picker, as the web panel does; the fourth is the ownership check. To change the *information* without changing the partner, use §4.11 — not a switch to the same partner.

These four are service-level `CourierException`s, so they arrive as `{ "ok": false, "message": … }` with HTTP `200`. The **separate** `422` you will hit far more often on a switch is `provider` not being enabled for the calling account — a vendor whose own configuration has no row for the new partner cannot book with it, whatever the admin has enabled (§1.1):

```json
{ "message": "The selected delivery partner is not enabled",
  "errors": { "provider": ["The selected delivery partner is not enabled"] } }
```

Build the switch picker from §4.3 rather than from a hardcoded list and this cannot happen: that endpoint returns only what the caller has enabled.

**The app must confirm before switching.** The web panels open a modal naming both partners and stating that switching may cause a conflict in which both partners try to process or collect the same order, and that the previous partner is not cancelled automatically. Reproduce that wording; it is the only control over the duplicate-booking risk.

### 4.7 Tracking history

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/track/{consignmentId}` |
| **Method** | `GET` |
| **Purpose** | The carrier's event timeline for one booked consignment |
| **Required parameters** | `consignmentId` (path) — from `courier_shipment.consignment_id` (§4.2), and it must belong to the authenticated seller |
| **When it is called** | Only when `courier_shipment.can_track` is `true`, and only on the merchant opening the timeline — not on screen load |
| **Providers** | `redx`, `dhl`, `lalamove`, `delhivery`, `aramex`, `shiprocket`, `tcs`, `garuda_express`. **Not** `pathao` / `pathao_nepal` — they expose current-status lookup, not event history (§7.1) |
| **Mandatory?** | **Optional.** A read-only convenience; nothing in the booking flow depends on it |
| **Example** | `GET https://shop.example.com/api/v3/seller/courier/track/CN-88` — `CN-88` = `courier_shipment.consignment_id`; URL-encode it, carrier ids can carry slashes |

```
GET /api/v3/seller/courier/track/{consignmentId}
```

```json
{
  "ok": true,
  "status": { "status": "picked_up", "label": "Picked up", "tone": "success" },
  "events": [
    { "status": "in_transit", "label": "In transit", "tone": "info",
      "time": "29 Jul 2026, 10:40 AM", "description": "Parcel left Dhaka hub" },
    { "status": "picked_up", "label": "Picked up", "tone": "success",
      "time": "29 Jul 2026, 09:05 AM", "description": "Collected from merchant" }
  ]
}
```

Newest first. `label` is translated; `tone` colours the row. `{ "ok": false, "message": "…" }` on failure, and `events: []` means the carrier has nothing yet — show "no tracking updates yet", not an error.

**`status` is the shipment's own status, and it is the only thing the status badge may follow.** Calling this endpoint first refreshes the shipment from the carrier's current-status lookup (`ProvidesOrderInfo`) and persists any change, so the value returned here is the same one `courier_shipment.shipment_status` will carry on the next order read (§4.2) — re-render the badge from it. A carrier that is unreachable, or that answers with a raw status the driver cannot map, leaves the stored status standing and returns it unchanged; a driver without a status lookup always returns the stored value. `status` is `null` only for a consignment with no shipment row.

**`time` is a display string, not a machine timestamp.** Carriers each stamp their events differently — RedX returns UTC ISO-8601 (`2020-03-16T05:44:24.000Z`), Delhivery an offset-bearing local time, TCS a written-out date — so `PresentsTrackingEvents` normalizes every one of them to **the timezone the admin selected in System Setup → Business Settings** in the same `d M Y, h:i A` shape `courier_shipment.dispatched_at` already uses (§4.2). Render it verbatim; do not parse it. A value the carrier sent in a shape no date parser accepts is passed through untouched rather than dropped, so treat `time` as free text and never assume it will parse.

**Some carriers ship no status on a tracking event.** RedX's `/parcel/track` returns only a message and a time. Messages confirmed against RedX are resolved to the normal vocabulary — `"Package is created successfully"` comes back as `"status": "pending"`, `"label": "Pending"`, with the sentence kept in `description` — so a timeline row and the Shipment Status field never describe one moment in two different vocabularies. An unrecognised message stays `"status": "unknown"` and carries the carrier's sentence in `label` instead of a status word.

Render `label` as the event heading, `description` beneath it, and suppress `description` when it repeats `label` — the web timeline does exactly that. **Never drive the status badge from an event**: the timeline is the carrier's log, `status` is the shipment's status.

**A carrier event can predate the dispatch.** RedX's sandbox answers every consignment with the same canned record (`2020-03-16T05:44:24.000Z`), so a parcel booked today shows a 2020 event. `time` is the carrier's own value rendered verbatim (see above) and is not reconciled against `courier_shipment.dispatched_at`; do not treat the two as one timeline.

Only call this when `can_track` is `true` (§4.2). Tracking is resolved against the **owner that booked the shipment**, so a consignment booked by the vendor is tracked on the vendor's credentials.

**Unlike the web route, the API rejects a consignment that is none of the authenticated seller's business** — an unknown id, or one belonging to another seller's order, answers `{ "ok": false }` rather than tracking someone else's parcel. Consignment ids are carrier-issued and guessable, and this route is reachable by any vendor with a token. **"Its business" means the shipment is on one of that seller's orders, not that the seller booked it** — a shipment the admin booked on the platform's account against a vendor's order tracks normally from the vendor app, exactly as it does on the vendor panel, which is the only way an order is booked at all while `vendor_delivery_partner_setup` is off (§1.1). `can_manage` stays `false` for it (§1.2): readable, not writable. **Pathao and Pathao Nepal return `can_track: false`** — they implement current-status lookup, not event history, and that lookup is not yet wired to any endpoint. See §7.

### 4.8 Backend layout

| Endpoint | Class |
|---|---|
| `courier/config` | `SellerApi\CourierConfigController` |
| `courier/providers` | `SellerApi\CourierProviderController` |
| `courier/locations/*` | `Admin\CourierLocationController` *(shared, already JSON)* |
| `courier/estimate`, `courier/dispatch`, `courier/revise`, `courier/track/{id}` | `SellerApi\CourierDispatchController` |

Validation is not duplicated: the API reuses `SaveCourierProviderRequest`, `SendToCourierRequest`, `ReviseCourierDetailsRequest` and `EstimateCourierChargeRequest` unchanged, apart from `SendToCourierRequest::failedValidation()` skipping its admin-panel toast when the request expects JSON.

Do **not** add an owner/seller parameter to any of these routes, and do **not** give them route names. `HostCourierOwnerResolver` reads the `Seller` model that `seller_api_auth` puts on the request; a name starting with `vendor.` would send it down the session-guard branch instead, and passing an owner in would be a privilege-escalation surface. `SellerApiRouteTest` asserts both.

### 4.9 The same endpoints on the web panels

Every endpoint above exists twice more, behind the session guards. Same controllers, same FormRequests, same response shapes for the JSON ones — only the auth, the resolved owner and the two HTML actions differ. Listed here so the two surfaces can be kept in step; an app developer needs nothing from this table.

| Purpose | Admin (owner = platform) | Vendor (owner = that seller) | Answers |
|---|---|---|---|
| Config screen | `GET admin/courier/config` | `GET vendor/courier/config` | HTML |
| Save one provider | `PUT admin/courier/config` | `PUT vendor/courier/config` | Redirect + toast |
| Pickup stores | `GET admin/courier/locations/stores` | `GET vendor/courier/locations/stores` | JSON, identical to §4.4.1 |
| Cities | `GET admin/courier/locations/cities` | `GET vendor/courier/locations/cities` | JSON, identical to §4.4.2 |
| Zones | `GET admin/courier/locations/zones/{cityId}` | `GET vendor/courier/locations/zones/{cityId}` | JSON, identical to §4.4.3 |
| Areas | `GET admin/courier/locations/areas/{zoneId}` | `GET vendor/courier/locations/areas/{zoneId}` | JSON, identical to §4.4.4 |
| Estimate | `POST admin/courier/estimate` | `POST vendor/courier/estimate` | JSON, identical to §4.5 |
| Dispatch / switch | `POST admin/courier/dispatch` | `POST vendor/courier/dispatch` | Redirect + toast (the API answers JSON) |
| Revise details | `POST admin/courier/revise` | `POST vendor/courier/revise` | Redirect + toast (the API answers JSON) |
| Track | `GET admin/courier/track/{consignmentId}` | `GET vendor/courier/track/{consignmentId}` | JSON, identical to §4.7 **minus the ownership check** (§7.6) |

Two consequences worth knowing:

- **There is no provider-list endpoint on the web.** The panels get `enabledOptions()` injected into the blade by a view composer, which is what §4.3 exposes over HTTP for the app.
- **The config screens make no dynamic calls at all.** Both are plain form posts; the country and environment selects are rendered from the catalog and take effect on save, and the credential dropdowns (Garuda's districts, Aramex's stations, Lalamove's languages and vehicles) are static option sets embedded in the page. The only dynamic lookups in the whole module are Groups C, D and E.

### 4.10 Inbound carrier webhook

| | |
|---|---|
| **Endpoint** | `courier/webhook/{provider}/{owner?}` |
| **Method** | `POST` |
| **Purpose** | The carrier pushes a status change; the module verifies it, logs it, updates the shipment, appends a tracking event and moves the host order status |
| **Required parameters** | `provider` — the driver id **or its webhook slug** (Shiprocket registers as `logistics-in`). `owner` — `vendor-<seller id>`; absent means the platform. Body and signature are the carrier's own format |
| **When it is called** | By the carrier, asynchronously, after the merchant has registered the URL in the carrier's own merchant panel. Read the exact URL from `webhook_url` in §4.1 — it is **per owner** and cannot be built client-side |
| **Providers** | `pathao`, `pathao_nepal` (`X-PATHAO-Signature` header), `redx` (`?token=`), `lalamove`, `delhivery` (token), `shiprocket` (token, slug `logistics-in`) |
| **Mandatory?** | **Optional but strongly recommended.** Without it a shipment's status only ever reflects what the booking response said; with it, `shipment_status` and the host `order_status` stay live (§6) |
| **Example** | `POST https://shop.example.com/courier/webhook/pathao/vendor-12`<br>`POST https://shop.example.com/courier/webhook/redx/vendor-12?token=s3cr3t-token` — RedX verifies on the `token` query parameter, which must equal the `webhook_token` credential<br>`POST https://shop.example.com/courier/webhook/logistics-in/vendor-12` — Shiprocket's slug, not `shiprocket`<br>`POST https://shop.example.com/courier/webhook/delhivery/vendor-12?token=s3cr3t-token` — Delhivery accepts the token on the `X-Delhivery-Token` header **or** this query parameter<br>`POST https://shop.example.com/courier/webhook/lalamove` — no owner segment = platform-owned |

Responses: `404 Unknown provider` for an unregistered id, `401 Invalid signature` when verification fails, otherwise the driver's own acknowledgement (Pathao demands `202` plus its secret echoed back in a header). Verification runs **before** parsing, and every attempt — verified or not — is written to `courier_webhook_logs`.

Posting to this route for a provider outside the list above raises `UnsupportedCourierOperationException` (`500`). The config card shows a `webhook_url` for **every** provider, including the four that cannot consume one — see §7.7.

### 4.11 Revise the delivery information of a booked order

| | |
|---|---|
| **Endpoint** | `/api/v3/seller/courier/revise` |
| **Method** | `POST` |
| **Purpose** | Correct the delivery information recorded against an already-booked order — a wrong phone number, a corrected address, a revised weight — **on this platform only** |
| **Required parameters** | `provider` (must equal the partner already holding the shipment), `host_order_reference`, `cod_amount`, then that provider's `required_fields` — the same envelope as §4.6 |
| **When it is called** | On saving the revise form, which opens prefilled from `courier_shipment.dispatch_details` (§4.2) |
| **Providers** | All 10. No carrier is called, so provider capability is irrelevant |
| **Mandatory?** | **Optional.** Nothing depends on it; it exists so a merchant is not stuck with a typo they can see and cannot fix |
| **Example** | `POST https://shop.example.com/api/v3/seller/courier/revise` |

```
POST /api/v3/seller/courier/revise
```

The body is **exactly the §4.6 dispatch payload** — same field names, same rules, same driver-derived `required_fields` (`ReviseCourierDetailsRequest` extends `SendToCourierRequest` and overrides only the provider rule). Send the full set, not a patch: the stored map is replaced wholesale, like `credentials` in §4.1.

```json
{
  "ok": true,
  "message": "Delivery information updated",
  "courier_shipment": { "…": "as in §4.2, with dispatch_details replaced" }
}
```

**Refusals arrive in two different envelopes — handle both.** The shipment checks live in the FormRequest, so they come back as a standard `422` keyed on `provider`, not as `{ ok: false }`:

```json
{
  "message": "This order is not assigned to a delivery partner",
  "errors": { "provider": ["This order is not assigned to a delivery partner"] }
}
```

```json
{
  "message": "This order is assigned to a different delivery partner",
  "errors": { "provider": ["This order is assigned to a different delivery partner"] }
}
```

The first fires when the caller's own account holds no active shipment for that order — including the case that matters most in practice: **a vendor calling against an order the admin booked on the platform account** (`can_manage: false` in §4.2). The second fires when `provider` names anyone but the partner currently holding it; to change partners, use §4.6 with `replace_existing`.

The `{ "ok": false, "message": … }` shape appears only for a `CourierException` raised after validation passed — a shipment superseded by another session between the two checks. Branch on the HTTP status first (`422` → field errors), then on `ok`.

#### What it does not do — read this before wiring the screen

**No request is sent to the delivery partner.** Not for `pathao`, not for the drivers that implement `UpdatesOrders`, not for any of the ten. Only `courier_shipments.dispatch_details` is rewritten. The shipment's `consignment_id`, `tracking_code`, `shipment_status`, `delivery_fee`, `tracking_url` and its whole tracking history are untouched, and no second shipment is created.

That is deliberate, and it means **a successful revise puts the platform's record deliberately out of step with the carrier's.** The app must say so before saving, in the same terms the web panels use: the order has already been assigned to a delivery partner, changing this information may create a mismatch with what was already submitted to that partner, and the merchant may need to update it with the partner manually — or contact them if the shipment has already been processed.

If you want the carrier updated, there is no endpoint for it. The merchant does it in the carrier's own panel. `CourierService::updateShipment()` exists and is wired to no route on purpose; exposing it is a separate change.

A revise is repeatable — each confirmed save replaces `dispatch_details` and leaves the shipment itself alone.

---

## 5. What each app must change

### 5.1 User app (v1)

1. Read `courier_shipment` from the **first element** of the order-details response.
2. When non-`null`, show **Delivery Partner**, **Tracking Number**, and **Shipment Status** (only when `shipment_status` is non-`null`).
3. Show a **Track Shipment** button **only when `tracking_url` is non-`null`** — open it in an in-app or external browser. When `null`, show the tracking number as copyable text and no button. Never build a tracking URL yourself from the tracking number; most carriers' public URLs are not derivable.
4. Render nothing at all when `courier_shipment` is `null` — no empty labels, no placeholder dashes.
5. Never display `delivery_fee`, provider ids, pickup identifiers, or consignment payloads. They are not in the v1 payload and must not be added.
6. **On the public tracking screen (§3.1) show only `courier_tracking_url`.** That response deliberately carries no partner name, tracking number or status, and the app must not backfill them from anywhere else — anyone with an order id and a phone number can reach it. The signed-in order-details screen is where the full `courier_shipment` belongs.
7. Nothing in §3 changed shape. If the customer's order had its partner switched, `courier_shipment` simply describes the new partner on the next fetch — so re-fetch rather than caching it across a session.

**Where the tracking URL comes from:** the carrier's own booking response (`shareLink` / `tracking_url`), stored on the shipment. Lalamove supplies one; Pathao and RedX currently do not, so expect `null` in Bangladesh and design the empty state as the common case.

### 5.2 Vendor app (v3)

**Works today, no backend change:** show `order.delivery_service_name` and `order.third_party_delivery_tracking_id` from the existing order-details response, and gate the third-party option on `delivery_partner_available`.

1. Gate on `delivery_partner_available` **now**. It is `false` for `inhouse_shipping` orders even though the vendor can open them — the admin owes that shipment. Do not offer the option there.
2. Delivery-type picker: three options only — *Self delivery man*, *By the 3rd party delivery partner*, and the placeholder. The legacy `partner_delivery` value is gone; treat any stored `partner_delivery` as `third_party_delivery`.

**Breaking:** delete the manual "delivery service name + tracking id" form. `assign-third-party-delivery` now answers `403` on v2 and v3, and `order-detail-info-update` refuses `delivery_type=third_party_delivery` for an unbooked order. Dispatch (§4.6) is the only way to put an order on a delivery partner. An order whose `delivery_partner_available` is `false` must not offer the third-party option at all.

**Against the §4 API:**

3. Add a **Courier Configuration** screen (§4.1) — a card per provider, rendered entirely from `fields`, `countries` and `environments`. Hide the screen on `404` (setup reserved for the admin). Show the per-vendor `webhook_url` as copyable text with a line telling the vendor to register it in the carrier's own panel, and re-read the catalog after a country change. Treat "no enabled provider" as a link to this screen, not an error.
4. Replace the two flat fields with the richer `courier_shipment` object; colour the status badge from `status_tone`; fall back to the flat fields when it is `null` (manual assign).
5. Fetch the provider list per session/order rather than caching it globally — it is **per vendor** (§1.1) and changes the moment the vendor edits their courier configuration.
6. Build the send-to-courier form **dynamically** from `address_mode`, `levels`, `level_labels` and `required_fields`. Do not hardcode a Bangladesh-shaped form — the same screen serves DHL (postal), Lalamove (geo) and Garuda (a single "District" level).
7. Do not preselect a provider. The operator picks one explicitly.
8. Prefill and lock `cod_amount` to `0` when `payment_status` is `paid`.
9. Surface `message` from any location/estimate/dispatch failure. An empty picker with no explanation is the single biggest usability trap in this flow.
10. Show the "this will unassign the delivery man" warning before dispatching an order with `delivery_man_id` set.

**Once a shipment exists** the booking form no longer disappears — it becomes two actions, both gated on `courier_shipment.can_manage` (§4.2):

11. **Switch delivery partner** — keep the partner picker visible, **excluding the one already holding the shipment** (offering it produces the "already assigned to the selected delivery partner" refusal). Selecting another partner opens a confirmation naming both partners and stating that switching may cause a conflict in which both try to process or collect the same order, and that the previous partner is **not cancelled automatically**. Only on confirmation open the booking form, prefilled with the carrier-neutral half of `dispatch_details`, and submit §4.6 with `replace_existing: true`.
12. **Update delivery information** — open the same form prefilled from `dispatch_details` for the *current* partner, and submit §4.11. Confirm first, saying the order is already assigned, that the change may create a mismatch with what the partner already holds, and that the merchant may need to update it with the partner manually or contact them if the shipment is already processed.
13. **Confirm before every dispatch.** Both a first booking and a switch put order information on the wire. The web panels show a final modal naming the partner and asking the merchant to verify the address, recipient, contact and delivery details, warning that incorrect information may cause delivery issues. Reproduce it — this is the last point at which a typo is free to fix.
14. **Treat `can_manage: false` as read-only.** A vendor viewing an order the admin booked on the platform account gets the shipment summary and nothing else: no switch, no revise. Both endpoints refuse anyway, so this is about not showing a button that cannot work.
15. **Re-fetch order details after a switch or a revise.** Both responses return the updated `courier_shipment`; use it rather than merging into cached state, and never show a superseded consignment id as current (§1.2).

---

## 6. Shipment status vocabulary

Carrier statuses are normalised to this closed set (`ShipmentStatus`). Treat unknown values as `unknown` rather than crashing.

**Never build a display string from the status value.** `ShipmentStatus::label()` is the one place the wording lives, and every payload carries it ready-made (`shipment_status_label`, or `status.label` on the track response). Two labels deliberately differ from a plain title-casing of the value.

| Status | Label | Tone |
|---|---|---|
| `pending` | Pending | `neutral` |
| `pickup_requested` | Pickup Requested | `info` |
| `picked_up` | Picked Up | `info` |
| `in_transit` | In Transit | `info` |
| `at_hub` | At Hub | `info` |
| `out_for_delivery` | Out For Delivery | `info` |
| `delivered` | Delivered | `success` |
| `partial_delivered` | Partially Delivered | `warning` |
| `return_initiated` | Return Initiated | `warning` |
| `returned` | Returned | `danger` |
| `cancelled` | Cancelled | `danger` |
| `on_hold` | On Hold | `warning` |
| `failed` | Failed | `danger` |
| `unknown` | Not Reported Yet | `neutral` |

Status also arrives asynchronously by carrier webhook, which **updates the host order status** through `CourierShipmentStatusListener`:

| Shipment status | Host `order_status` |
|---|---|
| `pickup_requested`, `picked_up`, `at_hub`, `in_transit` | `processing` |
| `out_for_delivery` | `out_for_delivery` |
| `delivered` | `delivered` |
| `returned` | `returned` |
| `cancelled` | `canceled` |
| `failed` | `failed` |
| anything else | unchanged |

An order already in `delivered` / `canceled` / `returned` / `failed` is never moved again, and the webhook path deliberately runs **no** payout, commission, wallet or stock finalization — that stays a deliberate admin action. So both apps should re-fetch order details rather than caching shipment status for long, and must not assume `order_status = delivered` implies the money side is settled.

Two things that do **not** move `order_status`, worth stating because they look like they should:

- **Dispatching does not.** Booking a shipment writes `delivery_type`, `delivery_service_name` and `third_party_delivery_tracking_id` onto the order and leaves `order_status` exactly where it was. Status stays webhook-driven.
- **Neither does a switch or a revise.** A switch replaces the shipment; a revise touches nothing but `dispatch_details`.

The one status move outside the webhook table: **editing an order that is already assigned to a delivery partner**. If its status is `pending` or `confirmed` at that moment, completing the edit sets it to `out_for_delivery` and writes a status-history entry — the parcel is already with a partner, so the timeline should not still claim it is being packed. An unassigned order keeps whatever the edit flow gave it. "Assigned" here is order-level and includes a legacy manual assign, not only a `courier_shipments` row. Note the knock-on: an order at `out_for_delivery` is no longer editable, so a second edit of an assigned order is refused.

---

## 7. Known gaps

1. **Pathao has no tracking history.** Pathao and Pathao Nepal implement current-status lookup (`ProvidesOrderInfo`), not event history (`TracksOrders`), and nothing consumes that interface yet — so `can_track` is `false` and no Track action appears for the most-used carrier. Fix is a fallback in `CourierService::track()`; until then, design for `can_track: false`.
2. **Public tracking URLs are rare.** Only carriers that return one in their booking payload have it. Expect `null`.
3. **Nine of ten drivers are unvalidated against live carrier sandboxes.** Only Pathao has been verified end to end. Treat non-Pathao responses as provisional.
4. **Estimate requires per-carrier pickup config.** RedX needs `Default Pickup Area ID` set in Courier Configuration or every estimate fails with a clear message.
5. **A provider can be enabled with an incomplete credential set** (the config UI hides the toggle, but a stale page or crafted request gets past it). An app that lists providers may therefore see one that fails at dispatch time with a carrier auth error — surface the message rather than retrying.
6. **The web `track` route is still unscoped.** §4.7's access check is on the API route only; `vendor/courier/track/{consignmentId}` continues to resolve any consignment by id. Closing it needs its own change, since the admin panel legitimately tracks a vendor's shipment.
7. **`webhook_url` is shown for providers that cannot consume one.** The config catalog composes the URL for all ten drivers, but only the six in Group F implement `HandlesWebhooks`; a merchant who dutifully registers the URL with DHL, Aramex, TCS or Garuda Express gets a `500` on every callback. Until the catalog carries a capability flag, an app rendering the setup screen has no way to tell them apart — the safe reading is the Group F list in §0.1.
8. **Aramex's postal fields disagree with themselves.** Its `addressFields` marks `postal_code` optional, but `required_fields` — the array both the form contract and the server-side rules are built from — includes it, because `requiredFields()` derives the postal triple generically. `required_fields` wins; render the asterisk from it, as §4.3 says.
9. **Garuda Express answers the zone and area lookups with an empty list.** It implements `ResolvesLocations` but returns `[]` for both, so a client that ignores `levels` and walks the full cascade sees `supported: true, options: []` — which §4.4 tells it to report as a carrier fault. Drive the cascade from `levels`, never from "the endpoint exists".
10. **A switch leaves two live bookings and the platform cannot see it.** The old carrier is never told (§4.6), so until the merchant cancels there, both partners may attempt collection. The confirmation copy is the only control; there is no reconciliation, no duplicate-pickup detection and no alert.
11. **A revise never reaches the carrier.** `courier_shipments.dispatch_details` and the carrier's record diverge on purpose (§4.11). Nothing measures or reports the divergence, and there is no endpoint that pushes it — `UpdatesOrders` is implemented by several drivers and wired to no route.
12. **Superseded shipments are stored but not listed.** No endpoint returns an order's shipment history; `courier_shipment` is always the active one. A superseded consignment is reachable only if the app kept its id, via `GET courier/track/{consignmentId}`. Surfacing the history is a separate change.
13. **The seller API has no shipment-history or cancel endpoint.** A vendor who switches partners in the app has no in-app way to cancel the old booking — they must use the carrier's own panel. `CancelsOrders` is implemented by several drivers and, like `UpdatesOrders`, exposed nowhere.
14. **The public tracking endpoints serialize the whole order, partner columns included.** `POST /api/v1/order/track-order` returns each row's full nested `order`, so `delivery_service_name` and `third_party_delivery_tracking_id` are visible to anyone with an order id and a matching phone number. `GET /api/v1/order/track` is looser still — it takes `order_id` alone, with **no phone check at all**, and returns the whole order model. Both pre-date the Courier module and neither was touched by the courier work; the courier-owned data (`courier_shipment`) is deliberately absent from both. Trimming them is a breaking reshape and belongs in its own change. Until then, do not treat "it is in the payload" as "it is safe to display" on a guest screen.

---

## 8. Per-provider dynamic-API map

What each provider's configuration and booking flow actually calls, end to end. §0.2 is the same information as a grid; this is the narrative, one carrier at a time. "Configuration" means the setup screen (§4.1); "Booking" means the per-order form (§4.3–§4.6).

Two rules hold for every provider without exception:

- **Configuration makes no dynamic calls.** Credentials, country, environment and every credential dropdown are static — read from `fields` in §4.1, saved with `PUT config`. Nothing is fetched from a carrier while the merchant fills the form; a wrong credential surfaces at the first booking-form lookup, not at save time.
- **Booking always calls §4.3 first**, then only the lookups that provider's `address_mode` and `levels` justify.
- **A switch is just a booking against the new partner** — read that partner's row below and ignore the old one's entirely; the old carrier is never called. **A revise (§4.11) calls nothing**, so no row below applies to it.

### 8.1 Pathao Bangladesh — `pathao`

| | |
|---|---|
| Configuration calls | None. `Default Pickup Store ID` is typed in by hand, copied from the Pathao merchant panel |
| Booking calls | §4.3 → §4.4.1 (stores) + §4.4.2 (cities) → §4.4.3 (zones) → §4.4.4 (areas) → optional §4.5 → §4.6 |
| Required lookups | cities, zones, areas — `city_id`, `zone_id`, `area_id` are all in `required_fields` |
| Optional lookups | stores. Empty selection falls back to the `store_id` credential |
| Not available | tracking history (§7.1) |
| Estimate needs | `weight` + `city_id` + `zone_id` (Pathao's price plan is quoted at zone level); `store_id` and `delivery_type` refine it |
| Webhook | Yes — `X-PATHAO-Signature` must match the `webhook_secret` credential; acknowledgement is `202` with the secret echoed back |

```
GET  /api/v3/seller/courier/providers
GET  /api/v3/seller/courier/locations/stores?provider=pathao
GET  /api/v3/seller/courier/locations/cities?provider=pathao
GET  /api/v3/seller/courier/locations/zones/1?provider=pathao          # 1   = Dhaka
GET  /api/v3/seller/courier/locations/areas/298?provider=pathao        # 298 = the chosen zone
POST /api/v3/seller/courier/estimate                                   # optional
POST /api/v3/seller/courier/dispatch
```

The pickup-store dropdown here is the canonical example of a provider-specific dynamic API: three drivers have it, seven do not, and the field must disappear entirely for the seven.

### 8.2 Pathao Nepal — `pathao_nepal`

```
GET  /api/v3/seller/courier/locations/stores?provider=pathao_nepal
GET  /api/v3/seller/courier/locations/cities?provider=pathao_nepal
GET  /api/v3/seller/courier/locations/zones/1?provider=pathao_nepal
GET  /api/v3/seller/courier/locations/areas/298?provider=pathao_nepal
POST /api/v3/seller/courier/dispatch
```

Identical flow, identical endpoints, NPR and Nepali hosts. The two extra credentials (`sandbox_base_url`, `live_base_url`) let a merchant correct an unverified host without a release — they change where every lookup above is sent, so a bad value makes cities, zones, areas and stores all fail at once with the same carrier message.

### 8.3 RedX — `redx`

| | |
|---|---|
| Configuration calls | None |
| Booking calls | §4.3 → §4.4.1 (stores) + §4.4.4 with the literal zone id `all` → optional §4.5 → §4.6 |
| Required lookups | areas only. `levels: ["area"]`, so no city or zone control is drawn |
| Optional lookups | stores; falls back to `default_pickup_store_id` |
| Never call | cities and zones — they answer with one synthetic `{"id": "all", "name": "Bangladesh"}` placeholder |
| Estimate needs | `weight`, `cod_amount`, `area_id`, **and** the `default_pickup_area_id` credential. Without that credential the call fails with an explicit merchant-readable message rather than a zero |
| Dispatch quirk | Booking re-reads the areas list server-side to resolve the area's *name*, which RedX wants alongside the id. A stale or invented `area_id` therefore fails at dispatch |
| Tracking | Yes, but its events carry **no status** — see §4.7's `unknown` rule |

```
GET  /api/v3/seller/courier/locations/stores?provider=redx
GET  /api/v3/seller/courier/locations/areas/all?provider=redx          # literal 'all', no cascade
POST /api/v3/seller/courier/estimate                                   # needs default_pickup_area_id
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
```

### 8.4 DHL — `dhl`

| | |
|---|---|
| Configuration calls | None. Origin address, account and package defaults are all typed in |
| Booking calls | §4.3 → optional §4.5 → §4.6. **No Group C lookup at all** — `address_mode: postal` |
| Required inputs | `country_code`, `postal_code`, `city_name` (typed, not picked); `state_province` optional |
| Estimate needs | destination country + postal code + city, `weight`, and the origin/package credentials. Quotes in the country profile's currency |
| Tracking | Yes |
| Webhook | No — do not register a callback URL |

```
GET  /api/v3/seller/courier/providers
POST /api/v3/seller/courier/estimate                                   # optional
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
```

Every ISO country is supported, so `country` is a real choice on the config screen and drives currency and product codes.

### 8.5 Lalamove — `lalamove`

| | |
|---|---|
| Configuration calls | None. Language, vehicle type and payment method are static per-market selects; the pickup point is entered as latitude/longitude |
| Booking calls | §4.3 → optional §4.5 → §4.6. **No Group C lookup** — `address_mode: geo` |
| Required inputs | `latitude`, `longitude` (map picker), plus the standard recipient trio |
| Delivery types | Country-scoped vehicle list from `delivery_types`; render the picker only when non-empty |
| Estimate needs | `latitude`, `longitude`, `recipient_address` and `delivery_type` — it is a real Lalamove quotation |
| Dispatch quirk | Booking re-requests a quotation internally and books against its `quotationId`, so a dispatch can fail with a quotation error even when the merchant never pressed *Estimate* |
| Tracking / webhook | Both yes. Lalamove is also the one carrier that commonly returns a public `tracking_url` |

```
GET  /api/v3/seller/courier/providers
POST /api/v3/seller/courier/estimate                                   # optional
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
POST /courier/webhook/lalamove/vendor-12                               # carrier → you
```

### 8.6 Delhivery — `delhivery`

| | |
|---|---|
| Configuration calls | None |
| Booking calls | §4.3 → optional §4.5 → §4.6. **No Group C lookup** — `address_mode: postal` |
| Required inputs | `country_code`, `postal_code` (pincode), `city_name`, `state_province` |
| Estimate needs | destination pincode, `weight`, `cod_amount`, and the `origin_pincode` credential |
| Tracking / webhook | Both yes (webhook verified by a token you choose) |

```
GET  /api/v3/seller/courier/providers
POST /api/v3/seller/courier/estimate                                   # optional
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
POST /courier/webhook/delhivery/vendor-12?token=s3cr3t-token           # carrier → you
```

### 8.7 Aramex — `aramex`

| | |
|---|---|
| Configuration calls | None. `account_entity` is a static station-code select scoped to the chosen country |
| Booking calls | §4.3 → §4.6. **No lookups, no estimate** |
| Required inputs | `country_code`, `postal_code`, `city_name` — see §7.8 on the `postal_code` disagreement |
| Estimate | Not implemented — hide the control. A real quote needs Aramex's Rate Calculator spec |
| Tracking | Yes |
| Webhook | No |

```
GET  /api/v3/seller/courier/providers
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
```

### 8.8 Shiprocket — `shiprocket`

| | |
|---|---|
| Configuration calls | None |
| Booking calls | §4.3 → optional §4.5 → §4.6. **No Group C lookup** — `address_mode: postal` |
| Required inputs | `country_code`, `postal_code` (pincode), `city_name`, `state_province` |
| Estimate needs | destination pincode, `weight`, `cod_amount`, and the `origin_pincode` credential; the quote is the recommended courier's rate |
| Dispatch quirk | One dispatch is three carrier calls — create order, assign AWB, and optionally request pickup when `auto_request_pickup` is on. The consignment id is the AWB from the second call |
| Tracking | Yes |
| Webhook | Yes, but the URL slug is **`logistics-in`**, not `shiprocket`, and it verifies on the `x-api-key` **header** rather than a query token. Take the URL from `webhook_url` (§4.1) rather than composing it |

```
GET  /api/v3/seller/courier/providers
POST /api/v3/seller/courier/estimate                                   # optional
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
POST /courier/webhook/logistics-in/vendor-12                           # carrier → you
```

### 8.9 TCS — `tcs`

| | |
|---|---|
| Configuration calls | None. Either credential pair satisfies `credentialsComplete()` — validate that client-side (§4.1) |
| Booking calls | §4.3 → §4.4.2 (cities) → §4.4.3 (zones) → §4.4.4 (areas) → §4.6 |
| Required lookups | all three levels. TCS's Country → City → Area → Block hierarchy is mapped onto city → zone → area |
| Not available | pickup stores, estimate |
| Sandbox | With `enable_simulation` on, all three lookups return **sample** data and booking returns a simulated consignment number — the screens work before TCS grants live access. Nothing reaches the network |
| Tracking | Yes |
| Webhook | No |

```
GET  /api/v3/seller/courier/locations/cities?provider=tcs
GET  /api/v3/seller/courier/locations/zones/KHI?provider=tcs           # KHI = Karachi city code
GET  /api/v3/seller/courier/locations/areas/CLIFTON?provider=tcs       # area code from the step above
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
```

### 8.10 Garuda Express — `garuda_express`

| | |
|---|---|
| Configuration calls | None. `Pickup District` is a static 77-option select rendered from the module's own district table |
| Booking calls | §4.3 → §4.4.2 (cities, labelled **District**) → §4.6 |
| Required lookups | cities only. `levels: ["city"]`, `level_labels: {"city": "District"}` — translate the label before rendering |
| Never call | zones and areas — implemented, but they return `[]` (§7.9) |
| Not available | pickup stores, estimate |
| Dispatch quirk | A Garuda order carries no recipient fields, only two carrier-issued address ids, so booking creates/reuses the delivery and pickup address records first. Districts are keyed on `id`, not code — `MNG` is both Morang and Manang |
| Sandbox | Garuda publishes no sandbox host, so the `sandbox` environment is simulated unconditionally and never reaches the network |
| Tracking | Yes |
| Webhook | No |

```
GET  /api/v3/seller/courier/locations/cities?provider=garuda_express   # 77 districts, id-keyed
POST /api/v3/seller/courier/dispatch
GET  /api/v3/seller/courier/track/CN-88
```
