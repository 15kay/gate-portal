@echo off
echo === PHP SQL Server Drivers Diagnostic ===
echo.

REM Find PHP
set PHP_PATH=
if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php
if exist "C:\PHP\php.exe" set PHP_PATH=C:\PHP
if exist "C:\Program Files\PHP\php.exe" set PHP_PATH=C:\Program Files\PHP

if "%PHP_PATH%"=="" (
    echo ERROR: Could not find PHP
    pause
    exit /b 1
)

echo PHP Location: %PHP_PATH%
echo.

REM Check extensions directory
echo Checking extensions directory...
for /f "delims=" %%i in ('"%PHP_PATH%\php.exe" -r "echo ini_get('extension_dir');"') do set EXT_DIR=%%i
echo Extensions Directory: %EXT_DIR%
echo.

REM Check if DLLs exist
echo Checking if DLL files exist:
if exist "%EXT_DIR%\php_pdo_sqlsrv.dll" (
    echo   [YES] php_pdo_sqlsrv.dll exists
) else (
    echo   [NO]  php_pdo_sqlsrv.dll NOT FOUND
)

if exist "%EXT_DIR%\php_sqlsrv.dll" (
    echo   [YES] php_sqlsrv.dll exists
) else (
    echo   [NO]  php_sqlsrv.dll NOT FOUND
)
echo.

REM Check php.ini location
echo Checking php.ini...
for /f "delims=" %%i in ('"%PHP_PATH%\php.exe" -r "echo php_ini_loaded_file();"') do set PHP_INI=%%i
echo php.ini Location: %PHP_INI%
echo.

REM Check if extensions are in php.ini
if exist "%PHP_INI%" (
    echo Checking if extensions are enabled in php.ini:
    findstr /C:"extension=php_pdo_sqlsrv" "%PHP_INI%" >nul
    if errorlevel 1 (
        echo   [NO]  php_pdo_sqlsrv NOT in php.ini
    ) else (
        echo   [YES] php_pdo_sqlsrv in php.ini
    )
    
    findstr /C:"extension=php_sqlsrv" "%PHP_INI%" >nul
    if errorlevel 1 (
        echo   [NO]  php_sqlsrv NOT in php.ini
    ) else (
        echo   [YES] php_sqlsrv in php.ini
    )
) else (
    echo   [ERROR] php.ini not found
)
echo.

REM Check loaded modules
echo Checking loaded PHP modules:
"%PHP_PATH%\php.exe" -m | findstr /C:"pdo_sqlsrv" >nul
if errorlevel 1 (
    echo   [NO]  pdo_sqlsrv NOT loaded
) else (
    echo   [YES] pdo_sqlsrv loaded
)

"%PHP_PATH%\php.exe" -m | findstr /C:"sqlsrv" >nul
if errorlevel 1 (
    echo   [NO]  sqlsrv NOT loaded
) else (
    echo   [YES] sqlsrv loaded
)
echo.

REM List all files in ext directory
echo Files in extensions directory:
dir /b "%EXT_DIR%\*sqlsrv*"
echo.

echo === Diagnostic Complete ===
echo.
pause
