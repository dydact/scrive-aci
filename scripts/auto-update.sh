#!/bin/bash
#
# Scrive ACI Auto-Update Script
# This script pulls the latest code from the repository without affecting database configurations
# Run this as a cron job: 0 2 * * 1 /path/to/auto-update.sh
#

# Configuration
LOGFILE="/var/log/scrive-aci-update.log"
APP_DIR="/var/www/localhost/htdocs"
BACKUP_DIR="/var/backups/scrive-aci"
DATE=$(date +"%Y%m%d_%H%M%S")

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOGFILE"
}

# Start update process
log "Starting Scrive ACI auto-update process..."

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Backup current configuration files
log "Backing up configuration files..."
cp "$APP_DIR/.env" "$BACKUP_DIR/.env.$DATE" 2>/dev/null || true
cp "$APP_DIR/src/config.php" "$BACKUP_DIR/config.php.$DATE" 2>/dev/null || true
cp "$APP_DIR/docker.env" "$BACKUP_DIR/docker.env.$DATE" 2>/dev/null || true

# Store current directory
CURRENT_DIR=$(pwd)

# Navigate to application directory
cd "$APP_DIR" || {
    log "ERROR: Failed to navigate to application directory"
    exit 1
}

# Check if git repository exists
if [ ! -d ".git" ]; then
    log "ERROR: Git repository not found in $APP_DIR"
    exit 1
fi

# Stash any local changes to prevent conflicts
log "Stashing local changes..."
git stash push -m "Auto-update stash $DATE"

# Fetch latest changes
log "Fetching latest changes from repository..."
git fetch origin

# Get current branch
BRANCH=$(git rev-parse --abbrev-ref HEAD)
log "Current branch: $BRANCH"

# Pull latest changes
log "Pulling latest changes..."
if git pull origin "$BRANCH"; then
    log "Successfully pulled latest changes"
else
    log "ERROR: Failed to pull latest changes"
    # Restore stashed changes
    git stash pop
    exit 1
fi

# Restore configuration files
log "Restoring configuration files..."
[ -f "$BACKUP_DIR/.env.$DATE" ] && cp "$BACKUP_DIR/.env.$DATE" "$APP_DIR/.env"
[ -f "$BACKUP_DIR/config.php.$DATE" ] && cp "$BACKUP_DIR/config.php.$DATE" "$APP_DIR/src/config.php"
[ -f "$BACKUP_DIR/docker.env.$DATE" ] && cp "$BACKUP_DIR/docker.env.$DATE" "$APP_DIR/docker.env"

# Apply any stashed changes if they don't conflict
log "Attempting to restore local modifications..."
git stash pop 2>/dev/null || log "No local changes to restore or conflicts detected"

# Run composer update if composer.json exists
if [ -f "composer.json" ]; then
    log "Running composer update..."
    composer install --no-dev --optimize-autoloader 2>&1 >> "$LOGFILE"
fi

# Clear PHP opcache if available
if command -v cachetool &> /dev/null; then
    log "Clearing PHP opcache..."
    cachetool opcache:reset 2>&1 >> "$LOGFILE"
fi

# Set proper permissions
log "Setting file permissions..."
chown -R apache:apache "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod 600 "$APP_DIR/.env" 2>/dev/null || true
chmod 600 "$APP_DIR/src/config.php" 2>/dev/null || true

# Restart Apache to ensure all changes take effect
log "Restarting Apache..."
if systemctl restart httpd 2>&1 >> "$LOGFILE"; then
    log "Apache restarted successfully"
else
    log "WARNING: Failed to restart Apache. Please check manually."
fi

# Clean up old backups (keep last 4 weeks)
log "Cleaning up old backups..."
find "$BACKUP_DIR" -name "*.env.*" -mtime +28 -delete
find "$BACKUP_DIR" -name "*.php.*" -mtime +28 -delete

# Return to original directory
cd "$CURRENT_DIR"

log "Auto-update process completed successfully"

# Send notification email (optional)
# echo "Scrive ACI auto-update completed at $(date)" | mail -s "Scrive ACI Update Report" admin@acgcares.com

exit 0 