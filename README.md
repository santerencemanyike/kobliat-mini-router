## Kobliat Mini Router — Local setup

Quick notes to run the exercise locally (MySQL):

## Kobliat Mini Router — Run & Populate (detailed)

This section explains how to run the app locally (direct or Docker), prepare the environment, run migrations, and populate data by calling the webhook endpoint.

Prerequisites

- PHP 8.0+ and Composer (if running locally)
- Node.js + npm (for building frontend assets)
- MySQL server (local or remote)
- Docker & Docker Compose (optional, recommended for portable setup)

Option A — Quick local (MySQL) setup

1. Install dependencies

```bash
composer install
npm install
```

2. Environment

```bash
cp .env.example .env
php artisan key:generate
# Configure MySQL connection in .env
# Example values (adjust to your local MySQL):
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=kobliat
# DB_USERNAME=root
# DB_PASSWORD=secret

# Create the database (local MySQL) and update .env accordingly
# Example (mysql CLI):
# mysql -u root -p -e "CREATE DATABASE kobliat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

3. Build assets (dev)

```bash
npm run dev
```

4. Run migrations

```bash
php artisan migrate
```

5. Start the app

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Now open the admin UI at http://127.0.0.1:8000/ (it uses a minimal Bootstrap/DataTables admin view).

Option B — Docker Compose

1. Build and run containers

```bash
docker compose up -d --build
```

2. Run artisan commands inside the PHP container (replace `app` with the PHP service name if different)

```bash
docker compose exec app composer install --no-interaction --no-dev --prefer-dist
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm install
docker compose exec app npm run build
```

3. Access the app

Open http://127.0.0.1:8000/ (or the host/port mapped by your Docker Compose) in your browser.

Populating data — using the inbound webhook (curl)

You can create conversations/messages by calling the webhook endpoint. Example (your example):

```bash
curl -v -X POST http://127.0.0.1:8000/api/webhooks/messages \
	-H "Content-Type: application/json" \
	-d '{"external_user_id":"+123456788","body":"Hello test","channel":"facebook"}'
```

Notes and examples

- `external_user_id` is the identifier for the user on the external platform (phone number, platform id, etc.).
- `channel` is a free-text channel identifier (e.g. `facebook`, `whatsapp`, `twitter`, `email`, `webchat`). The admin UI expects optional icon files at `public/icons/<channel>.png` for channel tabs.
- `sent_at` may be included in ISO8601 format to set the message timestamp (optional).

Bulk example (create many messages quickly):

```bash
for i in {1..10}; do \
	curl -s -X POST http://127.0.0.1:8000/api/webhooks/messages \
		-H "Content-Type: application/json" \
		-d "{\"external_user_id\":\"+1000000$i\",\"body\":\"Hello $i\",\"channel\":\"whatsapp\"}" >/dev/null &
done
wait
echo "Created 10 webhook messages"
```

Protected endpoints and `API_TOKEN`

- Some API endpoints that change data (for external integrations) are protected by a token. Set `API_TOKEN` in `.env` to enable minimal protection:

```bash
API_TOKEN=your-api-token-here
```

- When calling the protected reply endpoint use either the header `X-API-TOKEN` or `Authorization: Bearer your-api-token-here`:

```bash
curl -X POST http://127.0.0.1:8000/api/conversations/1/reply \
	-H "Content-Type: application/json" \
	-H "X-API-TOKEN: your-api-token-here" \
	-d '{"body":"Thanks, I received your message."}'
```

- The admin UI uses an internal UI-friendly endpoint `POST /api/conversations/{id}/reply-ui` which does not require the client to send the API token (the server uses its configured token internally). For local testing you can simply use the UI to reply without providing a token.

Seeding (optional)

- If the project includes seeders, run:

```bash
php artisan db:seed
# or to reset and seed
php artisan migrate:fresh --seed
```

Troubleshooting

- If assets or DataTables don't appear correctly, ensure the JS/CSS build ran: `npm run dev` (development) or `npm run build` (production).
- If images/icons are missing, place PNG files for channels in `public/icons/` (e.g. `public/icons/facebook.png`). The UI will fall back to textual labels if icons are absent.
- If uploads or storage links fail, run:

```bash
php artisan storage:link
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

- If you change environment variables, clear caches:

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

Quick test checklist

- Start the app (see Option A or B above).
- Call the webhook curl example to create a conversation.
- Open the admin UI at `/` and confirm the conversation appears in the DataTable.
- Click a conversation to open the modal, view per-channel tabs, and use the reply UI; replies posted from the UI use `POST /api/conversations/{id}/reply-ui` and do not require sending `API_TOKEN` from the browser.