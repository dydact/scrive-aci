#!/bin/bash
# Let's Encrypt SSL Certificate Setup for aci.dydact.io
set -e

echo "=== Let's Encrypt SSL Certificate Setup ==="
echo "Domain: aci.dydact.io"
echo "Time: $(date)"
echo ""

# Check if running as root (needed for certbot)
if [ "$EUID" -ne 0 ]; then
    echo "❌ This script needs to run as root to install certificates"
    echo "Please run: sudo ./ssl/setup-letsencrypt.sh"
    exit 1
fi

# Install certbot if not present
if ! command -v certbot &> /dev/null; then
    echo "📦 Installing certbot..."
    if command -v apt-get &> /dev/null; then
        # Ubuntu/Debian
        apt-get update
        apt-get install -y snapd
        snap install --classic certbot
        ln -sf /snap/bin/certbot /usr/bin/certbot
    elif command -v yum &> /dev/null; then
        # CentOS/RHEL
        yum install -y epel-release
        yum install -y certbot
    elif command -v brew &> /dev/null; then
        # macOS
        brew install certbot
    else
        echo "❌ Unable to install certbot automatically"
        echo "Please install certbot manually and run this script again"
        exit 1
    fi
    echo "✅ Certbot installed"
else
    echo "✅ Certbot already installed"
fi

# Stop Apache temporarily for certificate generation
echo "🔄 Stopping Apache temporarily..."
docker-compose stop iris-emr

# Generate certificate using standalone mode
echo "🔒 Generating Let's Encrypt certificate..."
certbot certonly \
    --standalone \
    --non-interactive \
    --agree-tos \
    --email admin@americancaregivers.com \
    --domains aci.dydact.io,www.aci.dydact.io \
    --cert-name aci.dydact.io \
    --verbose

if [ $? -eq 0 ]; then
    echo "✅ Certificate generated successfully"
else
    echo "❌ Certificate generation failed"
    echo "Starting Apache again..."
    docker-compose start iris-emr
    exit 1
fi

# Copy certificates to Docker-accessible location
CERT_DIR="/etc/letsencrypt/live/aci.dydact.io"
DOCKER_CERT_DIR="./ssl/certs"

echo "📋 Copying certificates to Docker directory..."
mkdir -p "$DOCKER_CERT_DIR"
cp "$CERT_DIR/fullchain.pem" "$DOCKER_CERT_DIR/aci.dydact.io.crt"
cp "$CERT_DIR/privkey.pem" "$DOCKER_CERT_DIR/aci.dydact.io.key"
chmod 644 "$DOCKER_CERT_DIR/aci.dydact.io.crt"
chmod 600 "$DOCKER_CERT_DIR/aci.dydact.io.key"

echo "✅ Certificates copied to Docker directory"

# Update Apache configuration
echo "🔧 Updating Apache configuration..."
cat > ./apache/aci-docker-ssl.conf << 'EOF'
# Apache SSL Configuration for ACI with Let's Encrypt
<VirtualHost *:443>
    ServerName aci.dydact.io
    ServerAlias www.aci.dydact.io
    
    DocumentRoot /var/www/localhost/htdocs
    DirectoryIndex index.php index.html
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/aci.dydact.io.crt
    SSLCertificateKeyFile /etc/ssl/private/aci.dydact.io.key
    
    # Modern SSL configuration
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder off
    SSLSessionTickets off
    
    <Directory /var/www/localhost/htdocs>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Subdomain handling
    <If "%{HTTP_HOST} == 'staff.aci.dydact.io'">
        RedirectMatch ^/?$ /staff
    </If>
    
    <If "%{HTTP_HOST} == 'admin.aci.dydact.io'">
        RedirectMatch ^/?$ /admin
    </If>
    
    <If "%{HTTP_HOST} == 'api.aci.dydact.io'">
        RedirectMatch ^/?$ /api
    </If>
    
    ErrorLog /var/log/apache2/aci_ssl_error.log
    CustomLog /var/log/apache2/aci_ssl_access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName aci.dydact.io
    ServerAlias www.aci.dydact.io staff.aci.dydact.io admin.aci.dydact.io api.aci.dydact.io
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>
EOF

echo "✅ Apache SSL configuration created"

# Update Dockerfile to copy real certificates
echo "🔧 Updating Dockerfile..."
sed -i.bak '/# Generate self-signed SSL certificates/,/CN=aci.dydact.io"/c\
# Copy Let'\''s Encrypt SSL certificates\
COPY ssl/certs/aci.dydact.io.crt /etc/ssl/certs/aci.dydact.io.crt\
COPY ssl/certs/aci.dydact.io.key /etc/ssl/private/aci.dydact.io.key' Dockerfile

# Update Apache configuration copy
sed -i.bak '/COPY apache\/aci-docker.conf/a\
COPY apache/aci-docker-ssl.conf /etc/apache2/conf.d/' Dockerfile

echo "✅ Dockerfile updated"

# Rebuild and restart containers
echo "🔄 Rebuilding containers with SSL certificates..."
docker-compose down
docker-compose up -d --build

echo ""
echo "🎉 SSL Certificate Setup Complete!"
echo ""
echo "✅ Let's Encrypt certificate installed for aci.dydact.io"
echo "✅ Apache configured with modern SSL settings"
echo "✅ HTTP automatically redirects to HTTPS"
echo "✅ Security headers enabled"
echo ""
echo "🌐 Test your site: https://aci.dydact.io"
echo "🔒 SSL rating: https://www.ssllabs.com/ssltest/analyze.html?d=aci.dydact.io"
echo ""
echo "📅 Certificate will auto-renew before expiration"
echo "   Add this to crontab: 0 12 * * * /usr/bin/certbot renew --quiet"
echo ""