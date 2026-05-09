@echo off
REM ============================================================================
REM SQL Server PHP Drivers Installation Script (Batch Version)
REM Run this on the WSU production server as Administrator
REM ============================================================================

echo === SQL Server PHP Drivers Installation ===
echo.

REM Step 1: Find PHP
echo Step 1: Finding PHP installation...

set PHP_PATH=
if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php
if exist "C:\PHP\php.exe" set PHP_PATH=C:\PHP
if exist "C:\Program Files\PHP\php.exe" set PHP_PATH=C:\Program Files\PHP
if exist "C:\Program Files (x86)\PHP\php.exe" set PHP_PATH=C:\Program Files (x86)\PHP

if "%PHP_PATH%"=="" (
    echo ERROR: Could not find PHP installation
    echo Please enter the full path to your PHP folder:
    set /p PHP_PATH="PHP Path: "
)

if not exist "%PHP_PATH%\php.exe" (
    echo ERROR: php.exe not found at %PHP_PATH%
    pause
    exit /b 1
)

echo   Found PHP at: %PHP_PATH%
echo.

REM Step 2: Get PHP version
echo Step 2: Checking PHP version...
"%PHP_PATH%\php.exe" -r "echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;"
echo.

REM Step 3: Get extension directory
echo Step 3: Finding extensions directory...
for /f "delims=" %%i in ('"%PHP_PATH%\php.exe" -r "echo ini_get('extension_dir');"') do set EXT_DIR=%%i
echo   Extensions Directory: %EXT_DIR%
echo.

REM Step 4: Check thread safety
echo Step 4: Checking thread safety...
for /f "delims=" %%i in ('"%PHP_PATH%\php.exe" -r "echo PHP_ZTS ? 'ts' : 'nts';"') do set THREAD_SAFE=%%i
echo   Thread Safety: %THREAD_SAFE%
echo.

REM Step 5: Download drivers
echo Step 5: Downloading SQL Server drivers...
echo   This may take a moment...
powershell -Command "Invoke-WebRequest -Uri 'https://github.com/microsoft/msphpsql/releases/download/v5.11.1/Windows-8.0.zip' -OutFile '%TEMP%\sqlsrv-drivers.zip' -UseBasicParsing"
if errorlevel 1 (
    echo   ERROR: Failed to download drivers
    echo   Please download manually from: https://github.com/microsoft/msphpsql/releases/tag/v5.11.1
    pause
    exit /b 1
)
echo   Downloaded successfully
echo.

REM Step 6: Extract drivers
echo Step 6: Extracting drivers...
if exist "%TEMP%\sqlsrv-drivers" rmdir /s /q "%TEMP%\sqlsrv-drivers"
powershell -Command "Expand-Archive -Path '%TEMP%\sqlsrv-drivers.zip' -DestinationPath '%TEMP%\sqlsrv-drivers' -Force"
echo   Extracted successfully
echo.

REM Step 7: Copy DLLs
echo Step 7: Installing drivers...
set DLL_SUFFIX=80_%THREAD_SAFE%_x64
set PDO_DLL=php_pdo_sqlsrv_%DLL_SUFFIX%.dll
set SQLSRV_DLL=php_sqlsrv_%DLL_SUFFIX%.dll

echo   Looking for: %PDO_DLL%
echo   Looking for: %SQLSRV_DLL%

REM Find and copy the DLLs
for /r "%TEMP%\sqlsrv-drivers" %%f in (%PDO_DLL%) do (
    if exist "%%f" (
        copy /y "%%f" "%EXT_DIR%\php_pdo_sqlsrv.dll"
        echo   Copied php_pdo_sqlsrv.dll
    )
)

for /r "%TEMP%\sqlsrv-drivers" %%f in (%SQLSRV_DLL%) do (
    if exist "%%f" (
        copy /y "%%f" "%EXT_DIR%\php_sqlsrv.dll"
        echo   Copied php_sqlsrv.dll
    )
)
echo.

REM Step 8: Update php.ini
echo Step 8: Updating php.ini...
for /f "delims=" %%i in ('"%PHP_PATH%\php.exe" -r "echo php_ini_loaded_file();"') do set PHP_INI=%%i

if "%PHP_INI%"=="" (
    echo   WARNING: Could not find php.ini
    echo   Please manually add these lines to php.ini:
    echo     extension=php_pdo_sqlsrv.dll
    echo     extension=php_sqlsrv.dll
) else (
    echo   php.ini location: %PHP_INI%
    
    findstr /C:"extension=php_pdo_sqlsrv" "%PHP_INI%" >nul
    if errorlevel 1 (
        echo extension=php_pdo_sqlsrv.dll >> "%PHP_INI%"
        echo   Added php_pdo_sqlsrv.dll to php.ini
    ) else (
        echo   php_pdo_sqlsrv.dll already in php.ini
    )
    
    findstr /C:"extension=php_sqlsrv" "%PHP_INI%" >nul
    if errorlevel 1 (
        echo extension=php_sqlsrv.dll >> "%PHP_INI%"
        echo   Added php_sqlsrv.dll to php.ini
    ) else (
        echo   php_sqlsrv.dll already in php.ini
    )
)
echo.

REM Step 9: Cleanup
echo Step 9: Cleaning up...
del /q "%TEMP%\sqlsrv-drivers.zip" 2>nul
rmdir /s /q "%TEMP%\sqlsrv-drivers" 2>nul
echo   Cleanup complete
echo.

REM Step 10: Restart IIS
echo Step 10: Restarting IIS...
iisreset
if errorlevel 1 (
    echo   WARNING: Could not restart IIS
    echo   Please run: iisreset
) else (
    echo   IIS restarted successfully
)
echo.

REM Step 11: Verify
echo Step 11: Verifying installation...
timeout /t 3 /nobreak >nul
"%PHP_PATH%\php.exe" -m | findstr sqlsrv
if errorlevel 1 (
    echo   WARNING: Drivers may not be loaded yet
    echo   Try restarting IIS manually: iisreset
) else (
    echo   SUCCESS! SQL Server drivers installed
)
echo.

echo === Installation Complete ===
echo.
echo Next steps:
echo 1. Verify drivers: php -m ^| findstr sqlsrv
echo 2. Test connection: Visit http://your-server/gate-portal/test_db.php
echo.
pause
