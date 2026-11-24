# ==============================================================================
# MEKOSOFT DEVLOCAL - 1-Click Docker Environment Setup (Windows PowerShell)
# Stack: Nginx Gateway + Liferay 7.4 GA132 (CMS & /tms) + Moodle 5.3 (/lms) + Postgres 17
# ==============================================================================

param(
    [switch]$PullLatest,
    [switch]$SkipLiferayInit,
    [switch]$MoodleOnly,
    [string]$Domain = "meko.localhost",
    [string]$BrandProfile = "meko"
)

$ErrorActionPreference = "Stop"
$ScriptDir = $PSScriptRoot

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "  MEKOSOFT DEVLOCAL STACK - 1-CLICK SETUP (WINDOWS)" -ForegroundColor Cyan
Write-Host "  Domain: http://$Domain (CMS: / | TMS: /tms | LMS: /lms)" -ForegroundColor Yellow
Write-Host "======================================================================"

# 0. Check Docker Engine
docker info > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Docker Desktop is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}

# 1. Ensure .env exists and load HTTP_PORT / DOMAIN
$envPath = Join-Path $ScriptDir ".env"
$envExamplePath = Join-Path $ScriptDir ".env.example"
if (-not (Test-Path $envPath)) {
    Copy-Item $envExamplePath $envPath
    Write-Host "[1/6] Created .env from .env.example" -ForegroundColor Green
} else {
    Write-Host "[1/6] Using existing .env" -ForegroundColor Green
}

$HttpPort = "80"
Get-Content $envPath | ForEach-Object {
    if ($_ -match "^\s*HTTP_PORT\s*=\s*(\d+)") { $HttpPort = $Matches[1] }
    if ($_ -match "^\s*DOMAIN\s*=\s*([^\s#]+)" -and -not $PSBoundParameters.ContainsKey("Domain")) { $Domain = $Matches[1] }
    if ($_ -match "^\s*MOODLE_BRAND_PROFILE\s*=\s*([^\s#]+)" -and -not $PSBoundParameters.ContainsKey("BrandProfile")) { $BrandProfile = $Matches[1] }
}
$PortSuffix = if ($HttpPort -eq "80") { "" } else { ":$HttpPort" }
$BaseUrl = "http://${Domain}${PortSuffix}"

# Check if HTTP_PORT is occupied by a non-Docker process (e.g. IIS, XAMPP, Laragon)
$listeners = Get-NetTCPConnection -LocalPort ([int]$HttpPort) -State Listen -ErrorAction SilentlyContinue
if ($listeners) {
    foreach ($l in $listeners) {
        $proc = Get-Process -Id $l.OwningProcess -ErrorAction SilentlyContinue
        if ($proc -and $proc.ProcessName -notin @("com.docker.backend", "wslrelay", "vpnkit")) {
            Write-Host "[ERROR] Port $HttpPort is already in use by '$($proc.ProcessName)' (PID $($proc.Id))." -ForegroundColor Red
            Write-Host "        Stop that service OR set HTTP_PORT=8090 in .env and re-run .\setup.ps1." -ForegroundColor Yellow
            exit 1
        }
    }
}

# 2. Verify Moodle theme & competency plugins in monorepo (../meko-lms)
Write-Host "[2/6] Checking Moodle theme & competency plugins in monorepo..." -ForegroundColor Green
$repoRoot = Split-Path $ScriptDir -Parent
$themeDir = Join-Path $repoRoot "meko-lms\theme_meko"
$compDir = Join-Path $repoRoot "meko-lms\local\meko_competencies"

if ($PullLatest) {
    Write-Host "  -> Pulling latest code in $repoRoot..." -ForegroundColor Cyan
    git -C $repoRoot pull --ff-only
}

if ((Test-Path (Join-Path $themeDir "version.php")) -and (Test-Path (Join-Path $compDir "version.php"))) {
    Write-Host "  -> Found theme_meko & local_meko_competencies at $repoRoot\meko-lms" -ForegroundColor Cyan
} else {
    Write-Host "[ERROR] Missing ../meko-lms/theme_meko or ../meko-lms/local/meko_competencies." -ForegroundColor Red
    exit 1
}

