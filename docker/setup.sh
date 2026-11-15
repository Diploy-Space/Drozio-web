#!/bin/sh
# One-time local setup: install PHP dependencies, ensure the schema is loaded,
# run migrations and create a local admin account.
set -eu

cd /var/www/html

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-6valley}"
DB_USERNAME="${DB_USERNAME:-6valley}"
DB_PASSWORD="${DB_PASSWORD:-6valley_secret}"

mysql_cmd() {
    MYSQL_PWD="$DB_PASSWORD" mysql \
        --protocol=TCP \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USERNAME" \
        --database="$DB_DATABASE" \
        "$@"
}

echo "==> Waiting for MariaDB at ${DB_HOST}:${DB_PORT}..."
i=0
while ! mysql_cmd -e "SELECT 1" >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 90 ]; then
        echo "Database did not become ready in time." >&2
        exit 1
    fi
    sleep 2
done

if ! mysql_cmd -e "SELECT 1 FROM business_settings LIMIT 1" >/dev/null 2>&1; then
    echo "==> Loading installation/backup/database.sql..."
    mysql_cmd < installation/backup/database.sql
else
    echo "==> Schema already present."
fi

if [ ! -f vendor/autoload.php ]; then
    echo "==> Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Creating local admin (admin@admin.com / 12345678)..."
php artisan tinker --execute="
\Illuminate\Support\Facades\DB::table('admins')->updateOrInsert(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Master Admin',
        'phone' => '01759412381',
        'admin_role_id' => 1,
        'image' => 'def.png',
        'password' => bcrypt('12345678'),
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]
);
"

php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true

echo "==> Setup complete."
