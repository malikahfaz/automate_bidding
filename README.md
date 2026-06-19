# Centralized Auction Aggregation & Automated Bidding System

This is a Laravel & Playwright (Node.js) based system designed to aggregate auctions from external platforms (B-Stock and Ivalua) and automate bid executions using browser automation.

---

## Server Requirements

- **Operating System**: Ubuntu 22.04 LTS (or similar Linux VPS distribution)
- **PHP**: v8.2 or v8.3 (with `sqlite`, `mysql`, `bcmath`, `curl`, `mbstring`, `xml`, `zip` extensions)
- **Database**: MySQL v8.0+
- **Cache & Queue**: Redis v7.0+
- **Process Manager**: Supervisor (for managing long-running daemons and queue workers)
- **Browser Runtime**: Node.js v20+, NPM v10+, and Playwright system dependencies (Chromium)

---

## Installation & Setup

### 1. Repository Setup & Composer Dependencies
Clone the repository to your VPS directory (e.g., `/var/www/automate_bidding`) and install PHP dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

### 2. Environment Configuration
Copy the environment template and modify variables:
```bash
cp .env.example .env
nano .env
```
Ensure you update the following sections:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=automate_bidding
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password

QUEUE_CONNECTION=redis
CACHE_STORE=database
```

Generate the encryption key:
```bash
php artisan key:generate
```

### 3. Run Database Migrations & Seeds
Run migrations to set up tables and seed initial administrator/platform records:
```bash
php artisan migrate --force
php artisan db:seed --force
```
*Note: This creates the default admin user account (`admin@ellectmobility.com` / `Password123!`) and creates the B-Stock and Ivalua platforms configuration.*

### 4. Node.js & Playwright Setup
Install npm packages and install the Chromium browser with its operating system dependencies:
```bash
# Install npm dependencies
npm install --omit=dev

# Install Playwright browser and system libraries
npx playwright install-deps chromium
npx playwright install chromium
```
*Note: On headless Linux servers, `playwright install-deps` installs required X11/Mesa graphical libraries that Chromium needs to run headless.*

---

## Daemon & Worker Configuration (Supervisor)

To ensure the system runs continuously and bid requests are executed instantly, you must configure **Supervisor** on the VPS.

Create a Supervisor config file:
```bash
sudo nano /etc/supervisor/conf.d/automate-bidding.conf
```

Add the following program blocks (adjust directory and user paths as necessary):

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/automate_bidding/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/automate_bidding
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/automate_bidding/storage/logs/worker.log
stopwaitsecs=3600

[program:sync-daemon]
command=php /var/www/automate_bidding/artisan auctions:sync --daemon --interval=10
directory=/var/www/automate_bidding
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/automate_bidding/storage/logs/sync-daemon.log

[program:proxy-daemon]
command=php /var/www/automate_bidding/artisan proxy-bids:monitor --daemon --interval=5
directory=/var/www/automate_bidding
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/automate_bidding/storage/logs/proxy-daemon.log
```

Apply changes and start processes:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## Laravel Scheduler Setup

The Laravel Scheduler handles daily platform sessions refresh. Add a cron entry to the server:
```bash
crontab -e
```
Add the following line:
```cron
* * * * * cd /var/www/automate_bidding && php artisan schedule:run >> /dev/null 2>&1
```

---

## Deployment Update / Restart Process

Whenever code is updated on the VPS, run this sequence to clear cache and restart workers:
```bash
# 1. Pull latest code
git pull origin main

# 2. Update dependencies and database
composer install --no-dev --optimize-autoloader
php artisan migrate --force

# 3. Clear cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Restart queue workers & daemons
php artisan queue:restart
sudo supervisorctl restart all
```

---

## Troubleshooting & Debugging

- **Failed Bids**: Check the **Failed Bids** list in the Admin Dashboard, which shows error logs and screenshot links.
- **Bot Detection**: If a platform blocks the scraper, screenshots will be captured and saved under `public/storage/automation/screenshots/`. Open the links from the Admin Logs viewer to inspect the page (e.g. check for CAPTCHAs).
- **Manual Sync**: If an auction appears outdated, click **Sync Now** in the Admin Auctions manager to queue a force sync.
