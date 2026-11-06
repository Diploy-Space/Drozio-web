# API contract snapshot harness

> For the parked V4 migration the harness of record is **parity**, not snapshots:
> `tests/Feature/Api/V4/ApiParityTestCase` calls a frozen endpoint and its `api/v4/*` counterpart in
> one run and diffs them, so it cannot go stale. Both harnesses boot the application the same way,
> through `Support\InteractsWithApiTestEnvironment`.


Freezes the current behaviour of the REST API so a service migration can prove
"response unchanged". A snapshot is `status + JSON body`, with volatile values
(ids, timestamps, tokens) replaced by placeholders, stored under
`__snapshots__/<group>/<name>.json`.

Nothing in `app/` is touched by this harness.

## One-time setup

```bash
php tests/bootstrap/import_snapshot_db.php          # clones <DB_DATABASE> into <DB_DATABASE>_snapshot_test
php tests/bootstrap/import_snapshot_db.php --fresh  # rebuild the clone from scratch
```

The application loads settings, themes, payment configs and add-on states from
the database at boot, so the harness runs against a populated clone rather than
a migrated empty schema. This is a separate database from the Contract suite's
`6valley_test`, which `tests/bootstrap/import_test_db.php` fills from the
install dump — snapshots of product, order and seller endpoints need the richer
data a working database carries. Every test runs inside a transaction that is rolled
back, so the clone stays as imported.

Environment overrides (all optional):

| Variable | Meaning |
|---|---|
| `SNAPSHOT_SOURCE_DB` | database to clone from (default: `DB_DATABASE`) |
| `SNAPSHOT_TEST_DB` | clone name, must end in `_test` (default: `<source>_snapshot_test`) |
| `MYSQL_BIN_DIR` | directory holding `mysql` / `mysqldump` |
| `SNAPSHOT_UPDATE=1` | overwrite existing snapshots instead of comparing |
| `SNAPSHOT_DEBUG=1` | boot with `APP_DEBUG=true` to see stack traces while writing a test |

If the clone is missing, every snapshot test skips with the command to run — it
never fails for an environment reason.

## Running

```bash
php artisan test --compact --filter=Snapshot              # everything
php artisan test --compact --filter=CartSnapshot          # one group
```

| Group filter | Endpoints |
|---|---|
| `ProductSnapshot` | v1 product details / search / filter |
| `CartSnapshot` | v1 cart list / add / update |
| `AuthSnapshot` | v1 register / login / verify-otp |
| `OrderPlaceSnapshot` | v1 order place flow |
| `RefundSnapshot` | v1 refund request / store / details |
| `SellerOrderSnapshot` | v3 order list / details / status |
| `SellerProductSnapshot` | v3 product list / add / edit / update / details |
| `PosSnapshot` | v3 POS place-order / product-list / customers |

## Workflow for a migration task

1. **Before touching production code**, add a test per endpoint the task
   migrates — happy path *and* the validation-error path, because the error body
   is part of the contract:

   ```php
   class CouponSnapshotTest extends ApiSnapshotTestCase
   {
       protected string $snapshotGroup = 'v1/coupon';

       public function test_coupon_list_without_a_token(): void
       {
           $response = $this->getJson('api/v1/coupon/list');

           $this->assertMatchesSnapshot('list-unauthenticated', 'GET api/v1/coupon/list', $response);
       }
   }
   ```

2. Run the group. A missing snapshot is **recorded** and the test passes,
   printing `recorded snapshot: …`. Read the recorded file — it is the contract
   you are promising to preserve, so a wrong recording silently blesses a bug.

3. Migrate the endpoint.

4. Run the group again. Now the snapshot is **compared**: any change in status,
   key names, key order, or values fails the test with a diff. A failure means
   the migration was not verbatim — fix the code, not the snapshot.

5. Only regenerate with `SNAPSHOT_UPDATE=1` when the contract change is
   intentional and approved, and say so in the task report.

## Writing a test

- Extend `ApiSnapshotTestCase` and set `$snapshotGroup`.
- Guest calls: merge `$this->guestParams()` into the query or body —
  `apiGuestCheck` rejects a request with neither `guest_id` nor a bearer token.
- Customer calls: `$headers = $this->actingAsCustomer($customer);`
- Seller app calls: `$headers = $this->sellerHeaders($seller);` (v3 authenticates
  on `sellers.auth_token`).
- Fixtures come from the clone through `Support\ApiTestData`; wrap them in
  `$this->requireFixture(...)` so a thinner clone skips instead of failing.
- Pass extra volatile keys as the fourth argument when a response carries a
  value MySQL will not roll back, e.g. `['in_cart_key']`.

## What is normalized

`Support\ResponseNormalizer` replaces, by key: `id` / `*_id` / `*_ids` →
`<id>`; `*_at` and date-ish keys → `<timestamp>`; token, otp, password and
`guest_id` keys → `<token>`; and any string that looks like a date → 
`<timestamp>`. Nulls stay null and keys keep their position, so a renamed,
removed, or newly-nulled field still fails the comparison.
