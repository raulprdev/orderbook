# Orderbook

A minimal limit-order matching engine. Two parties place buy/sell orders for BTC or ETH; when prices and amounts align, the engine matches them, debits/credits balances and assets, records a trade, and pushes a real-time event to both parties' UIs.

Built as a Laravel 12 API + Vue 3 SPA, with Postgres for persistence and Laravel Reverb for websockets (Pusher protocol, self-hosted). Everything runs in Docker Compose.

## Stack

| Layer | Tech |
|---|---|
| API | Laravel 12, PHP 8.3, Sanctum (SPA cookie auth) |
| Frontend | Vue 3 + TypeScript (Composition API), Vite, Tailwind 4, Pinia, Laravel Echo |
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

# Migrate dev + test databases
docker compose exec app php artisan migrate
docker compose exec -e DB_DATABASE=orderbook_test app php artisan migrate

# (Optional) seed two demo accounts ready to trade
docker compose exec app php artisan db:seed --class=DemoSeeder
```

The demo seeder creates:

| Email | Password | Starting state |
|---|---|---|
| `alice@example.com` | `secret-password` | $100,000 USD + 5 ETH |
| `bob@example.com` | `secret-password` | 1 BTC |

Open http://localhost:8093 and sign in (one account per browser session, or use a second incognito window for the counterparty).

## Container layout

| Container | Role |
|---|---|
| `orderbook_nginx` | Reverse proxy to php-fpm |
| `orderbook_app` | PHP-FPM, Laravel HTTP runtime |
| `orderbook_queue` | `php artisan queue:work` (broadcasts go through here) |
| `orderbook_reverb` | `php artisan reverb:start` (websocket server) |
| `orderbook_postgres` | Database |
| `orderbook_node` | Vite dev server with HMR |

## Project layout

```
backend/
├── app/
│   ├── Domain/            ← pure PHP: framework-free
│   │   ├── Entities/      ← Order, Asset, Trade, User
│   │   ├── ValueObjects/  ← Money, Amount, Price (integer-backed, exact math)
│   │   ├── Events/        ← OrderMatched (pure data)
│   │   ├── Matching/      ← MatchingStrategy interface + FirstValidMatchStrategy
│   │   └── Exceptions/    ← InsufficientBalance, InvalidOrderTransition, ...
│   ├── DataTransferObjects/ ← Spatie laravel-data DTOs (HTTP boundary)
│   ├── Services/          ← PlaceOrderService, MatchOrderService, CancelOrderService
│   ├── Repositories/      ← contract interfaces + Eloquent impls; all locking lives here
│   ├── Http/              ← Controllers, FormRequests, Resources
│   ├── Events/            ← Laravel-coupled wrappers (OrderMatchedBroadcast)
│   └── Models/            ← Eloquent only, no business logic
├── database/migrations/   ← schema (bigint columns for prices/amounts)
└── tests/
    ├── Unit/              ← pure PHP, mocked repos
    ├── Integration/       ← real Postgres, repository tests
    └── Feature/           ← HTTP-to-DB-to-broadcast end-to-end

