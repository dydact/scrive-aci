#!/bin/bash
# iris-emr startup script for American Caregivers Incorporated

# Enable error tracing and exit on first error
set -e

echo "Starting iris EMR for American Caregivers Incorporated..."

# Function to log messages with timestamps
log() {
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    echo "[$timestamp] $1"
}

# Function to check if a directory exists
check_dir() {
    if [ ! -d "$1" ]; then
        log "ERROR: Directory $1 does not exist. Creating..."
        mkdir -p "$1"
    fi
}

# Apply any necessary file system changes
log "Applying file system customizations..."

# Ensure parent directory exists before creating symlink
check_dir "/var/www/localhost/htdocs/"

# Ensure symlink exists from openemr to iris
if [ ! -L /var/www/localhost/htdocs/iris ]; then
    log "Creating symlink from openemr to iris..."
    ln -sf /var/www/localhost/htdocs/openemr /var/www/localhost/htdocs/iris
fi

# Verify that the symlink was created correctly
if [ ! -L /var/www/localhost/htdocs/iris ]; then
    log "ERROR: Failed to create the symlink. Please check permissions."
    exit 1
fi

# Ensure directories exist with proper permissions
check_dir "/var/www/localhost/htdocs/iris/sites/americancaregivers"
check_dir "/var/www/localhost/htdocs/iris/sites/americancaregivers/documents/smarty/gacl"
check_dir "/var/www/localhost/htdocs/iris/sites/americancaregivers/documents/smarty/main"

# Set proper permissions
chmod -R 755 /var/www/localhost/htdocs/iris/sites/americancaregivers
chmod -R 777 /var/www/localhost/htdocs/iris/sites/americancaregivers/documents
chmod -R 777 /var/www/localhost/htdocs/iris/sites/americancaregivers/images

# Run the database customization script in the background
# This will wait for MySQL to be ready before applying changes
log "Scheduling database customization in background..."
$(dirname "$0")/run-database-customization.sh &

# Add a temporary ServerName to Apache config if not present
if ! grep -q "ServerName" /etc/apache2/httpd.conf 2>/dev/null; then
    log "Adding ServerName directive to Apache config..."
    echo "ServerName localhost" >> /etc/apache2/httpd.conf
fi

# Create Apache log directory with proper permissions
log "Ensuring Apache logs directory exists with proper permissions..."
check_dir "/var/log/apache2"
chmod 755 /var/log/apache2

# Check if Apache is installed properly
if ! command -v httpd &> /dev/null; then
    log "ERROR: Apache (httpd) not found! Please check your Docker image."
    exit 1
fi

# Allow Apache to start
log "Starting Apache web server..."
exec /usr/sbin/httpd -D FOREGROUND 