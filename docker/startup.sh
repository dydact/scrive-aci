#!/bin/sh
# Scrive ACI startup script
set -e
echo "Starting Scrive ACI..."

# Initialize database first (synchronously, not in background)
echo "Initializing database..."
if /docker/init-database.sh; then
    echo "Database initialized successfully"
else
    echo "Database initialization had issues, but continuing..."
fi

# Start Apache
echo "Starting Apache..."
exec /usr/sbin/httpd -D FOREGROUND
