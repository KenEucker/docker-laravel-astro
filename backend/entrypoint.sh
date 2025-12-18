#!/bin/sh
set -eu

APP_DIR="/var/www/html/app"
TMP_DIR="/tmp/laravel_src"

echo ">> Laravel entrypoint starting..."
mkdir -p "$APP_DIR"

# --- wait for DB (mysql) ---------------------------------------------------
# We avoid relying on docker-compose command logic and just wait here.
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
  echo ">> Waiting for MySQL at ${DB_HOST:-db}:${DB_PORT:-3306}..."
  for i in $(seq 1 60); do
    php -r '
      $h=getenv("DB_HOST") ?: "db";
      $p=(int)(getenv("DB_PORT") ?: 3306);
      $db=getenv("DB_DATABASE") ?: "app";
      $u=getenv("DB_USERNAME") ?: "app";
      $pw=getenv("DB_PASSWORD") ?: "secret";
      try {
        new PDO("mysql:host=$h;port=$p;dbname=$db", $u, $pw, [PDO::ATTR_TIMEOUT=>2]);
        exit(0);
      } catch (Throwable $e) { exit(1); }
    ' && break || true
    sleep 2
  done
fi

# --- scaffold laravel if missing -------------------------------------------
if [ ! -f "$APP_DIR/artisan" ]; then
  echo ">> No artisan found. Scaffolding Laravel into temp dir then copying into volume..."
  rm -rf "$TMP_DIR"
  mkdir -p "$TMP_DIR"
  cd "$TMP_DIR"
  composer create-project laravel/laravel . --no-interaction --no-scripts
  mkdir -p "$APP_DIR"
  cp -a ./. "$APP_DIR/"
else
  echo ">> Laravel already present (artisan found). Skipping scaffold."
fi

cd "$APP_DIR"

# --- apply overrides (safe if missing/empty) -------------------------------
# Mount your override folders at /overrides/app, /overrides/database, /overrides/routes
for d in app database routes; do
  if [ -d "/overrides/$d" ]; then
    echo ">> Applying overrides: $d"
    mkdir -p "$APP_DIR/$d"
    cp -a "/overrides/$d/." "$APP_DIR/$d/" 2>/dev/null || true
  fi
done

# --- ensure .env exists and has required keys ------------------------------
if [ ! -f .env ]; then
  echo ">> Creating .env from .env.example"
  cp .env.example .env
fi

# Write/update keys from runtime env (idempotent)
php -r '
  $path = ".env";
  $env = file_exists($path) ? file_get_contents($path) : "";

  $pairs = [
    "APP_ENV" => getenv("APP_ENV") ?: "local",
    "APP_DEBUG" => getenv("APP_DEBUG") ?: "true",
    "APP_URL" => getenv("APP_URL") ?: "http://localhost:8000",

    "DB_CONNECTION" => getenv("DB_CONNECTION") ?: "mysql",
    "DB_HOST" => getenv("DB_HOST") ?: "db",
    "DB_PORT" => getenv("DB_PORT") ?: "3306",
    "DB_DATABASE" => getenv("DB_DATABASE") ?: "app",
    "DB_USERNAME" => getenv("DB_USERNAME") ?: "app",
    "DB_PASSWORD" => getenv("DB_PASSWORD") ?: "secret",

    "SANCTUM_STATEFUL_DOMAINS" => getenv("SANCTUM_STATEFUL_DOMAINS") ?: "localhost:3000,localhost",
    "SESSION_DOMAIN" => getenv("SESSION_DOMAIN") ?: "localhost",
    "SESSION_DRIVER" => getenv("SESSION_DRIVER") ?: "cookie",
    "SESSION_SECURE_COOKIE" => getenv("SESSION_SECURE_COOKIE") ?: "false",
  ];

  foreach ($pairs as $k => $v) {
    $v = str_replace(["\n","\r"], "", $v);
    if (preg_match("/^".preg_quote($k,"/")."=.*/m", $env)) {
      $env = preg_replace("/^".preg_quote($k,"/")."=.*/m", $k."=".$v, $env);
    } else {
      $env .= (substr($env, -1)==="\n" || $env==="" ? "" : "\n") . $k."=".$v."\n";
    }
  }

  file_put_contents($path, $env);
'

# --- install deps -----------------------------------------------------------
echo ">> composer install"
composer install --no-interaction

# --- breeze api install (only once) ----------------------------------------
if [ ! -d "vendor/laravel/breeze" ]; then
  echo ">> Installing Breeze (API)..."
  composer require laravel/breeze --dev --no-interaction
  php artisan breeze:install api --no-interaction || true
  composer install --no-interaction
fi

# --- key, caches, migrate ---------------------------------------------------
php artisan key:generate --force || true
php artisan optimize:clear || true

echo ">> Running migrations..."
php artisan migrate --force || true

# --- optional default user --------------------------------------------------
if [ "${SEED_DEFAULT_USER:-false}" = "true" ]; then
  echo ">> Ensuring default user..."
  php artisan tinker --execute="
    \$email = getenv('DEFAULT_USER_EMAIL');
    \$name = getenv('DEFAULT_USER_NAME') ?: 'Admin';
    \$pass = getenv('DEFAULT_USER_PASSWORD') ?: '';
    if (!\$email || !\$pass) { echo 'Default user env missing\n'; exit(0); }
    \\App\\Models\\User::updateOrCreate(
      ['email' => \$email],
      ['name' => \$name, 'password' => \\Illuminate\\Support\\Facades\\Hash::make(\$pass)]
    );
    echo 'Default user ensured: '.\$email.\"\\n\";
  " || true
else
  echo ">> SEED_DEFAULT_USER=false; skipping default user."
fi

echo ">> Starting Laravel on 0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
