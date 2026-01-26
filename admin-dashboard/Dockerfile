# React Admin Dashboard Dockerfile
FROM oven/bun:1-alpine AS deps

# Install dependencies only when needed
WORKDIR /app

# Copy package files
COPY package.json bun.lockb* package-lock.json* ./

# Install dependencies with Bun (much faster than npm)
RUN bun install

# Build the application
FROM oven/bun:1-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Set build-time environment variables
ARG VITE_API_URL=http://localhost:8000
ENV VITE_API_URL=$VITE_API_URL

# Build the application
RUN bun run build

# Production image using nginx
FROM nginx:alpine AS runner

# Copy built files to nginx
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 5173

CMD ["nginx", "-g", "daemon off;"]
