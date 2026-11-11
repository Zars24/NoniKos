# NoniKos — Deployment

**Status:** the application runs locally on XAMPP (Windows, PHP 8.1.10, MariaDB 10.4.25). The steps below describe a repeatable deployment; none of them have been executed on a production host.

## Requirements

| Component | Version | Notes |
|---|---|---|
| PHP | 8.1.x | Laravel 10 supports 8.1–8.3. PHP ≥ 8.2 is needed for a future Laravel 12 upgrade. |
| Extensions | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `dom`, `xml`, `curl` | `gd` is **not** required (XLSX uses OpenSpout, PDF uses DomPDF). |
| Database | MySQL 5.7+ / MariaDB 10.3+ | utf8mb4 |
| Node | 18+ (build only) | Assets are prebuilt; Node is not needed at runtime. |
| Web server | Apache/Nginx with `public/` as document root | |

## First install

```powershell
composer install --no-dev --optimize-autoloader
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate
```

Edit `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, database credentials, `SESSION_SECURE_COOKIE=true`, mail settings.

Create the database (utf8mb4) then:

```powershell
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan db:seed --force   # optional demo data; NEVER on production
C:\xampp\php\php.exe artisan storage:link
npm ci && npm run build
C:\xampp\php\php.exe artisan config:cache
C:\xampp\php\php.exe artisan route:cache
C:\xampp\php\php.exe artisan view:cache
```

Demo accounts after `db:seed` (password `password` for all, fictional data only):
`owner@nonikos.test`, `manager@nonikos.test`, `finance@nonikos.test`,
`operations@nonikos.test`, and tenant portal logins `budi@example.test`,
`siti@example.test`, `rizky@example.test`.

## Permissions (Linux)

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

`storage/app/private` and `storage/app/backups` must not be web-accessible.

## Scheduler

Add one cron entry:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Windows: create a Task Scheduler job running `php artisan schedule:run` every minute.

Scheduled commands: `mark-overdue`, `apply-late-fees`, `expire-agreements`, `generate-invoices` (25th), `send-reminders`, `notify-ending-contracts`, `backup`, `prune-backups`.

## Queue

`QUEUE_CONNECTION=sync` is the current default; outbound WhatsApp/gateway calls happen inside the request. For production, switch to `database` or `redis` and run:

```
php artisan queue:work --tries=3
```

## Web server

Nginx example:

```nginx
server {
    listen 443 ssl http2;
    server_name nonikos.example.com;
    root /var/www/nonikos/public;

    index index.php;
    client_max_body_size 8M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

## Post-deploy checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` set
- [ ] HTTPS enforced, `SESSION_SECURE_COOKIE=true`
- [ ] `storage/` and `bootstrap/cache/` writable, not public
- [ ] `migrate --force` applied
- [ ] Scheduler running (`artisan schedule:list` shows all tasks)
- [ ] First owner account created via `/register`, then disable public registration if desired
- [ ] Backup path configured (`DB_DUMP_BINARY`, `DB_MYSQL_BINARY`) and one backup verified
- [ ] Mail transport tested
- [ ] Integration credentials either configured or intentionally left empty (sandbox mode)
- [ ] `php artisan test` green on the deployment checkout
- [ ] `APP_TIMEZONE=Asia/Jakarta` set (invoice periods, due dates, and reminders use it)
- [ ] Uptime monitor pointed at `GET /health` (200 `ok`, 503 `degraded`; returns booleans only)
- [ ] Activity log reachable by the owner and showing login events

## Upgrade notes

- Laravel 10.50.3 carries two advisories that are fixed in later majors (see `docs/SECURITY.md`). Upgrading requires PHP ≥ 8.2 first.
- Run `php artisan migrate` (never `migrate:fresh`) on any database with real data.
- Clear caches after deploy: `config:clear`, `route:clear`, `view:clear`, then re-cache.

## Rollback

1. Restore the pre-deploy database backup (`docs/BACKUP_RESTORE.md`).
2. Check out the previous release, `composer install`, `npm run build`.
3. Re-run the cache commands.

There is no automated rollback; keep the release artifact and a fresh backup before every deploy.
