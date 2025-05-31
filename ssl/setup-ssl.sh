#!/bin/bash

# SSL Certificate Setup for aci.dydact.io using Let's Encrypt
# This script sets up free SSL certificates for all subdomains

echo "Setting up SSL certificates for aci.dydact.io..."

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo "Installing certbot..."
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Ubuntu/Debian
        sudo apt update
        sudo apt install certbot python3-certbot-apache -y
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        brew install certbot
    else
        echo "Please install certbot manually for your operating system"
        echo "Visit: https://certbot.eff.org/"
        exit 1
    fi
fi

# Domain list
DOMAINS=(
    "aci.dydact.io"
    "www.aci.dydact.io"
    "staff.aci.dydact.io" 
    "admin.aci.dydact.io"
    "api.aci.dydact.io"
)

# Generate domain string for certbot
DOMAIN_STRING=""
for domain in "${DOMAINS[@]}"; do
    DOMAIN_STRING="${DOMAIN_STRING} -d ${domain}"
done

echo "Requesting SSL certificate for domains: ${DOMAINS[*]}"

# Request certificate
sudo certbot certonly \
    --apache \
    --agree-tos \
    --redirect \
    --hsts \
    --staple-ocsp \
    --email admin@aci.dydact.io \
    $DOMAIN_STRING

if [ $? -eq 0 ]; then
    echo "✓ SSL certificates generated successfully"
    echo "✓ Certificates location: /etc/letsencrypt/live/aci.dydact.io/"
    
    # Set up auto-renewal
    echo "Setting up automatic renewal..."
    (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
    
    echo "✓ Auto-renewal configured (daily at noon)"
    echo ""
    echo "Next steps:"
    echo "1. Update your Apache virtual host configuration"
    echo "2. Restart Apache: sudo systemctl restart apache2"
    echo "3. Test HTTPS: https://aci.dydact.io"
else
    echo "✗ SSL certificate generation failed"
    echo "Make sure:"
    echo "1. DNS is properly configured and propagated"
    echo "2. Apache is running and accessible from the internet"
    echo "3. Ports 80 and 443 are open"
fi