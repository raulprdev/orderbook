#!/bin/bash
set -euxo pipefail

# 1. Install Docker from the official apt repo
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
ARCH=$(dpkg --print-architecture)
CODENAME=$(. /etc/os-release && echo "$VERSION_CODENAME")
echo "deb [arch=$ARCH signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $CODENAME stable" \
  > /etc/apt/sources.list.d/docker.list
apt-get update -y
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
usermod -aG docker ubuntu

# 2. Clone the application
git clone ${repo_url} /opt/orderbook
cd /opt/orderbook

# 3. Generate per-instance secrets
DB_PASSWORD=$(openssl rand -hex 16)
REVERB_APP_SECRET=$(openssl rand -hex 16)

# 4. Top-level .env (compose interpolates DB_PASSWORD into the postgres service)
cat > .env <<EOF
DOMAIN=${domain}
DB_PASSWORD=$DB_PASSWORD
EOF

# 5. Backend .env (Laravel). Static block first, then append dynamic secrets.
cat > backend/.env <<'BACKEND_ENV'
APP_NAME=Orderbook
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${domain}

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=orderbook
DB_USERNAME=orderbook

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=${domain}

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
CACHE_STORE=database

SANCTUM_STATEFUL_DOMAINS=${domain}

REVERB_APP_ID=orderbook
REVERB_APP_KEY=orderbookkey
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

COMMISSION_BASIS_POINTS=150
BACKEND_ENV

cat >> backend/.env <<EOF
DB_PASSWORD=$DB_PASSWORD
REVERB_APP_SECRET=$REVERB_APP_SECRET
EOF

# 6. Frontend .env (compiled into the SPA build)
cat > frontend/.env <<'FRONTEND_ENV'
VITE_API_URL=
VITE_APP_NAME=Orderbook
VITE_REVERB_APP_KEY=orderbookkey
VITE_REVERB_HOST=${domain}
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
FRONTEND_ENV

chown -R 1000:1000 /opt/orderbook

# 7. Bring the stack up in dependency order
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

$COMPOSE build
$COMPOSE up -d postgres
sleep 8

$COMPOSE run --rm app php artisan key:generate --force
$COMPOSE run --rm app php artisan migrate --force
$COMPOSE run --rm app php artisan db:seed --class=DemoSeeder --force

$COMPOSE --profile build run --rm node sh -c "npm ci && npm run build"

$COMPOSE up -d