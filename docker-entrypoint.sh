#!/usr/bin/env bash
set -euo pipefail

# Working dir is /var/www
cd /var/www

# Mark the repo as a safe directory to avoid "dubious ownership" errors
git config --global --add safe.directory /var/www || true

# If vendor not present, install PHP deps
if [ ! -d "vendor" ]; then
  echo "Installing composer dependencies..."
  composer install --no-interaction --no-ansi --no-progress --no-scripts || true
fi

# Always regenerate autoload
echo "Regenerating autoload..."
composer dump-autoload --no-interaction --no-ansi || true

# If node_modules not present, install node deps
if [ ! -d "node_modules" ]; then
  echo "Installing node dependencies..."
  npm install --legacy-peer-deps --no-audit --no-fund || true
fi

# Build frontend if public/build missing or empty
if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null || true)" ]; then
  echo "Building frontend assets..."
  npm run build || true
fi

php artisan optimize
php artisan storage:link || true

chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage
chmod 755 /var/www


# Run php-fpm as PID 1
exec php-fpm
