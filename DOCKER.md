# InstashPro - Docker Setup

## Quick Start

### 1. Clone the repository
```bash
git clone git@github.com:asipbajrami/instashpro.git
cd instashpro
```

### 2. Configure Environment
Create a `.env` file in the root directory and add your credentials:
```env
APP_KEY=base64:...
OPENAI_API_KEY=sk-...
TYPESENSE_API_KEY=...
```

### 3. Run Everything
```bash
make setup
```

**Services will be available at:**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- Admin Dashboard: http://localhost:5173
- Typesense: http://localhost:8108

## Nginx Deployment

To deploy on a server with a master Nginx proxy:

1. Copy `instash.conf` to your Nginx sites-enabled directory:
   ```bash
   ln -s /path/to/instashpro/instash.conf /etc/nginx/sites-enabled/
   ```

2. Update the subdomains in `instash.conf` if necessary.

3. Restart Nginx:
   ```bash
   sudo systemctl restart nginx
   ```

## Makefile Commands

```bash
make help       # Show all available commands
make setup      # Full setup: build, start, migrate
make build      # Build all Docker images
make up         # Start all services
make down       # Stop all services
make logs       # View logs from all services
make clean      # Remove all containers, images, and volumes
make migrate    # Run database migrations
make shell      # Open shell in backend container
```

