# Laravel Parse API Test Task

# USE Ключ FROM THE API: https://privnote.com/hLae7zHq

Console application that fetches paginated API data (stocks, incomes, sales, orders) with concurrent processing and background job queuing.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure database and API credentials in .env
touch database/database.sqlite database/queue.sqlite
# Unix/macOS above. For Windows PowerShell use:
New-Item -ItemType File -Path database\queue.sqlite
php artisan migrate:fresh
```

## Usage

**Fetch all endpoints (default: yesterday to today):**
```bash
php artisan fetch:api-data
```

**Fetch specific endpoint with date range:**
```bash
php artisan fetch:api-data --endpoint=stocks --date-from=2000-01-01 --date-to=2040-01-31
```

**Advanced options:**
```bash
php artisan fetch:api-data --endpoint=all --date-from=2025-01-01 --concurrent=20 --limit=250
```

### Available Options
- `--endpoint` - Target endpoint: `stocks`, `incomes`, `sales`, `orders`, or `all` (default: `all`)
- `--date-from` - Start date in YYYY-MM-DD format (default: yesterday)
- `--date-to` - End date in YYYY-MM-DD format (default: today)
- `--concurrent` - Number of concurrent requests (default: `30`)
- `--limit` - Items per page, max 500 (default: `500`)

**Note:** Stocks endpoint always uses today's date regardless of `--date-from` parameter.
