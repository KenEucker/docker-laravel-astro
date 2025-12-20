#!/bin/sh
set -eu

APP_DIR="/var/www/html/app"
TMP_DIR="/tmp/laravel_src"

# -----------------------------------------------------------------------------
# Logging (colors + GitHub Actions groups)
# -----------------------------------------------------------------------------

# Disable colors if not a TTY or if NO_COLOR is set
USE_COLOR=1
if [ ! -t 1 ] || [ "${NO_COLOR:-}" != "" ]; then
  USE_COLOR=0
fi

RESET=""
BOLD=""
BG_GRAY=""
BG_BLUE=""
BG_GREEN=""
BG_YELLOW=""
BG_RED=""
BG_CYAN=""
FG_WHITE=""
FG_BLACK=""

# if [ "$USE_COLOR" -eq 1 ]; then
  RESET="\033[0m"
  BOLD="\033[1m"

  BG_GRAY="\033[100m"
  BG_BLUE="\033[44m"
  BG_GREEN="\033[42m"
  BG_YELLOW="\033[43m"
  BG_RED="\033[41m"
  BG_CYAN="\033[46m"

  FG_WHITE="\033[97m"
  FG_BLACK="\033[30m"
# fi

ts() { date "+%H:%M:%S"; }

badge () {
  # badge "LABEL" "BG+FG"
  printf "%b%s%b" "$2" "$1" "$RESET"
}

log_line () {
  # log_line "BADGE" "message"
  printf "%s  %s %s\n" "$(ts)" "$1" "$2"
}

step () {
  printf "\n%s  %b==>%b %s\n" "$(ts)" "$BOLD" "$RESET" "$1"
}

# GitHub Actions grouping
ga_group_open () {
  if [ "${GITHUB_ACTIONS:-}" = "true" ]; then
    printf "::group::%s\n" "$1"
  else
    step "$1"
  fi
}

ga_group_close () {
  if [ "${GITHUB_ACTIONS:-}" = "true" ]; then
    printf "::endgroup::\n"
  fi
}

# Badged log helpers
log_ok()    { log_line "$(badge " OK   " "$BG_GREEN$FG_BLACK")" "$1"; }
log_warn()  { log_line "$(badge " WARN " "$BG_YELLOW$FG_BLACK")" "$1"; }
log_err()   { log_line "$(badge " ERROR" "$BG_RED$FG_WHITE")" "$1"; }
log_wait()  { log_line "$(badge " WAIT " "$BG_CYAN$FG_BLACK")" "$1"; }
log_run()   { log_line "$(badge " RUN  " "$BG_CYAN$FG_BLACK")" "$1"; }

log_sync()  { log_line "$(badge " SYNC " "$BG_CYAN$FG_BLACK")" "$1"; }
log_copy()  { log_line "$(badge " COPY " "$BG_BLUE$FG_WHITE")" "$1"; }
log_over()  { log_line "$(badge " OVER " "$BG_BLUE$FG_WHITE")" "$1"; } # overwrite
log_add()   { log_line "$(badge " ADD  " "$BG_GREEN$FG_BLACK")" "$1"; }
log_wire()  { log_line "$(badge " WIRE " "$BG_CYAN$FG_BLACK")" "$1"; }
log_skip()  { log_line "$(badge " SKIP " "$BG_GRAY$FG_WHITE")" "$1"; }
log_hot()   { log_line "$(badge " HOT  " "$BG_CYAN$FG_BLACK")" "$1"; }

# -----------------------------------------------------------------------------
# Summary counters
# -----------------------------------------------------------------------------
RESET_COUNTS () {
  COUNT_SYNC=0
  COUNT_COPY=0
  COUNT_OVER=0
  COUNT_ADD=0
  COUNT_WIRE=0
  COUNT_SKIP=0
  COUNT_HOT=0
}
RESET_COUNTS

