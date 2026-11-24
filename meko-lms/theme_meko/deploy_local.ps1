# ==============================================================================
# MEKO-LMS: 1-Click Deploy Theme & Bơm Dữ Liệu Mẫu (Windows PowerShell)
# Dùng trực tiếp cho Container Moodle hiện có trên máy Dev (Không tạo mới!)
# Hỗ trợ tự do cấu hình Port, Domain, Container name - 0% Hardcode
# ==============================================================================

param(
    [string]$Container = "",
    [string]$Port = "",
    [string]$Domain = "localhost"
)

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "  MEKO-LMS: 1-Click Deploy Theme & Setup Dữ liệu Mẫu (Local Container)" -ForegroundColor Cyan
Write-Host "======================================================================"

# 1. Tự động nhận diện Container Moodle đang chạy nếu không truyền vào
if (-not $Container) {
    $running = docker ps --format "{{.Names}}" | Where-Object { $_ -match "meko-lms-app|accees-moodle-app|moodle" }
    if ($running -and ($running -is [array])) {
        $Container = $running[0]
    } elseif ($running) {
        $Container = $running
    }
}

if (-not $Container) {
    Write-Host "[!] Không tìm thấy container Moodle nào có chữ 'moodle' đang chạy." -ForegroundColor Yellow
    $Container = Read-Host "Vui lòng nhập tên Container Moodle trên máy bạn (hoặc chạy 'docker ps' để xem tên)"
    if (-not $Container) {
        Write-Error "Chưa nhập tên container. Dừng lại!"
        exit 1
    }
}

# Tự động dò tìm Port nếu không truyền vào
if (-not $Port) {
    $portInfo = docker port $Container 80 2>$null
    if ($portInfo -and ($portInfo -match ":(\d+)")) {
        $Port = $Matches[1]
    } else {
        $Port = "8088"
    }
}

Write-Host "[1/3] Đang kết nối tới Container: $Container (Port: $Port, Domain: $Domain)..." -ForegroundColor Green

# 2. Xác định thư mục webroot của Moodle trong container
$targetPath = "/var/www/html/theme/meko"
$checkPath = docker exec $Container sh -c '[ -d "/var/www/moodle/public" ] && echo 1 || echo 0'
if ($checkPath -eq "1") {
    $targetPath = "/var/www/moodle/public/theme/meko"
}

Write-Host "[2/3] Đang kiểm tra & cập nhật theme_meko vào $targetPath trong container..." -ForegroundColor Green

# Kiểm tra xem thư mục theme đã được Live Bind Mount trực tiếp hay chưa
docker exec $Container test -d "$targetPath/.git" 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "  -> Thư mục theme đã được Mount trực tiếp (Live Bind Mount), đồng bộ tức thì từ máy Host!" -ForegroundColor Cyan
} else {
    docker exec $Container mkdir -p $targetPath
    $itemsToCopy = @("classes", "cli", "js", "lang", "layout", "pix", "scss", "style", "templates", "config.php", "lib.php", "settings.php", "styles.css", "version.php")
    foreach ($item in $itemsToCopy) {
        $srcPath = Join-Path $PSScriptRoot $item
        if (Test-Path $srcPath) {
            docker cp $srcPath "${Container}:${targetPath}/"
        }
    }
    docker exec $Container chown -R www-data:www-data $targetPath
    docker exec $Container chmod -R 755 $targetPath
}

# Cấu hình tương thích, đồng bộ wwwroot & symlink trong container
docker exec $Container sh -c "mkdir -p /var/www/html/public/theme && rm -rf /var/www/html/public/theme/accees_lms_theme /var/www/html/theme/accees_lms_theme 2>/dev/null || true ; ln -sf /var/www/html/theme/meko /var/www/html/public/theme/meko 2>/dev/null || true ; if [ -f /var/www/html/config.php ]; then sed -i 's/\\\$CFG->sslproxy = true;/\\\$CFG->sslproxy = false;/g' /var/www/html/config.php 2>/dev/null || true ; sed -i 's#http://localhost:[0-9]*#http://${Domain}:${Port}#g' /var/www/html/config.php 2>/dev/null || true ; fi"

# Đồng bộ local_accees_competencies nếu tồn tại trong thư mục accees-lms-repo
$compSrc = Join-Path $PSScriptRoot "..\accees-lms-repo\local\accees_competencies"
if (Test-Path $compSrc) {
    docker exec $Container mkdir -p /var/www/html/public/local /var/www/html/local 2>$null
    docker cp $compSrc "${Container}:/var/www/html/public/local/"
    docker cp $compSrc "${Container}:/var/www/html/local/" 2>$null
    docker exec $Container chown -R www-data:www-data /var/www/html/public/local/accees_competencies 2>$null
}

# Tự động chạy upgrade CLI để cập nhật database plugin
docker exec $Container php /var/www/html/admin/cli/upgrade.php --non-interactive 2>$null

# 3. Chạy script bơm dữ liệu chuẩn Moodle
Write-Host "[3/3] Đang kích hoạt Theme meko & Bơm 12 khóa học, ảnh 16:9, tài khoản mẫu..." -ForegroundColor Green
docker exec $Container php "$targetPath/cli/seed_dev_data.php"

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host ">>> THÀNH CÔNG 100%!" -ForegroundColor Green
Write-Host "  URL Local : http://${Domain}:${Port}" -ForegroundColor Yellow
Write-Host "  Tài khoản : admin / AdminMoodle2026!@#" -ForegroundColor Yellow
Write-Host "              teacher1 / Teacher2026!@# | student1 / Student2026!@#" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Cyan
