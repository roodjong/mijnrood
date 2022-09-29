#!/bin/sh
set -e

/var/www/project/bin/console cache:clear

exec php-fpm