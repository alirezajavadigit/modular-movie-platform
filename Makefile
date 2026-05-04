DC     = docker compose -f docker/docker-compose.yml
APP    = $(DC) exec app

.PHONY: help build up down restart logs shell \
        composer artisan migrate seed fresh test \
        cache-clear queue-restart db-shell redis-cli

help:
	@echo ""
	@echo "  Usage: make <target>"
	@echo ""
	@echo "  Docker"
	@echo "  ------"
	@echo "  build          Build all images"
	@echo "  up             Start all services (detached)"
	@echo "  down           Stop and remove containers"
	@echo "  restart        Restart all services"
	@echo "  logs           Tail logs for all services"
	@echo "  shell          Open bash shell in app container"
	@echo ""
	@echo "  Application"
	@echo "  -----------"
	@echo "  setup          First-time project setup"
	@echo "  composer       Run composer (e.g. make composer c='require pkg')"
	@echo "  artisan        Run artisan (e.g. make artisan c='module:list')"
	@echo "  migrate        Run migrations"
	@echo "  seed           Run database seeders"
	@echo "  fresh          Drop all tables and re-migrate with seed"
	@echo "  test           Run PHPUnit test suite"
	@echo "  cache-clear    Clear all Laravel caches"
	@echo "  queue-restart  Gracefully restart queue workers"
	@echo ""
	@echo "  Services"
	@echo "  --------"
	@echo "  db-shell       Open psql session"
	@echo "  redis-cli      Open Redis CLI"
	@echo "  pgadmin        Start pgAdmin at http://localhost:5050"
	@echo ""

build:
	$(DC) build --no-cache

up:
	$(DC) up -d

down:
	$(DC) down

restart:
	$(DC) restart

logs:
	$(DC) logs -f --tail=100

shell:
	$(APP) bash

setup:
	@echo "→ Copying .env …"
	cp -n docker/.env.example .env || true
	@echo "→ Building images …"
	$(DC) build
	@echo "→ Starting services …"
	$(DC) up -d
	@echo "→ Waiting for containers to be healthy …"
	sleep 5
	@echo "→ Installing PHP dependencies …"
	$(APP) composer install --no-interaction
	@echo "→ Generating app key …"
	$(APP) php artisan key:generate
	@echo "→ Generating JWT secret …"
	$(APP) php artisan jwt:secret --no-interaction
	@echo "→ Running migrations …"
	$(APP) php artisan migrate --no-interaction
	@echo "→ Seeding database …"
	$(APP) php artisan db:seed --class="Modules\\Auth\\Database\\Seeders\\AuthDatabaseSeeder" --no-interaction
	@echo ""
	@echo "✓ Setup complete. App is running at http://localhost:8080"
	@echo "  Admin: admin@example.com / Password1!"
	@echo "  User:  user@example.com  / Password1!"
composer:
	$(APP) composer $(c)

artisan:
	$(APP) php artisan $(c)

migrate:
	$(APP) php artisan migrate --no-interaction

seed:
	$(APP) php artisan db:seed --no-interaction

fresh:
	$(APP) php artisan migrate:fresh --seed --no-interaction

permission:
	$(APP) php artisan permission:sync --fresh 

test:
	$(APP) php artisan test 

test-filter:
	$(APP) php artisan test --filter=$(f) --no-interaction

cache-clear:
	$(APP) php artisan optimize:clear
	$(APP) php artisan cache:clear
	$(APP) php artisan config:clear
	$(APP) php artisan route:clear
	$(APP) php artisan view:clear

queue-restart:
	$(APP) php artisan queue:restart

db-shell:
	$(DC) exec postgres psql -U $${DB_USERNAME:-movies_user} -d $${DB_DATABASE:-movies_db}

redis-cli:
	$(DC) exec redis redis-cli -a $${REDIS_PASSWORD:-redispassword}

pgadmin:
	$(DC) --profile tools up -d pgadmin
	@echo "→ pgAdmin running at http://localhost:5050"
	@echo "  Email: admin@admin.com  Password: admin"
