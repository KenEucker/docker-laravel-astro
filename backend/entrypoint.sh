#!/bin/sh
set -eu

APP_DIR="/var/www/html/app"
TMP_DIR="/tmp/laravel_src"

echo ">> Laravel entrypoint starting..."
mkdir -p "$APP_DIR"

# --- wait for DB (mysql) ---------------------------------------------------
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

# --- ensure .env exists and has required keys ------------------------------
if [ ! -f .env ]; then
  echo ">> Creating .env from .env.example"
  cp .env.example .env
fi

# Write/update keys from runtime env (idempotent)
php -r '
  $path = ".env";
  $map = [];

  // Load existing .env into map (last wins)
  if (file_exists($path)) {
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
      if ($line === "" || $line[0] === "#") continue;
      if (!str_contains($line, "=")) continue;
      [$k,$v] = explode("=", $line, 2);
      $map[$k] = $v;
    }
  }

  $pairs = [
    "APP_ENV" => getenv("APP_ENV") ?: "local",
    "APP_DEBUG" => getenv("APP_DEBUG") ?: "true",
    "APP_URL" => getenv("APP_URL") ?: "http://localhost:8000",
    "FRONTEND_URL" => getenv("FRONTEND_URL") ?: "http://localhost:3000",

    "DB_CONNECTION" => getenv("DB_CONNECTION") ?: "mysql",
    "DB_HOST" => getenv("DB_HOST") ?: "db",
    "DB_PORT" => getenv("DB_PORT") ?: "3306",
    "DB_DATABASE" => getenv("DB_DATABASE") ?: "app",
    "DB_USERNAME" => getenv("DB_USERNAME") ?: "app",
    "DB_PASSWORD" => getenv("DB_PASSWORD") ?: "secret",

    "SANCTUM_STATEFUL_DOMAINS" => getenv("SANCTUM_STATEFUL_DOMAINS") ?: "localhost:3000,localhost",
    "SESSION_DOMAIN" => getenv("SESSION_DOMAIN") ?: "localhost",
    "SESSION_DRIVER" => getenv("SESSION_DRIVER") ?: "file",
    "SESSION_SECURE_COOKIE" => getenv("SESSION_SECURE_COOKIE") ?: "false",
  ];

  foreach ($pairs as $k => $v) {
    $map[$k] = str_replace(["\n","\r"], "", $v);
  }

  $out = "";
  foreach ($map as $k => $v) {
    $out .= $k . "=" . $v . "\n";
  }

  file_put_contents($path, $out);
'
php artisan optimize:clear || true

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

# --- orchid platform install (only once) -----------------------------------
if [ ! -d "vendor/orchid/platform" ]; then
  echo ">> Installing Orchid Platform..."
  composer require orchid/platform --no-interaction

  php artisan orchid:install --no-interaction || true

  composer install --no-interaction
fi

# --- apply overrides (initial sync + wire custom files) -------------------
apply_overrides () {
  echo ">> Applying overrides from /overrides/*..."

  for d in app bootstrap database routes config; do
    SRC="/overrides/$d"
    [ -d "$SRC" ] || continue

    # Special-case: seeders are additive (copy only if missing)
    if [ "$d" = "database" ] && [ -d "$SRC/seeders" ]; then
      echo ">> Additive: database/seeders"
      mkdir -p "$APP_DIR/database/seeders"
      find "$SRC/seeders" -type f | while read -r sf; do
        rels="${sf#$SRC/seeders/}"
        target_seeder="$APP_DIR/database/seeders/$rels"
        if [ -f "$target_seeder" ]; then
          echo ">> Skip seeder (exists): database/seeders/$rels"
        else
          echo ">> Copy seeder: database/seeders/$rels"
          mkdir -p "$(dirname "$target_seeder")"
          cp -f "$sf" "$target_seeder"
        fi
      done
    fi

    # Sync override files into the app (overwrite if exists, create if missing)
    find "$SRC" -type f ! -name "*_custom.php" | while read -r f; do
      rel="${f#$SRC/}"
      target="$APP_DIR/$d/$rel"

      # Don't double-handle seeders here (handled above)
      if [ "$d" = "database" ] && printf "%s" "$rel" | grep -q "^seeders/"; then
        continue
      fi

      echo ">> Sync: $d/$rel"
      mkdir -p "$(dirname "$target")"
      cp -f "$f" "$target"
    done

    # Wire up *_custom.php files (additive pattern)
    find "$SRC" -type f -name "*_custom.php" | while read -r f; do
      rel="${f#$SRC/}"
      target="$APP_DIR/$d/$rel"

      echo ">> Additive custom: $d/$rel"
      mkdir -p "$(dirname "$target")"
      cp -f "$f" "$target"

      # Determine base file (api_custom.php -> api.php)
      base_rel="$(printf "%s" "$rel" | sed 's/_custom\.php$/.php/')"
      base="$APP_DIR/$d/$base_rel"

      if [ -f "$base" ]; then
        # Valid PHP: require __DIR__.'/api_custom.php';
        require_line="require __DIR__.'/"$(basename "$rel")"';"

        if ! grep -Fq "$require_line" "$base"; then
          echo ">> Wiring $(basename "$rel") into $d/$base_rel"
          printf "\n// auto-included from overrides\n%s\n" "$require_line" >> "$base"
        fi
      else
        echo ">> Skip wiring (base missing): $d/$base_rel"
      fi
    done
  done
}