# 3. Check & configure Windows hosts file (skipped automatically for *.localhost RFC 6761)
Write-Host "[3/6] Checking local DNS / hosts for $Domain..." -ForegroundColor Green
if ($Domain -like "*.localhost" -or $Domain -eq "localhost") {
    Write-Host "  -> RFC 6761 '*.localhost' resolves to 127.0.0.1 automatically (no hosts edit needed)." -ForegroundColor Cyan
} else {
    $hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
    $hostsContent = Get-Content $hostsPath -Raw -ErrorAction SilentlyContinue
    if ($hostsContent -notmatch "(?m)^\s*127\.0\.0\.1\s+.*\b$([regex]::Escape($Domain))\b") {
        try {
            Add-Content -Path $hostsPath -Value "`r`n127.0.0.1 $Domain" -Encoding Ascii -ErrorAction Stop
            Write-Host "  [+] Added '127.0.0.1 $Domain' to hosts file." -ForegroundColor Cyan
        } catch {
            Write-Host "  [WARN] Cannot write to $hostsPath (run PowerShell as Administrator to auto-add)." -ForegroundColor Yellow
            Write-Host "         Please manually add: 127.0.0.1 $Domain" -ForegroundColor White
        }
    } else {
        Write-Host "  -> Hosts entry already configured." -ForegroundColor Cyan
    }
}

# 4. Pre-generate Moodle config.php (skips slow mysqli wait loop) & Start Docker Compose
Write-Host "[4/6] Preparing Moodle config.php & starting Docker Compose stack..." -ForegroundColor Green
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$configPhp = @"
<?php
unset(`$CFG);
global `$CFG;
`$CFG = new stdClass();

`$CFG->dbtype    = 'pgsql';
`$CFG->dblibrary = 'native';
`$CFG->dbhost    = 'postgres';
`$CFG->dbname    = 'accees_lms';
`$CFG->dbuser    = 'lms_user';
`$CFG->dbpass    = 'LmsPassword2026!@#';
`$CFG->prefix    = 'mdl_';
`$CFG->dboptions = ['dbport' => 5432, 'dbsocket' => ''];

`$CFG->wwwroot   = '$BaseUrl/lms';
`$CFG->dataroot  = '/var/moodledata';
`$CFG->routerconfigured = true;
`$CFG->sslproxy  = false;
`$CFG->admin     = 'admin';
`$CFG->directorypermissions = 02777;

if (
    isset(`$_SERVER['SCRIPT_NAME']) &&
    strpos(`$_SERVER['SCRIPT_NAME'], '/lms/') !== 0 &&
    `$_SERVER['SCRIPT_NAME'] !== '/lms' &&
    isset(`$_SERVER['REQUEST_URI']) &&
    strpos(`$_SERVER['REQUEST_URI'], '/lms') === 0
) {
    `$_SERVER['SCRIPT_NAME'] = '/lms' . `$_SERVER['SCRIPT_NAME'];
}

require_once(__DIR__ . '/lib/setup.php');
"@
$moodleCfgPath = Join-Path $ScriptDir "moodle\config.php"
[System.IO.File]::WriteAllText($moodleCfgPath, $configPhp, $utf8NoBom)

Push-Location $ScriptDir
if ($MoodleOnly) {
    Write-Host "  -> -MoodleOnly mode: starting Postgres + Moodle + Mailpit + Gateway (skipping Liferay)..." -ForegroundColor Yellow
    docker compose up -d --no-deps postgres moodle mailpit gateway
    $SkipLiferayInit = $true
} else {
    docker compose up -d
}
if ($LASTEXITCODE -ne 0) {
    Pop-Location
    Write-Host "[ERROR] docker compose up -d failed!" -ForegroundColor Red
    exit 1
}
Pop-Location

# Wait for PostgreSQL healthy
Write-Host "  -> Waiting for shared PostgreSQL 17 to be ready..." -ForegroundColor Yellow
for ($i = 0; $i -lt 30; $i++) {
    docker exec devlocal-postgres pg_isready -U postgres > $null 2>&1
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep -Seconds 2
}

# 5. Install/Upgrade Moodle DB at /lms + seed 100% real data
Write-Host "[5/6] Installing/Upgrading Moodle 5.3 at /lms & seeding real data..." -ForegroundColor Green
Start-Sleep -Seconds 3

