#!/usr/bin/env sh
set -eu

export APP_ENV=prod
export APP_DEBUG=0

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console cache:clear
php bin/console cache:warmup
