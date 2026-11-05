.PHONY: up build down restart logs flush-views migrate fresh shell-api shell-web

# Start everything (builds missing images, runs migrations + seeds automatically)
up:
	docker compose up -d

# Rebuild images (run after changing a Dockerfile or package.json)
build:
	docker compose build

# Stop all containers
down:
	docker compose down

# Restart all containers
restart:
	docker compose restart

# Follow container logs
logs:
	docker compose logs -f

# Manually flush cached read counts to PostgreSQL
flush-views:
	docker compose exec php php artisan views:flush

# Run migrations inside the PHP container
migrate:
	docker compose exec php php artisan migrate --force

# Reset database and re-seed demo data
fresh:
	docker compose exec php php artisan migrate:fresh --seed --force

# Open shell inside Laravel API container
shell-api:
	docker compose exec php sh

# Open shell inside Nuxt Web container
shell-web:
	docker compose exec nuxt sh
