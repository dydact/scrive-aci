#!/bin/bash
# Script to run iris database customization

# Enable debugging if needed
# set -x

# Enable error tracing
set -e

# Function to log messages with timestamps
log() {
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    echo "[$timestamp] $1"
}

log "Running iris database customization for American Caregivers Incorporated..."

# Database connection parameters
DB_HOST="mysql"
DB_PORT="3306"
DB_USER="root"
DB_PASS="root"
DB_NAME="aci-EMR"

# Max retry attempts for database connection
MAX_ATTEMPTS=30
ATTEMPT=0
RETRY_INTERVAL=5

# Check if mysql client is installed
if ! command -v mysql &> /dev/null; then
    log "MySQL client not found. Attempting to install..."
    
    # Try to install mysql client based on distro
    if command -v apt-get &> /dev/null; then
        apt-get update && apt-get install -y default-mysql-client
    elif command -v apk &> /dev/null; then
        apk add --no-cache mysql-client
    elif command -v yum &> /dev/null; then
        yum install -y mysql
    else
        log "ERROR: Could not install MySQL client. Please install it manually."
        exit 1
    fi
    
    # Verify installation succeeded
    if ! command -v mysql &> /dev/null; then
        log "ERROR: Failed to install MySQL client."
        exit 1
    fi
    
    log "MySQL client installed successfully."
fi

# Function to check MySQL connection
check_mysql_connection() {
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1
    return $?
}

# Wait for MySQL to be ready
log "Waiting for MySQL to be ready..."

while ! check_mysql_connection; do
    ATTEMPT=$((ATTEMPT+1))
    if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
        log "ERROR: Could not connect to MySQL after $MAX_ATTEMPTS attempts. Exiting."
        exit 1
    fi
    log "MySQL is not ready yet... waiting $RETRY_INTERVAL seconds (attempt $ATTEMPT/$MAX_ATTEMPTS)"
    sleep $RETRY_INTERVAL
done

log "MySQL is ready. Checking if database exists..."

# Check if database exists, create it if it doesn't
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" >/dev/null 2>&1; then
    log "Database $DB_NAME does not exist. Creating..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE \`$DB_NAME\`"
    log "Database created successfully."
else
    log "Database $DB_NAME exists."
fi

log "Applying database customizations..."

# File containing the customization SQL
CUSTOMIZATION_FILE="/docker-entrypoint-initdb.d/iris-database-customization.sql"

# Check if the file exists
if [ ! -f "$CUSTOMIZATION_FILE" ]; then
    log "ERROR: Database customization file not found: $CUSTOMIZATION_FILE"
    exit 1
fi

# Run the database customization script
if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$CUSTOMIZATION_FILE"; then
    log "Base customization script applied successfully."
else
    log "ERROR: Failed to apply base customization script."
    exit 1
fi

# Apply additional path and URL updates
log "Applying additional URL path updates..."

# Save SQL commands to a temporary file
TMP_SQL_FILE=$(mktemp)
cat << EOF > "$TMP_SQL_FILE"
-- Update any URLs or paths in the database to use /iris instead of /openemr
UPDATE globals SET gl_value = REPLACE(gl_value, '/openemr', '/iris') WHERE gl_value LIKE '%/openemr%';
UPDATE globals SET gl_value = REPLACE(gl_value, 'openemr/', 'iris/') WHERE gl_value LIKE '%openemr/%';

-- Update any other tables that might contain openemr references
UPDATE registry SET directory = REPLACE(directory, 'openemr', 'iris') WHERE directory LIKE '%openemr%';
UPDATE documents SET url = REPLACE(url, '/openemr', '/iris') WHERE url LIKE '%/openemr%';
EOF

# Run the additional customization SQL
if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$TMP_SQL_FILE"; then
    log "Additional URL path updates applied successfully."
else
    log "ERROR: Failed to apply URL path updates."
    exit 1
fi

# Clean up temporary file
rm -f "$TMP_SQL_FILE"

log "Database customization completed successfully!"

# Print a summary of what was changed
log "Summary of database changes:"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT gl_name, gl_value FROM globals WHERE gl_name IN ('openemr_name', 'login_tagline_text', 'practice_name')"

log "Database customization process complete!"
exit 0 