summarize () {
  step "Overrides summary"
  log_line "$(badge " SYNC " "$BG_CYAN$FG_BLACK")" "overlay directories processed: $COUNT_SYNC"
  log_line "$(badge " COPY " "$BG_BLUE$FG_WHITE")" "files copied (new):            $COUNT_COPY"
  log_line "$(badge " OVER " "$BG_BLUE$FG_WHITE")" "files overwritten:             $COUNT_OVER"
  log_line "$(badge " ADD  " "$BG_GREEN$FG_BLACK")" "custom/additive files copied:  $COUNT_ADD"
  log_line "$(badge " WIRE " "$BG_CYAN$FG_BLACK")" "base files appended (wired):   $COUNT_WIRE"
  log_line "$(badge " SKIP " "$BG_GRAY$FG_WHITE")" "skips:                         $COUNT_SKIP"
}

# -----------------------------------------------------------------------------
# Start
# -----------------------------------------------------------------------------
step "Laravel entrypoint starting"
mkdir -p "$APP_DIR"

# --- wait for DB (mysql) ---------------------------------------------------
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
  ga_group_open "Waiting for MySQL (${DB_HOST:-db}:${DB_PORT:-3306})"
  for i in $(seq 1 60); do
    if php -r '
      $h=getenv("DB_HOST") ?: "db";
      $p=(int)(getenv("DB_PORT") ?: 3306);
      $db=getenv("DB_DATABASE") ?: "app";
      $u=getenv("DB_USERNAME") ?: "app";
      $pw=getenv("DB_PASSWORD") ?: "secret";
      try {
        new PDO("mysql:host=$h;port=$p;dbname=$db", $u, $pw, [PDO::ATTR_TIMEOUT=>2]);
        exit(0);
      } catch (Throwable $e) { exit(1); }
    '; then
      log_ok "MySQL is reachable"
      break
    fi

    if [ "$i" -eq 60 ]; then
      log_err "MySQL not reachable after 60 attempts"
      exit 1
    fi

    log_wait "MySQL not ready (attempt $i/60)..."
    sleep 2
  done
  ga_group_close
fi

# --- scaffold laravel if missing -------------------------------------------
ga_group_open "Ensuring Laravel app exists"
if [ ! -f "$APP_DIR/artisan" ]; then
  log_warn "No artisan found. Scaffolding Laravel into temp dir then copying into volume..."
  rm -rf "$TMP_DIR"
  mkdir -p "$TMP_DIR"
  cd "$TMP_DIR"
  composer create-project laravel/laravel . --no-interaction --no-scripts
  mkdir -p "$APP_DIR"
  cp -a ./. "$APP_DIR/"
  log_ok "Laravel scaffold copied into $APP_DIR"
else
  log_ok "Laravel already present (artisan found)"
fi
ga_group_close

cd "$APP_DIR"

# --- ensure .env exists and has required keys ------------------------------
ga_group_open "Ensuring .env exists and syncing required keys"
if [ ! -f .env ]; then
  log_warn "Creating .env from .env.example"
  cp .env.example .env
fi

php -r '
  $path = ".env";
  $map = [];

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
log_ok ".env synchronized"
ga_group_close

# Clear cached artifacts early (safe)
log_run "php artisan optimize:clear"
php artisan optimize:clear >/dev/null 2>&1 || true
log_ok "Caches cleared"

# --- install deps -----------------------------------------------------------
ga_group_open "Composer install"
log_run "composer install"
composer install --no-interaction
log_ok "Composer dependencies installed"
ga_group_close

# --- breeze api install (only once) ----------------------------------------
ga_group_open "Ensuring Breeze (API) is installed"
if [ ! -d "vendor/laravel/breeze" ]; then
  log_warn "Installing Breeze..."
  composer require laravel/breeze --dev --no-interaction
  php artisan breeze:install api --no-interaction || true
  composer install --no-interaction
  log_ok "Breeze installed"
else
  log_ok "Breeze already present"
fi
ga_group_close

# --- orchid platform install (only once) -----------------------------------
ga_group_open "Ensuring Orchid Platform is installed"
if [ ! -d "vendor/orchid/platform" ]; then
  log_warn "Installing Orchid Platform..."
  composer require orchid/platform --no-interaction
  php artisan orchid:install --no-interaction || true
  composer install --no-interaction
  log_ok "Orchid installed"
else
  log_ok "Orchid already present"
fi
ga_group_close

