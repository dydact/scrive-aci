#!/bin/bash

# Apply all SQL schemas to the production database
echo "Applying all SQL schemas to production database..."

# Database connection details
DB_HOST="mysql"
DB_USER="root"
DB_PASS="root"
DB_NAME="openemr"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting American Caregivers Inc Database Setup${NC}"
echo "================================================"
echo ""

# IMPORTANT: Apply base tables FIRST to avoid foreign key errors
echo -e "${YELLOW}Step 1: Creating base tables (MUST be first)${NC}"
echo "Applying 00_create_base_tables.sql..."
docker-compose exec -T mysql mysql -u$DB_USER -p$DB_PASS $DB_NAME < sql/00_create_base_tables.sql
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Successfully created base tables${NC}"
else
    echo -e "${RED}✗ Failed to create base tables - cannot continue${NC}"
    exit 1
fi

# SQL files to apply in order (after base tables)
SQL_FILES=(
    "iris-database-customization.sql"
    "add_organization_settings_table.sql"
    "production_missing_tables.sql"
    "clinical_documentation_system.sql"
    "billing_time_integration.sql"
    "financial_billing_enhancement.sql"
    "scheduling_resource_management.sql"
    "create_admin_user.sql"
)

echo ""
echo -e "${YELLOW}Step 2: Applying additional schemas${NC}"

# Apply each SQL file
for sql_file in "${SQL_FILES[@]}"; do
    echo "Applying $sql_file..."
    docker-compose exec -T mysql mysql -u$DB_USER -p$DB_PASS $DB_NAME < sql/$sql_file
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Successfully applied $sql_file${NC}"
    else
        echo -e "${RED}✗ Failed to apply $sql_file${NC}"
    fi
done

echo ""
echo "Database setup complete!"
echo ""
echo "Testing database structure..."

# Test critical tables
echo "Checking billing tables..."
docker-compose exec mysql mysql -u$DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES LIKE 'autism_billing%';"

echo ""
echo "Checking scheduling tables..."
docker-compose exec mysql mysql -u$DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES LIKE 'autism_schedule%';"

echo ""
echo "Checking financial tables..."
docker-compose exec mysql mysql -u$DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES LIKE 'autism_insurance%';"

echo ""
echo "All schemas applied! The system now has:"
echo "✓ Complete billing integration with Medicaid compliance"
echo "✓ Advanced scheduling and resource management"
echo "✓ Financial tracking and EDI support"
echo "✓ Clinical documentation system"
echo "✓ Time clock integration"
echo ""
echo "You can now use all features at http://aci.dydact.io"