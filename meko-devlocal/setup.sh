#!/usr/bin/env bash
# ==============================================================================
# MEKOSOFT DEVLOCAL - 1-Click Docker Environment Setup (Linux / macOS)
# Stack: Nginx Gateway + Liferay 7.4 GA132 (CMS & /tms) + Moodle 5.3 (/lms) + Postgres 17
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOMAIN="${DOMAIN:-meko.localhost}"
BRAND_PROFILE="${MOODLE_BRAND_PROFILE:-meko}"
PULL_LATEST=false
MOODLE_ONLY=false

for arg in "$@"; do
  case "$arg" in
    --pull-latest) PULL_LATEST=true ;;
    --moodle-only) MOODLE_ONLY=true ;;
  esac
done

echo "======================================================================"
echo "  MEKOSOFT DEVLOCAL STACK - 1-CLICK SETUP (LINUX / MACOS)"
echo "  Domain: http://${DOMAIN} (CMS: / | TMS: /tms | LMS: /lms)"
echo "======================================================================"

if ! docker info >/dev/null 2>&1; then
  echo "[ERROR] Docker is not running."
  exit 1
fi

if [ ! -f "${SCRIPT_DIR}/.env" ]; then
  cp "${SCRIPT_DIR}/.env.example" "${SCRIPT_DIR}/.env"
  echo "[1/6] Created .env from .env.example"
else
  echo "[1/6] Using existing .env"
fi

set -a
source "${SCRIPT_DIR}/.env"
set +a
DOMAIN="${DOMAIN:-meko.localhost}"
HTTP_PORT="${HTTP_PORT:-80}"
BRAND_PROFILE="${MOODLE_BRAND_PROFILE:-meko}"
PORT_SUFFIX=""
[ "$HTTP_PORT" != "80" ] && PORT_SUFFIX=":${HTTP_PORT}"
BASE_URL="http://${DOMAIN}${PORT_SUFFIX}"

echo "[2/6] Checking Moodle theme & competency plugins in monorepo..."
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
THEME_DIR="${REPO_ROOT}/meko-lms/theme_meko"
COMP_DIR="${REPO_ROOT}/meko-lms/local/meko_competencies"

if [ "$PULL_LATEST" = true ]; then
  git -C "$REPO_ROOT" pull --ff-only || true
fi

if [ ! -f "${THEME_DIR}/version.php" ] || [ ! -f "${COMP_DIR}/version.php" ]; then
  echo "[ERROR] Missing ../meko-lms/theme_meko or ../meko-lms/local/meko_competencies."
  exit 1
fi
echo "  -> Found theme_meko & local_meko_competencies at ${REPO_ROOT}/meko-lms"

echo "[3/6] Checking local DNS / hosts for ${DOMAIN}..."
case "$DOMAIN" in
  *.localhost|localhost)
    echo "  -> RFC 6761 '*.localhost' resolves to 127.0.0.1 automatically (no /etc/hosts edit needed)."
    ;;
  *)
    if ! grep -qE "^\s*127\.0\.0\.1\s+.*\b${DOMAIN}\b" /etc/hosts; then
      echo "  [WARN] Please add '127.0.0.1 ${DOMAIN}' to /etc/hosts (sudo sh -c 'echo \"127.0.0.1 ${DOMAIN}\" >> /etc/hosts')"
    fi
    ;;
esac

echo "[4/6] Preparing Moodle config.php & starting Docker Compose stack..."
cat > "${SCRIPT_DIR}/moodle/config.php" <<EOF
<?php
unset(\$CFG);
global \$CFG;
\$CFG = new stdClass();

\$CFG->dbtype    = 'pgsql';
\$CFG->dblibrary = 'native';
\$CFG->dbhost    = 'postgres';
\$CFG->dbname    = 'accees_lms';
\$CFG->dbuser    = 'lms_user';
\$CFG->dbpass    = 'LmsPassword2026!@#';
\$CFG->prefix    = 'mdl_';
\$CFG->dboptions = ['dbport' => 5432, 'dbsocket' => ''];

