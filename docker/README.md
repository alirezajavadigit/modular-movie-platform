# Docker — Local Development Setup

Stack: **Laravel 11 · PHP 8.3-FPM · Nginx · PostgreSQL 16 · Redis 7**

---

## Directory layout

```
your-project/
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── .dockerignore          ← copy to project root as .dockerignore
│   ├── .env.example           ← copy to project root as .env
│   ├── Makefile               ← copy to project root as Makefile
│   ├── nginx/
│   │   └── conf.d/
│   │       └── default.conf
│   └── php/
│       ├── php.ini
│       ├── opcache.ini
│       └── supervisord.conf
└── ...
```

---

## Services

| Container        | Image                 | Port (host) | Purpose                    |
|------------------|-----------------------|-------------|----------------------------|
| movies_app       | custom (PHP 8.3-FPM)  | —           | Laravel application        |
| movies_nginx     | nginx:1.27-alpine     | **8080**    | Web server / reverse proxy |
| movies_postgres  | postgres:16-alpine    | **5432**    | Primary database           |
| movies_redis     | redis:7.2-alpine      | **6379**    | Cache + queue broker       |
| movies_queue     | custom (PHP 8.3-FPM)  | —           | Laravel queue worker       |
| movies_pgadmin   | dpage/pgadmin4:8      | **5050**    | DB GUI (optional profile)  |

---

## First-time setup (one command)

```bash
# From the project root
make setup
```

This single command: copies `.env`, builds images, starts services, installs
Composer dependencies, generates `APP_KEY` and `JWT_SECRET`, runs migrations,
and seeds the database with default roles and users.

App available at: **http://localhost:8080**

---

## Manual setup (step by step)

If you prefer to run each step yourself:

```bash
# 1. Copy environment file
cp docker/.env.example .env

# 2. Copy Makefile to project root
cp docker/Makefile ./Makefile

# 3. Copy .dockerignore to project root
cp docker/.dockerignore ./.dockerignore

# 4. Build Docker images
docker compose -f docker/docker-compose.yml build

# 5. Start all services in background
docker compose -f docker/docker-compose.yml up -d

# 6. Verify all containers are running
docker compose -f docker/docker-compose.yml ps

# 7. Install PHP dependencies
docker compose -f docker/docker-compose.yml exec app composer install

# 8. Generate application key
docker compose -f docker/docker-compose.yml exec app php artisan key:generate

# 9. Generate JWT secret
docker compose -f docker/docker-compose.yml exec app php artisan jwt:secret

# 10. Run migrations
docker compose -f docker/docker-compose.yml exec app php artisan migrate

# 11. Seed roles, permissions and default users
docker compose -f docker/docker-compose.yml exec app \
    php artisan db:seed --class="Modules\\Auth\\Database\\Seeders\\AuthDatabaseSeeder"
```

---

## Daily commands

### Using the Makefile (recommended)

```bash
make up              # start all services
make down            # stop everything
make restart         # restart all services
make logs            # tail logs of all containers
make shell           # bash shell inside app container

make migrate         # run pending migrations
make seed            # run seeders
make fresh           # drop all tables + migrate:fresh + seed
make test            # run full test suite
make cache-clear     # clear config / route / view / cache
make queue-restart   # gracefully restart queue workers

make db-shell        # psql session inside postgres container
make redis-cli       # redis-cli inside redis container

make artisan c="module:list"         # any artisan command
make composer c="require some/pkg"   # any composer command
make test-filter f="RegisterTest"    # run a specific test class
```

### Using docker compose directly

```bash
# Open a shell in the app container
docker compose -f docker/docker-compose.yml exec app bash

# Run any artisan command
docker compose -f docker/docker-compose.yml exec app php artisan <command>

# Run tests
docker compose -f docker/docker-compose.yml exec app php artisan test

# Watch logs for a specific service
docker compose -f docker/docker-compose.yml logs -f app
docker compose -f docker/docker-compose.yml logs -f nginx
docker compose -f docker/docker-compose.yml logs -f queue
docker compose -f docker/docker-compose.yml logs -f postgres
docker compose -f docker/docker-compose.yml logs -f redis

# Rebuild a single service after Dockerfile changes
docker compose -f docker/docker-compose.yml build app
docker compose -f docker/docker-compose.yml up -d app
```

---

## pgAdmin (optional DB GUI)

pgAdmin is gated behind a Docker Compose profile so it doesn't start by default:

```bash
# Start pgAdmin
make pgadmin
# or: docker compose -f docker/docker-compose.yml --profile tools up -d pgadmin

# Open http://localhost:5050
# Email:    admin@admin.com
# Password: admin

# Add a new server in pgAdmin:
#   Host:     postgres        (the service name, NOT localhost)
#   Port:     5432
#   Database: movies_db
#   Username: movies_user
#   Password: secret
```

---

## Environment variables

Critical `.env` values for Docker (host names must match service names in
`docker-compose.yml`):

```dotenv
DB_HOST=postgres      # NOT localhost
DB_PORT=5432
DB_DATABASE=movies_db
DB_USERNAME=movies_user
DB_PASSWORD=secret

REDIS_HOST=redis      # NOT localhost
REDIS_PASSWORD=redispassword
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

---

## Rebuilding after changes

```bash
# After changing Dockerfile or php.ini
docker compose -f docker/docker-compose.yml build app
docker compose -f docker/docker-compose.yml up -d app

# After adding a Composer package
make composer c="require vendor/package"
# or inside the container:
make shell
composer require vendor/package

# Full clean rebuild (nuclear option)
docker compose -f docker/docker-compose.yml down -v   # WARNING: deletes volumes/data
docker compose -f docker/docker-compose.yml build --no-cache
docker compose -f docker/docker-compose.yml up -d
```

---

## Troubleshooting

**Port already in use**
```bash
# Change host port mapping in docker-compose.yml, e.g.:
#   ports: ["9080:80"]   for nginx
#   ports: ["5433:5432"] for postgres
```

**Permission errors on storage/**
```bash
docker compose -f docker/docker-compose.yml exec app \
    chmod -R 775 storage bootstrap/cache
docker compose -f docker/docker-compose.yml exec app \
    chown -R www-data:www-data storage bootstrap/cache
```

**Container exits immediately**
```bash
docker compose -f docker/docker-compose.yml logs app
```

**Postgres connection refused**
```bash
# Confirm postgres is healthy
docker compose -f docker/docker-compose.yml ps postgres
# The app container waits for the healthcheck to pass before starting,
# but you can verify manually:
docker compose -f docker/docker-compose.yml exec postgres \
    pg_isready -U movies_user -d movies_db
```

**JWT_SECRET is empty**
```bash
docker compose -f docker/docker-compose.yml exec app php artisan jwt:secret
```
