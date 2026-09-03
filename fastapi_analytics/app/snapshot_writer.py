from datetime import datetime, timezone

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession
import json


async def write_snapshot(
    session: AsyncSession,
    snapshot_type: str,
    reference_id: int | None,
    result_data: dict,
    generated_at: datetime | None = None,
) -> None:
    """
    Inserts one row into analytics_snapshots. Matches AnalyticsSnapshot.php:
    columns are snapshot_id (pk, auto), snapshot_type, reference_id,
    result_data (jsonb), generated_at.

    Deliberately just INSERTs rather than upserting. AnalyticsController.php's
    latestBatchWithNames() and latestOfType() both key off MAX(generated_at)
    per type, so old rows are naturally superseded without needing to be
    deleted, and for 'business_health' specifically, old rows are *supposed*
    to stick around, since that's what the 30-day trend line reads.
    """
    generated_at = generated_at or datetime.now(timezone.utc)

    await session.execute(
        text(
            """
            INSERT INTO analytics_snapshots (snapshot_type, reference_id, result_data, generated_at)
            VALUES (:snapshot_type, :reference_id, CAST(:result_data AS jsonb), :generated_at)
            """
        ),
        {
            "snapshot_type": snapshot_type,
            "reference_id": reference_id,
            "result_data": json.dumps(result_data),
            "generated_at": generated_at,
        },
    )


async def write_batch(
    session: AsyncSession,
    snapshot_type: str,
    rows: list[tuple[int, dict]],
    generated_at: datetime | None = None,
) -> None:
    """Convenience wrapper for snapshot types with one row per record (menu item / ingredient)."""
    generated_at = generated_at or datetime.now(timezone.utc)
    for reference_id, result_data in rows:
        await write_snapshot(session, snapshot_type, reference_id, result_data, generated_at)
