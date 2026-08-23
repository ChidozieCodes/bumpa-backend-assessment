# Bumpa Achievement and Cashback Service

An event-driven Laravel backend for unlocking purchase achievements, awarding badges, and sending a NGN 300 cashback for every newly unlocked badge.

## Design summary

The request path records a purchase and returns quickly. Domain work is separated through events:

```text
POST purchase
  -> PurchaseCompleted
     -> unlock eligible achievements (idempotent)
        -> AchievementUnlocked
           -> unlock eligible badges (idempotent)
              -> BadgeUnlocked
                 -> queued NGN 300 cashback (idempotent)
```

Key choices:

- Achievement and badge ladders live in `config/achievements.php`; adding a milestone does not require changing listener or endpoint logic.
- Separate unlock tables preserve history rather than deriving rewards from a mutable count.
- Composite unique constraints prevent duplicate achievement and badge unlocks under retries.
- One unique cashback row per badge unlock plus a stable provider reference prevents duplicate payouts.
- Paystack is behind `CashbackGateway`, so provider code is replaceable and tests never call a real payment API.
- Cashback runs on the queue. A provider outage cannot slow down the purchase request and failed jobs can be retried.
- Amounts are stored as integer kobo to avoid floating-point errors.

## Explicit assumptions

The assessment names `First Purchase` and `5 Purchases`, and states that five achievements leave three more to the `Advanced` badge. It does not define the remaining purchase milestones or every badge threshold. This implementation therefore uses:

| Purchase count | Achievement |
| ---: | --- |
| 1 | First Purchase |
| 5, 10, 25, 50, 100, 250, 500, 1000, 2500 | N Purchases |

| Achievement count | Badge |
| ---: | --- |
| 1 | Beginner |
| 4 | Intermediate |
| 8 | Advanced |
| 10 | Master |

These values are deliberately isolated in configuration. If the product owner supplies a different ladder, only that file and the corresponding expectations need changing. Although the brief refers to achievement "groups," it specifies only purchases; the progress service supports more groups without inventing another business rule.

## Run with Docker Compose

Requirements: Docker Engine with Docker Compose.

```bash
docker compose up --build
```

This starts:

- Laravel web service at `http://localhost:8000`
- PostgreSQL 17
- A database-backed queue worker

Migrations run automatically. The Compose environment uses the safe `log` payment driver, which records simulated transfers instead of moving money.

Stop the stack with `docker compose down`. Use `docker compose down -v` only when you also want to delete the local database volume.

## Run locally without Docker

Requirements: PHP 8.3+, Composer, and the SQLite PHP extension.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

In another terminal, run `php artisan queue:work --tries=5`. The default `.env.example` uses SQLite and the `log` payment driver.

## API

### Record a purchase

This supporting endpoint represents the ecommerce purchase event source.

```http
POST /api/users/{user}/purchases
Content-Type: application/json

{
  "amount_kobo": 500000,
  "reference": "order-2026-0001"
}
```

`amount_kobo` must be an integer of at least 100 and `reference` must be globally unique.

### Read achievement progress

```http
GET /users/{user}/achievements
Accept: application/json
```

Example response after the first purchase:

```json
{
  "unlocked_achievements": ["First Purchase"],
  "next_available_achievements": ["5 Purchases"],
  "current_badge": "Beginner",
  "next_badge": "Intermediate",
  "remaining_to_unlock_next_badge": 3
}
```

Only the next locked achievement in each configured group is returned. At the final badge, `next_badge` is `null` and the remaining count is `0`.

## Paystack integration

The local default does not send money. To use Paystack in an authorized environment:

```dotenv
PAYMENT_DRIVER=paystack
PAYSTACK_SECRET_KEY=your_secret_key
PAYSTACK_BASE_URL=https://api.paystack.co
QUEUE_CONNECTION=database
```

Users also require `bank_code`, `account_number`, and `account_name`. The gateway creates a Paystack transfer recipient and initiates a transfer in kobo. A failed request is recorded and rethrown so Laravel's queue retry policy applies. In production, transfer webhooks should reconcile final provider status because an accepted transfer may later fail.

Never commit a real Paystack secret or real customer bank details.

## Tests and code style

Run everything with `composer test` and check formatting with `vendor/bin/pint --test`.

The suite covers:

- first and fifth purchase milestones;
- exact `AchievementUnlocked` payload behavior;
- badge progression and the five-to-eight example from the brief;
- initial, intermediate, and terminal endpoint states;
- event replay/idempotency;
- purchase validation and duplicate references;
- one NGN 300 cashback per badge;
- failed cashback persistence and retry behavior;
- Paystack recipient and transfer requests;
- rejection before provider calls when bank details are incomplete.

## Production considerations

- Add authentication and authorization. They are omitted because the assessment provides no auth contract.
- Put the queue on Redis or SQS and supervise workers.
- Run multiple stateless web and worker instances against PostgreSQL.
- Add a transactional outbox if event delivery must survive a process crash after purchase commit.
- Encrypt bank data at rest and restrict its access.
- Add Paystack webhook signature verification and reconciliation.
- Export metrics for unlocks, queue lag, cashback failures, and provider latency.
- Move secrets to a secret manager and disable debug mode.

## Project map

```text
app/Actions/RecordPurchase.php              Purchase transaction boundary
app/Events/                                 Domain event payloads
app/Listeners/                              Achievement, badge, cashback reactions
app/Payments/                               Provider contract and Paystack adapter
app/Services/AchievementProgress.php        Endpoint projection
config/achievements.php                     Extensible progression rules
database/migrations/                        Constraints and reward history
tests/Feature/                              HTTP and end-to-end domain flow
tests/Unit/                                 Cashback and provider integration
```
