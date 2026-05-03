# Orderbook

A minimal limit-order matching engine. Laravel 12 API + Vue 3 SPA, real-time updates via Laravel Reverb (Pusher protocol), Postgres for persistence.

## Stack

| Layer | Tech |
|---|---|
| API | Laravel 12, PHP 8.3, Sanctum (SPA auth) |
| Frontend | Vue 3 (Composition API), Vite, Tailwind 4, Pinia, Laravel Echo |
| Database | Postgres 16 |
| Realtime | Laravel Reverb (Pusher protocol, self-hosted) |
| Runtime | Docker Compose |

## Local URLs

| URL | Service |
|---|---|
| http://localhost:8092 | Laravel API (nginx) |
| http://localhost:8093 | Vue SPA (Vite dev) |
| ws://localhost:8094 | Reverb websocket |
| postgres://localhost:54320 | Postgres (user/pass: orderbook/orderbook) |

## Quick start

```bash
cp .env.example .env
docker compose build app
docker compose up -d
```

## Container layout

| Container | Role |
|---|---|
| `orderbook_nginx` | Reverse proxy to php-fpm |
| `orderbook_app` | PHP-FPM, Laravel HTTP runtime |
| `orderbook_queue` | `php artisan queue:work` |
| `orderbook_reverb` | `php artisan reverb:start` (websocket) |
| `orderbook_postgres` | Database |
| `orderbook_node` | Vite dev server with HMR |

## Architecture

- **`backend/app/Domain/`**: entities and value objects (Money, Price, Amount, Symbol, Side, OrderStatus, Order, Asset). Pure PHP, no framework. Invariants enforced in constructors so an invalid object cannot exist.
- **`backend/app/DataTransferObjects/`**: Spatie laravel-data DTOs that move data across boundaries (HTTP request to service, service to broadcast payload). Pure data, no behavior.
- **`backend/app/Services/`**: use case orchestration. Constructor-injects repository interfaces, never Eloquent. One service per use case.
- **`backend/app/Repositories/`**: data access with explicit locking methods (`lockForMatching`, `findOpenForUpdate`). Services never call `Model::query()` or `DB::`.
- **Matching** is synchronous inside `PlaceOrderService`'s transaction. Counter-order is locked with `SELECT FOR UPDATE`. Broadcast is queued via `ShouldBroadcast`.
- **Commission**: buyer pays 1.5% in USD. Lock at place-time is `amount * price * 1.015`; the over-lock is refunded on match.
- **Mono-repo** here for review velocity. Production would split backend and frontend into separate repos with independent deploys.
