# ==============================================================================
# MEKO-LMS: Theme Packaging Tool for Moodle (PowerShell)
# Tự động nén theme_meko thành file ZIP chuẩn Frankenstyle Moodle release
# ==============================================================================

[CmdletBinding()]
param (
    [string]$OutputDir = ""
)

if (-not $OutputDir) {
    $OutputDir = Join-Path $PSScriptRoot "releases"
}

$ErrorActionPreference = "Stop"

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host "  MEKO-LMS: BẮT ĐẦU ĐÓNG GÓI THEME MOODLE (theme_meko)" -ForegroundColor Cyan
Write-Host "======================================================================" -ForegroundColor Cyan

# 1. Đọc phiên bản từ version.php
$versionFile = Join-Path $PSScriptRoot "version.php"
if (-not (Test-Path $versionFile)) {
    Write-Error "Không tìm thấy file version.php tại: $versionFile"
    exit 1
}

$versionContent = Get-Content $versionFile -Raw
$versionNum = ""
$releaseName = ""

if ($versionContent -match '\$plugin->version\s*=\s*(\d+);') {
    $versionNum = $Matches[1]
}
if ($versionContent -match '\$plugin->release\s*=\s*[''"]([^''"]+)[''"];') {
    $releaseName = $Matches[1]
}

Write-Host "[1/3] Đã đọc thông tin phiên bản:" -ForegroundColor Green
Write-Host "  -> Version : $versionNum" -ForegroundColor Yellow
Write-Host "  -> Release : $releaseName" -ForegroundColor Yellow

# 2. Chuẩn bị thư mục tạm để nén đúng cấu trúc 'meko/'
$tempDir = Join-Path ([System.IO.Path]::GetTempPath()) ("meko_pkg_" + [System.Guid]::NewGuid().ToString().Substring(0,8))
$themeTargetDir = Join-Path $tempDir "meko"
New-Item -ItemType Directory -Path $themeTargetDir -Force | Out-Null

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$cleanReleaseName = ($releaseName -replace '[^a-zA-Z0-9_\.-]', '_').Trim('_')
$zipFileName = "theme_meko_${versionNum}.zip"
$zipFilePath = Join-Path $OutputDir $zipFileName

if (Test-Path $zipFilePath) {
    Remove-Item $zipFilePath -Force
}

Write-Host "[2/3] Đang sao chép các thành phần mã nguồn chuẩn Moodle..." -ForegroundColor Green

$itemsToInclude = @(
    "classes",
    "cli",
    "js",
    "lang",
    "layout",
    "pix",
    "scss",
    "style",
    "templates",
    "config.php",
    "lib.php",
    "settings.php",
    "styles.css",
    "version.php",
    "README.md"
)

foreach ($item in $itemsToInclude) {
    $src = Join-Path $PSScriptRoot $item
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination (Join-Path $themeTargetDir $item) -Recurse -Force
    }
}

# 3. Tạo file ZIP
Write-Host "[3/3] Dang nen file ZIP chuan cau truc Frankenstyle [meko]..." -ForegroundColor Green
Compress-Archive -Path $themeTargetDir -DestinationPath $zipFilePath -Force

# Don dep temp
Remove-Item -Path $tempDir -Recurse -Force -ErrorAction SilentlyContinue

$zipItem = Get-Item $zipFilePath
$zipSizeMb = [Math]::Round($zipItem.Length / 1MB, 2)

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host ">>> DONG GOI THANH CONG 100%!" -ForegroundColor Green
Write-Host "  File ZIP    : $zipFilePath" -ForegroundColor Yellow
Write-Host "  Dung luong  : $zipSizeMb MB" -ForegroundColor Yellow
Write-Host "  Cau truc    : meko (San sang cai qua Moodle Admin UI hoac SSH CLI)" -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Cyan
