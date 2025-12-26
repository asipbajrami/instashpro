#!/bin/bash
# InstashPro - Production Deployment Update Script
# Usage: ./update.sh
set -e

# Configuration
MASTER_NGINX_CONTAINER="deployment-nginx"
COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env.prod"

echo "Starting InstashPro Production Update..."

# 1. Update Code
echo "--- Updating Repository ---"
git fetch origin
git reset --hard origin/main

# 2. Rebuild and Restart Docker
echo "--- Rebuilding and Restarting Containers ---"
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --build --remove-orphans

# 3. Database Migrations
echo "--- Running Database Migrations ---"
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan migrate --force

# 4. Run Seeders (only if needed - uncomment)
# echo "--- Running Database Seeders ---"
# docker compose -f "$COMPOSE_FILE" exec -T backend php artisan db:seed --force

# 5. Clear Caches
echo "--- Clearing Caches ---"
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan view:cache

# 6. Refresh Master Nginx DNS Cache
if docker ps | grep -q "$MASTER_NGINX_CONTAINER"; then
    echo "--- Refreshing Master Nginx ---"
    docker restart "$MASTER_NGINX_CONTAINER"
    echo "Master Nginx refreshed."
fi

echo ""
echo "Update Complete! InstashPro is now in sync."

