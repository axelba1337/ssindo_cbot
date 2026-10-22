# Deployment guide

This guide prepares the `ssindo_cbot` academic prototype for a small Linux VPS. It does not publish the application or configure a real domain. Replace every placeholder with approved hosting details.

## Runtime and architecture

- PHP 8.3 with PHP-FPM and extensions required by Laravel/MySQL (`curl`, `mbstring`, `mysql`, `xml`, `zip`, `bcmath`, `intl`).
- Composer 2, Node.js 20+ with npm, MySQL 8/MariaDB equivalent, and Nginx or Apache.
- Laravel 12 serves the application from `public/`; the repository root must never be the web document root.
- The current application dispatches no queued jobs and defines no scheduled tasks. Use `QUEUE_CONNECTION=sync`; no queue worker or scheduler is required until code starts dispatching jobs.
- The repository currently contains incremental migrations, not a complete creation history for all business/RAG tables. An empty MySQL database cannot be provisioned safely with `migrate` alone; a reviewed schema baseline or missing creation migrations are required before any brand-new deployment.

## 1. Pre-deployment safety

1. Put the site in a maintenance window if replacing an existing instance.
2. Record the deployed commit and review pending migrations:

   ```bash
   git rev-parse HEAD
   php artisan migrate:status
   ```

3. Back up the existing database before migration. The command prompts for the password instead of exposing it in shell history:

   ```bash
   mkdir -p /var/backups/ssindo_cbot
   mysqldump --single-transaction --routines --triggers \
     -u SSINDO_DB_USER -p SSINDO_DB_NAME \
     > /var/backups/ssindo_cbot/pre-deploy-YYYYMMDD-HHMMSS.sql
   ```

4. Copy the existing production `.env` and `APP_KEY` to protected backup storage. Never run `php artisan key:generate` on an existing deployment. Changing `APP_KEY` invalidates sessions and can make encrypted data unreadable.
5. Do not run `chatbot:build-knowledge`, truncate tables, import database dumps, or rebuild embeddings as part of deployment.

## 2. Install the release

Example release path: `/var/www/ssindo_cbot`.

```bash
cd /var/www/ssindo_cbot
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

`public/build` is generated and Git-ignored, so it must be built on the server or supplied by a trusted CI artifact. Ensure `public/hot` is absent in production; it is only a Vite development marker.

For a brand-new installation only, create `.env` from `.env.example`, configure it, and generate `APP_KEY` once. Existing deployments must retain their current key. Before a new installation, resolve the missing business-schema baseline noted above; do not blindly restore an old data dump.

## 3. Production environment

Use server environment variables or a root-owned `.env` readable by the deployment/PHP-FPM user. Never commit it.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.example.com
APP_KEY=<existing-production-key-or-one-time-new-install-key>

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<database>
DB_USERNAME=<least-privilege-user>
DB_PASSWORD=<secret>

GEMINI_API_KEY=<server-side-secret>
GEMINI_API_VERSION=v1beta
GEMINI_MODEL=<verified-chat-model>
GEMINI_EMBED_MODEL=<model-used-by-existing-embeddings>
GEMINI_EMBED_DIM=768
GEMINI_GOOGLE_SEARCH=false

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync

CHATBOT_RATE_LIMIT_PER_MINUTE=10
CHATBOT_RATE_LIMIT_PER_HOUR=60
CHATBOT_EVENT_RATE_LIMIT_PER_MINUTE=20
ADMIN_API_RATE_LIMIT_PER_MINUTE=120
ADMIN_LOGIN_MAX_ATTEMPTS=5
ADMIN_LOGIN_DECAY_SECONDS=60

# Keep empty for a direct Nginx/Apache deployment. If a load balancer is used,
# list only its approved IP/CIDR values. Wildcards are rejected by the app.
TRUSTED_PROXIES=
```

Keep the existing Gemini embedding model and dimension unless a deliberate, tested embedding migration is approved. A model change does not justify an automatic rebuild during deployment.

## 4. Database and permissions

After reviewing the migration list and backup:

