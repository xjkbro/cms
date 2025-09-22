#!/usr/bin/env bash
set -euo pipefail

# Working dir is /var/www
cd /var/www

# If vendor not present, install PHP deps
if [ ! -d "vendor" ]; then
  echo "Installing composer dependencies..."
  composer install --no-interaction --no-ansi --no-progress --no-scripts || true
fi

# If node_modules not present, install node deps
if [ ! -d "node_modules" ]; then
  echo "Installing node dependencies..."
  npm install --no-audit --no-fund || true
fi

# Build frontend if public/build missing or empty
if [ ! -d "public/build" ] || [ -z "$(ls -A public/build 2>/dev/null || true)" ]; then
  echo "Building frontend assets..."
  npm run build || true
fi

php artisan optimize

# Run php-fpm as PID 1
exec php-fpm