frontend/
├── src/
│   ├── views/             ← LoginView, RegisterView, OverviewView, PlaceOrderView
│   ├── components/        ← AppNav, AppToasts, PlaceOrderForm, MyOrdersPanel, OrderbookPanel
│   ├── stores/            ← Pinia: auth, profile, orderbook, myOrders, toasts
│   ├── lib/               ← api (axios + Sanctum CSRF), echo (Reverb client), time
│   ├── router/            ← vue-router with auth guard + bootstrap
│   └── types/             ← shared enum literals
```

## Domain & business rules

**Money types are integer-backed value objects**, never floats. Three precisions, each derived from the smallest practical unit:

| VO | Internal | One whole unit |
|---|---|---|
| `Money` | int (micro-USD, 10⁻⁶) | $1 = 1,000,000 |
| `Amount` | int (subunit, 10⁻⁸) | 1 BTC = 100,000,000 |
| `Price` | int (cent, 10⁻²) | $1 = 100 |

This is the same pattern most exchanges use internally: no float drift, exact equality, fast comparisons. The `PositiveScalar` trait shares parsing/formatting/arithmetic across all three; each concrete VO declares only its scale, exception class, and any extra invariants (Price is strictly > 0).

**Matching policy** (`OrderRepository::firstMatchableCounterOrder`):
- Same symbol, opposite side, status open
- Equal amount (no partial fills — full match only)
- Price condition: `buy.price >= sell.price`
- Excludes orders from the same user (no self-trade)
- FIFO — the oldest valid counter wins

**Commission** = 1.5%, paid by the buyer in USD (env-driven, `COMMISSION_BASIS_POINTS=150`):
- At place-time, the buyer's balance is debited by `volume + fee` at *their* price (the over-lock)
- At match-time, the actual cost is `volume + fee` at the *maker's* price
- The difference (if maker price < buyer price) is refunded to the buyer

**Concurrency safety**: every state-changing flow is wrapped in `DB::transaction` at the service layer. Rows are acquired with `SELECT ... FOR UPDATE` via repository methods (`findOpenForUpdate`, `findByIdForUpdate`, `findByUserAndSymbolForUpdate`). Two simultaneous matches against the same counter-order serialize on the row lock; the second sees `status=filled` after acquiring it and skips.

## Real-time

When a match settles, `PlaceOrderService` dispatches `OrderMatchedBroadcast` (a Laravel-coupled wrapper around the pure-domain `OrderMatched` event). It implements `ShouldBroadcast`, goes through the queue, then to Reverb, then to both buyer's and seller's `private-user.{id}` channels. The SPA listens via Echo and refreshes wallet/orders/orderbook stores; a toast appears with the trade details.

Channel auth (`routes/channels.php`) restricts each `user.{id}` channel to the user themselves.

## API surface

| Method | Endpoint | Auth | Function |
|---|---|---|---|
| POST | `/register` | open | Create user, auto-login |
| POST | `/login` | open | Session login |
| POST | `/logout` | required | Destroy session |
| GET | `/api/profile` | required | User + USD balance + assets |
| GET | `/api/orders?symbol=BTC` | required | Orderbook (open orders for symbol) |
| GET | `/api/orders/mine` | required | User's own order history (all statuses) |
| POST | `/api/orders` | required | Place order (with synchronous matching) |
| POST | `/api/orders/{order}/cancel` | required | Cancel open order, refund/release lock |

Domain exceptions (`InsufficientBalance`, `CannotCancelOrder`, etc.) are mapped to HTTP 422 by the global exception handler.

## Frontend features

- Two protected screens: **Overview** (wallet + my orders + orderbook) and **Place order** (form)
- **Order filtering** on My Orders by symbol / side / status (client-side dropdowns)
- **Volume preview** in the order form with per-side fee breakdown ("You'll pay $406.00 = $400.00 volume + $6.00 fee" for buys)
- **Toast notifications** on match events (auto-dismiss after 10s)
- **Relative timestamps** ("2 minutes ago") on My Orders, ticking every 30s

## Testing

```bash
# Full suite (130 tests)
docker compose exec app vendor/bin/phpunit

# By suite
docker compose exec app vendor/bin/phpunit --testsuite Unit
docker compose exec app vendor/bin/phpunit --testsuite Integration
docker compose exec app vendor/bin/phpunit --testsuite Feature

# Code style (Laravel Pint)
docker compose exec app vendor/bin/pint
```

- **Unit** — pure PHP, no Laravel boot, mocked repositories. Domain VOs, entities, services.
- **Integration** — real Postgres (`orderbook_test` database), repository tests with `RefreshDatabase`. Verifies SQL behavior including locking semantics.
- **Feature** — HTTP-to-DB end-to-end. Auth, validation, response shapes.

TDD discipline throughout: each commit pairs a failing test with the implementation that makes it pass.

## Design notes

| Decision | Rationale | Production direction |
|---|---|---|
| Integer-backed VOs | Exact arithmetic, no float drift | Same |
| Single 10⁻⁸ precision for all assets | BTC native; fine for ETH at human trade sizes | Per-symbol precision via a `Symbol::scale()` registry; BCMath / GMP for ETH at native wei |
| Equal-amount matching only | Simplest correct interpretation of full-match-only | Partial fills with `filled_amount` column on orders |
| FIFO over price-time priority | Simpler to reason about | `BestPricedMatchStrategy` (the strategy seam exists) |
| Synchronous match in `PlaceOrderService` transaction | Atomic, race-safe by `lockForUpdate`; one consistency boundary | Same — async matching adds two transaction boundaries and idempotency complexity for no clear win at this scale |
| Buyer pays USD fee with lock-then-refund | Buyer always receives the full asset amount they ordered | Same |
| Domain pure, broadcast via wrapper | Domain has zero framework deps | Same |
| Frontend literal types hardcoded (`'BTC' \| 'ETH'`) | Single source of truth on the frontend (`src/types/enums.ts`); manual sync with backend `Symbol` enum is acceptable for a small, stable symbol set | `GET /api/config` server-driven, OR OpenAPI codegen for stricter pairing |
| Client-side filtering on My Orders | Per-user history is bounded (dozens to hundreds) | Server-side: `/api/orders/mine?status=open&symbol=BTC&page=1` with indexed columns and pagination |
| `POST /api/orders` has no idempotency key | Frontend disables submit during request — covers accidental double-submits | `Idempotency-Key` header + DB column with unique index per user; middleware returns the existing order on retry |
| Mono-repo | Single clone, single git log, one `docker compose up` | Two repos with independent CI/deploy/versioning |