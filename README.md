# Leads Import

Laravel application for importing leads from XLSX files.

## Stack

- **PHP 8.3** + **Laravel 11**
- **MySQL 8** — leads and import history storage
- **Redis** — queue driver and session/cache
- **Nginx** — web server
- **openspout/openspout** — streaming XLSX reader (low memory, handles 100k+ rows)

## Architecture

The import works in two phases to respect `max_execution_time = 30`:

1. **HTTP request** (~1 s) — stores the uploaded file, creates an `imports` record, dispatches `ImportLeadsJob` to Redis queue, redirects to status page.
2. **Queue worker** (no time limit) — streams the XLSX row by row, inserts in batches of 500 using `upsert` (idempotent re-runs), updates `imports.processed_rows` after each batch.

The status page polls `/import/{id}/json` every 2 s and displays a live progress bar.

## Quick start (Docker)

```bash
git clone <repo-url>
cd leads-import

cp .env.example .env

docker compose up -d --build

# Wait for MySQL to be ready, then run migrations
docker compose exec app php artisan migrate --force

# App is now available at http://localhost:8080
```

## Database structure

See `database/schema.sql` or run migrations:

```bash
docker compose exec app php artisan migrate
```

### `leads`

| Column | Type | Description |
|---|---|---|
| id | bigint PK | |
| external_id | varchar(50) UNIQUE | Source system ID (e.g. `LD-000001`) |
| created_at_source | datetime | Lead creation time in source system |
| first_name | varchar(100) | |
| last_name | varchar(100) | |
| phone | varchar(30) | |
| email | varchar(150) | |
| city | varchar(100) | |
| source | varchar(100) | Traffic source |
| utm_campaign | varchar(100) | |
| product | varchar(150) | |
| budget_uah | decimal(12,2) | |
| status | varchar(50) | |
| manager | varchar(100) | |
| comment | text | |
| next_contact_at | datetime | |
| created_at / updated_at | timestamp | |

### `imports`

| Column | Type | Description |
|---|---|---|
| id | bigint PK | |
| filename | varchar | Stored file path |
| original_name | varchar | Original uploaded filename |
| total_rows | int | Total data rows in file |
| processed_rows | int | Successfully upserted rows |
| failed_rows | int | Rows that could not be inserted |
| status | enum | `pending` / `processing` / `done` / `failed` |
| error_message | text | Set if status = `failed` |

## Usage

1. Open `http://localhost:8080`
2. Upload an `.xlsx` file (any size — processing is async)
3. Monitor progress on the status page (auto-refresh every 2 s)

## Queue worker

The `queue` container runs automatically via `docker-compose.yml`.  
Manual start (outside Docker):

```bash
php artisan queue:work redis --tries=1 --timeout=3600 --memory=256
```