$hasConfigTable = docker exec devlocal-postgres psql -U postgres -d accees_lms -tAc "SELECT to_regclass('public.mdl_config');" 2>$null
$seedDumpGz = Join-Path $ScriptDir "moodle\moodle_seed.sql.gz"
if ($hasConfigTable -ne "mdl_config") {
    if (Test-Path $seedDumpGz) {
        Write-Host "  -> Fresh database detected: fast-restoring moodle_seed.sql.gz..." -ForegroundColor Cyan
        $tmpSql = Join-Path $env:TEMP "devlocal_moodle_seed.sql"
        if (Get-Command python -ErrorAction SilentlyContinue) {
            python -c "import gzip, shutil; shutil.copyfileobj(gzip.open(r'$seedDumpGz'), open(r'$tmpSql', 'wb'))"
        } else {
            $inS = [System.IO.File]::OpenRead((Resolve-Path $seedDumpGz))
            $outS = [System.IO.File]::Create($tmpSql)
            $gzS = New-Object System.IO.Compression.GZipStream($inS, [System.IO.Compression.CompressionMode]::Decompress)
            $gzS.CopyTo($outS); $gzS.Close(); $outS.Close(); $inS.Close()
        }
        docker cp $tmpSql "devlocal-postgres:/tmp/moodle_seed.sql"
        docker exec devlocal-postgres psql -U lms_user -d accees_lms -f /tmp/moodle_seed.sql -q > $null
        docker exec devlocal-postgres rm -f /tmp/moodle_seed.sql
        Remove-Item $tmpSql -Force -ErrorAction SilentlyContinue
    } else {
        Write-Host "  -> Fresh database detected: running Moodle CLI install_database.php..." -ForegroundColor Cyan
        docker exec devlocal-moodle php /var/www/html/admin/cli/install_database.php `
            --lang=en `
            --adminuser=admin `
            --adminpass="AdminMoodle2026!@#" `
            --adminemail="admin@mekosoft.vn" `
            --fullname="MEKOSOFT LMS - Digital Learning & Competencies" `
            --shortname="MEKO-LMS" `
            --agree-license
    }
}

docker exec devlocal-moodle php /var/www/html/admin/cli/upgrade.php --non-interactive

# Run real data seeder
$seederSrc = Join-Path $ScriptDir "moodle\seed_real_data.php"
docker cp $seederSrc "devlocal-moodle:/tmp/seed_real_data.php"
docker exec -e MOODLE_BRAND_PROFILE=$BrandProfile -e DOMAIN=$Domain devlocal-moodle php /tmp/seed_real_data.php
docker exec devlocal-moodle rm -f /tmp/seed_real_data.php

# 6. Initialize Liferay CMS & TMS Site (/tms)
if (-not $SkipLiferayInit) {
    Write-Host "[6/6] Initializing Liferay 7.4 GA132 Site (/tms)..." -ForegroundColor Green
    $initPy = Join-Path $ScriptDir "liferay\init_liferay.py"
    if (Get-Command python -ErrorAction SilentlyContinue) {
        $env:DOMAIN = $Domain
        $env:LIFERAY_INIT_URL = "http://127.0.0.1${PortSuffix}"
        python -u $initPy
    } else {
        Write-Host "  [WARN] Python not found; skipping automated Liferay /tms site creation." -ForegroundColor Yellow
    }
} else {
    Write-Host "[6/6] Skipped Liferay API init (-SkipLiferayInit)." -ForegroundColor Yellow
}

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "  SETUP COMPLETED!" -ForegroundColor Green
Write-Host "  - Dev Info  : $BaseUrl/dev-info" -ForegroundColor Yellow
Write-Host "  - Liferay   : $BaseUrl/  (Admin: test@liferay.com / test)" -ForegroundColor Yellow
Write-Host "  - TMS       : $BaseUrl/tms" -ForegroundColor Yellow
Write-Host "  - Moodle    : $BaseUrl/lms/ (Admin: admin / AdminMoodle2026!@#)" -ForegroundColor Yellow
Write-Host "  - Mailpit   : $BaseUrl/mail/ (SMTP: mailpit:1025)" -ForegroundColor Yellow
Write-Host "  - Postgres  : localhost:5433 (DBs: lportal | accees_lms)" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Cyan
