"""
Spoilage Risk Monitor (SRS REQ-4.7-6): urgency rating per ingredient from
expiration vs usage rate.

REAL SCHEMA USED (from schema.sql):
  ingredients(ingredient_id, ..., shelf_life_days INTEGER NULLABLE, ...)
    -> there is NO expiry_date column. shelf_life_days is a duration, not a
       date, so "days until expiry" has to be derived: shelf_life_days minus
       how many days have passed since the ingredient's most recent stock-in
       movement. This assumes each restock effectively resets the shelf-life
       clock for that ingredient's whole current stock (a simplification —
       true batch/FEFO tracking would need a separate ingredient_batches
       table, which schema.sql doesn't have).
  stock_movements(movement_id, ingredient_id, movement_type, qty_changed, recorded_at, waste_reason, user_id)
    -> most recent recorded_at WHERE movement_type = 'stock_in' = last restock date.

For ingredients with shelf_life_days = NULL (shelf-stable items like spices,
rice, etc.) or with no stock-in history yet, risk falls back to a pure
runout signal: days of stock left at the current usage rate, since
"spoilage" there functionally means "about to run out," not "about to expire."
"""

from datetime import date

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession


def _classify_risk(days_remaining: float | None) -> str:
    if days_remaining is None:
        return "low"
    if days_remaining <= 1:
        return "critical"
    if days_remaining <= 3:
        return "high"
    if days_remaining <= 7:
        return "medium"
    return "low"


async def compute_spoilage_risk(session: AsyncSession, usage_lookback_days: int) -> list[tuple[int, dict]]:
    rows = (
        await session.execute(
            text(
                """
                WITH current_stock AS (
                    SELECT ingredient_id, COALESCE(SUM(qty_changed), 0) AS qty
                    FROM stock_movements
                    GROUP BY ingredient_id
                ),
                last_restock AS (
                    SELECT ingredient_id, MAX(recorded_at) AS last_stock_in
                    FROM stock_movements
                    WHERE movement_type = 'stock_in'
                    GROUP BY ingredient_id
                ),
                usage AS (
                    SELECT ingredient_id, SUM(ABS(qty_changed)) / (:days)::float AS avg_daily_usage
                    FROM stock_movements
                    WHERE movement_type = 'stock_out'
                      AND recorded_at >= CURRENT_DATE - make_interval(days => (:days)::int)
                    GROUP BY ingredient_id
                )
                SELECT
                    i.ingredient_id,
                    i.shelf_life_days,
                    lr.last_stock_in,
                    COALESCE(cs.qty, 0) AS current_qty,
                    COALESCE(u.avg_daily_usage, 0) AS avg_daily_usage
                FROM ingredients i
                LEFT JOIN current_stock cs ON cs.ingredient_id = i.ingredient_id
                LEFT JOIN last_restock lr ON lr.ingredient_id = i.ingredient_id
                LEFT JOIN usage u ON u.ingredient_id = i.ingredient_id
                """
            ),
            {"days": usage_lookback_days},
        )
    ).all()

    results: list[tuple[int, dict]] = []
    for r in rows:
        avg_daily_usage = float(r.avg_daily_usage or 0)
        current_qty = float(r.current_qty or 0)

        days_until_expiry = None
        if r.shelf_life_days is not None and r.last_stock_in is not None:
            age_days = (date.today() - r.last_stock_in.date()).days
            days_until_expiry = r.shelf_life_days - age_days

        if days_until_expiry is not None:
            risk_signal = days_until_expiry
        elif avg_daily_usage > 0:
            risk_signal = current_qty / avg_daily_usage  # runout-based fallback
        else:
            risk_signal = None

        results.append((
            int(r.ingredient_id),
            {
                "risk_level": _classify_risk(risk_signal),
                "days_until_expiry": days_until_expiry,
                "usage_rate_per_day": round(avg_daily_usage, 2),
            },
        ))

    return results
