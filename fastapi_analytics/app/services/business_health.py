"""
Business Health Score (SRS REQ-4.7-7): a single 0-100 composite from revenue,
inventory condition, menu profitability, and waste levels, plus a
plain-language driver sentence.

Writes ONE NEW ROW PER DAY (not overwritten) — see the contract docblock in
AnalyticsController.php and businessHealthHistory(), which reads a real date
range for the 30-day trend chart.

REAL SCHEMA USED (from schema.sql):
  orders(order_id, cashier_id, table_number, order_type, status, total_amount, created_at)
  payments(payment_id, order_id, payment_method, gcash_ref_num, amount_tethered, change_due)
    -> no created_at on payments, and amount_tethered is cash-in-hand, not the
       order total, so revenue comes from orders.total_amount instead. An
       order is only counted as revenue if it has a matching payments row
       (EXISTS check) rather than trusting a specific orders.status string,
       since the exact status enum values aren't in schema.sql's DDL.
  ingredients(ingredient_id, ingredient_name, unit_of_measurement, unit_cost, restock_threshold, ...)
  stock_movements(movement_id, ingredient_id, movement_type, qty_changed, recorded_at, waste_reason, user_id)
    -> qty_changed is assumed SIGNED (positive for stock_in/positive
       adjustments, negative for stock_out/waste/negative adjustments), so
       current stock = SUM(qty_changed). schema.sql doesn't have a CHECK
       constraint confirming the sign convention — flag this to your team if
       INSERTs into this table are written the other way.
  recipe_ingredients(recipe_ingredient_id, menu_item_id, ingredient_id, qty_consumed, is_active)
  order_items(order_item_id, order_id, menu_item_id, quantity, total)
    -> no per-unit price or historical cost column, so "cost" here is
       computed from the CURRENT recipe + CURRENT ingredient unit_cost, not
       a true historical snapshot. Margins on old orders will drift slightly
       if recipes or ingredient costs change over time — acceptable for a
       capstone, worth a footnote in your defense if asked.
"""

from datetime import datetime, timezone

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession

WEIGHTS = {"revenue": 0.30, "inventory_health": 0.25, "menu_profitability": 0.25, "waste_level": 0.20}


async def _revenue_component(session: AsyncSession) -> float:
    row = (
        await session.execute(
            text(
                """
                SELECT
                    COALESCE(SUM(o.total_amount) FILTER (WHERE o.created_at::date = CURRENT_DATE), 0) AS today,
                    COALESCE(SUM(o.total_amount) FILTER (WHERE o.created_at::date = CURRENT_DATE - INTERVAL '1 day'), 0) AS yesterday
                FROM orders o
                WHERE EXISTS (SELECT 1 FROM payments p WHERE p.order_id = o.order_id)
                """
            )
        )
    ).one()
    today, yesterday = float(row.today), float(row.yesterday)

    if yesterday == 0:
        pct_change = 0.0 if today == 0 else 100.0
    else:
        pct_change = ((today - yesterday) / yesterday) * 100

    # Flat vs yesterday = 50; every 2% swing moves the score 1pt, clamped 0-100.
    return max(0.0, min(100.0, 50 + (pct_change / 2)))


async def _inventory_health_component(session: AsyncSession) -> float:
    row = (
        await session.execute(
            text(
                """
                WITH current_stock AS (
                    SELECT ingredient_id, COALESCE(SUM(qty_changed), 0) AS qty
                    FROM stock_movements
                    GROUP BY ingredient_id
                )
                SELECT
                    COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE COALESCE(cs.qty, 0) < i.restock_threshold) AS below_threshold
                FROM ingredients i
                LEFT JOIN current_stock cs ON cs.ingredient_id = i.ingredient_id
                """
            )
        )
    ).one()

    total, below = int(row.total or 0), int(row.below_threshold or 0)
    if total == 0:
        return 100.0
    return 100 * (1 - (below / total))


