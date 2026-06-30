#!/bin/bash
set -e
cd /www/wwwroot/eabms-running-docker

echo "🚀 Deploying absolute latest version..."

# 1. Pull the latest image from GHCR
echo "📥 Pulling latest images..."
docker-compose pull

# 2. Recreate containers with the new image and volume architecture
# Using --force-recreate ensures we don't use stale layers
echo "🔄 Recreating containers..."
docker-compose up -d --force-recreate

# 3. Setup host-side storage directories for the bind-mounts
echo "📁 Ensuring storage directories exist..."
mkdir -p backend/storage/app/public/avatars \
         backend/storage/framework/sessions \
         backend/storage/framework/views \
         backend/storage/framework/cache/data \
         backend/storage/logs \
         backend/bootstrap/cache

# 4. Correct permissions on host (so container can write)
echo "🔐 Setting permissions..."
chmod -R 775 backend/storage backend/bootstrap/cache 2>/dev/null || chmod -R 777 backend/storage backend/bootstrap/cache

# 5. Clear Laravel caches inside the container
echo "🧹 Clearing application caches..."
docker exec -u root eabms_backend php artisan cache:clear
docker exec -u root eabms_backend php artisan config:clear
docker exec -u root eabms_backend php artisan route:clear
docker exec -u root eabms_backend php artisan view:clear

# 6. Run database migrations
echo "🗄️ Running migrations..."
docker exec -u root eabms_backend php artisan migrate --force

# 7. Reload Nginx to ensure it picks up the fresh volume files
echo "📡 Reloading Nginx..."
docker exec eabms_nginx_prod nginx -s reload

echo "✅ Deployment Successful! http://eabms.mloganzila.or.tz:8080"
