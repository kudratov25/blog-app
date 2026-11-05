#!/bin/sh
set -e

cd /var/www/api

# 1. Install Composer dependencies if vendor directory does not exist
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 2. Ensure .env exists
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    cp .env.example .env
fi

# 3. Ensure storage & cache directories are writable
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} + 2>/dev/null || true
find storage bootstrap/cache -type f -exec chmod 664 {} + 2>/dev/null || true

# 4. Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# 5. Seed initial data if users table is empty
# (plain PDO query instead of booting artisan tinker, which is slow)
USER_COUNT=$(php -r 'try { $p = new PDO("pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo $p->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch (Throwable $e) { echo 0; }' 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "Seeding initial database..."
    php artisan db:seed --force
fi

# 6. Link public storage if not linked
php artisan storage:link --force 2>/dev/null || true

echo "API container ready."
exec "$@"
