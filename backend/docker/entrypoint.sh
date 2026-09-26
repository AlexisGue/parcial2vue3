#!/bin/sh
set -e

# Fail fast if Postgres is still waking (Render free sleeps the DB too).
export PGCONNECT_TIMEOUT="${PGCONNECT_TIMEOUT:-8}"

# Free-tier cold boots start HTTP before migrations finish.
# Database session/cache drivers 500 every /api request if tables are missing.
# File drivers keep CSRF + throttling working while migrate catches up.
export SESSION_DRIVER="${SESSION_DRIVER_OVERRIDE:-file}"
export CACHE_STORE="${CACHE_STORE_OVERRIDE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION_OVERRIDE:-sync}"

php artisan config:clear

# CRITICAL: open the HTTP port ASAP.
# Otherwise Render stays on "Application loading" forever while migrate waits on a sleeping DB.
echo "Starting Laravel on port ${PORT:-8000} (session=${SESSION_DRIVER}, cache=${CACHE_STORE})..."
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}" &
SERVER_PID=$!

run_migrations() {
  i=0
  echo "Waiting for database / running migrations..."
  while true; do
    if [ -n "$DB_SEARCH_PATH" ] && [ "$DB_SEARCH_PATH" != "public" ]; then
      php -r '
        $schema = getenv("DB_SEARCH_PATH");
        if (!preg_match("/^[a-z_][a-z0-9_]*$/", $schema)) { fwrite(STDERR, "Invalid schema\n"); exit(1); }
        $host = getenv("DB_HOST") ?: "";
        $port = getenv("DB_PORT") ?: "5432";
        $db = getenv("DB_DATABASE") ?: "";
        $user = getenv("DB_USERNAME") ?: "";
        $pass = getenv("DB_PASSWORD") !== false ? getenv("DB_PASSWORD") : "";
        $ssl = getenv("DB_SSLMODE") ?: "require";
        if ($host === "") {
          $url = getenv("DATABASE_URL") ?: getenv("DB_URL") ?: "";
          $parts = parse_url($url);
          if (!is_array($parts) || empty($parts["host"])) { fwrite(STDERR, "No database host\n"); exit(1); }
          $host = $parts["host"];
          if (!empty($parts["port"])) { $port = (string) $parts["port"]; }
          $db = isset($parts["path"]) ? ltrim($parts["path"], "/") : "";
          $user = isset($parts["user"]) ? rawurldecode($parts["user"]) : "";
          $pass = isset($parts["pass"]) ? rawurldecode($parts["pass"]) : "";
          if (!empty($parts["query"])) {
            parse_str($parts["query"], $q);
            if (!empty($q["sslmode"])) { $ssl = $q["sslmode"]; }
          }
        }
        $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s;sslmode=%s", $host, $port, $db, $ssl);
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE SCHEMA IF NOT EXISTS ".$schema);
      ' || echo "Schema not ready yet." >&2
    fi
    if php artisan migrate --force --no-interaction; then
      echo "Migrations OK."
      if [ "${RUN_SEED:-false}" = "true" ]; then
        echo "Seeding database (RUN_SEED=true)..."
        php artisan db:seed --force --no-interaction || echo "WARNING: seed failed" >&2
      fi
      php artisan config:clear || true
      php artisan config:cache || true
      php artisan route:cache || true
      return 0
    fi
    i=$((i + 1))
    echo "Migrate not ready yet (attempt ${i}). Retrying in 5s..."
    sleep 5
  done
}

# Keep retrying forever in background so a late DB wake still creates tables.
run_migrations &

echo "Server ready (pid ${SERVER_PID})."
wait "${SERVER_PID}"
