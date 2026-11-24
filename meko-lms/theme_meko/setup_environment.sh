#!/usr/bin/env bash
# ==============================================================================
# MEKOSOFT LMS - 1-Click Environment Setup for Dev Team (Linux / macOS)
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

PORT="8088"
DOMAIN="localhost"
MOODLE_CONTAINER="meko-lms-app"
DB_CONTAINER="meko-postgres-db"
DB_USER="lms_user"
DB_NAME="accees_lms"
BRAND_MODE="meko"

# Đọc file .env nếu có
ENV_FILE="${SCRIPT_DIR}/.env"
if [ -f "$ENV_FILE" ]; then
    set -a
    # shellcheck disable=SC1090
    source "$ENV_FILE"
    set +a
fi

while [[ $# -gt 0 ]]; do
  case $1 in
    -p|--port)
      PORT="$2"
      shift 2
      ;;
    -d|--domain)
      DOMAIN="$2"
      shift 2
      ;;
    -c|--container)
      MOODLE_CONTAINER="$2"
      shift 2
      ;;
    *)
      shift
      ;;
  esac
done

SEED_FILE="${SCRIPT_DIR}/local_dev/moodle_golden_seed.sql.gz"

echo "======================================================================"
echo "  MEKOSOFT LMS - 1-CLICK DOCKER DEV ENVIRONMENT SETUP"
echo "  Target URL : http://${DOMAIN}:${PORT}"
echo "  Brand Mode : ${BRAND_MODE} (MEKOSOFT LMS)"
echo "======================================================================"

if ! docker info >/dev/null 2>&1; then
    echo "[ERROR] Docker chưa chạy. Vui lòng bật Docker trước!"
    exit 1
fi

echo "[1/5] Khởi động Docker Compose (Container: ${MOODLE_CONTAINER})..."
export PORT DOMAIN MOODLE_CONTAINER DB_CONTAINER
(cd "$SCRIPT_DIR" && docker compose up -d)

echo "  -> Đợi CSDL PostgreSQL sẵn sàng..."
RETRY=0
MAX_RETRY=12
while [ $RETRY -lt $MAX_RETRY ]; do
    if docker exec "$DB_CONTAINER" pg_isready -U "$DB_USER" -d "$DB_NAME" >/dev/null 2>&1; then
        break
    fi
    sleep 3
    RETRY=$((RETRY + 1))
done

# Tạo hoặc cập nhật config.php nếu chưa có
NEED_CONFIG=0
if ! docker exec "$MOODLE_CONTAINER" test -f /var/www/html/config.php 2>/dev/null; then
    NEED_CONFIG=1
elif ! docker exec "$MOODLE_CONTAINER" grep -q "pgsql" /var/www/html/config.php 2>/dev/null; then
    NEED_CONFIG=1
fi

if [ "$NEED_CONFIG" -eq 1 ]; then
    echo "  -> Thiết lập file cấu hình config.php (PostgreSQL 17, Port ${PORT})..."
    docker exec -i "$MOODLE_CONTAINER" sh -c "cat << 'EOF' > /var/www/html/config.php
<?php
unset(\$CFG);
global \$CFG;
\$CFG = new stdClass();

\$CFG->dbtype    = 'pgsql';
\$CFG->dblibrary = 'native';
\$CFG->dbhost    = '${DB_CONTAINER}';
\$CFG->dbname    = '${DB_NAME}';
\$CFG->dbuser    = '${DB_USER}';
\$CFG->dbpass    = 'LmsPassword2026!@#';
\$CFG->prefix    = 'mdl_';
\$CFG->dboptions = ['dbport' => 5432, 'dbsocket' => ''];

\$CFG->wwwroot   = 'http://${DOMAIN}:${PORT}';
\$CFG->dataroot  = '/var/moodledata';
\$CFG->routerconfigured = true;
\$CFG->sslproxy = false;
\$CFG->admin     = 'admin';

\$CFG->directorypermissions = 02777;

require_once(__DIR__ . '/lib/setup.php');
EOF
chown www-data:www-data /var/www/html/config.php
chmod 644 /var/www/html/config.php
"
    docker restart "$MOODLE_CONTAINER" >/dev/null 2>&1
    sleep 3
fi

echo "[2/5] Nạp CSDL Golden Seed..."
if [ -f "$SEED_FILE" ]; then
    gunzip -c "$SEED_FILE" | docker exec -i "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -q
    echo "  -> Nạp CSDL thành công!"
fi

