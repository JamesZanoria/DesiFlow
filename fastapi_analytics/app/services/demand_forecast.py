"""
Demand Forecast (SRS REQ-4.7-5): time-series projection of expected
ingredient consumption over the next N days.

Uses a simple linear trend fit (numpy polyfit) over daily stock_out
consumption — easy to explain in a defense, and matches what Capstone 1
Ch.3 commits to (pandas/statsmodels/scikit-learn available, no specific
algorithm mandated).

Items with fewer than min_data_points days of stock-out history are skipped
entirely (no snapshot row) — Laravel shows "Insufficient Data" for those via
the same gap-handling as menu_engineering.py.

REAL SCHEMA USED (from schema.sql):
  stock_movements(movement_id, ingredient_id, movement_type, qty_changed, recorded_at, waste_reason, user_id)
    -> qty_changed assumed signed (negative for stock_out); ABS() is applied
       so the magnitude is what gets forecasted, regardless of sign
       convention on write.
"""

from datetime import date, timedelta

import numpy as np
import pandas as pd
from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession

DAY_LABELS = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]


async def compute_demand_forecast(
    session: AsyncSession,
    usage_lookback_days: int,
    horizon_days: int,
    min_data_points: int,
) -> list[tuple[int, dict]]:
    rows = (
        await session.execute(
            text(
                """
                SELECT ingredient_id, recorded_at::date AS day, SUM(ABS(qty_changed)) AS qty
                FROM stock_movements
                WHERE movement_type = 'stock_out'
                  AND recorded_at >= CURRENT_DATE - make_interval(days => :days)
                GROUP BY ingredient_id, recorded_at::date
                ORDER BY ingredient_id, day
                """
            ),
            {"days": usage_lookback_days},
        )
    ).all()

    if not rows:
        return []

    df = pd.DataFrame(rows, columns=["ingredient_id", "day", "qty"])
    results: list[tuple[int, dict]] = []

    for ingredient_id, group in df.groupby("ingredient_id"):
        if len(group) < min_data_points:
            continue

        full_range = pd.date_range(group["day"].min(), group["day"].max(), freq="D")
        daily = group.set_index("day")["qty"].reindex(full_range, fill_value=0)

        x = np.arange(len(daily))
        y = daily.values.astype(float)
        slope, intercept = np.polyfit(x, y, 1) if len(x) > 1 else (0.0, y.mean() if len(y) else 0.0)

        future_x = np.arange(len(daily), len(daily) + horizon_days)
        projected = np.clip(slope * future_x + intercept, 0, None)

        today = date.today()
        daily_forecast = [
            {"label": DAY_LABELS[(today + timedelta(days=i)).weekday()], "qty": round(float(v), 1)}
            for i, v in enumerate(projected)
        ]

        if slope > 0.05:
            trend = "increasing"
        elif slope < -0.05:
            trend = "decreasing"
        else:
            trend = "stable"

        if len(group) >= min_data_points * 2:
            confidence = "high"
        elif len(group) >= min_data_points:
            confidence = "medium"
        else:
            confidence = "low"

        results.append((
            int(ingredient_id),
            {
                "forecasted_qty": round(float(sum(v["qty"] for v in daily_forecast)), 1),
                "period": f"next_{horizon_days}_days",
                "trend": trend,
                "confidence": confidence,
                "daily_forecast": daily_forecast,
            },
        ))

    return results
