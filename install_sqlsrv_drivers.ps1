# ============================================================================
# SQL Server PHP Drivers Installation Script
# Run this on the WSU production server (clestudtrack02.wsu.ac.za)
# ============================================================================

Write-Host "=== SQL Server PHP Drivers Installation ===" -ForegroundColor Cyan
Write-Host ""

# Step 1: Detect PHP version and path
Write-Host "Step 1: Detecting PHP installation..." -ForegroundColor Yellow
$phpPath = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $phpPath) {
    Write-Host "ERROR: PHP not found in PATH" -ForegroundColor Red
    Write-Host "Please run this script from a location where PHP is accessible" -ForegroundColor Red
    exit 1
}

$phpVersion = php -r "echo PHP_VERSION;"
$phpDir = Split-Path $phpPath -Parent
$extDir = php -r "echo ini_get('extension_dir');"

Write-Host "  PHP Path: $phpPath" -ForegroundColor Green
Write-Host "  PHP Version: $phpVersion" -ForegroundColor Green
Write-Host "  Extensions Directory: $extDir" -ForegroundColor Green
Write-Host ""

# Step 2: Determine thread safety
Write-Host "Step 2: Checking PHP thread safety..." -ForegroundColor Yellow
$threadSafe = php -r "echo PHP_ZTS ? 'ts' : 'nts';"
Write-Host "  Thread Safety: $threadSafe" -ForegroundColor Green
Write-Host ""

# Step 3: Download drivers
Write-Host "Step 3: Downloading SQL Server drivers..." -ForegroundColor Yellow
$downloadUrl = "https://github.com/microsoft/msphpsql/releases/download/v5.11.1/Windows-8.0.zip"
$tempZip = "$env:TEMP\sqlsrv-drivers.zip"
$tempExtract = "$env:TEMP\sqlsrv-drivers"

try {
    Invoke-WebRequest -Uri $downloadUrl -OutFile $tempZip -UseBasicParsing
    Write-Host "  Downloaded successfully" -ForegroundColor Green
} catch {
    Write-Host "  ERROR: Failed to download drivers" -ForegroundColor Red
    Write-Host "  $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 4: Extract drivers
Write-Host "Step 4: Extracting drivers..." -ForegroundColor Yellow
if (Test-Path $tempExtract) {
    Remove-Item $tempExtract -Recurse -Force
}
Expand-Archive -Path $tempZip -DestinationPath $tempExtract -Force
Write-Host "  Extracted successfully" -ForegroundColor Green
Write-Host ""

# Step 5: Copy correct DLLs
Write-Host "Step 5: Installing drivers..." -ForegroundColor Yellow

$dllSuffix = "80_${threadSafe}_x64"
$pdoSqlsrvDll = "php_pdo_sqlsrv_${dllSuffix}.dll"
$sqlsrvDll = "php_sqlsrv_${dllSuffix}.dll"

$sourcePdo = Get-ChildItem -Path $tempExtract -Filter $pdoSqlsrvDll -Recurse | Select-Object -First 1
$sourceSqlsrv = Get-ChildItem -Path $tempExtract -Filter $sqlsrvDll -Recurse | Select-Object -First 1

if (-not $sourcePdo -or -not $sourceSqlsrv) {
    Write-Host "  ERROR: Could not find DLL files for PHP 8.0 $threadSafe" -ForegroundColor Red
    Write-Host "  Looking for: $pdoSqlsrvDll and $sqlsrvDll" -ForegroundColor Red
    exit 1
}

# Copy DLLs
Copy-Item $sourcePdo.FullName -Destination "$extDir\php_pdo_sqlsrv.dll" -Force
Copy-Item $sourceSqlsrv.FullName -Destination "$extDir\php_sqlsrv.dll" -Force

Write-Host "  Copied php_pdo_sqlsrv.dll to $extDir" -ForegroundColor Green
Write-Host "  Copied php_sqlsrv.dll to $extDir" -ForegroundColor Green
Write-Host ""

# Step 6: Update php.ini
Write-Host "Step 6: Updating php.ini..." -ForegroundColor Yellow
$phpIni = php -r "echo php_ini_loaded_file();"

if (-not $phpIni -or -not (Test-Path $phpIni)) {
    Write-Host "  WARNING: Could not find php.ini" -ForegroundColor Yellow
    Write-Host "  You need to manually add these lines to php.ini:" -ForegroundColor Yellow
    Write-Host "    extension=php_pdo_sqlsrv.dll" -ForegroundColor Cyan
    Write-Host "    extension=php_sqlsrv.dll" -ForegroundColor Cyan
} else {
    $iniContent = Get-Content $phpIni -Raw
    
    $needsUpdate = $false
    if ($iniContent -notmatch "extension=php_pdo_sqlsrv") {
        Add-Content -Path $phpIni -Value "`nextension=php_pdo_sqlsrv.dll"
        $needsUpdate = $true
        Write-Host "  Added php_pdo_sqlsrv.dll to php.ini" -ForegroundColor Green
    } else {
        Write-Host "  php_pdo_sqlsrv.dll already in php.ini" -ForegroundColor Green
    }
    
    if ($iniContent -notmatch "extension=php_sqlsrv") {
        Add-Content -Path $phpIni -Value "extension=php_sqlsrv.dll"
        $needsUpdate = $true
        Write-Host "  Added php_sqlsrv.dll to php.ini" -ForegroundColor Green
    } else {
        Write-Host "  php_sqlsrv.dll already in php.ini" -ForegroundColor Green
    }
    
    Write-Host "  php.ini location: $phpIni" -ForegroundColor Green
}
Write-Host ""

# Step 7: Cleanup
Write-Host "Step 7: Cleaning up..." -ForegroundColor Yellow
Remove-Item $tempZip -Force -ErrorAction SilentlyContinue
Remove-Item $tempExtract -Recurse -Force -ErrorAction SilentlyContinue
Write-Host "  Cleanup complete" -ForegroundColor Green
Write-Host ""

# Step 8: Restart IIS
Write-Host "Step 8: Restarting IIS..." -ForegroundColor Yellow
try {
    iisreset
    Write-Host "  IIS restarted successfully" -ForegroundColor Green
} catch {
    Write-Host "  WARNING: Could not restart IIS automatically" -ForegroundColor Yellow
    Write-Host "  Please run: iisreset" -ForegroundColor Yellow
}
Write-Host ""

# Step 9: Verify installation
Write-Host "Step 9: Verifying installation..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
$modules = php -m
if ($modules -match "pdo_sqlsrv" -and $modules -match "sqlsrv") {
    Write-Host "  SUCCESS! SQL Server drivers installed" -ForegroundColor Green
    Write-Host "  - pdo_sqlsrv: Installed" -ForegroundColor Green
    Write-Host "  - sqlsrv: Installed" -ForegroundColor Green
} else {
    Write-Host "  WARNING: Drivers may not be loaded yet" -ForegroundColor Yellow
    Write-Host "  Try restarting IIS manually: iisreset" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "=== Installation Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Verify drivers: php -m | findstr sqlsrv" -ForegroundColor White
Write-Host "2. Test connection: Visit http://your-server/gate-portal/test_db.php" -ForegroundColor White
Write-Host ""