\$CFG->wwwroot   = '${BASE_URL}/lms';
\$CFG->dataroot  = '/var/moodledata';
\$CFG->routerconfigured = true;
\$CFG->sslproxy  = false;
\$CFG->admin     = 'admin';
\$CFG->directorypermissions = 02777;

if (
    isset(\$_SERVER['SCRIPT_NAME']) &&
    strpos(\$_SERVER['SCRIPT_NAME'], '/lms/') !== 0 &&
    \$_SERVER['SCRIPT_NAME'] !== '/lms' &&
    isset(\$_SERVER['REQUEST_URI']) &&
    strpos(\$_SERVER['REQUEST_URI'], '/lms') === 0
) {
    \$_SERVER['SCRIPT_NAME'] = '/lms' . \$_SERVER['SCRIPT_NAME'];
}

require_once(__DIR__ . '/lib/setup.php');
EOF

if [ "$MOODLE_ONLY" = true ]; then
  echo "  -> --moodle-only mode: starting Postgres + Moodle + Mailpit + Gateway (skipping Liferay)..."
  (cd "$SCRIPT_DIR" && docker compose up -d --no-deps postgres moodle mailpit gateway)
else
  (cd "$SCRIPT_DIR" && docker compose up -d)
fi

echo "  -> Waiting for PostgreSQL 17..."
for _ in $(seq 1 30); do
  if docker exec devlocal-postgres pg_isready -U postgres >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "[5/6] Installing/Upgrading Moodle 5.3 at /lms & seeding real data..."
sleep 3
HAS_CFG="$(docker exec devlocal-postgres psql -U postgres -d accees_lms -tAc "SELECT to_regclass('public.mdl_config');" 2>/dev/null || true)"
SEED_DUMP_GZ="${SCRIPT_DIR}/moodle/moodle_seed.sql.gz"
if [ "$HAS_CFG" != "mdl_config" ]; then
  if [ -f "$SEED_DUMP_GZ" ]; then
    echo "  -> Fresh database detected: fast-restoring moodle_seed.sql.gz..."
    gzip -dc "$SEED_DUMP_GZ" | docker exec -i devlocal-postgres psql -U lms_user -d accees_lms -q >/dev/null
  else
    docker exec devlocal-moodle php /var/www/html/admin/cli/install_database.php \
      --lang=en \
      --adminuser=admin \
      --adminpass="AdminMoodle2026!@#" \
      --adminemail="admin@mekosoft.vn" \
      --fullname="MEKOSOFT LMS - Digital Learning & Competencies" \
      --shortname="MEKO-LMS" \
      --agree-license
  fi
fi

docker exec devlocal-moodle php /var/www/html/admin/cli/upgrade.php --non-interactive

docker cp "${SCRIPT_DIR}/moodle/seed_real_data.php" "devlocal-moodle:/tmp/seed_real_data.php"
docker exec -e "MOODLE_BRAND_PROFILE=${BRAND_PROFILE}" -e "DOMAIN=${DOMAIN}" devlocal-moodle php /tmp/seed_real_data.php
docker exec devlocal-moodle rm -f /tmp/seed_real_data.php

echo "[6/6] Initializing Liferay 7.4 GA132 Site (/tms)..."
if [ "$MOODLE_ONLY" = true ]; then
  echo "  -> Skipped Liferay init (--moodle-only)."
elif command -v python3 >/dev/null 2>&1; then
  DOMAIN="${DOMAIN}" LIFERAY_INIT_URL="http://127.0.0.1${PORT_SUFFIX}" python3 -u "${SCRIPT_DIR}/liferay/init_liferay.py"
fi

echo "======================================================================"
echo "  SETUP COMPLETED!"
echo "  - Dev Info : ${BASE_URL}/dev-info"
echo "  - Liferay  : ${BASE_URL}/  (Admin: test@liferay.com / test)"
echo "  - TMS      : ${BASE_URL}/tms"
echo "  - Moodle   : ${BASE_URL}/lms/ (Admin: admin / AdminMoodle2026!@#)"
echo "  - Mailpit  : ${BASE_URL}/mail/ (SMTP: mailpit:1025)"
echo "======================================================================"
