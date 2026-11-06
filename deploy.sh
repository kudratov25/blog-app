#!/usr/bin/env bash
#
# Deploy blog-app to production (https://blog.kudratov.uz).
#
# Run from your Mac:   ./deploy.sh           deploy what is pushed to origin/main
#                      ./deploy.sh --check   only run the pre-flight checks, change nothing
#
# What it does on the server (root@playstat.uz):
#   1. git pull --ff-only in /var/www/blog-app
#   2. API:  composer install (no dev), migrate, rebuild Laravel caches, reload blog-fpm
#   3. Web:  build Nuxt in a separate build dir (the live site keeps running),
#            then switch the "current" symlink to the new release and restart PM2
#   4. Health check; keeps the last 3 web releases for quick rollback
#
# Server layout (see README / deploy notes):
#   /var/www/blog-app            git checkout (API runs from here)
#   /var/www/blog-web/build      Nuxt build workspace (persistent node_modules)
#   /var/www/blog-web/releases/  built .output dirs, one per deploy
#   /var/www/blog-web/current -> releases/<latest>   (PM2 "blog-web" runs this)
#
set -euo pipefail

SERVER="${DEPLOY_SERVER:-root@playstat.uz}"
BRANCH="main"
CHECK_ONLY=0
[[ "${1:-}" == "--check" ]] && CHECK_ONLY=1

red()   { printf '\033[31m%s\033[0m\n' "$*"; }
green() { printf '\033[32m%s\033[0m\n' "$*"; }
step()  { printf '\n\033[1m==> %s\033[0m\n' "$*"; }

cd "$(dirname "$0")"

# ---------------------------------------------------------------- local checks
step "Local checks"
git fetch -q origin "$BRANCH"
LOCAL=$(git rev-parse "$BRANCH")
REMOTE=$(git rev-parse "origin/$BRANCH")
if [[ "$LOCAL" != "$REMOTE" ]]; then
  if git merge-base --is-ancestor "$REMOTE" "$LOCAL"; then
    red "Local $BRANCH has commits that are not pushed. Run: git push origin $BRANCH"
    exit 1
  fi
fi
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  red "Warning: you have uncommitted changes. They will NOT be deployed:"
  git status --short --untracked-files=no
fi
echo "Deploying origin/$BRANCH @ $(git log -1 --format='%h %s' "origin/$BRANCH")"

# ---------------------------------------------------------------- remote part
ssh -o BatchMode=yes "$SERVER" "CHECK_ONLY=$CHECK_ONLY BRANCH=$BRANCH bash -s" <<'REMOTE'
set -euo pipefail
export LC_ALL=C.UTF-8 COMPOSER_ALLOW_SUPERUSER=1 NUXT_TELEMETRY_DISABLED=1

APP=/var/www/blog-app
WEB_ROOT=/var/www/blog-web
BUILD=$WEB_ROOT/build
RELEASES=$WEB_ROOT/releases
CURRENT=$WEB_ROOT/current
PHP=/opt/php-8.4.27/bin/php          # custom-built PHP (NOT Ubuntu's 8.4.11)
ARTISAN="sudo -u www-data $PHP artisan"

# Runtime env for the Nuxt server (PM2 "blog-web")
export HOST=127.0.0.1 PORT=3001 NODE_ENV=production
export NUXT_API_BASE=https://blog.kudratov.uz/api/v1
export NUXT_PUBLIC_API_BASE=https://blog.kudratov.uz/api/v1

step() { printf '\n\033[1m==> [server] %s\033[0m\n' "$*"; }
fail() { printf '\033[31m%s\033[0m\n' "$*"; exit 1; }

