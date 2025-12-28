#!/bin/sh
set -e

# Wait for database to be ready
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    max_attempts=30
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if nc -z "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; then
            echo "Database is ready!"
            break
        fi
        attempt=$((attempt + 1))
        echo "Attempt $attempt/$max_attempts: Database not ready, waiting..."
        sleep 2
    done
    if [ $attempt -eq $max_attempts ]; then
        echo "ERROR: Could not connect to database after $max_attempts attempts"
        exit 1
    fi
fi

# Install/update dependencies (in case composer.json changed)
echo "Installing/updating composer dependencies..."
composer install --prefer-dist

# Generate autoloader
composer dump-autoload

# Always create/overwrite .env with Docker-specific settings
# (mounted .env from host has wrong DB_HOST for Docker)
echo "Creating .env file with Docker settings..."
cat > /var/www/html/.env << EOF
APP_NAME=${APP_NAME:-InstashPro}
APP_ENV=${APP_ENV:-local}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost:8000}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-instashpro}
DB_USERNAME=${DB_USERNAME:-instashpro}
DB_PASSWORD=${DB_PASSWORD:-instashpro_password}

BROADCAST_DRIVER=log
CACHE_DRIVER=${CACHE_DRIVER:-file}
FILESYSTEM_DISK=local
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=120

SCOUT_DRIVER=${SCOUT_DRIVER:-typesense}
TYPESENSE_API_KEY=${TYPESENSE_API_KEY:-xyz}
TYPESENSE_HOST=${TYPESENSE_HOST:-typesense}
TYPESENSE_PORT=${TYPESENSE_PORT:-8108}
TYPESENSE_PROTOCOL=${TYPESENSE_PROTOCOL:-http}

FRONTEND_URL=${FRONTEND_URL:-http://localhost:3000}
ADMIN_URL=${ADMIN_URL:-http://localhost:5173}

INSTAGRAM_SCRAPER_API_KEY=${INSTAGRAM_SCRAPER_API_KEY:-}
INSTAGRAM_SCRAPER_API_HOST=${INSTAGRAM_SCRAPER_API_HOST:-instagram-scraper-api2.p.rapidapi.com}
OPENROUTER_API_KEY=${OPENROUTER_API_KEY:-}
OPENROUTER_DEFAULT_MODEL=${OPENROUTER_DEFAULT_MODEL:-google/gemini-2.5-flash-preview-09-2025}

EMBEDDING_ENABLED=${EMBEDDING_ENABLED:-false}
EMBEDDING_URL=${EMBEDDING_URL:-${LITELLM_EMBEDDING_URL:-}}
EMBEDDING_API_KEY=${EMBEDDING_API_KEY:-${LITELLM_API_KEY:-}}
EMBEDDING_TEXT_MODEL=${EMBEDDING_TEXT_MODEL:-qwen3-embedding}
EMBEDDING_IMAGE_MODEL=${EMBEDDING_IMAGE_MODEL:-siglip2-embedding}
LITELLM_EMBEDDING_URL=${LITELLM_EMBEDDING_URL:-}
LITELLM_API_KEY=${LITELLM_API_KEY:-}

LOG_VIEWER_USERNAME=${LOG_VIEWER_USERNAME:-admin}
LOG_VIEWER_PASSWORD=${LOG_VIEWER_PASSWORD:-asip3000!}
EOF

# Generate app key if not set
if [ -z "$APP_KEY" ] || ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Run seeders if SEED_DATABASE is set to true (first-time setup)
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force
fi

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true

# Clear caches for development (don't cache in dev)
echo "Clearing caches for development..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Starting queue worker in background..."
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 &

echo "Starting Laravel development server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
