# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Laravel 13 application (PHP ^8.3) using Blade views, Tailwind CSS v4, and Vite. The project is in an early scaffolding stage — `app/Models` only contains `User.php`, `routes/web.php` has a single `/` route rendering `home.blade.php`, and the only custom controller (`app/Http/Controllers/viewcourt.php`) is an empty stub. Migrations are still the default Laravel set (users, cache, jobs). When adding features, expect to be creating routes, controllers, models, and migrations mostly from scratch rather than extending existing patterns.

The app is primarily developed and deployed via Docker. The repo ships two separate compose stacks:
- `docker-compose.dev.yml` — local dev with `app` (PHP 8.4 + Apache on `:8080`), `mysql` 8 (on `:3310`), and a `vite` dev-server container (on `:5173`) for HMR.
- `docker-compose.yml` + `Dockerfile` — production image that builds assets at image-build time and expects an external MySQL (configured via `.env`).

`.env.example.dev` is the source of truth for dev env vars; `.env.example` is for production. The production entrypoint (`docker-entrypoint.sh`) refuses to start if `APP_KEY` is unset.

## Common commands

All PHP/artisan commands should be run inside the `app` container during development:

```bash
# Bring up dev stack (first run builds images)
docker compose -f docker-compose.dev.yml up -d --build

# Install PHP deps / generate key / migrate (first-time setup)
docker compose -f docker-compose.dev.yml exec app composer install
docker compose -f docker-compose.dev.yml exec app php artisan key:generate
docker compose -f docker-compose.dev.yml exec app php artisan migrate

# Shell into the app container
docker compose -f docker-compose.dev.yml exec app bash

# Tear down (add -v to also drop the mysql volume)
docker compose -f docker-compose.dev.yml down
```

Tests use **Pest 4** (not PHPUnit directly), configured via `phpunit.xml` and `tests/Pest.php`:

```bash
# Full test suite (clears config first, per composer script)
docker compose -f docker-compose.dev.yml exec app composer test

# Or directly
docker compose -f docker-compose.dev.yml exec app php artisan test

# Run a single test file or filter by name
docker compose -f docker-compose.dev.yml exec app php artisan test tests/Feature/ExampleTest.php
docker compose -f docker-compose.dev.yml exec app php artisan test --filter=testName
```

Lint/format with Pint:

```bash
docker compose -f docker-compose.dev.yml exec app ./vendor/bin/pint
```

Frontend (Vite runs in its own container automatically during `docker compose up`; these are only needed if running outside Docker):

```bash
npm run dev     # vite dev server with HMR
npm run build   # production build
```

Non-Docker dev shortcut (runs `php artisan serve`, the queue listener, and Vite concurrently):

```bash
composer dev
```

## Production deployment notes

- Build image then generate a key before first boot: `docker compose build && docker compose run --rm app php artisan key:generate --show`, then paste the value into `.env` as `APP_KEY`. The container will fail to start without it.
- After deploys, run migrations with `docker compose exec app php artisan migrate --force` and clear caches via `docker compose exec app php artisan optimize:clear`.
- Production expects an **external** MySQL — set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env`. The dev compose file is the only one that spins up MySQL locally.
