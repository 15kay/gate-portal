#!/bin/bash
# ============================================================================
# Run all missing database migrations
# ============================================================================

echo "Running database migrations..."

# Database credentials from .env
DB_HOST="gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com"
DB_USER="admin"
DB_PASS="Gate123-portal"
DB_NAME="gate_portal"

# Run faculties and departments migration
echo "1. Adding faculties and departments tables..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < sql/migrate_faculties_departments.sql

# Run email verifications migration
echo "2. Adding email_verifications table..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < sql/migrate_email_verifications.sql

echo ""
echo "✓ All migrations completed!"
echo ""
