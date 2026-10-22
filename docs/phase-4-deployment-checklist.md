# Phase 4 deployment checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, the public HTTPS `APP_URL`, and a unique generated `APP_KEY`.
- Serve HTTPS only. Set `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, and `SESSION_ENCRYPT=true`.
- Use a dedicated least-privilege database account; keep database and Gemini credentials only in the server environment.
- Leave `TRUSTED_PROXIES` empty unless a known reverse proxy is used. If needed, list only its IP/CIDR values.
- Set production log destination/retention and `LOG_LEVEL=warning`; restrict access to `storage/logs`.
- Run `php artisan migrate --force`, then `php artisan admin:create admin@example.com` interactively.
- Run `php artisan config:cache` and `php artisan view:cache`, then verify `/up`, admin login/logout, chatbot continuity, 401/403/419/429 behavior, and security headers.
- Ensure the web server denies `.env`, repository, database dump, and storage-log downloads; rotate credentials after any suspected disclosure.
