FROM openemr/openemr:7.0.2

LABEL maintainer="dydact <auto@dydact.io>"
LABEL description="Scrive ACI - American Caregivers Healthcare Management System"
LABEL version="1.0.0"

# Copy startup scripts first
COPY docker/startup.sh /usr/local/bin/
COPY docker/run-database-customization.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/startup.sh /usr/local/bin/run-database-customization.sh

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
COPY about.php /var/www/localhost/htdocs/
COPY services.php /var/www/localhost/htdocs/
COPY contact.php /var/www/localhost/htdocs/
COPY application_form.php /var/www/localhost/htdocs/
COPY authorize.php /var/www/localhost/htdocs/
COPY .htaccess /var/www/localhost/htdocs/

# Copy backend src directory (secured)
COPY src /var/www/localhost/htdocs/src

# Copy autism waiver app
COPY autism_waiver_app /var/www/localhost/htdocs/autism_waiver_app

# Copy public assets
COPY public /var/www/localhost/htdocs/public

# Copy setup and automation scripts
COPY setup_production.php /var/www/localhost/htdocs/
COPY scripts /var/www/localhost/htdocs/scripts
RUN chmod +x /var/www/localhost/htdocs/scripts/*.sh

# Generate self-signed SSL certificates
RUN mkdir -p /etc/ssl/certs && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/webserver.key.pem \
    -out /etc/ssl/certs/webserver.cert.pem \
    -subj "/C=US/ST=MD/L=Silver Spring/O=American Caregivers/CN=aci.dydact.io"

# Copy Apache configuration
COPY apache/iris.conf /etc/apache2/conf.d/

# Enable Apache modules needed for the application
RUN sed -i '/LoadModule rewrite_module/s/^#//g' /etc/apache2/httpd.conf

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
CMD ["/usr/local/bin/startup.sh"]
