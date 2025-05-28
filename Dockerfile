FROM openemr/openemr:7.0.2

LABEL maintainer="dydact LLMs <support@dydact.ai>"
LABEL description="iris EMR customized for American Caregivers Incorporated"
LABEL version="1.0.0"

# No modifications to the base image
# Using the default OpenEMR setup with path changes for iris branding

# First copy the site files
COPY sites/americancaregivers /var/www/localhost/htdocs/openemr/sites/americancaregivers

# Set correct permissions
RUN chmod -R 755 /var/www/localhost/htdocs/openemr/sites/americancaregivers && \
    mkdir -p /var/www/localhost/htdocs/openemr/sites/americancaregivers/documents

# Create the iris symlink during build
RUN ln -sf /var/www/localhost/htdocs/openemr /var/www/localhost/htdocs/iris

# Now you can modify setup.php
RUN sed -i 's/\$allow_multisite_setup = false;/\$allow_multisite_setup = true;/' /var/www/localhost/htdocs/openemr/setup.php

# Copy your startup and customization scripts
COPY docker/startup.sh /usr/local/bin/
COPY docker/run-database-customization.sh /usr/local/bin/

# Make them executable
RUN chmod +x /usr/local/bin/startup.sh /usr/local/bin/run-database-customization.sh

# Copy SQL customization script
COPY sql/iris-database-customization.sql /docker-entrypoint-initdb.d/

# Generate self-signed SSL certificates for testing
RUN mkdir -p /etc/ssl/certs && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/webserver.key.pem \
    -out /etc/ssl/certs/webserver.cert.pem \
    -subj "/C=US/ST=MD/L=Silver Spring/O=American Caregivers/CN=localhost"

# Copy Apache configuration
COPY apache/iris.conf /etc/apache2/conf.d/

# Use our custom startup script instead of the default command
CMD ["/usr/local/bin/startup.sh"]
