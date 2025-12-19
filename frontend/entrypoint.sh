#!/bin/sh
set -eu

FRONTEND_DIR="/app"
cd "$FRONTEND_DIR"

echo ">> Frontend entrypoint starting..."
echo ">> node $(node -v) npm $(npm -v)"

# --- scaffold astro if missing ---------------------------------------------
if [ ! -f "$FRONTEND_DIR/package.json" ]; then
  echo ">> No package.json found. Creating Astro app..."
  npm create astro@latest . -- --template minimal --no-install
  npm install
else
  echo ">> Astro app already present (package.json found)."
fi

# --- install dependencies --------------------------------------------------
echo ">> Installing dependencies..."
npm ci --no-audit --no-fund || npm install

# --- install blocks CMS dependencies ---------------------------------------
echo ">> Checking/installing Blocks CMS dependencies..."

# Check if dependencies are already installed, if not install them
DEPS_TO_INSTALL=""

# Check each required dependency
if ! npm list marked --depth=0 >/dev/null 2>&1; then
  DEPS_TO_INSTALL="$DEPS_TO_INSTALL marked"
fi

if ! npm list isomorphic-dompurify --depth=0 >/dev/null 2>&1; then
  DEPS_TO_INSTALL="$DEPS_TO_INSTALL isomorphic-dompurify"
fi

if ! npm list @astrojs/vue --depth=0 >/dev/null 2>&1; then
  DEPS_TO_INSTALL="$DEPS_TO_INSTALL @astrojs/vue"
fi

if ! npm list vue --depth=0 >/dev/null 2>&1; then
  DEPS_TO_INSTALL="$DEPS_TO_INSTALL vue"
fi

# Install missing dependencies
if [ -n "$DEPS_TO_INSTALL" ]; then
  echo ">> Installing missing dependencies:$DEPS_TO_INSTALL"
  npm install $DEPS_TO_INSTALL --save
else
  echo ">> All Blocks CMS dependencies already installed"
fi

# --- start dev server ------------------------------------------------------
echo ">> Starting Astro dev server on 0.0.0.0:3000"
exec npm run dev
