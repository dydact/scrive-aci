#!/bin/sh
# Scrive ACI startup script
set -e
echo \"Starting Scrive ACI...\"
exec /usr/sbin/httpd -D FOREGROUND
