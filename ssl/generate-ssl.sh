#!/bin/bash
# Generate Let's Encrypt SSL Certificate using Docker
set -e

echo "=== SSL Certificate Generation for aci.dydact.io ==="
echo "Time: $(date)"
echo ""

# Create directories
mkdir -p ssl/certs

# Stop the web server temporarily
echo "🔄 Stopping web container temporarily..."
docker-compose stop iris-emr

# Wait a moment for port to be freed
sleep 5

# Generate certificate using certbot Docker image
echo "🔒 Generating Let's Encrypt certificate..."
docker run --rm \
    -p 80:80 \
    -v "$(pwd)/ssl/certs:/etc/letsencrypt" \
    certbot/certbot \
    certonly \
    --standalone \
    --non-interactive \
    --agree-tos \
    --email admin@americancaregivers.com \
    --domains aci.dydact.io \
    --cert-name aci.dydact.io

if [ $? -eq 0 ]; then
    echo "✅ Certificate generated successfully"
    
    # Copy certificates to accessible location
    if [ -f "ssl/certs/live/aci.dydact.io/fullchain.pem" ]; then
        cp ssl/certs/live/aci.dydact.io/fullchain.pem ssl/certs/aci.dydact.io.crt
        cp ssl/certs/live/aci.dydact.io/privkey.pem ssl/certs/aci.dydact.io.key
        chmod 644 ssl/certs/aci.dydact.io.crt
        chmod 600 ssl/certs/aci.dydact.io.key
        echo "✅ Certificates copied to ssl/certs/"
    else
        echo "❌ Certificate files not found in expected location"
        ls -la ssl/certs/
        exit 1
    fi
else
    echo "❌ Certificate generation failed"
    echo "Starting web container again..."
    docker-compose start iris-emr
    exit 1
fi

# Create updated Apache SSL configuration
echo "🔧 Creating Apache SSL configuration..."
cat > apache/aci-ssl.conf << 'EOF'
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

# Update Dockerfile to use real certificates
echo "🔧 Updating Dockerfile..."
cat > Dockerfile.new << 'EOF'
FROM openemr/openemr:7.0.2

LABEL maintainer="dydact <auto@dydact.io>"
LABEL description="Scrive ACI - American Caregivers Healthcare Management System"
LABEL version="1.0.0"

# Copy startup scripts first
COPY docker/startup.sh /docker/startup.sh
COPY docker/run-database-customization.sh /docker/run-database-customization.sh
COPY docker/init-database-fixed.sh /docker/init-database-fixed.sh
RUN chmod +x /docker/*.sh

# Copy site configuration
COPY sites/americancaregivers /var/www/localhost/htdocs/openemr/sites/americancaregivers
RUN chmod -R 755 /var/www/localhost/htdocs/openemr/sites/americancaregivers && \
    mkdir -p /var/www/localhost/htdocs/openemr/sites/americancaregivers/documents

# Create iris symlink for backward compatibility
RUN ln -sf /var/www/localhost/htdocs/openemr /var/www/localhost/htdocs/iris

# Enable multisite setup
RUN sed -i 's/\$allow_multisite_setup = false;/\$allow_multisite_setup = true;/' /var/www/localhost/htdocs/openemr/setup.php

# Copy SQL customization script
COPY sql/iris-database-customization.sql /docker-entrypoint-initdb.d/

# Copy public interface files to web root
COPY index.php /var/www/localhost/htdocs/
COPY .htaccess /var/www/localhost/htdocs/

# Copy pages directory structure
COPY pages /var/www/localhost/htdocs/pages

# Copy backend src directory (secured)
COPY src /var/www/localhost/htdocs/src

# Copy autism waiver app
COPY autism_waiver_app /var/www/localhost/htdocs/autism_waiver_app

# Copy public assets
COPY public /var/www/localhost/htdocs/public

# Copy scripts
COPY scripts /var/www/localhost/htdocs/scripts
RUN chmod +x /var/www/localhost/htdocs/scripts/*.sh

# Copy config directory
COPY config /var/www/localhost/htdocs/config

# Copy Let's Encrypt SSL certificates
COPY ssl/certs/aci.dydact.io.crt /etc/ssl/certs/aci.dydact.io.crt
COPY ssl/certs/aci.dydact.io.key /etc/ssl/private/aci.dydact.io.key

# Copy Apache configuration
COPY apache/iris.conf /etc/apache2/conf.d/
COPY apache/aci-docker.conf /etc/apache2/conf.d/
COPY apache/aci-ssl.conf /etc/apache2/conf.d/

# Enable Apache modules needed for the application
RUN sed -i '/LoadModule rewrite_module/s/^#//g' /etc/apache2/httpd.conf && \
    sed -i '/LoadModule ssl_module/s/^#//g' /etc/apache2/httpd.conf && \
    sed -i '/LoadModule socache_shmcb_module/s/^#//g' /etc/apache2/httpd.conf && \
    sed -i '/LoadModule headers_module/s/^#//g' /etc/apache2/httpd.conf

# Disable OpenEMR configuration that overrides DocumentRoot
RUN mv /etc/apache2/conf.d/openemr.conf /etc/apache2/conf.d/openemr.conf.disabled

# Create necessary directories for application
RUN mkdir -p /var/www/localhost/htdocs/uploads && \
    mkdir -p /var/www/localhost/htdocs/logs && \
    chown -R apache:apache /var/www/localhost/htdocs/uploads && \
    chown -R apache:apache /var/www/localhost/htdocs/logs

# Set proper permissions for web files
RUN chown -R apache:apache /var/www/localhost/htdocs/ && \
    chmod -R 755 /var/www/localhost/htdocs/ && \
    chmod 755 /var/www/localhost/htdocs/src && \
    chmod 644 /var/www/localhost/htdocs/src/*.php

EXPOSE 80 443

# Use our custom startup script
CMD ["/docker/startup.sh"]
EOF

# Replace the old Dockerfile
mv Dockerfile Dockerfile.old
mv Dockerfile.new Dockerfile

echo "✅ Dockerfile updated with SSL certificates"

# Rebuild and restart containers
echo "🔄 Rebuilding containers with SSL certificates..."
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
echo ""
echo "📅 To renew certificate (run before expiration):"
echo "   ./ssl/generate-ssl.sh"
echo ""