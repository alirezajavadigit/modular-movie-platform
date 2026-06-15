# Modular Movie Platform

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)
![CI](https://github.com/alirezajavadigit/modular-movie-platform/actions/workflows/ci.yml/badge.svg)

A production-ready RESTful API for a movie streaming platform built with **Laravel 11**. The codebase is split into 14 independent modules — each owning its own models, migrations, services, repositories, and tests — so the project scales without turning into a monolith.

---

## Modules

| Module | Purpose |
|---|---|
| **Movie** | Movies & serials, episodes, IMDB scores, badges, download links, multilingual titles |
| **Article** | Blog/news with multilingual content, draft/publish/archive workflow, soft deletes |
| **Person** | Cast & crew profiles (actor, director, writer), image uploads via media library |
| **Category** | Nested category tree with slug-based lookup, soft deletes |
| **Tag** | Polymorphic tags shared across movies and articles |
| **Auth** | JWT login/register, token refresh, Google OAuth, OTP via email & SMS |
| **Authorization** | Role-based access control (RBAC), per-module permissions |
| **Like** | Toggle likes on any resource (polymorphic) |
| **Favorite** | User favorites list with toggle support (polymorphic) |
| **Discussion** | Threaded comments with moderation (approve / reject / pending) |
| **Notification** | Multi-channel notifications (email, SMS), read/unread state |
| **Subscription** | Subscription plans, subscribe / cancel / activate flows |
| **Payment** | Stripe, PayPal, ZarinPal, Zibal gateway adapters |
| **User** | Admin user management with soft deletes |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 11 |
| Database | PostgreSQL 16 |
| Cache & Queues | Redis 7.2 |
| Web Server | Nginx 1.27 |
| Module System | nwidart/laravel-modules |
| Auth | php-open-source-saver/jwt-auth + laravel/socialite |
| RBAC | spatie/laravel-permission |
| Media | spatie/laravel-medialibrary |
| API Transformers | spatie/laravel-fractal |
| Multilingual | spatie/laravel-translatable |
| File Storage | Local or AWS S3 |

---

## Architecture

Every module follows the same internal layout:

```
Modules/Movie/
├── app/
│   ├── Contracts/          # Repository & service interfaces
│   ├── DTOs/               # Immutable, typed data transfer objects (readonly)
│   ├── Enums/              # PHP 8.1 backed enums (BadgeType, MovieType, …)
│   ├── Http/
│   │   ├── Controllers/    # Thin controllers — authorize, build DTO, call service
│   │   ├── Requests/       # Form request validation (array rules for translatable fields)
│   │   └── Resources/      # Fractal transformers for consistent JSON output
│   ├── Models/
│   ├── Policies/           # Laravel policy classes, wired via Gate::policy()
│   ├── Repositories/       # Data access — all queries via newQuery()
│   └── Services/           # Business logic, validation, transaction boundaries
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/api.php
└── tests/
    ├── Feature/            # HTTP-level tests with mocked services
    └── Unit/               # Service tests with mocked repositories
```

### Key Design Decisions

**Repositories always use `newQuery()`** — `$this->model->newQuery()->where(...)` instead of `$this->model->where(...)` — to guarantee a fresh query builder that doesn't inherit global scope state between calls.

**Multilingual fields are `json` columns** — Article, Category, Tag, Person, Movie, and Episode all store translatable attributes (`title`, `description`, `name`, `slug`, `body`, etc.) as JSON via `spatie/laravel-translatable`. Clients send `{"en": "...", "fa": "..."}` and the API returns the value for the active locale.

**DTOs are `readonly`** — constructed once from validated request data, then passed through the service → repository chain without mutation.

**Authorization is policy-based** — a `Gate::before` super-admin bypass is registered once in `AuthorizationPolicyServiceProvider`; all other access decisions live in per-module Policy classes.

---

## Getting Started

### With Docker (recommended)

```bash
git clone https://github.com/alirezajavadigit/modular-movie-platform.git
cd modular-movie-platform

cp .env.example .env

docker compose -f docker/docker-compose.yml up -d

docker exec movies_app composer install
docker exec movies_app php artisan key:generate
docker exec movies_app php artisan jwt:secret
docker exec movies_app php artisan migrate --seed
```

The API is available at **http://localhost:8080**.

### Without Docker

```bash
composer install
cp .env.example .env

# Set DB_*, REDIS_*, JWT_SECRET in .env, then:
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

### Make Commands

```bash
make setup         # First-time Docker setup (build, migrate, seed)
make up            # Start containers
make down          # Stop containers
make fresh         # migrate:fresh --seed (reset all data)
make test          # Run full PHPUnit suite
make shell         # Open bash in app container
make db-shell      # Open psql
make redis-cli     # Open Redis CLI
make cache-clear   # Clear all caches
make seed          # Run seeders only
```

---

## Demo Credentials

After running `--seed`, the following accounts are available:

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | Password1! |
| User | user@example.com | Password1! |

Seeded data includes: 70 films · 12 series with episodes · 60 articles · 150 people · 90 subscribers · full payment history.

---

## Docker Services

| Service | Image | Port | Purpose |
|---|---|---|---|
| `app` | PHP 8.3-FPM | 9000 | Laravel application |
| `nginx` | nginx:1.27-alpine | 8080 | Reverse proxy |
| `postgres` | postgres:16-alpine | 5432 | Primary database |
| `redis` | redis:7.2-alpine | 6379 | Cache & queue backend |
| `queue` | (same as app) | — | Queue worker |
| `adminer` | adminer | 5050 | Database UI |

---

## API Overview

All endpoints are prefixed with `/api/v1`. Authenticated routes require `Authorization: Bearer <token>`.

### Auth

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/auth/register` | Register a new account |
| `POST` | `/auth/login` | Get JWT access + refresh tokens |
| `POST` | `/auth/refresh` | Rotate the access token |
| `POST` | `/auth/logout` | Invalidate the token |
| `GET` | `/auth/me` | Current user profile |
| `POST` | `/auth/forgot-password` | Send OTP for password reset |
| `POST` | `/auth/change-password` | Change password |
| `GET` | `/auth/oauth/google` | Redirect to Google OAuth |
| `GET` | `/auth/oauth/google/callback` | Google OAuth callback |

### Movies & Episodes

| Method | Endpoint | Notes |
|---|---|---|
| `GET` | `/movies` | Public listing |
| `GET` | `/movies/{movie}` | Public detail |
| `GET` | `/movies/{movie}/episodes` | Episode list |
| `GET` | `/movies/{movie}/episodes/{episode}` | Episode detail |
| `POST` | `/movies` | Auth — create |
| `PUT` | `/movies/{movie}` | Auth — update |
| `DELETE` | `/movies/{movie}` | Auth — soft delete |
| `POST` | `/movies/{movie}/restore` | Auth — restore |
| `POST` | `/movies/{movie}/episodes` | Auth — create episode |
| `PUT` | `/movies/{movie}/episodes/{episode}` | Auth — update episode |

### Articles

Public routes (`/articles/*`) expose published content with search, slug lookup, and related articles. Admin routes (`/admin/articles/*`) add draft/archive/trash management.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/articles/published` | Published articles (paginated) |
| `GET` | `/articles/slug/{slug}` | Lookup by slug |
| `GET` | `/articles/{article}/related` | Related articles |
| `GET` | `/articles/search` | Full-text search |
| `POST` | `/admin/articles` | Create |
| `PATCH` | `/admin/articles/{article}/publish` | Publish |
| `PATCH` | `/admin/articles/{article}/archive` | Archive |
| `DELETE` | `/admin/articles/{article}/force-delete` | Permanently delete |

### People & Credits

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/persons/popular` | Top persons by credit count |
| `GET` | `/persons/search` | Search persons |
| `GET` | `/persons/slug/{slug}` | Lookup by slug |
| `GET` | `/credits/{type}/{id}/cast` | Cast list for any resource |
| `GET` | `/credits/{type}/{id}/crew` | Crew list for any resource |
| `POST` | `/admin/persons/{person}/image` | Upload profile photo |

### Social (Auth required)

| Endpoint | Description |
|---|---|
| `POST /likes/toggle` | Like or unlike any resource |
| `POST /favorites/toggle` | Add or remove from favorites |
| `GET /favorites` | Current user's favorites |
| `POST /discussions` | Post a comment |
| `POST /discussions/{id}/approve` | Approve a comment (moderator) |

### Subscriptions & Payments

```
GET  /subscription-plans                    # Browse available plans
POST /subscriptions/subscribe               # Subscribe to a plan (triggers payment)
PATCH /subscriptions/{id}/cancel            # Cancel an active subscription

GET  /payments/callback/{driver}            # Gateway callback (stripe | paypal | zarinpal | zibal)
GET  /payments                              # User's own payment history
PATCH /payments/{id}/verify                 # Verify a payment manually
```

---

## Multilingual Support

Translatable fields are stored as JSON columns and handled by `spatie/laravel-translatable`. When creating or updating a resource, pass an object keyed by locale:

```json
{
  "title": { "en": "Inception", "fa": "تلقین" },
  "description": { "en": "A thief who steals...", "fa": "دزدی که..." }
}
```

The API returns the value for the active locale (set via `Accept-Language` header or `?locale=` query param).

**Modules with translatable fields:**

| Module | Translatable Fields |
|---|---|
| Movie | `title`, `description` |
| Episode | `title`, `description` |
| Article | `title`, `slug`, `summary`, `body` |
| Category | `name`, `slug`, `description` |
| Tag | `name`, `slug`, `description` |
| Person | `first_name`, `last_name`, `biography`, `place_of_birth` |

---

## Payment Gateways

Four gateway adapters are included, all implementing `GatewayInterface`:

| Gateway | Region | Driver key |
|---|---|---|
| Stripe | International | `stripe` |
| PayPal | International | `paypal` |
| ZarinPal | Iran | `zarinpal` |
| Zibal | Iran | `zibal` |

Configure the active gateway per subscription by passing `driver` in the subscribe request. Callback URLs are handled at `GET /payments/callback/{driver}`.

---

## Running Tests

Tests use an in-memory SQLite database — no external setup required.

```bash
php artisan test                              # All tests
php artisan test --filter MovieFeatureTest    # Single module
php artisan test --filter MovieServiceTest    # Unit only
```

Each module has:
- **Unit tests** — service logic tested in isolation with mocked repositories
- **Feature tests** — full HTTP stack with mocked services, including authorization assertions

---

## CI / CD

Pushing to `develop` triggers the GitHub Actions pipeline:

1. **Test** — PHP 8.3, install dependencies, generate app key, run full PHPUnit suite
2. **Merge** — on green, merges `develop` → `main` with `--no-ff`
3. **Tag** — creates `v1` tag on `main` (once, skipped on subsequent runs)

---

## Environment Variables

```dotenv
# Application
APP_URL=http://localhost:8080
APP_LOCALE=en

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=movies_db
DB_USERNAME=movies_user
DB_PASSWORD=secret

# Cache & Queues
REDIS_HOST=127.0.0.1
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Auth
JWT_SECRET=          # php artisan jwt:secret
JWT_TTL=60           # access token lifetime in minutes
JWT_REFRESH_TTL=20160

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=http://localhost:8080/api/auth/google/callback

# File Storage
MOVIE_UPLOAD_DISK=local   # local | s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_BUCKET=

# Stripe
STRIPE_SECRET_KEY=
STRIPE_CALLBACK_URL=http://localhost:8080/api/v1/payments/callback/stripe

# PayPal
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_MODE=sandbox
PAYPAL_CALLBACK_URL=http://localhost:8080/api/v1/payments/callback/paypal

# ZarinPal
ZARINPAL_MERCHANT_ID=
ZARINPAL_SANDBOX=true

# Zibal
ZIBAL_MERCHANT=

# Frontend redirect after payment
PAYMENT_FRONTEND_REDIRECT_URL=http://localhost:3000/account/subscriptions
```

---

## License

MIT