# --- apply overrides (initial sync + wire custom files) ---------------------
apply_overrides () {
  RESET_COUNTS
  ga_group_open "Applying overrides from /overrides/*"

  # Include resources explicitly (you requested this)
  for d in app bootstrap database routes config resources; do
    SRC="/overrides/$d"

    if [ ! -d "$SRC" ]; then
      COUNT_SKIP=$((COUNT_SKIP + 1))
      log_skip "Overlay directory missing: $d (expected $SRC)"
      continue
    fi

    COUNT_SYNC=$((COUNT_SYNC + 1))
    log_sync "Overlay directory: $d"

    # Special-case: seeders are additive (copy only if missing)
    if [ "$d" = "database" ] && [ -d "$SRC/seeders" ]; then
      log_add "database/seeders (copy only if missing)"
      mkdir -p "$APP_DIR/database/seeders"

      find "$SRC/seeders" -type f | while read -r sf; do
        rels="${sf#$SRC/seeders/}"
        target_seeder="$APP_DIR/database/seeders/$rels"

        if [ -f "$target_seeder" ]; then
          COUNT_SKIP=$((COUNT_SKIP + 1))
          log_skip "database/seeders/$rels (exists)"
        else
          COUNT_COPY=$((COUNT_COPY + 1))
          log_copy "database/seeders/$rels"
          mkdir -p "$(dirname "$target_seeder")"
          cp -f "$sf" "$target_seeder"
        fi
      done
    fi

    # Sync override files (overwrite if exists, create if missing)
    find "$SRC" -type f ! -name "*_custom.php" | while read -r f; do
      rel="${f#$SRC/}"
      target="$APP_DIR/$d/$rel"

      # Don't double-handle seeders here (handled above)
      if [ "$d" = "database" ] && printf "%s" "$rel" | grep -q "^seeders/"; then
        continue
      fi

      mkdir -p "$(dirname "$target")"

      if [ -f "$target" ]; then
        COUNT_OVER=$((COUNT_OVER + 1))
        log_over "$d/$rel"
      else
        COUNT_COPY=$((COUNT_COPY + 1))
        log_copy "$d/$rel"
      fi

      cp -f "$f" "$target"
    done

    # Wire up *_custom.php files (additive pattern)
    find "$SRC" -type f -name "*_custom.php" | while read -r f; do
      rel="${f#$SRC/}"
      target="$APP_DIR/$d/$rel"

      COUNT_ADD=$((COUNT_ADD + 1))
      log_add "$d/$rel (_custom)"
      mkdir -p "$(dirname "$target")"
      cp -f "$f" "$target"

      base_rel="$(printf "%s" "$rel" | sed 's/_custom\.php$/.php/')"
      base="$APP_DIR/$d/$base_rel"

      if [ -f "$base" ]; then
        require_line="require __DIR__.'/"$(basename "$rel")"';"

        if ! grep -Fq "$require_line" "$base"; then
          COUNT_WIRE=$((COUNT_WIRE + 1))
          log_wire "$(basename "$rel") -> $d/$base_rel"
          printf "\n// auto-included from overrides\n%s\n" "$require_line" >> "$base"
        else
          COUNT_SKIP=$((COUNT_SKIP + 1))
          log_skip "Already wired: $(basename "$rel") -> $d/$base_rel"
        fi
      else
        COUNT_SKIP=$((COUNT_SKIP + 1))
        log_skip "Base missing, cannot wire: $d/$base_rel"
      fi
    done
  done

  log_ok "Overrides applied"
  ga_group_close

  summarize
}

apply_overrides

# New classes added via overrides need autoload refreshed
ga_group_open "Refreshing Composer autoload"
log_run "composer dump-autoload -o"
composer dump-autoload -o --no-interaction
log_ok "Autoload refreshed"
ga_group_close

# --- key, caches, migrate ---------------------------------------------------
ga_group_open "Final Laravel prep"
php artisan key:generate --force >/dev/null 2>&1 || true
log_run "php artisan optimize:clear"
php artisan optimize:clear >/dev/null 2>&1 || true
log_ok "Caches cleared"

log_run "php artisan storage:link"
php artisan storage:link >/dev/null 2>&1 || true
log_ok "storage:link done"

log_run "php artisan migrate --force"
php artisan migrate --force || true
log_ok "Migrations complete (or already up to date)"
ga_group_close

