#!/bin/bash
# Setup cron job for SSL certificate auto-renewal

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CRON_JOB="0 2 * * * $SCRIPT_DIR/auto-renew.sh"

echo "=== Setting up SSL Certificate Auto-Renewal ==="
echo "Project directory: $PROJECT_DIR"
echo "Auto-renewal script: $SCRIPT_DIR/auto-renew.sh"
echo ""

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "auto-renew.sh"; then
    echo "⚠️  Cron job already exists for SSL auto-renewal"
    echo "Current cron jobs containing 'auto-renew.sh':"
    crontab -l 2>/dev/null | grep "auto-renew.sh"
    echo ""
    read -p "Do you want to replace it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Cancelled - keeping existing cron job"
        exit 0
    fi
    
    # Remove existing auto-renew cron jobs
    crontab -l 2>/dev/null | grep -v "auto-renew.sh" | crontab -
    echo "🗑️  Removed existing auto-renewal cron job"
fi

# Add new cron job
echo "📅 Adding cron job: $CRON_JOB"
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -

if [ $? -eq 0 ]; then
    echo "✅ Cron job added successfully"
    echo ""
    echo "Current cron jobs:"
    crontab -l
    echo ""
    echo "📋 SSL Certificate Auto-Renewal Setup:"
    echo "   • Runs daily at 2:00 AM"
    echo "   • Checks certificate expiration"
    echo "   • Renews if expiring within 30 days"
    echo "   • Automatically restarts containers"
    echo "   • Logs to: $PROJECT_DIR/logs/ssl-renewal.log"
    echo ""
    echo "🔧 To test the renewal script manually:"
    echo "   $SCRIPT_DIR/auto-renew.sh"
    echo ""
    echo "🗑️  To remove the cron job:"
    echo "   crontab -e  # then delete the line containing auto-renew.sh"
else
    echo "❌ Failed to add cron job"
    exit 1
fi