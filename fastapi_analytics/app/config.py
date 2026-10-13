from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Loaded from .env (see .env.example)."""

    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    database_url: str

    daily_run_hour: int = 2
    daily_run_minute: int = 0

    sales_lookback_days: int = 30
    usage_lookback_days: int = 14
    forecast_horizon_days: int = 7
    min_data_points_for_forecast: int = 5

    # only matters for orders that got voided/refunded AFTER payment and should
    # therefore be excluded from revenue/sales figures even though a payments
    # row still exists. These strings against wherever void/refund
    # approval updates orders.status.
    excluded_order_statuses: tuple[str, ...] = ("voided", "cancelled", "refunded")

    # stock_movements.movement_type strings, confirmed against
    # InventoryController.php's MOVEMENT_TYPES const. 'sale_deduction' has no
    # writer yet (per that file's own comment: it's meant to be created by
    # the Kitchen Display's Serve action, which doesn't exist as a built
    # module yet), so demand_forecast/spoilage_risk/waste figures that
    # depend on it will legitimately stay empty/neutral until that module
    # ships, not because of a wrong string here.
    movement_type_stock_in: str = "delivery"
    movement_type_stock_out: str = "sale_deduction"
    movement_type_waste: str = "spoilage"
    movement_type_adjustment: str = "manual_adjustment"


settings = Settings()