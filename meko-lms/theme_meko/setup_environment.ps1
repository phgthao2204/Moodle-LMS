# ==============================================================================
# MEKOSOFT LMS - 1-Click Environment Setup for Dev Team (Windows PowerShell)
# Thiet lap toan dien tu container trang + nap CSDL Golden + cau hinh Theme MEKO
# ==============================================================================

param(
    [string]$Port = "8088",
    [string]$Domain = "localhost",
    [string]$MoodleContainer = "meko-lms-app",
    [string]$DbContainer = "meko-postgres-db",
    [string]$DbUser = "lms_user",
    [string]$DbName = "accees_lms",
    [string]$BrandMode = "meko"
)

$ScriptDir = $PSScriptRoot

# Doc file .env neu co
$envFile = Join-Path $ScriptDir ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match "^\s*PORT\s*=\s*(\d+)") { $Port = $Matches[1] }
        if ($_ -match "^\s*DOMAIN\s*=\s*([^\s#]+)") { $Domain = $Matches[1] }
        if ($_ -match "^\s*MOODLE_CONTAINER\s*=\s*([^\s#]+)") { $MoodleContainer = $Matches[1] }
        if ($_ -match "^\s*DB_CONTAINER\s*=\s*([^\s#]+)") { $DbContainer = $Matches[1] }
    }
}

$SeedFile = Join-Path $ScriptDir "local_dev\moodle_golden_seed.sql.gz"

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "  MEKOSOFT LMS - 1-CLICK DOCKER DEV ENVIRONMENT SETUP" -ForegroundColor Cyan
Write-Host "  Target URL : http://${Domain}:${Port}" -ForegroundColor Yellow
Write-Host "  Brand Mode : $BrandMode (MEKOSOFT LMS)" -ForegroundColor Yellow
Write-Host "======================================================================"

# Kiem tra Docker Engine
docker info > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[!] Docker chua chay. Vui long bat Docker Desktop truoc!" -ForegroundColor Red
    exit 1
}

# 1. Khoi dong Docker Compose
Write-Host "[1/5] Dang khoi dong Docker Compose (Container: $MoodleContainer)..." -ForegroundColor Green
$env:PORT = $Port
$env:DOMAIN = $Domain
$env:MOODLE_CONTAINER = $MoodleContainer
$env:DB_CONTAINER = $DbContainer

Push-Location $ScriptDir
docker compose up -d
Pop-Location

# Doi DB san sang
Write-Host "  -> Doi CSDL PostgreSQL san sang..." -ForegroundColor Yellow
$retry = 0
$maxRetry = 12
while ($retry -lt $maxRetry) {
    docker exec $DbContainer pg_isready -U $DbUser -d $DbName > $null 2>&1
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep -Seconds 3
    $retry++
}

# Tao hoac cap nhat config.php chuan cho Moodle
$needConfig = $false
docker exec $MoodleContainer test -f /var/www/html/config.php > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    $needConfig = $true
} else {
    $chk = docker exec $MoodleContainer grep -q "pgsql" /var/www/html/config.php > $null 2>&1
    if ($LASTEXITCODE -ne 0) { $needConfig = $true }
}

