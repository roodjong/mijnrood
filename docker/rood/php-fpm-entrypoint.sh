#!/bin/sh
set -e

/var/www/project/bin/console cache:clear

chown -R www-data:www-data /var/www/project/var/

exec php-fpm
