#!/usr/bin/env bash
set -euo pipefail

APP_NAME=${APP_NAME:-nautilus-chatbot}
SERVER_NAME=${SERVER_NAME:-nautilus.local}
APACHE_CONF_NAME=${APACHE_CONF_NAME:-nautilus-chatbot}
ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
DOC_ROOT="$ROOT_DIR/public"

PHP_BIN=${PHP_BIN:-php}
COMPOSER_BIN=${COMPOSER_BIN:-composer}
NPM_BIN=${NPM_BIN:-npm}

SUDO=""
if command -v sudo >/dev/null 2>&1; then SUDO="sudo"; fi
if [ "$(id -u)" -eq 0 ]; then SUDO=""; fi

echo "Installing $APP_NAME at $ROOT_DIR"

command -v "$PHP_BIN" >/dev/null 2>&1 || { echo "php not found"; exit 1; }
command -v "$COMPOSER_BIN" >/dev/null 2>&1 || { echo "composer not found"; exit 1; }
command -v "$NPM_BIN" >/dev/null 2>&1 || { echo "npm not found"; exit 1; }

"$COMPOSER_BIN" install --no-dev --prefer-dist

if [ -f package-lock.json ]; then "$NPM_BIN" ci; else "$NPM_BIN" install; fi
"$NPM_BIN" run build

if [ ! -f .env ]; then
  cat > .env <<EOF
APP_NAME="$APP_NAME"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://$SERVER_NAME
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info
DB_CONNECTION=sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file
MEMCACHED_HOST=127.0.0.1
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="
${APP_NAME}"
VITE_APP_NAME="${APP_NAME}"
EOF
fi

"$PHP_BIN" artisan key:generate

mkdir -p database
if ! grep -q '^DB_CONNECTION=mysql' .env; then touch database/database.sqlite; fi

"$PHP_BIN" artisan migrate --force || true

$SUDO chown -R www-data:www-data storage bootstrap/cache || true
$SUDO chmod -R 775 storage bootstrap/cache || true

FPM_SOCK=$(ls /run/php/php*-fpm.sock 2>/dev/null | head -n1 || true)
CONF_CONTENT="<VirtualHost *:80>
  ServerName $SERVER_NAME
  DocumentRoot $DOC_ROOT
  <Directory $DOC_ROOT>
    AllowOverride All
    Require all granted
  </Directory>
  ErrorLog \\${APACHE_LOG_DIR}/$APACHE_CONF_NAME_error.log
  CustomLog \\${APACHE_LOG_DIR}/$APACHE_CONF_NAME_access.log combined
"
if [ -n "$FPM_SOCK" ]; then
  CONF_CONTENT+=$'  <FilesMatch \\ \\ .php$>\n    SetHandler "proxy:unix:'"$FPM_SOCK"'|fcgi://localhost/"\n  </FilesMatch>\n'
fi
CONF_CONTENT+=$'</VirtualHost>\n'

if [ -d /etc/apache2/sites-available ]; then
  TMP_CONF="$ROOT_DIR/build"
  mkdir -p "$TMP_CONF"
  echo -e "$CONF_CONTENT" > "$TMP_CONF/$APACHE_CONF_NAME.conf"
  if [ -w /etc/apache2/sites-available ]; then
    echo -e "$CONF_CONTENT" | $SUDO tee "/etc/apache2/sites-available/$APACHE_CONF_NAME.conf" >/dev/null
    $SUDO a2enmod rewrite || true
    if [ -n "$FPM_SOCK" ]; then $SUDO a2enmod proxy_fcgi setenvif || true; fi
    $SUDO a2ensite "$APACHE_CONF_NAME" || true
    $SUDO systemctl reload apache2 || true
    echo "Site enabled: $APACHE_CONF_NAME"
  else
    echo "Created $TMP_CONF/$APACHE_CONF_NAME.conf"
    echo "Copy to /etc/apache2/sites-available and run: a2ensite $APACHE_CONF_NAME && systemctl reload apache2"
  fi
else
  echo "Apache not detected; generated vhost config at $ROOT_DIR/build/$APACHE_CONF_NAME.conf"
fi

echo "Add to /etc/hosts if needed: 127.0.0.1 $SERVER_NAME"
echo "Done. Visit: http://$SERVER_NAME/chat"