step "Server checks"
cd "$APP"
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || fail "Server checkout has local changes:
$(git status --short --untracked-files=no)"
git fetch -q origin "$BRANCH"
echo "server is at:  $(git log -1 --format='%h %s')"
echo "will deploy:   $(git log -1 --format='%h %s' "origin/$BRANCH")"
systemctl is-active --quiet blog-fpm || fail "blog-fpm is not running"
systemctl is-active --quiet redis-server || fail "redis-server is not running"
echo "free memory:   $(free -m | awk '/Mem:/{print $7}') MB available"

if [[ "${CHECK_ONLY}" == "1" ]]; then
  printf '\n\033[32mChecks passed (--check: nothing changed).\033[0m\n'
  exit 0
fi

step "Pull code"
git merge --ff-only -q "origin/$BRANCH"
git log -1 --format='%h %s'

step "API: dependencies, migrations, caches"
cd "$APP/api"
$PHP /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction --quiet
chmod -R u=rwX,go=rX vendor bootstrap public
chown -R www-data:www-data storage bootstrap/cache
$ARTISAN migrate --force
$ARTISAN optimize:clear >/dev/null
$ARTISAN config:cache >/dev/null
$ARTISAN route:cache >/dev/null
$ARTISAN event:cache >/dev/null
systemctl reload blog-fpm
echo "API updated"

step "Web: build Nuxt (live site keeps running)"
mkdir -p "$BUILD" "$RELEASES"
rsync -a --delete --exclude node_modules --exclude .output --exclude .nuxt "$APP/web/" "$BUILD/"
cd "$BUILD"
LOCK_HASH=$(sha256sum package-lock.json | cut -d' ' -f1)
if [[ ! -d node_modules || ! -d node_modules/@tailwindcss/typography || "$(cat .lock-hash 2>/dev/null)" != "$LOCK_HASH" ]]; then
  echo "package-lock.json changed -> npm ci"
  nice -n 19 npm ci --include=dev --no-audit --no-fund --loglevel=error
  echo "$LOCK_HASH" > .lock-hash
else
  echo "dependencies unchanged, skipping npm ci"
fi
NODE_OPTIONS=--max-old-space-size=900 nice -n 19 npx nuxt build 2>&1 | grep -E "Build complete|ERROR|error" || true
[[ -f .output/server/index.mjs ]] || fail "Nuxt build failed (no .output/server/index.mjs). Live site was NOT changed."

step "Web: switch release"
RELEASE="$RELEASES/$(date +%Y%m%d-%H%M%S)-$(git -C "$APP" rev-parse --short HEAD)"
mv .output "$RELEASE"
ln -sfn "$RELEASE" "$CURRENT"
SCRIPT="$CURRENT/server/index.mjs"
ECOSYSTEM="$APP/web/ecosystem.config.cjs"   # name, script path and runtime env live here
if [[ "$(pm2 jlist | node -e 'const l=JSON.parse(require("fs").readFileSync(0));const p=l.find(x=>x.name==="blog-web");process.stdout.write(p?p.pm2_env.pm_exec_path:"")')" == "$SCRIPT" ]]; then
  pm2 startOrRestart "$ECOSYSTEM" --update-env >/dev/null
else
  # first run with the release layout: re-register blog-web on the symlink path
  pm2 delete blog-web >/dev/null 2>&1 || true
  pm2 start "$ECOSYSTEM" >/dev/null
fi
pm2 save >/dev/null
echo "now serving: $(basename "$RELEASE")"

# keep the 3 newest releases
ls -1dt "$RELEASES"/*/ | tail -n +4 | xargs -r rm -rf

step "Health check"
sleep 4
WEB=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 https://blog.kudratov.uz/)
API=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 https://blog.kudratov.uz/api/v1/posts)
echo "web: $WEB   api: $API"
if [[ "$WEB" != "200" || "$API" != "200" ]]; then
  fail "Health check FAILED. Roll back the web with:
  ln -sfn \$(ls -1dt $RELEASES/*/ | sed -n 2p) $CURRENT && pm2 restart blog-web"
fi
printf '\n\033[32mDeployed successfully.\033[0m\n'
REMOTE
