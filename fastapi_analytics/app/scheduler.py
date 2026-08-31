import logging

from apscheduler.schedulers.asyncio import AsyncIOScheduler
from apscheduler.triggers.cron import CronTrigger

from app.config import settings
from app.database import AsyncSessionLocal
from app.services.business_health import compute_business_health
from app.services.demand_forecast import compute_demand_forecast
from app.services.menu_engineering import compute_menu_engineering
from app.services.spoilage_risk import compute_spoilage_risk
from app.snapshot_writer import write_batch, write_snapshot

logger = logging.getLogger("desiflow.analytics")


async def run_all_analytics() -> dict:
    """
    Runs all four calculators in one pass and writes their snapshots.
    This is the single function both the daily scheduler AND the manual
    /internal/run-analytics endpoint call — one code path, two triggers.
    """
    summary = {}

    async with AsyncSessionLocal() as session:
        async with session.begin():
            health = await compute_business_health(session, settings.sales_lookback_days)
            await write_snapshot(session, "business_health", None, health)
            summary["business_health"] = health["score"]

            menu = await compute_menu_engineering(session, settings.sales_lookback_days)
            await write_batch(session, "menu_engineering", menu)
            summary["menu_engineering_items"] = len(menu)

            forecast = await compute_demand_forecast(
                session,
                settings.usage_lookback_days,
                settings.forecast_horizon_days,
                settings.min_data_points_for_forecast,
            )
            await write_batch(session, "demand_forecast", forecast)
            summary["demand_forecast_ingredients"] = len(forecast)

            spoilage = await compute_spoilage_risk(session, settings.usage_lookback_days)
            await write_batch(session, "spoilage_risk", spoilage)
            summary["spoilage_risk_ingredients"] = len(spoilage)

    logger.info("Analytics run complete: %s", summary)
    return summary


def start_scheduler() -> AsyncIOScheduler:
    scheduler = AsyncIOScheduler()
    scheduler.add_job(
        run_all_analytics,
        trigger=CronTrigger(hour=settings.daily_run_hour, minute=settings.daily_run_minute),
        id="daily_analytics_run",
        replace_existing=True,
    )
    scheduler.start()
    logger.info(
        "Scheduler started — daily analytics run at %02d:%02d",
        settings.daily_run_hour,
        settings.daily_run_minute,
    )
    return scheduler
