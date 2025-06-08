#!/bin/bash
# Automatic SSL Certificate Renewal for aci.dydact.io
# This script should be run via cron every day at 2 AM

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="$PROJECT_DIR/logs/ssl-renewal.log"

# Create logs directory if it doesn't exist
mkdir -p "$(dirname "$LOG_FILE")"

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== SSL Certificate Auto-Renewal Check ==="

# Change to project directory
cd "$PROJECT_DIR"

# Check certificate expiration
CERT_FILE="ssl/certs/live/aci.dydact.io/fullchain.pem"

if [ ! -f "$CERT_FILE" ]; then
    log "❌ Certificate file not found: $CERT_FILE"
    exit 1
fi

# Get certificate expiration date (in seconds since epoch)
CERT_EXPIRY=$(openssl x509 -enddate -noout -in "$CERT_FILE" | cut -d= -f2)
CERT_EXPIRY_EPOCH=$(date -j -f "%b %d %H:%M:%S %Y %Z" "$CERT_EXPIRY" "+%s" 2>/dev/null || date -d "$CERT_EXPIRY" "+%s")
CURRENT_EPOCH=$(date "+%s")

# Calculate days until expiration
DAYS_UNTIL_EXPIRY=$(( (CERT_EXPIRY_EPOCH - CURRENT_EPOCH) / 86400 ))

log "Certificate expires in $DAYS_UNTIL_EXPIRY days"

# If certificate expires in 30 days or less, renew it
if [ $DAYS_UNTIL_EXPIRY -le 30 ]; then
    log "🔄 Certificate expires in $DAYS_UNTIL_EXPIRY days, renewing..."
    
    # Stop the web container temporarily
    log "Stopping web container..."
    docker-compose stop iris-emr
    
    # Wait for port to be freed
    sleep 5
    
    # Renew certificate using certbot Docker image
    log "Renewing certificate with certbot..."
    docker run --rm \
        -p 80:80 \
        -v "$(pwd)/ssl/certs:/etc/letsencrypt" \
        certbot/certbot \
        renew \
        --standalone \
        --non-interactive \
        --force-renewal
    
    if [ $? -eq 0 ]; then
        log "✅ Certificate renewed successfully"
        
        # Copy new certificates to accessible location
        if [ -f "ssl/certs/live/aci.dydact.io/fullchain.pem" ]; then
            cp ssl/certs/live/aci.dydact.io/fullchain.pem ssl/certs/aci.dydact.io.crt
            cp ssl/certs/live/aci.dydact.io/privkey.pem ssl/certs/aci.dydact.io.key
            chmod 644 ssl/certs/aci.dydact.io.crt
            chmod 600 ssl/certs/aci.dydact.io.key
            log "✅ Certificates copied to ssl/certs/"
        else
            log "❌ New certificate files not found"
            # Start container anyway
            docker-compose start iris-emr
            exit 1
        fi
        
        # Start web container
        log "Starting web container..."
        docker-compose start iris-emr
        
        # Wait for container to start
        sleep 10
        
        # Copy certificates to running container
        log "Copying certificates to container..."
        docker cp ssl/certs/aci.dydact.io.crt scrive-aci-iris-emr-1:/etc/ssl/certs/
        docker cp ssl/certs/aci.dydact.io.key scrive-aci-iris-emr-1:/etc/ssl/private/
        docker exec scrive-aci-iris-emr-1 chmod 644 /etc/ssl/certs/aci.dydact.io.crt
        docker exec scrive-aci-iris-emr-1 chmod 600 /etc/ssl/private/aci.dydact.io.key
        
        # Gracefully restart Apache
        log "Restarting Apache..."
        docker exec scrive-aci-iris-emr-1 /usr/sbin/httpd -k graceful
        
        # Test the renewed certificate
        log "Testing renewed certificate..."
        if curl -I https://aci.dydact.io/ --connect-timeout 10 > /dev/null 2>&1; then
            log "✅ HTTPS test successful - certificate renewal complete"
        else
            log "❌ HTTPS test failed after renewal"
        fi
        
    else
        log "❌ Certificate renewal failed"
        # Start container anyway
        docker-compose start iris-emr
        exit 1
    fi
    
else
    log "✅ Certificate is valid for $DAYS_UNTIL_EXPIRY days, no renewal needed"
fi

log "=== Auto-Renewal Check Complete ==="