apply_overrides

# New classes added via overrides need autoload refreshed
echo ">> composer dump-autoload"
composer dump-autoload -o --no-interaction

# --- key, caches, migrate ---------------------------------------------------
php artisan key:generate --force || true
php artisan optimize:clear || true

echo ">> Creating storage symlink..."
php artisan storage:link || true

echo ">> Running migrations..."
php artisan migrate --force || true

# --- seeders (only once) ---------------------------------------------------
SEED_ONCE_FILE="$APP_DIR/storage/app/.seeders_ran"
mkdir -p "$APP_DIR/storage/app" || true

if [ ! -f "$SEED_ONCE_FILE" ]; then
  echo ">> Running seeders (first boot only)..."

  # IMPORTANT: remove the `|| true` while debugging so you can see real failures.
  php artisan db:seed --class="Database\\Seeders\\OrchidPermissionsSeeder" --force
  php artisan db:seed --class="Database\\Seeders\\DefaultAdminUserSeeder" --force
  php artisan db:seed --class="Database\\Seeders\\SettingsSeeder" --force

  touch "$SEED_ONCE_FILE"
  echo ">> Seeders complete. Marker created: $SEED_ONCE_FILE"
else
  echo ">> Seeders already ran (marker exists): $SEED_ONCE_FILE"
fi

# --- hot reload file watcher (background) ------------------------------------
start_file_watcher () {
  echo ">> Starting hot reload file watcher..."

  # Run in background, watch /overrides/* for changes
  (
    while true; do
      inotifywait -r -e modify,create,delete,move \
        /overrides/app \
        /overrides/database \
        /overrides/routes \
        /overrides/config \
        /overrides/bootstrap \
        2>/dev/null | while read -r path action file; do

        # Determine which override directory was modified
        override_dir=$(echo "$path" | sed 's|^/overrides/\([^/]*\).*|\1|')

        # Calculate relative path from override directory
        rel_path="${path#/overrides/$override_dir/}$file"
        target="$APP_DIR/$override_dir/$rel_path"
        source_file="$path$file"

        echo ">> [Hot reload] Change detected: $override_dir/$rel_path"

        # Handle different actions
        case "$action" in
          CREATE|MODIFY|MOVED_TO)
            if [ -f "$source_file" ]; then
              mkdir -p "$(dirname "$target")"
              cp -f "$source_file" "$target"
              echo ">> [Hot reload] Synced: $override_dir/$rel_path"

              # If it's a new PHP class, refresh autoloader
              if echo "$file" | grep -q '\.php$' && [ "$override_dir" = "app" ]; then
                (cd "$APP_DIR" && composer dump-autoload -o --no-interaction >/dev/null 2>&1) &
                echo ">> [Hot reload] Refreshing autoloader..."
              fi

              # If it's a config file, clear config cache
              if [ "$override_dir" = "config" ]; then
                (cd "$APP_DIR" && php artisan config:clear >/dev/null 2>&1) &
                echo ">> [Hot reload] Cleared config cache"
              fi

              # If it's a route file, clear route cache
              if [ "$override_dir" = "routes" ]; then
                (cd "$APP_DIR" && php artisan route:clear >/dev/null 2>&1) &
                echo ">> [Hot reload] Cleared route cache"
              fi
            fi
            ;;
          DELETE|MOVED_FROM)
            if [ -f "$target" ]; then
              rm -f "$target"
              echo ">> [Hot reload] Removed: $override_dir/$rel_path"
            fi
            ;;
        esac
      done
    done
  ) &

  WATCHER_PID=$!
  echo ">> Hot reload file watcher started (PID: $WATCHER_PID)"
}

start_file_watcher

echo ">> Starting Laravel on 0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
