#!/bin/bash

DB="hotelapp"
USER="root"

echo "🟡 Starting setup..."

# Step 1 - Create DB if not exists
mysql -u $USER -e "CREATE DATABASE IF NOT EXISTS $DB;" && \
echo "✅ Database '$DB' created or already exists."

# Step 2 - Apply schema
if [ -f "schema.sql" ]; then
  mysql -u $USER $DB < schema.sql
  echo "✅ Schema applied."
else
  echo "❌ Error: Schema file not found at schema.sql"
fi

