 #!/bin/bash
# InstashPro - Docker Development Setup Script
# Usage: ./docker-dev.sh [command]
# Commands: start, stop, restart, fresh, logs, shell, seed, migrate

set -e

COMPOSE_FILE="docker-compose.dev.yml"
ENV_FILE=".env.dev"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[*]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[x]${NC} $1"
}

# Check if .env.dev exists
check_env() {
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env.dev not found!"
        print_status "Copying from .env.dev.example..."
        if [ -f ".env.dev.example" ]; then
            cp .env.dev.example .env.dev
            print_warning "Please edit .env.dev with your API keys before running again."
            exit 1
        else
            print_error ".env.dev.example not found either!"
            exit 1
        fi
    fi
}

# Start all services
start() {
    check_env
    print_status "Starting all services..."
    docker compose -f $COMPOSE_FILE --env-file $ENV_FILE up -d
    print_status "Waiting for services to be ready..."
    sleep 10
    print_status "Running database seeders..."
    docker compose -f $COMPOSE_FILE exec backend php artisan db:seed --force || true
    print_status ""
    print_status "Services are running:"
    echo "  - Backend:  http://localhost:8000"
    echo "  - Frontend: http://localhost:3000"
    echo "  - Admin:    http://localhost:5173"
    echo ""
    print_status "Login credentials:"
    echo "  - Email:    asip.bajrami@gmail.com"
    echo "  - Password: asip3000!"
}

# Stop all services
stop() {
    print_status "Stopping all services..."
    docker compose -f $COMPOSE_FILE down
}

# Restart all services
restart() {
    stop
    start
}

# Fresh start (remove volumes and rebuild)
fresh() {
    check_env
    print_warning "This will delete all data including the database!"
    read -p "Are you sure? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Stopping and removing all containers and volumes..."
        docker compose -f $COMPOSE_FILE down -v
        print_status "Rebuilding images..."
        docker compose -f $COMPOSE_FILE --env-file $ENV_FILE build
        print_status "Starting fresh..."
        docker compose -f $COMPOSE_FILE --env-file $ENV_FILE up -d
        print_status "Waiting for MySQL to be ready..."
        sleep 15
        print_status "Running database seeders..."
        docker compose -f $COMPOSE_FILE exec backend php artisan db:seed --force || true
        print_status ""
        print_status "Fresh setup complete!"
        echo "  - Backend:  http://localhost:8000"
        echo "  - Frontend: http://localhost:3000"
        echo "  - Admin:    http://localhost:5173"
        echo ""
        print_status "Login credentials:"
        echo "  - Email:    asip.bajrami@gmail.com"
        echo "  - Password: asip3000!"
    else
        print_status "Cancelled."
    fi
}

# View logs
logs() {
    SERVICE=${1:-""}
    if [ -n "$SERVICE" ]; then
        docker compose -f $COMPOSE_FILE logs -f $SERVICE
    else
        docker compose -f $COMPOSE_FILE logs -f
    fi
}

# Shell into backend
shell() {
    docker compose -f $COMPOSE_FILE exec backend sh
}

# Run seeders
seed() {
    print_status "Running database seeders..."
    docker compose -f $COMPOSE_FILE exec backend php artisan db:seed --force
}

# Run migrations
migrate() {
    print_status "Running database migrations..."
    docker compose -f $COMPOSE_FILE exec backend php artisan migrate --force
}

# Run artisan command
artisan() {
    docker compose -f $COMPOSE_FILE exec backend php artisan "$@"
}

# Build images
build() {
    check_env
    print_status "Building Docker images..."
    docker compose -f $COMPOSE_FILE --env-file $ENV_FILE build
}

# Show status
status() {
    docker compose -f $COMPOSE_FILE ps
}

# Show help
help() {
    echo "InstashPro Docker Development Script"
    echo ""
    echo "Usage: ./docker-dev.sh [command]"
    echo ""
    echo "Commands:"
    echo "  start     Start all services"
    echo "  stop      Stop all services"
    echo "  restart   Restart all services"
    echo "  fresh     Fresh start (removes all data)"
    echo "  build     Build Docker images"
    echo "  logs      View logs (optional: service name)"
    echo "  shell     Shell into backend container"
    echo "  seed      Run database seeders"
    echo "  migrate   Run database migrations"
    echo "  status    Show container status"
    echo "  artisan   Run artisan command"
    echo "  help      Show this help"
    echo ""
    echo "Examples:"
    echo "  ./docker-dev.sh start"
    echo "  ./docker-dev.sh logs backend"
    echo "  ./docker-dev.sh artisan tinker"
}

# Main
case "${1:-start}" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    fresh)
        fresh
        ;;
    build)
        build
        ;;
    logs)
        logs "$2"
        ;;
    shell)
        shell
        ;;
    seed)
        seed
        ;;
    migrate)
        migrate
        ;;
    status)
        status
        ;;
    artisan)
        shift
        artisan "$@"
        ;;
    help|--help|-h)
        help
        ;;
    *)
        print_error "Unknown command: $1"
        help
        exit 1
        ;;
esac
