#!/bin/sh
set -e

echo "Starting EABMS Backend container..."

# Ensure storage directories exist
mkdir -p /var/www/storage/app/public/avatars
mkdir -p /var/www/storage/framework/{sessions,views,cache/data}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

echo "Synchronizing frontend assets to shared volume..."
if [ -d "/var/www/public_image" ]; then
    # Create public directory if it doesn't exist (e.g. fresh volume)
    mkdir -p /var/www/public
    # Synchronize files from image to volume
    cp -r /var/www/public_image/. /var/www/public/
    echo "Frontend assets synchronized successfully"
else
    echo "Notice: /var/www/public_image not found (likely dev environment), skipping sync"
fi

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