async def _menu_profitability_component(session: AsyncSession, lookback_days: int) -> float:
    row = (
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
                SELECT AVG(
                    CASE WHEN s.units_sold > 0 AND s.revenue > 0
                        THEN ((s.revenue / s.units_sold) - COALESCE(rc.cost_per_serving, 0))
                             / (s.revenue / s.units_sold) * 100
                        ELSE NULL
                    END
                ) AS avg_margin_pct
                FROM sales s
                LEFT JOIN recipe_cost rc ON rc.menu_item_id = s.menu_item_id
                """
            ),
            {"days": lookback_days},
        )
    ).one()

    avg_margin_pct = row.avg_margin_pct
    if avg_margin_pct is None:
        return 50.0  # neutral default when there's no sales history yet
    return max(0.0, min(100.0, float(avg_margin_pct)))


async def _waste_level_component(session: AsyncSession, lookback_days: int) -> float:
    row = (
        await session.execute(
            text(
                """
                SELECT
                    COALESCE(SUM(ABS(qty_changed)) FILTER (WHERE movement_type = 'waste'), 0) AS waste_qty,
                    COALESCE(SUM(ABS(qty_changed)) FILTER (WHERE movement_type IN ('stock_out', 'waste')), 0) AS total_out_qty
                FROM stock_movements
                WHERE recorded_at >= CURRENT_DATE - make_interval(days => :days)
                """
            ),
            {"days": lookback_days},
        )
    ).one()

    waste_qty, total_out_qty = float(row.waste_qty or 0), float(row.total_out_qty or 0)
    if total_out_qty == 0:
        return 100.0
    return max(0.0, min(100.0, 100 * (1 - (waste_qty / total_out_qty))))


def _build_summary(components: dict[str, float], overall: float, previous_score: float | None) -> str:
    driver_name, driver_value = max(components.items(), key=lambda kv: abs(kv[1] - 50))
    label_map = {
        "revenue": "revenue",
        "inventory_health": "inventory stock levels",
        "menu_profitability": "menu profitability",
        "waste_level": "food waste",
    }
    direction = "up" if driver_value >= 50 else "down"
    magnitude = round(abs(driver_value - 50))

    if previous_score is None:
        return f"First recorded score — {label_map[driver_name]} is the strongest current factor."

    trend_word = "up" if overall > previous_score else ("down" if overall < previous_score else "flat")
    change_pct = round(abs(overall - previous_score))
    return (
        f"Business health is {trend_word} {change_pct}% from yesterday, "
        f"driven mainly by {label_map[driver_name]} trending {direction} {magnitude}%."
    )


async def compute_business_health(session: AsyncSession, sales_lookback_days: int) -> dict:
    revenue_score = await _revenue_component(session)
    inventory_score = await _inventory_health_component(session)
    profitability_score = await _menu_profitability_component(session, sales_lookback_days)
    waste_score = await _waste_level_component(session, sales_lookback_days)

    components = {
        "revenue": round(revenue_score, 1),
        "inventory_health": round(inventory_score, 1),
        "menu_profitability": round(profitability_score, 1),
        "waste_level": round(waste_score, 1),
    }

    overall = round(max(0.0, min(100.0, sum(components[k] * WEIGHTS[k] for k in WEIGHTS))))

    previous = (
        await session.execute(
            text(
                """
                SELECT result_data->>'score' AS score
                FROM analytics_snapshots
                WHERE snapshot_type = 'business_health'
                ORDER BY generated_at DESC
                LIMIT 1
                """
            )
        )
    ).first()
    previous_score = float(previous.score) if previous and previous.score is not None else None

    trend = "flat"
    if previous_score is not None:
        trend = "up" if overall > previous_score else ("down" if overall < previous_score else "flat")

    return {
        "score": overall,
        "summary": _build_summary(components, overall, previous_score),
        "trend": trend,
        "previous_score": previous_score,
        "components": components,
    }
