#!/usr/bin/env bash
# ==============================================================================
# MEKO-LMS: 1-Click Deploy Theme & Bơm Dữ Liệu Mẫu (Linux / macOS / Git Bash)
# Dùng trực tiếp cho Container Moodle hiện có trên máy Dev (Không tạo mới!)
# Hỗ trợ tự do cấu hình Port, Domain, Container name - 0% Hardcode
# ==============================================================================

set -euo pipefail

CONTAINER=""
PORT=""
DOMAIN="localhost"

while [[ $# -gt 0 ]]; do
  case $1 in
    -c|--container)
      CONTAINER="$2"
      shift 2
      ;;
    -p|--port)
      PORT="$2"
      shift 2
      ;;
    -d|--domain)
      DOMAIN="$2"
      shift 2
      ;;
    *)
      if [ -z "$CONTAINER" ]; then
        CONTAINER="$1"
      fi
      shift
      ;;
  esac
done

echo "======================================================================"
echo "  MEKO-LMS: 1-Click Deploy Theme & Setup Dữ liệu Mẫu (Local Container)"
echo "======================================================================"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 1. Tự động nhận diện container Moodle nếu không truyền vào
if [ -z "$CONTAINER" ]; then
    CONTAINER=$(docker ps --format "{{.Names}}" | grep -E "meko-lms-app|accees-moodle-app|moodle" | head -n 1 || true)
fi

if [ -z "$CONTAINER" ]; then
    echo "[!] Không tự động tìm thấy container Moodle đang chạy."
    read -rp "Vui lòng nhập tên Container Moodle trên máy của bạn: " CONTAINER
    if [ -z "$CONTAINER" ]; then
        echo "Chưa nhập tên container. Dừng lại!"
        exit 1
    fi
fi

# Tự động dò tìm Port nếu không truyền vào
if [ -z "$PORT" ]; then
    PORT=$(docker port "$CONTAINER" 80 2>/dev/null | grep -oE '[0-9]+$' | head -n 1 || true)
    if [ -z "$PORT" ]; then
        PORT="8088"
    fi
fi

echo "[1/3] Đang kết nối tới Container: $CONTAINER (Port: $PORT, Domain: $DOMAIN)..."

# 2. Xác định thư mục webroot của Moodle
TARGET_PATH="/var/www/html/theme/meko"
if docker exec "$CONTAINER" sh -c '[ -d "/var/www/moodle/public" ]'; then
    TARGET_PATH="/var/www/moodle/public/theme/meko"
fi

echo "[2/3] Đang kiểm tra & cập nhật theme_meko vào $TARGET_PATH trong container..."

IS_MOUNTED=$(docker exec "$CONTAINER" sh -c "[ -f '${TARGET_PATH}/version.php' ] && [ -d '${TARGET_PATH}/.git' ] && echo 1 || echo 0")
if [ "$IS_MOUNTED" = "1" ]; then
    echo "  -> Thư mục theme đã được Mount trực tiếp (Live Bind Mount), đồng bộ tức thì từ máy Host!"
else
    docker exec "$CONTAINER" mkdir -p "$TARGET_PATH"
    ITEMS=("classes" "cli" "js" "lang" "layout" "pix" "scss" "style" "templates" "config.php" "lib.php" "settings.php" "version.php")
    for item in "${ITEMS[@]}"; do
        if [ -e "${SCRIPT_DIR}/$item" ]; then
            docker cp "${SCRIPT_DIR}/$item" "${CONTAINER}:${TARGET_PATH}/"
        fi
    done
    docker exec "$CONTAINER" chown -R www-data:www-data "$TARGET_PATH"
    docker exec "$CONTAINER" chmod -R 755 "$TARGET_PATH"
fi

# Cấu hình tương thích, đồng bộ wwwroot & symlink trong container
docker exec "$CONTAINER" sh -c "mkdir -p /var/www/html/public/theme && rm -rf /var/www/html/public/theme/accees_lms_theme /var/www/html/theme/accees_lms_theme 2>/dev/null || true ; ln -sf /var/www/html/theme/meko /var/www/html/public/theme/meko 2>/dev/null || true ; if [ -f /var/www/html/config.php ]; then sed -i 's/\\\$CFG->sslproxy = true;/\\\$CFG->sslproxy = false;/g' /var/www/html/config.php 2>/dev/null || true ; sed -i 's#http://localhost:[0-9]*#http://${DOMAIN}:${PORT}#g' /var/www/html/config.php 2>/dev/null || true ; fi"

# Đồng bộ local_accees_competencies nếu tồn tại trong thư mục accees-lms-repo
COMP_SRC="${SCRIPT_DIR}/../accees-lms-repo/local/accees_competencies"
if [ -d "$COMP_SRC" ]; then
    docker exec "$CONTAINER" mkdir -p /var/www/html/public/local /var/www/html/local 2>/dev/null || true
    docker cp "$COMP_SRC" "${CONTAINER}:/var/www/html/public/local/" 2>/dev/null || true
    docker cp "$COMP_SRC" "${CONTAINER}:/var/www/html/local/" 2>/dev/null || true
    docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/public/local/accees_competencies 2>/dev/null || true
fi

# Tự động chạy upgrade CLI để cập nhật database plugin
docker exec "$CONTAINER" php /var/www/html/admin/cli/upgrade.php --non-interactive 2>/dev/null || true

# 3. Chạy script bơm dữ liệu chuẩn Moodle
echo "[3/3] Đang kích hoạt Theme meko & Bơm 12 khóa học, ảnh 16:9, tài khoản mẫu..."
docker exec "$CONTAINER" php "$TARGET_PATH/cli/seed_dev_data.php"

echo "======================================================================"
echo ">>> THÀNH CÔNG 100%!"
echo "  URL Local : http://${DOMAIN}:${PORT}"
echo "  Tài khoản : admin / AdminMoodle2026!@#"
echo "              teacher1 / Teacher2026!@# | student1 / Student2026!@#"
echo "======================================================================"
