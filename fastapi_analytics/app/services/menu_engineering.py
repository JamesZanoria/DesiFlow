"""
Menu Engineering Matrix (SRS REQ-4.7-4 — Kasavana & Smith model).

Classifies each menu item into star / plowhorse / puzzle / dog by comparing
its popularity (units sold) and profitability (contribution margin) against
the menu-wide averages for the lookback window.

REAL SCHEMA USED (from schema.sql):
  order_items(order_item_id, order_id, menu_item_id, quantity, total)
    -> "total" is the line total (price × quantity at time of order), but
       there's no stored per-unit price or cost snapshot. Average selling
       price per unit is derived as SUM(total)/SUM(quantity).
  recipe_ingredients(recipe_ingredient_id, menu_item_id, ingredient_id, qty_consumed, is_active)
  ingredients(ingredient_id, ..., unit_cost, ...)
    -> cost per serving = SUM(qty_consumed * unit_cost) over that item's
       active recipe rows. This is CURRENT cost, not a historical snapshot —
       same caveat as business_health.py.
  orders(order_id, ..., created_at)
  payments(payment_id, order_id, ...)
    -> only orders with a matching payments row count as a completed sale.

Only writes a row for items that actually sold in the window. A brand-new
item with zero sales has nothing to classify — AnalyticsController.php's
latestBatchWithNames() already handles that gap (Table 7's "Insufficient
Data" alternate flow), so no placeholder row is needed here.
"""

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession


async def compute_menu_engineering(session: AsyncSession, lookback_days: int) -> list[tuple[int, dict]]:
    rows = (
        await session.execute(
            text(
                """
                WITH recipe_cost AS (
                    SELECT ri.menu_item_id, SUM(ri.qty_consumed * i.unit_cost) AS cost_per_serving
                    FROM recipe_ingredients ri
                    JOIN ingredients i ON i.ingredient_id = ri.ingredient_id
                    WHERE ri.is_active = true
                    GROUP BY ri.menu_item_id
                ),
                sales AS (
                    SELECT oi.menu_item_id, SUM(oi.quantity) AS units_sold, SUM(oi.total) AS revenue
                    FROM order_items oi
                    JOIN orders o ON o.order_id = oi.order_id
                    WHERE o.created_at >= CURRENT_DATE - make_interval(days => :days)
                      AND EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.order_id)
                    GROUP BY oi.menu_item_id
                )
                SELECT
                    s.menu_item_id,
                    s.units_sold,
                    (s.revenue / NULLIF(s.units_sold, 0)) AS avg_unit_price,
                    COALESCE(rc.cost_per_serving, 0) AS cost_per_serving,
                    ((s.revenue / NULLIF(s.units_sold, 0)) - COALESCE(rc.cost_per_serving, 0)) AS contribution_margin,
                    CASE WHEN (s.revenue / NULLIF(s.units_sold, 0)) > 0
                        THEN (COALESCE(rc.cost_per_serving, 0) / (s.revenue / NULLIF(s.units_sold, 0))) * 100
                        ELSE NULL
                    END AS food_cost_pct
                FROM sales s
                LEFT JOIN recipe_cost rc ON rc.menu_item_id = s.menu_item_id
                """
            ),
            {"days": lookback_days},
        )
    ).all()

    if not rows:
        return []

    units_sold_values = [float(r.units_sold) for r in rows]
    margin_values = [float(r.contribution_margin or 0) for r in rows]

    avg_units_sold = sum(units_sold_values) / len(units_sold_values)
    avg_margin = sum(margin_values) / len(margin_values)

    max_units = max(units_sold_values) or 1
    max_margin = max(margin_values) if max(margin_values) > 0 else 1

    results: list[tuple[int, dict]] = []
    for r in rows:
        units_sold = float(r.units_sold)
        margin = float(r.contribution_margin or 0)

        is_popular = units_sold >= avg_units_sold
        is_profitable = margin >= avg_margin

        if is_popular and is_profitable:
            classification = "star"
        elif is_popular and not is_profitable:
            classification = "plowhorse"
        elif not is_popular and is_profitable:
            classification = "puzzle"
        else:
            classification = "dog"

        popularity_score = round(max(0.0, min(100.0, (units_sold / max_units) * 100)))
        profitability_score = round(max(0.0, min(100.0, (margin / max_margin) * 100)))

        results.append((
            int(r.menu_item_id),
            {
                "classification": classification,
                "units_sold": int(units_sold),
                "food_cost_percentage": round(float(r.food_cost_pct or 0), 1),
                "contribution_margin": round(margin, 2),
                "popularity_score": popularity_score,
                "profitability_score": profitability_score,
            },
        ))

    return results
