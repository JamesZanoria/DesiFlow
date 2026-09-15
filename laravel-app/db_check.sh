#!/bin/bash
PORTS=(5432 5433)
DB_PASS="LOVEme143"
USER="postgres"
HOST="127.0.0.1"
TABLES=("users" "menu_items" "ingredients" "recipe_ingredients" "stock_movements" "orders" "order_items" "payments")

for PORT in "${PORTS[@]}"; do
    echo "--- Port $PORT ---"
    DBS=$(PGPASSWORD=$DB_PASS psql -h $HOST -p $PORT -U $USER -t -c "SELECT datname FROM pg_database WHERE datname NOT LIKE 'template%';" | xargs)
    for DB in $DBS; do
        echo "Database: $DB"
        for TABLE in "${TABLES[@]}"; do
            COUNT=$(PGPASSWORD=$DB_PASS psql -h $HOST -p $PORT -U $USER -d $DB -t -c "SELECT count(*) FROM $TABLE;" 2>/dev/null)
            if [ ! -z "$COUNT" ]; then
                echo "  $TABLE: $(echo $COUNT | xargs)"
            else
                echo "  $TABLE: NOT FOUND"
            fi
        done
    done
done
