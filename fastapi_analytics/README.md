# DesiFlow Analytics Microservice (FastAPI)

Computes the four outputs and writes them to `analytics_snapshots`,
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