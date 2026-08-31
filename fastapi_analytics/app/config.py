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

    # stock_movements.movement_type strings — same caveat, confirm against
    # InventoryController.php.
    movement_type_stock_in: str = "stock_in"
    movement_type_stock_out: str = "stock_out"
    movement_type_waste: str = "waste"
    movement_type_adjustment: str = "adjustment"


settings = Settings()
