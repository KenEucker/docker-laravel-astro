FROM node:20-alpine

WORKDIR /app
EXPOSE 3000

CMD sh -lc '\
  set -eux; \
  cd /app; \
  echo ">> node $(node -v) npm $(npm -v)"; \
  if [ ! -f package.json ]; then \
    echo ">> Creating Astro app..." && \
    npm create astro@latest . -- --template minimal --no-install && \
    npm install ; \
  else \
    echo ">> Installing deps..."; \
    npm ci --no-audit --no-fund; \
    npm install ; \
  fi && \
  echo ">> Starting Astro"; \
  npm run dev\
'
