# AGENTS.md

## Commands (Docker-only development)
- **Start all**: `docker compose up -d`
- **Backend tests**: `docker compose exec app php artisan test`
- **Single test**: `docker compose exec app php artisan test --filter=TestClassName`
- **Backend lint**: `docker compose exec app ./vendor/bin/pint`
- **Frontend dev**: `docker compose exec node npm run dev` (or `docker compose logs -f node`)
- **Frontend build**: `docker compose exec node npm run build`
- **Run migrations**: `docker compose exec app php artisan migrate`
- **Enter app shell**: `docker compose exec app bash`
- **Enter node shell**: `docker compose exec node sh`

> **Note**: This project uses pure Docker containerized development. Do NOT run php/npm/node commands directly on host.

## Architecture
- **Monorepo**: `backend/` (Laravel 11 + PHP 8.4), `frontend/` (Vue 3 + Vite), `docker/` (infra)
- **Backend API**: RESTful API via Laravel, MySQL 8.0 database
- **Frontend SPA**: Vue 3 single-page app consuming backend API
- **Docker services**: app (PHP-FPM), web (Nginx:8080), db (MySQL:3306), node

## Code Style
- **TDD required**: Follow Red/Green/Refactor; write tests before implementation
- **Tests**: Unit in `backend/tests/Unit/`, Feature in `backend/tests/Feature/`; use `RefreshDatabase`
- **PHP**: PSR-4 autoloading, Laravel Pint for formatting
- **Frontend**: ES modules, Vite for bundling

## Project Rules
- Track progress in `docs/progress.md`; update on task changes
- See `docs/tdd-backend.md` for detailed TDD workflow