```bash
php artisan migrate --force
sudo chown -R deploy:www-data /var/www/ssindo_cbot
sudo find /var/www/ssindo_cbot/storage /var/www/ssindo_cbot/bootstrap/cache \
  -type d -exec chmod 775 {} \;
sudo find /var/www/ssindo_cbot/storage /var/www/ssindo_cbot/bootstrap/cache \
  -type f -exec chmod 664 {} \;
sudo chmod 640 /var/www/ssindo_cbot/.env
```

Provision the first administrator interactively; the password is hidden and is never stored in source code:

```bash
php artisan admin:create admin@example.com
```

## 5. Web server

### Nginx

```nginx
server {
    listen 80;
    server_name demo.example.com;
    root /var/www/ssindo_cbot/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Validate before reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Apache

Enable `rewrite`, configure PHP 8.3/FPM, and use this virtual-host shape:

```apache
<VirtualHost *:80>
    ServerName demo.example.com
    DocumentRoot /var/www/ssindo_cbot/public

    <Directory /var/www/ssindo_cbot/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
```

The repository already contains `public/.htaccess`. Never point `DocumentRoot` at `/var/www/ssindo_cbot`.

## 6. HTTPS and optimization

Configure the approved DNS/domain first, then issue a certificate using the VPS provider's standard Certbot flow. Redirect HTTP to HTTPS only after certificate validation. Confirm that HTTPS requests receive HSTS and secure session cookies.

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

Do not run `route:cache` yet: current page routes include closures. Cache configuration only after `.env` is final. Reload PHP-FPM after environment changes.

## 7. Verification

```bash
php artisan about --only=environment
php artisan migrate:status
php artisan route:list --path=api/admin -vv
curl -I https://demo.example.com/
curl -i https://demo.example.com/api/admin/faq
```

Expected results:

- Homepage returns 200 with CSP, `X-Content-Type-Options`, `X-Frame-Options`, and HSTS over HTTPS.
- Unauthenticated `/api/admin/faq` returns JSON 401; admin pages redirect to `/admin/login`.
- Service “Tanya Neev” buttons open the existing widget and prefill without automatically sending.
- A sent chatbot message returns an opaque token; numeric session IDs return 422.
- Valid tokens retain their own history, while unrelated tokens cannot access it.
- WhatsApp links use `https://wa.me/<validated-number>`.
- Rate excess returns 429. Test repeated requests only in staging or with a temporarily lower configured limit.
- Browser responses never contain `GEMINI_API_KEY`, stack traces, database credentials, or raw provider errors.

## 8. Operations and rollback

- Monitor Nginx/Apache, PHP-FPM, MySQL, and `storage/logs/laravel-*.log`; retain logs according to policy and do not expose them publicly.
- Back up database and `.env` separately and test restoration in a non-production environment.
- Deploy code through versioned releases or source-control commits. Roll back application code to the recorded commit if necessary; restore the database only from a verified backup and only when migration impact requires it.
- Add Supervisor/systemd queue workers only after queued jobs are introduced. At present, a worker would provide no benefit.

## Railway container deployment (not deployed by this change)

The root container recipe is tracked in Git as **`DockerFile`** (capital F). Windows treats this like `Dockerfile`, but Linux does not. `railway.json` explicitly selects that existing filename and `/up` health check, avoiding a case-sensitive autodetection failure without renaming/staging anyone's existing work. The recipe retains PHP 8.3/Apache and uses a separate Node 22 build stage (`npm ci`, `npm run build`). It copies the compiled Vite manifest/assets and the existing local `public/assets` images/styles/scripts into the runtime image. Apache serves only `public/`, permits the existing Laravel rewrite rules, and listens on `0.0.0.0:$PORT` (default 8080). The startup script validates the port and prepares caches after Railway injects runtime variables. Do not override its start command with the legacy Heroku `Procfile`.

See Railway's [Dockerfile guidance](https://docs.railway.com/builds/dockerfiles), [configuration reference](https://docs.railway.com/config-as-code/reference), and [public networking guidance](https://docs.railway.com/networking/public-networking). No domain or service has been created here.

Before deploying:

1. Use the included `railway.json` and its repository-root `DockerFile` path. Leave build/start command overrides empty. For a local Docker smoke test, use `docker build -f DockerFile -t ssindo-cbot .` and an approved external environment file; do not bake secrets into the image. Local Windows/Laragon continues to use the existing normal development workflow, not this Linux entrypoint.
2. Set Railway variables: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<approved-Railway-host>`, the **existing** `APP_KEY`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=warning`, `QUEUE_CONNECTION=sync`, and existing MySQL/Gemini settings. Leave `ASSET_URL` unset unless an approved HTTPS asset origin is actually used; never set it to localhost or an HTTP origin.
3. Keep `SESSION_DRIVER=database` and `CACHE_STORE=database` on an appropriately provisioned persistent database. Do not use ephemeral container files for durable sessions or business data. The business-schema baseline blocker below still applies. Keep knowledge/embeddings and the original embedding model/dimensions unchanged.
4. Back up the database and review/apply only approved pending migrations separately. Container startup never migrates, seeds, regenerates keys, or rebuilds embeddings. Keep the existing optional `AIVEN_CA_CERT` secret if required by the configured TLS database; it is written privately at runtime and is never printed.
5. Configure only verified ingress proxy IP/CIDR values in `TRUSTED_PROXIES`. The application deliberately rejects wildcard trust. Verify actual client IP detection and per-IP rate limiting in staging; an unconfigured proxy may otherwise group visitors under its IP. Do not solve this by blindly trusting client-supplied forwarding headers. HTTPS asset generation is already forced outside local development; that alone does not verify proxy/client IP handling.
6. `.dockerignore` excludes `.env*`, development `public/hot`, stale host build/config/view caches, session/log files and private certificates. Do not put secrets in Docker build arguments or Vite variables. The runtime image builds its own assets, so no development asset server is needed.
7. Smoke-test HTTPS asset requests, secure login/session cookies, admin denial for guests, chatbot token isolation, WhatsApp links and rate limits on staging. Inspect logs for errors without exposing secrets. Do not publish this academic prototype as an official Neevwork deployment.

### Mobile/device checks

The phone rules are scoped below 768px; the existing desktop grid remains intact. The hero retains both original local images, five-second rotation, manual controls and reduced-motion behavior. Phone banners show uncropped images against a navy fade. Floating chat follows `visualViewport` resize/scroll when supported, falls back to dynamic viewport units/window resize, respects safe areas, and avoids forced input focus on touch devices. Touch dragging/resizing is disabled; desktop mouse behavior remains available. Menu/chat opening is coordinated without resetting conversation state. Complex admin tables scroll only inside labeled keyboard-focusable regions.

Run automated checks without paid Gemini requests:

```powershell
npm.cmd run build
node --test tests/js/*.test.mjs
php artisan test --compact --filter="ExampleTest|MobileDeploymentReadinessTest|ChatbotContextTest|BusinessAnswerAccuracyTest|SecurityReadinessTest"
```

These include source-level breakpoint checks at 320, 360, 390, 430, 768 and 1366px, mocked keyboard/viewport/menu/chat events, and HTTPS asset rendering without a Vite development server. They are not device screenshots. Still test actual Safari/Chrome keyboards, portrait/landscape, safe areas, long answers, 200% text size, and navigation plus chatbot at those widths on staging. A Docker engine and real Railway environment are required to validate the container and ingress end-to-end.

Local verification on 2026-10-19: production Vite build passed; 31 targeted Laravel tests passed (367 assertions); 31 JavaScript interaction/source checks passed; PHP/JS and Linux entrypoint syntax checks passed; isolated production configuration caching and cached Artisan bootstrap passed, with the temporary cache removed afterward. No migrations, database imports, embedding rebuilds, paid Gemini requests, domain changes or deployments were performed. Browser/device and actual container checks were unavailable in this environment.

## Current blockers before a public demo

- Approved VPS, domain, DNS, and SSL details are not provided.
- A complete, reviewed migration/schema baseline for fresh business and RAG tables is not present; deploy against a backed-up existing database or resolve this before a new installation.
- Production database credentials, Gemini key/model approval, and backup location are not configured.
- An administrator must be provisioned interactively.
- Business content and contact details require owner approval before being represented as official.
- Browser/device smoke testing and responsible external load testing must be completed on staging.
