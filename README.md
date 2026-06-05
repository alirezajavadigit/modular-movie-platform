# Modular Movie Platform

A production-ready RESTful API for a movie streaming platform built with **Laravel 11**. The codebase is split into independent modules — each owning its own models, migrations, services, and tests — so the project scales without turning into a monolith.

---

## What's inside

| Module | What it does |
|---|---|
| **Movie** | Movies & serials, episodes, IMDB scores, badges, download links |
| **Article** | Blog/news with multilingual content, publishing workflow, soft deletes |
| **Person** | Cast & crew profiles (actor, director, writer…), image uploads |
| **Category** | Nested category tree with slug-based lookup |
| **Tag** | Polymorphic tags shared across movies and articles |
| **Auth** | JWT login/register, token refresh, Google OAuth, OTP via email & SMS |
| **Authorization** | Role-based access control, per-module permissions, auto-authorize middleware |
| **Like** | Toggle likes on any likeable resource |
| **Favorite** | User favorites list with toggle support |
| **Discussion** | Threaded comments with moderation (approve / reject / pending) |
| **Notification** | Multi-channel notifications (email, SMS), read/unread state |
| **Subscription** | Subscription plans, subscribe / cancel / activate flows |
| **Payment** | Stripe, PayPal, ZarinPal, Zibal gateway adapters |
| **User** | Admin user management with soft deletes |

---

## Tech stack

- **PHP 8.3** · **Laravel 11**
- **PostgreSQL 16** — primary database
- **Redis** — cache & queues
- **Docker + Nginx** — containerized from day one
- **nwidart/laravel-modules** — module system
- **spatie/laravel-permission** — RBAC
- **spatie/laravel-medialibrary** — file & image management
- **spatie/laravel-fractal** — API transformers
- **spatie/laravel-translatable** — multilingual model attributes
- **php-open-source-saver/jwt-auth** — stateless JWT auth
- **laravel/socialite** — Google OAuth

---

## Architecture

Every module follows the same internal structure:

```
Modules/Movie/
├── app/
│   ├── Contracts/          # Repository & service interfaces
│   ├── DTOs/               # Typed data transfer objects
│   ├── Enums/              # PHP 8 backed enums
│   ├── Http/
│   │   ├── Controllers/    # Single-action controllers
│   │   ├── Requests/       # Form request validation
│   │   └── Resources/      # Fractal transformers
│   ├── Models/
│   ├── Policies/
│   ├── Repositories/
│   └── Services/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/api.php
└── tests/
    ├── Feature/
    └── Unit/
```

The `auto.authorize` middleware resolves the correct policy gate from the route name automatically — no manual `$this->authorize()` calls scattered through controllers.

---

## Getting started

### With Docker (recommended)

```bash
git clone https://github.com/alirezajavadigit/modular-movie-platform.git
cd modular-movie-platform

cp .env.example .env

docker compose -f docker/docker-compose.yml up -d

docker exec movies_app composer install
docker exec movies_app php artisan key:generate
docker exec movies_app php artisan migrate --seed
```

The API will be available at `http://localhost:8080`.

### Without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate

# configure DB_* and REDIS_* in .env, then:
php artisan migrate --seed
php artisan serve
```

---

## API overview

All endpoints are prefixed with `/api/v1`. Public routes need no token; admin routes require `Authorization: Bearer <token>`.

### Auth

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/auth/register` | Register a new account |
| `POST` | `/auth/login` | Get JWT access + refresh tokens |
| `POST` | `/auth/refresh` | Rotate the access token |
| `POST` | `/auth/logout` | Invalidate the token |
| `GET` | `/auth/me` | Current user profile |
| `POST` | `/auth/forgot-password` | Send OTP for password reset |
| `GET` | `/auth/oauth/google` | Redirect to Google OAuth |

### Movies & Episodes

| Method | Endpoint | Notes |
|---|---|---|
| `GET` | `/movies` | Public listing |
| `GET` | `/movies/{movie}` | Public detail |
| `GET` | `/movies/{movie}/episodes` | Episode list |
| `POST` | `/movies` | Admin — create |
| `PUT` | `/movies/{movie}` | Admin — update |
| `DELETE` | `/movies/{movie}` | Admin — soft delete |
| `POST` | `/movies/{movie}/restore` | Admin — restore |

### Articles

Public routes (`/articles/*`) expose published content with search, slug lookup, and related articles. Admin routes (`/admin/articles/*`) add draft/archive management and trash/restore.

### People & Credits

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/persons/popular` | Top persons by credit count |
| `GET` | `/credits/{type}/{id}/cast` | Cast for a movie or article |
| `GET` | `/credits/{type}/{id}/crew` | Crew for a movie or article |
| `POST` | `/admin/persons/{person}/image` | Upload profile photo |

### Social

| Endpoint | Description |
|---|---|
| `POST /likes/toggle` | Like or unlike any resource |
| `POST /favorites/toggle` | Add or remove from favorites |
| `GET /favorites` | Current user's favorites |

### Subscriptions & Payments

```
GET  /subscription-plans           # Browse available plans
POST /subscriptions/subscribe       # Subscribe to a plan (triggers payment)
PATCH /subscriptions/{id}/cancel    # Cancel an active subscription

GET  /payments/callback/{driver}    # Payment gateway callback (Stripe, ZarinPal, …)
GET  /payments                      # User's own payment history (admin: all payments)
```

---

## Running tests

Tests use an in-memory SQLite database — no external setup needed.

```bash
php artisan test
```

To run a specific module:

```bash
php artisan test --filter MovieFeatureTest
```

---

## CI / CD

Pushing to `develop` triggers the GitHub Actions pipeline:

1. **Test** — installs dependencies, generates app key, runs the full PHPUnit suite
2. **Merge** — on green, merges `develop` into `main` automatically
3. **Tag** — creates the `v1` tag on `main` (once, skipped on subsequent runs)

---

## Environment variables

Key variables to configure in `.env`:

```dotenv
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=movies_db
DB_USERNAME=movies_user
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1

JWT_SECRET=          # php artisan jwt:secret

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

STRIPE_KEY=
STRIPE_SECRET=
ZARINPAL_MERCHANT_ID=
ZIBAL_MERCHANT=
```

---

## License

MIT
