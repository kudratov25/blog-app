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
chmod -R 775 storage bootstrap/cache || true

# 4. Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# 5. Seed initial data if users table is empty
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tr -d '[:space:]' || echo "0")
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "Seeding initial database..."
    php artisan db:seed --force
fi

# 6. Link public storage if not linked
php artisan storage:link --force 2>/dev/null || true

echo "API container ready."
exec "$@"