# --- seeders (only once) ---------------------------------------------------
ga_group_open "Seeding (first boot only)"
SEED_ONCE_FILE="$APP_DIR/storage/app/.seeders_ran"
mkdir -p "$APP_DIR/storage/app" || true

if [ ! -f "$SEED_ONCE_FILE" ]; then
  log_run "db:seed OrchidPermissionsSeeder"
  php artisan db:seed --class="Database\\Seeders\\OrchidPermissionsSeeder" --force

  log_run "db:seed DefaultAdminUserSeeder"
  php artisan db:seed --class="Database\\Seeders\\DefaultAdminUserSeeder" --force

  log_run "db:seed SettingsSeeder"
  php artisan db:seed --class="Database\\Seeders\\SettingsSeeder" --force

  log_run "db:seed DefaultBlocksSeeder"
  php artisan db:seed --class="Database\\Seeders\\DefaultBlocksSeeder" --force

  touch "$SEED_ONCE_FILE"
  log_ok "Seeders complete. Marker created: $SEED_ONCE_FILE"
else
  log_ok "Seeders already ran (marker exists)"
fi
ga_group_close

# --- hot reload file watcher (background, polling-based) -------------------
start_file_watcher () {
  ga_group_open "Starting hot reload file watcher (polling mode)"

  POLL_INTERVAL="${LARAVEL_WATCH_INTERVAL:-2}"
  log_ok "Polling every ${POLL_INTERVAL}s"

  (
    while true; do
      sleep "$POLL_INTERVAL"

      for d in app bootstrap database routes config resources; do
        SRC="/overrides/$d"
        [ -d "$SRC" ] || continue

        find "$SRC" -type f 2>/dev/null | while read -r source_file; do
          rel="${source_file#$SRC/}"
          target="$APP_DIR/$d/$rel"

          if [ ! -f "$target" ] || [ "$source_file" -nt "$target" ]; then
            COUNT_HOT=$((COUNT_HOT + 1))
            log_hot "Change detected: $d/$rel"
            mkdir -p "$(dirname "$target")"
            cp -f "$source_file" "$target"
            log_hot "Synced: $d/$rel"

            # If it's a PHP file in app directory, refresh autoloader
            if echo "$rel" | grep -q '\.php$' && [ "$d" = "app" ]; then
              (cd "$APP_DIR" && composer dump-autoload -o --no-interaction >/dev/null 2>&1) &
              log_hot "Refreshing autoloader"
            fi

            # If it's a config file, clear config cache
            if [ "$d" = "config" ]; then
              (cd "$APP_DIR" && php artisan config:clear >/dev/null 2>&1) &
              log_hot "Cleared config cache"
            fi

            # If it's a route file, clear route cache
            if [ "$d" = "routes" ]; then
              (cd "$APP_DIR" && php artisan route:clear >/dev/null 2>&1) &
              log_hot "Cleared route cache"
            fi

            # If it's *_custom.php, ensure it's wired into base files
            if echo "$rel" | grep -q '_custom\.php$' && { [ "$d" = "routes" ] || [ "$d" = "config" ] || [ "$d" = "app" ]; }; then
              base_rel="$(printf "%s" "$rel" | sed 's/_custom\.php$/.php/')"
              base="$APP_DIR/$d/$base_rel"
              custom_basename="$(basename "$rel")"

              if [ -f "$base" ]; then
                require_line="require __DIR__.'/$custom_basename';"
                if ! grep -Fq "$require_line" "$base"; then
                  log_hot "Wiring $custom_basename -> $d/$base_rel"
                  printf "\n// auto-included from overrides\n%s\n" "$require_line" >> "$base"
                fi
              fi
            fi
          fi
        done
      done
    done
  ) &

  WATCHER_PID=$!
  log_ok "Hot reload watcher started (PID: $WATCHER_PID)"
  ga_group_close
}

start_file_watcher

# -----------------------------------------------------------------------------
# Start server
# -----------------------------------------------------------------------------
step "Starting Laravel on 0.0.0.0:8000"
log_run "php artisan serve --host=0.0.0.0 --port=8000"
exec php artisan serve --host=0.0.0.0 --port=8000
