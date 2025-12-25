# InstashPro - Docker Operations Makefile

.PHONY: help build up down logs clean setup shell migrate seed fresh

# Default target
help:
	@echo "InstashPro - Docker Commands"
	@echo ""
	@echo "Operations:"
	@echo "  make build      - Build all Docker images"
	@echo "  make up         - Start all services"
	@echo "  make down       - Stop all services"
	@echo "  make logs       - View logs from all services"
	@echo "  make clean      - Remove all containers, images, and volumes"
	@echo "  make setup      - Full setup: build, start, migrate"
	@echo "  make shell      - Open shell in backend container"
	@echo ""
	@echo "Database:"
	@echo "  make migrate    - Run database migrations"
	@echo "  make seed       - Run database seeders"
	@echo "  make fresh      - Fresh migrate and seed"

# Build all images
build:
	docker compose build

# Start all services
up:
	docker compose up -d

# Stop all services
down:
	docker compose down

# View logs
logs:
	docker compose logs -f

# Clean everything
clean:
	docker compose down -v --rmi all --remove-orphans

# Database commands
migrate:
	docker compose exec backend php artisan migrate --force

seed:
	docker compose exec backend php artisan db:seed --force

fresh:
	docker compose exec backend php artisan migrate:fresh --seed

# Shell into backend
shell:
	docker compose exec backend sh

# Full setup
setup:
	@echo "Building Docker images..."
	docker compose build
	@echo "Starting services..."
	docker compose up -d
	@echo "Waiting for MySQL to be healthy..."
	@timeout=60; \
	while [ $$timeout -gt 0 ]; do \
		if docker compose ps mysql | grep -q "healthy"; then \
			echo "MySQL is healthy!"; \
			break; \
		fi; \
		echo "Waiting for MySQL... ($$timeout seconds remaining)"; \
		sleep 2; \
		timeout=$$((timeout - 2)); \
	done
	@echo "Running migrations..."
	docker compose exec backend php artisan migrate --force
	@echo ""
	@echo "Setup complete!"
	@echo "  Frontend:  http://localhost:3000"
	@echo "  Backend:   http://localhost:8000"
	@echo "  Admin:     http://localhost:5173"

