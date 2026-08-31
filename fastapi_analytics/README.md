# DesiFlow Analytics Microservice (FastAPI)

Computes the four SRS §4.7 outputs and writes them to `analytics_snapshots`,
which `AnalyticsController.php` already reads. Nothing on the Laravel side
needs to change.

**Status: schema-confirmed and tested end-to-end.** Every query in this
service has been run against a real PostgreSQL 16 instance loaded with your
actual table definitions (from `schema.sql`) plus seed data, all the way
through `run_all_analytics()` → real `INSERT`s into `analytics_snapshots`
with the correct JSON shape. This isn't just code that compiles — it's been
executed.

## 1. Setup

```bash
cd fastapi_analytics
python3 -m venv venv
source venv/bin/activate        # Windows: venv\Scripts\activate
pip install -r requirements.txt
cp .env.example .env
```

Edit `.env` — set `DATABASE_URL` to your actual Postgres 16 instance:
```
postgresql+asyncpg://<user>:<password>@<host>:5432/<database>
```

## 2. Run it

```bash
uvicorn app.main:app --reload --port 8001
```

- `GET /health` → `{"status": "ok"}`
- `GET /docs` → Swagger UI
- `POST /internal/run-analytics` → runs all four calculators immediately
  instead of waiting for the 2 AM cron job. Dev/testing only — see §4.

Once it runs clean against your real data, reload the Laravel Analytics page.

## 3. Schema mapping — confirmed against your actual `schema.sql`

Your first message assumed column names that turned out to be wrong in
several places. These are now corrected and verified by direct execution
against your real table definitions:

| Table.column used | Confirmed vs schema.sql |
|---|---|
| `orders.total_amount`, `orders.created_at` | used for revenue instead of `payments.amount`/`created_at`, which don't exist — `payments` has no `created_at` at all, and `amount_tethered` is cash tendered, not the order total |
| `order_items.quantity`, `order_items.total` | there's no `historical_price`/`historical_cost` — cost is computed live from the recipe instead (see below) |
| `recipe_ingredients.qty_consumed`, `.is_active` | was `quantity_per_serving` in my first draft |
| `ingredients.unit_cost`, `.restock_threshold`, `.shelf_life_days` | `shelf_life_days` is a duration (int), not a stored `expiry_date` — Spoilage Risk now derives "days until expiry" from `shelf_life_days - days since that ingredient's most recent stock_in movement` |
| `stock_movements.qty_changed`, `.recorded_at`, `.movement_type` | were `quantity_change`/`created_at` in my first draft |
| `analytics_snapshots.*` | matches exactly, no changes needed |

**One assumption still open, flagged in the code comments:** whether
`qty_changed` is stored *signed* (negative for stock_out/waste) or always
positive with `movement_type` as the only sign indicator. The queries assume
signed storage (`SUM(qty_changed)` = current stock). If your Inventory
Management page writes movements as unsigned magnitudes instead, tell me and
I'll adjust every `SUM`/`ABS` call accordingly — this is the one thing I
couldn't verify without your actual `INSERT` code for stock movements (the
Inventory page you're about to build).

**Menu Engineering / Business Health cost caveat:** since `order_items`
doesn't snapshot historical price or cost, profitability figures are computed
from the *current* recipe and *current* `unit_cost` — not what those items
actually cost on the day they were sold. Fine for a capstone; worth a
one-line footnote if your panel asks about historical accuracy.

## 4. What's NOT done yet

- `/internal/run-analytics` is dev-only — lock it down or remove it before deployment.
- Two-DB-role separation (Capstone 1 Ch.3: read-only credentials for FastAPI)
  isn't set up; one connection string does both reads and the
  `analytics_snapshots` write for now.
- Smart Assistant (REQ-4.9) is a separate Laravel-side rule-based feature —
  not part of this service, and SRS §2.5 bans it from calling any AI API.

## 5. Bugs this testing round caught (fixed, for your own awareness)

Three real PostgreSQL/asyncpg issues only show up when a query actually runs
against a live connection — worth knowing about since you'll hit variants of
these yourself once you're writing more raw SQL in this service:

1. `(:param || ' days')::interval` fails under asyncpg because it can't infer
   whether `:param` is text or a number for the `||` operator. Fixed by using
   `make_interval(days => :param)` instead.
2. A bind parameter immediately followed by a cast, `:param::float`, confuses
   SQLAlchemy's `text()` parser. Fixed by wrapping it: `(:param)::float`.
3. Reusing the same named parameter in two different type contexts within one
   query (once as `float`, once as `int` for `make_interval`) makes asyncpg
   unify it to one type and break the other usage. Fixed by casting explicitly
   at each usage site.
