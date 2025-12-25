#!/bin/bash
# InstashPro - Deployment Update Script
set -e

# Configuration
MASTER_NGINX_CONTAINER="deployment-nginx"
COMPOSE_FILE="docker-compose.yml"
ENV_FILE=".env"

echo "🚀 Starting InstashPro Update..."

# 1. Update Code
echo "--- 📦 Updating Repository ---"
git fetch origin
git reset --hard origin/main

# 2. Rebuild and Restart Docker
echo "--- 🏗️  Rebuilding and Restarting Containers ---"
docker compose -f "$COMPOSE_FILE" up -d --build --remove-orphans

# 3. Database Migrations
echo "--- 🗄️  Running Database Migrations ---"
docker compose exec -T backend php artisan migrate --force

# 4. Refresh Master Nginx DNS Cache
if docker ps | grep -q "$MASTER_NGINX_CONTAINER"; then
    echo "--- 🌐 Refreshing Master Nginx ---"
    docker restart "$MASTER_NGINX_CONTAINER"
    echo "✅ Master Nginx refreshed."
fi

echo ""
echo "✨ Update Complete! InstashPro is now in sync."

