#!/bin/sh
set -e

echo "Starting EABMS Backend container..."

# Ensure storage directories exist
mkdir -p /var/www/storage/app/public/avatars
mkdir -p /var/www/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Create storage symlink if it doesn't exist
if [ ! -L /var/www/public/storage ]; then
    echo "Creating storage symlink..."
    ln -sf /var/www/storage/app/public /var/www/public/storage
    echo "Storage symlink created successfully"
else
    echo "Storage symlink already exists"
fi

# Ensure proper permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "Starting PHP-FPM..."
exec "$@"
