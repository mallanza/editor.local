# E2E (Playwright)

Runs browser-level tests against the Quill2 redlining UI.

## Prereqs
- `npm install`
- `composer install`
- A working local `.env`
- Database migrated + seeded (for login-required scenarios later):
  - `php artisan migrate --seed`

## Run
- Start a server in a separate terminal (choose one):
  - `php artisan serve --host=127.0.0.1 --port=8010`
  - or use your existing local host (e.g. OSPanel domain)
- Then run: `npm run test:e2e`

Notes
- Default base URL is `http://127.0.0.1:8010`.
- To point at a different server:
  - `set PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000`
- If you want Playwright to start `php artisan serve` for you:
  - `set PLAYWRIGHT_WEBSERVER=1`
  - `npm run test:e2e`
