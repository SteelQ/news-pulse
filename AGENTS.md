# AGENTS.md

## Commands
- **Backend tests**: `cd backend && php artisan test` or `docker compose exec app php artisan test`
- **Single test**: `cd backend && php artisan test --filter=TestClassName` or `--filter=test_method_name`
- **Backend lint**: `cd backend && ./vendor/bin/pint`
- **Frontend dev**: `cd frontend && npm run dev`
- **Frontend build**: `cd frontend && npm run build`
- **Start all**: `docker-compose up -d`

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
