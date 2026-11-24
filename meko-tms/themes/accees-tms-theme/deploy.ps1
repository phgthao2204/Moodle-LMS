param([string]$BaseUrl = 'http://meko.localhost')
$ErrorActionPreference = 'Stop'
$workspaceRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$deployRoot = Join-Path $workspaceRoot '..\meko-devlocal\liferay\deploy'
$themeWar = Join-Path $PSScriptRoot 'build\libs\accees-tms-theme.war'
$initializerJar = Join-Path $workspaceRoot 'modules\tms-site-initializer\build\libs\tms-site-initializer.jar'
if (!(Test-Path -LiteralPath $themeWar) -or !(Test-Path -LiteralPath $initializerJar)) {
    throw 'Run gradlew clean build from meko-tms before deploying.'
}
Copy-Item -LiteralPath $themeWar -Destination $deployRoot
$expectedBuild = (Get-Content -LiteralPath (Join-Path $PSScriptRoot 'build\theme-build-id.txt') -Raw).Trim()
$themeReady = $false
$deadline = (Get-Date).AddMinutes(3)
while ((Get-Date) -lt $deadline) {
    try {
        $response = Invoke-WebRequest -Uri "$($BaseUrl.TrimEnd('/'))/o/accees-tms-theme/build-id.txt?v=$expectedBuild" -UseBasicParsing -TimeoutSec 10
        $themeReady = $response.StatusCode -eq 200 -and $response.Content.Trim() -eq $expectedBuild
    } catch {
        $themeReady = $false
    }
    if ($themeReady) { break }
    Start-Sleep -Seconds 2
}
if (!$themeReady) { throw 'Theme did not deploy within three minutes. Check Liferay logs.' }
Copy-Item -LiteralPath $initializerJar -Destination $deployRoot
Write-Output "Copied theme and page/role initializer to $deployRoot"
Write-Output "Open $($BaseUrl.TrimEnd('/'))/tms after Liferay finishes activating the initializer."
