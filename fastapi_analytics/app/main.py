import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException

from app.scheduler import run_all_analytics, start_scheduler

logging.basicConfig(level=logging.INFO)


@asynccontextmanager
async def lifespan(app: FastAPI):
    scheduler = start_scheduler()
    yield
    scheduler.shutdown()


app = FastAPI(
    title="DesiFlow Analytics Microservice",
    description=(
        "Computes Business Health Score, Menu Engineering, Demand Forecast, "
        "and Spoilage Risk, writing results into analytics_snapshots "
        "for the Laravel AnalyticsController to read."
    ),
    lifespan=lifespan,
)


@app.get("/health")
async def health():
    """
    Simple liveness check, hit this first after `uvicorn app.main:app`
    to confirm the service is up and the DB connection string parses,
    before worrying about whether the actual queries match the schema.
    """
    return {"status": "ok"}


@app.post("/internal/run-analytics")
async def trigger_analytics_run():
    """
    Manually triggers the same computation the daily scheduler runs.
    NOT meant to be customer- or even cashier-facing, this exists purely so
    later we can force a fresh snapshot without waiting for
    2 AM while developing. Wire this behind an internal/admin-only check
    before anything resembling production use.
    """
    try:
        summary = await run_all_analytics()
    except Exception as exc:  # noqa: BLE001 - surface the real DB/query error while developing
        raise HTTPException(status_code=500, detail=str(exc)) from exc
    return {"status": "completed", "summary": summary}
