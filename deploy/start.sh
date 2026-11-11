#!/bin/sh
set -eu
mkdir -p /var/www/html/storage/private /var/www/html/storage/sessions
chown www-data:www-data /var/www/html/storage /var/www/html/storage/private /var/www/html/storage/sessions
chmod 700 /var/www/html/storage/private /var/www/html/storage/sessions
php /var/www/html/bin/migrate.php
exec apache2-foreground
