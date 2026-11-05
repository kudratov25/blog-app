# Personal Engineering Blog & Portfolio (Nuxt 3 + Laravel 11)

A full-stack engineering blog, portfolio case-study platform, and Markdown publishing studio built with **Nuxt 3** (Bento Studio UI) and **Laravel 11** (REST API + Sanctum + Redis Cache Buffering).

---

## Stack & Services

| Service | Technology | Port / URL | Description |
| :--- | :--- | :--- | :--- |
| **`nuxt`** | Nuxt 3 + Tailwind CSS | `http://localhost:3000` | Public blog, projects & Admin Studio (`/studio-portal`) |
| **`nginx`** | Nginx 1.27 Alpine | `http://localhost:8000` | Reverse proxy for Laravel API (`/api/v1`) |
| **`php`** | PHP 8.3 FPM + Redis + GD | Internal `:9000` | Laravel 11 API container (auto-migrates & seeds on start) |
| **`scheduler`** | PHP 8.3 CLI (`schedule:work`) | Background | Runs `php artisan views:flush` every 5 minutes |
| **`postgres`** | PostgreSQL 16 Alpine | `localhost:5432` | Primary relational database (`blog`) |
| **`redis`** | Redis 7 Alpine | `localhost:6379` | Cache buffer for read counts, sessions & queues |

---

## Quick Start (Plug & Play with Docker)

### 1. Start the Entire Stack
Make sure **Docker Desktop** (or Docker Engine + Compose) is running, then run from the project root:

```bash
docker compose up -d --build
```
*(Or using the Makefile: `make up`)*

**What happens automatically on first boot:**
1. Starts **PostgreSQL 16** and **Redis 7** and waits for healthchecks.
2. Installs Composer dependencies inside `api/` if `vendor/` is missing.
3. Runs database migrations (`php artisan migrate --force`).
4. Seeds initial categories, tags, projects, articles, and the admin account (`php artisan db:seed --force`) if the database is empty.
5. Links the public media storage directory (`php artisan storage:link`).
6. Starts the background scheduler (`php artisan schedule:work`) and Nuxt 3 server.

---

### 2. Access the Application

- **Public Website**: [http://localhost:3000](http://localhost:3000)
- **Admin Studio (Hidden from crawlers & bots)**: [http://localhost:3000/studio-portal](http://localhost:3000/studio-portal)
- **Backend API Base**: [http://localhost:8000/api/v1](http://localhost:8000/api/v1)

#### Admin Credentials
Configure your admin credentials in `api/.env` before seeding:
```dotenv
ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```
Then run `make fresh` (or `php artisan db:seed --force`) to create/update the admin account from `api/.env`.

---

## Using Your Custom Domain(s) in Docker

All URLs and ports are controlled from the root `.env` file (copied from `.env.example`). You can use **either** separate domains for frontend and backend **or** a single unified domain:

### Option A: Separate Frontend & Backend Domains
If your frontend is on `https://yourdomain.com` and your backend API is on `https://api.yourdomain.com`, set in the root `.env`:
```dotenv
APP_ENV=production
APP_DEBUG=false
NODE_ENV=production

FRONTEND_URL=https://yourdomain.com
BACKEND_URL=https://api.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

### Option B: Single Unified Domain
Because `docker/nginx/default.conf` routes `/api/*`, `/sanctum/*`, and `/storage/*` to Laravel while proxying `/` to Nuxt, you can also run both on the exact same domain (`https://yourdomain.com`):
```dotenv
APP_ENV=production
APP_DEBUG=false
NODE_ENV=production

FRONTEND_URL=https://yourdomain.com
BACKEND_URL=https://yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

---

## Common Docker Commands

You can use either standard `docker compose` commands or the included `Makefile` shortcuts:

| Task | `Makefile` Shortcut | Full Docker Command |
| :--- | :--- | :--- |
| **Start all containers** | `make up` | `docker compose up -d --build` |
| **Stop all containers** | `make down` | `docker compose down` |
| **Restart all containers** | `make restart` | `docker compose restart` |
| **Follow live logs** | `make logs` | `docker compose logs -f` |
| **Flush cached read counts now** | `make flush-views` | `docker compose exec php php artisan views:flush` |
| **Run migrations** | `make migrate` | `docker compose exec php php artisan migrate --force` |
| **Reset DB & re-seed fresh data** | `make fresh` | `docker compose exec php php artisan migrate:fresh --seed --force` |
| **Open shell in API container** | `make shell-api` | `docker compose exec php sh` |
| **Open shell in Nuxt container** | `make shell-web` | `docker compose exec nuxt sh` |
| **Stop & wipe database volume** | — | `docker compose down -v` |

---

## How Read Counting Works (Cache-Buffered + Batch Flush)

To avoid expensive database `UPDATE` queries and row locks on every page load:

1. **On Article / Project Open (`GET /api/v1/posts/{slug}` or `/projects/{slug}`)**:
   - Checks a **6-hour visitor deduplication key** (`views:seen:{type}:{id}:{visitor_hash}`) in Redis/Cache so page refreshes (`F5`) from the same visitor do not inflate read numbers.
   - Increments an in-memory cache buffer (`views:buffer:{type}:{id}`) with **zero SQL writes**.
2. **Every 5 Minutes (`php artisan views:flush`)**:
   - The `scheduler` container flushes all buffered view counts from Redis/Cache to PostgreSQL in a single transaction (`UPDATE posts SET views_count = views_count + :delta`).
   - You can also view pending cached reads and trigger an immediate sync anytime from the **Total Reads** card on `/admin` or by running `make flush-views`.

---

## Project Structure

```text
.
├── api/                  # Laravel 11 REST API
│   ├── app/
│   │   ├── Console/Commands/FlushViewsCommand.php
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   └── Services/ViewCounterService.php
│   ├── database/
│   └── routes/
├── web/                  # Nuxt 3 Frontend & Admin Studio
│   ├── assets/css/main.css
│   ├── components/       # Bento cards, AdminPostEditor, ConfirmModal, MarkdownRenderer
│   ├── composables/      # useApi, useAuth, useConfirm
│   ├── layouts/          # default, admin, auth
│   └── pages/            # Public & Admin (/admin/*) routes
├── docker/               # Dockerfiles & Nginx / PHP entrypoint configs
│   ├── nginx/default.conf
│   ├── nuxt/Dockerfile
│   └── php/
│       ├── Dockerfile
│       └── entrypoint.sh
├── compose.yaml          # Plug-and-play Docker Compose configuration
└── Makefile              # Convenient CLI shortcuts
```