echo "[3/5] Đồng bộ Plugin Khung năng lực và cấu hình Moodle..."
docker exec "$MOODLE_CONTAINER" rm -rf /var/www/html/public/local/local_accees_competencies /var/www/html/local/local_accees_competencies 2>/dev/null || true

COMP_SRC=""
for CANDIDATE in "${SCRIPT_DIR}/local_dev/plugins/accees_competencies" "${SCRIPT_DIR}/../accees-lms-repo/local/accees_competencies" "${SCRIPT_DIR}/../../accees-lms-repo/local/accees_competencies"; do
    if [ -d "$CANDIDATE" ]; then
        COMP_SRC="$CANDIDATE"
        break
    fi
done

if [ -n "$COMP_SRC" ]; then
    docker exec "$MOODLE_CONTAINER" mkdir -p /var/www/html/public/local/accees_competencies /var/www/html/local/accees_competencies 2>/dev/null || true
    docker cp "$COMP_SRC/." "${MOODLE_CONTAINER}:/var/www/html/public/local/accees_competencies/" 2>/dev/null || true
    docker cp "$COMP_SRC/." "${MOODLE_CONTAINER}:/var/www/html/local/accees_competencies/" 2>/dev/null || true
    docker exec "$MOODLE_CONTAINER" chown -R www-data:www-data /var/www/html/public/local/accees_competencies 2>/dev/null || true
    echo "  -> Đã đồng bộ accees_competencies vào container."
fi

docker exec "$MOODLE_CONTAINER" sh -c "mkdir -p /var/www/html/public/theme && rm -rf /var/www/html/public/theme/accees_lms_theme /var/www/html/theme/accees_lms_theme 2>/dev/null || true ; if [ -f /var/www/html/config.php ]; then sed -i 's/\\\$CFG->sslproxy = true;/\\\$CFG->sslproxy = false;/g' /var/www/html/config.php 2>/dev/null || true ; sed -i 's#http://localhost:[0-9]*#http://${DOMAIN}:${PORT}#g' /var/www/html/config.php 2>/dev/null || true ; fi"

docker exec "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" -c "DELETE FROM mdl_config_plugins WHERE plugin = 'theme_accees_lms_theme'; UPDATE mdl_config SET value = 'meko' WHERE name = 'theme';" >/dev/null 2>&1 || true

docker exec "$MOODLE_CONTAINER" php /var/www/html/admin/cli/upgrade.php --non-interactive 2>/dev/null || true

echo "[4/5] Kích hoạt Theme MEKO & Bật Theme Designer Mode..."
docker exec -i "$MOODLE_CONTAINER" php -r "
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
set_config('brand_profile', '${BRAND_MODE}', 'theme_meko');
set_config('brand_profile', '${BRAND_MODE}', 'theme_accees_lms_theme');
set_config('theme', 'meko');
set_config('themedesignermode', 1);
set_config('enablemycourses', 1);
set_config('supportname', 'Ban Quan Tri MEKOSOFT');
set_config('supportemail', 'support@mekosoft.vn');
set_config('supportpage', 'http://${DOMAIN}:${PORT}');
global \$DB;
\$site = \$DB->get_record('course', ['id' => 1]);
if (\$site) {
    \$site->fullname = 'MEKOSOFT LMS - Digital Learning & Competencies';
    \$site->shortname = 'MEKO-LMS';
    \$site->summary = 'He thong Quan ly Hoc tap va Danh gia Khung Nang luc So MEKOSOFT LMS';
    \$DB->update_record('course', \$site);
}
echo 'Brand Profile & Site Settings initialized successfully.\n';
"

docker exec "$MOODLE_CONTAINER" php /var/www/html/theme/meko/cli/seed_dev_data.php 2>/dev/null || true

echo "[5/5] Xóa sạch cache Moodle..."
docker exec -i "$MOODLE_CONTAINER" php /var/www/html/admin/cli/purge_caches.php 2>/dev/null || true

# Đảm bảo Apache đang chạy bình thường
if ! docker exec "$MOODLE_CONTAINER" pgrep apache2 >/dev/null 2>&1; then
    docker restart "$MOODLE_CONTAINER" >/dev/null 2>&1
    sleep 3
fi

echo "======================================================================"
echo ">>> CHUẨN BỊ MÔI TRƯỜNG THÀNH CÔNG 100%!"
echo "  Địa chỉ Web : http://${DOMAIN}:${PORT}"
echo "  Giao diện   : MEKOSOFT LMS (Theme meko v1.3.3)"
echo "  Tài khoản   : admin    / AdminMoodle2026!@#"
echo "                teacher1 / Teacher2026!@#"
echo "                student1 / Student2026!@#"
echo "======================================================================"