if ($needConfig) {
    Write-Host "  -> Thiet lap file cau hinh config.php (PostgreSQL 17, Port $Port)..." -ForegroundColor Yellow
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    $configContent = @"
<?php
unset(`$CFG);
global `$CFG;
`$CFG = new stdClass();

`$CFG->dbtype    = 'pgsql';
`$CFG->dblibrary = 'native';
`$CFG->dbhost    = '$DbContainer';
`$CFG->dbname    = '$DbName';
`$CFG->dbuser    = '$DbUser';
`$CFG->dbpass    = 'LmsPassword2026!@#';
`$CFG->prefix    = 'mdl_';
`$CFG->dboptions = ['dbport' => 5432, 'dbsocket' => ''];

`$CFG->wwwroot   = 'http://${Domain}:${Port}';
`$CFG->dataroot  = '/var/moodledata';
`$CFG->routerconfigured = true;
`$CFG->sslproxy = false;
`$CFG->admin     = 'admin';

`$CFG->directorypermissions = 02777;

require_once(__DIR__ . '/lib/setup.php');
"@
    $tempConfig = Join-Path $env:TEMP "config.php"
    [System.IO.File]::WriteAllText($tempConfig, $configContent, $utf8NoBom)
    docker cp $tempConfig "${MoodleContainer}:/var/www/html/config.php"
    Remove-Item $tempConfig -Force -ErrorAction SilentlyContinue
    docker exec $MoodleContainer chown www-data:www-data /var/www/html/config.php
    docker exec $MoodleContainer chmod 644 /var/www/html/config.php
    docker restart $MoodleContainer > $null 2>&1
    Start-Sleep -Seconds 4
}

# 2. Giai nen va Nap CSDL Golden Seed
Write-Host "[2/5] Dang nap CSDL Golden Seed (12 khoa hoc, tai khoan test, chuan CBE)..." -ForegroundColor Green
if (Test-Path $SeedFile) {
    $tempSql = Join-Path $env:TEMP "moodle_golden_seed.sql"
    if (Get-Command python -ErrorAction SilentlyContinue) {
        python -c "import gzip, shutil; shutil.copyfileobj(gzip.open(r'$SeedFile'), open(r'$tempSql', 'wb'))"
    } else {
        $inStream = [System.IO.File]::OpenRead((Resolve-Path $SeedFile))
        $outStream = [System.IO.File]::Create($tempSql)
        $gzipStream = New-Object System.IO.Compression.GZipStream($inStream, [System.IO.Compression.CompressionMode]::Decompress)
        $gzipStream.CopyTo($outStream)
        $gzipStream.Close()
        $outStream.Close()
        $inStream.Close()
    }
    docker cp $tempSql "${DbContainer}:/tmp/seed.sql"
    docker exec $DbContainer psql -U $DbUser -d $DbName -f /tmp/seed.sql -q > $null 2>&1
    docker exec $DbContainer rm -f /tmp/seed.sql
    Remove-Item $tempSql -Force -ErrorAction SilentlyContinue
    Write-Host "  -> Nap CSDL hoan tat 100%!" -ForegroundColor Cyan
} else {
    Write-Host "  [!] Khong tim thay file $SeedFile, bo qua nap CSDL." -ForegroundColor Yellow
}

# 3. Dong bo Plugin Khung nang luc va Cau hinh Webroot
Write-Host "[3/5] Dang dong bo Plugin Khung nang luc so va cau hinh Webroot..." -ForegroundColor Green

# Xoa bo thu muc sai ten de loai bo triet de loi detectedmisplacedplugin
docker exec $MoodleContainer rm -rf /var/www/html/public/local/local_accees_competencies /var/www/html/local/local_accees_competencies > $null 2>&1

$candidatePaths = @(
    (Join-Path $ScriptDir "local_dev\plugins\accees_competencies"),
    (Join-Path $ScriptDir "..\accees-lms-repo\local\accees_competencies"),
    (Join-Path $ScriptDir "..\..\accees-lms-repo\local\accees_competencies")
)
$compSrc = ""
foreach ($p in $candidatePaths) {
    if (Test-Path $p) {
        $compSrc = $p
        break
    }
}
if ($compSrc) {
    docker exec $MoodleContainer mkdir -p /var/www/html/public/local/accees_competencies /var/www/html/local/accees_competencies > $null 2>&1
    docker cp "$compSrc/." "${MoodleContainer}:/var/www/html/public/local/accees_competencies/" > $null 2>&1
    docker cp "$compSrc/." "${MoodleContainer}:/var/www/html/local/accees_competencies/" > $null 2>&1
    docker exec $MoodleContainer chown -R www-data:www-data /var/www/html/public/local/accees_competencies > $null 2>&1
    Write-Host "  -> Da dong bo accees_competencies vao container." -ForegroundColor Cyan
}

# Don dep symlink cu va xoa bo plugin cu accees_lms_theme de loai bo loi Missing from disk
docker exec $MoodleContainer sh -c "mkdir -p /var/www/html/public/theme && rm -rf /var/www/html/public/theme/accees_lms_theme /var/www/html/theme/accees_lms_theme 2>/dev/null || true ; if [ -f /var/www/html/config.php ]; then sed -i 's/\\\$CFG->sslproxy = true;/\\\$CFG->sslproxy = false;/g' /var/www/html/config.php 2>/dev/null || true ; sed -i 's#http://localhost:[0-9]*#http://${Domain}:${Port}#g' /var/www/html/config.php 2>/dev/null || true ; fi"

# Xoa dau vet theme_accees_lms_theme trong CSDL
docker exec $DbContainer psql -U $DbUser -d $DbName -c "DELETE FROM mdl_config_plugins WHERE plugin = 'theme_accees_lms_theme'; UPDATE mdl_config SET value = 'meko' WHERE name = 'theme';" > $null 2>&1

# Nang cap CSDL Moodle qua CLI
docker exec $MoodleContainer php /var/www/html/admin/cli/upgrade.php --non-interactive > $null 2>&1

# 4. Kich hoat Theme MEKO & Thiet lap Nhan dien thuong hieu
Write-Host "[4/5] Kich hoat Theme 'meko' & Bat Theme Designer Mode..." -ForegroundColor Green
docker exec -i $MoodleContainer php -r "
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
set_config('brand_profile', '$BrandMode', 'theme_meko');
set_config('brand_profile', '$BrandMode', 'theme_accees_lms_theme');
set_config('theme', 'meko');
set_config('themedesignermode', 1);
set_config('enablemycourses', 1);
set_config('supportname', 'Ban Quan Tri MEKOSOFT');
set_config('supportemail', 'support@mekosoft.vn');
set_config('supportpage', 'http://${Domain}:${Port}');
global `$DB;
`$site = `$DB->get_record('course', ['id' => 1]);
if (`$site) {
    `$site->fullname = 'MEKOSOFT LMS - Digital Learning & Competencies';
    `$site->shortname = 'MEKO-LMS';
    `$site->summary = 'He thong Quan ly Hoc tap va Danh gia Khung Nang luc So MEKOSOFT LMS';
    `$DB->update_record('course', `$site);
}
echo 'Brand Profile & Site Settings initialized successfully.\n';
"

# Bom du lieu khoa hoc 16:9 va tai khoan test
docker exec $MoodleContainer php /var/www/html/theme/meko/cli/seed_dev_data.php > $null 2>&1

# 5. Xoa sach cache
Write-Host "[5/5] Xoa sach cache Moodle..." -ForegroundColor Green
docker exec -i $MoodleContainer php /var/www/html/admin/cli/purge_caches.php > $null 2>&1

# Dam bao Apache dang chay binh thuong
docker exec $MoodleContainer pgrep apache2 > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    docker restart $MoodleContainer > $null 2>&1
    Start-Sleep -Seconds 3
}

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host ">>> CHUAN BI MOI TRUONG THANH CONG 100%!" -ForegroundColor Green
Write-Host "  Dia chi Web : http://${Domain}:${Port}" -ForegroundColor Yellow
Write-Host "  Giao dien   : MEKOSOFT LMS (Theme meko v1.3.3)" -ForegroundColor Yellow
Write-Host "  Tai khoan   : admin    / AdminMoodle2026!@#" -ForegroundColor Yellow
Write-Host "                teacher1 / Teacher2026!@#" -ForegroundColor Yellow
Write-Host "                student1 / Student2026!@#" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Cyan