#!/usr/bin/env bash
# ==============================================================================
# MEKO-LMS: Theme Packaging Tool for Moodle (Bash / Linux / macOS)
# Tự động nén theme_meko thành file ZIP chuẩn Frankenstyle Moodle release
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUTPUT_DIR="${1:-${SCRIPT_DIR}/releases}"

echo "======================================================================"
echo "  MEKO-LMS: BẮT ĐẦU ĐÓNG GÓI THEME MOODLE (theme_meko)"
echo "======================================================================"

VERSION_FILE="${SCRIPT_DIR}/version.php"
if [ ! -f "$VERSION_FILE" ]; then
    echo "Lỗi: Không tìm thấy file version.php tại: $VERSION_FILE"
    exit 1
fi

VERSION_NUM=$(grep -oE '\$plugin->version\s*=\s*[0-9]+' "$VERSION_FILE" | grep -oE '[0-9]+' || true)
if [ -z "$VERSION_NUM" ]; then
    VERSION_NUM="latest"
fi

echo "[1/3] Đã đọc thông tin phiên bản:"
echo "  -> Version : $VERSION_NUM"

mkdir -p "$OUTPUT_DIR"
ZIP_FILE="${OUTPUT_DIR}/theme_meko_${VERSION_NUM}.zip"
rm -f "$ZIP_FILE"

TEMP_DIR=$(mktemp -d 2>/dev/null || mktemp -d -t 'meko_pkg')
TARGET_DIR="${TEMP_DIR}/meko"
mkdir -p "$TARGET_DIR"

echo "[2/3] Đang sao chép các thành phần mã nguồn chuẩn Moodle..."

ITEMS=("classes" "cli" "js" "lang" "layout" "pix" "scss" "style" "templates" "config.php" "lib.php" "settings.php" "styles.css" "version.php" "README.md")
for item in "${ITEMS[@]}"; do
    if [ -e "${SCRIPT_DIR}/$item" ]; then
        cp -R "${SCRIPT_DIR}/$item" "${TARGET_DIR}/"
    fi
done

echo "[3/3] Đang nén file ZIP chuẩn cấu trúc Frankenstyle (meko/)..."
(cd "$TEMP_DIR" && zip -r -q "$ZIP_FILE" meko)

rm -rf "$TEMP_DIR"

FILE_SIZE=$(du -h "$ZIP_FILE" | cut -f1)

echo "======================================================================"
echo ">>> ĐÓNG GÓI THÀNH CÔNG 100%!"
echo "  File ZIP    : $ZIP_FILE"
echo "  Dung lượng  : $FILE_SIZE"
echo "  Cấu trúc    : meko/ (Sẵn sàng cài qua Moodle Admin UI hoặc SSH CLI)"
echo "======================================================================"

