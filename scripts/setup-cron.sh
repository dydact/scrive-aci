#!/bin/bash
#
# Setup Cron Job for Scrive ACI Auto-Updates
# This script configures the weekly auto-update cron job
#

echo "=== Scrive ACI Cron Setup ==="
echo

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: This script must be run as root (use sudo)"
    exit 1
fi

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
UPDATE_SCRIPT="$SCRIPT_DIR/auto-update.sh"

# Check if auto-update script exists
if [ ! -f "$UPDATE_SCRIPT" ]; then
    echo "ERROR: auto-update.sh not found at $UPDATE_SCRIPT"
    exit 1
fi

# Make auto-update script executable
chmod +x "$UPDATE_SCRIPT"
echo "✓ Made auto-update.sh executable"

# Create log directory
mkdir -p /var/log
touch /var/log/scrive-aci-update.log
chown apache:apache /var/log/scrive-aci-update.log
echo "✓ Created log file"

# Add cron job
CRON_JOB="0 2 * * 1 $UPDATE_SCRIPT"
CRON_FILE="/etc/cron.d/scrive-aci-update"

# Create cron file
cat > "$CRON_FILE" << EOF
# Scrive ACI Weekly Auto-Update
# Runs every Monday at 2:00 AM
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

$CRON_JOB
EOF

# Set proper permissions on cron file
chmod 644 "$CRON_FILE"
echo "✓ Created cron job file"

# Restart cron service
if systemctl restart crond 2>/dev/null || systemctl restart cron 2>/dev/null; then
    echo "✓ Restarted cron service"
else
    echo "⚠ Warning: Could not restart cron service. Please restart manually."
fi

echo
echo "=== Setup Complete ==="
echo
echo "Weekly auto-updates configured successfully!"
echo "Updates will run every Monday at 2:00 AM"
echo "Logs will be written to: /var/log/scrive-aci-update.log"
echo
echo "To view the cron job:"
echo "  cat $CRON_FILE"
echo
echo "To test the update script manually:"
echo "  $UPDATE_SCRIPT"
echo
echo "To monitor update logs:"
echo "  tail -f /var/log/scrive-aci-update.log"
echo 