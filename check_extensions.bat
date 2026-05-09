@echo off
echo === PHP Extensions Diagnostic ===
echo.

set PHP_EXE=C:\xampp\php\php.exe
set PHP_DIR=C:\xampp\php

echo PHP Location: %PHP_DIR%
echo.

echo === Step 1: Check PHP Version ===
%PHP_EXE% -v
echo.

echo === Step 2: Check Extensions Directory ===
%PHP_EXE% -i | findstr "extension_dir"
echo.

echo === Step 3: Check if DLL files exist ===
if exist "%PHP_DIR%\ext\php_pdo_sqlsrv.dll" (
    echo [YES] php_pdo_sqlsrv.dll exists
) else (
    echo [NO]  php_pdo_sqlsrv.dll NOT FOUND
)

if exist "%PHP_DIR%\ext\php_sqlsrv.dll" (
    echo [YES] php_sqlsrv.dll exists
) else (
    echo [NO]  php_sqlsrv.dll NOT FOUND
)
echo.

echo === Step 4: List all sqlsrv files in ext folder ===
dir /b "%PHP_DIR%\ext\*sqlsrv*" 2>nul
if errorlevel 1 echo No sqlsrv files found
echo.

echo === Step 5: Check php.ini location ===
%PHP_EXE% -i | findstr "Loaded Configuration File"
echo.

echo === Step 6: Check if extensions are in php.ini ===
if exist "%PHP_DIR%\php.ini" (
    echo Checking php.ini...
    findstr /C:"pdo_sqlsrv" "%PHP_DIR%\php.ini"
    findstr /C:"sqlsrv" "%PHP_DIR%\php.ini"
) else (
    echo php.ini not found at %PHP_DIR%\php.ini
)
echo.

echo === Step 7: Check loaded modules ===
%PHP_EXE% -m | findstr "sqlsrv"
if errorlevel 1 echo No sqlsrv modules loaded
echo.

echo === Diagnostic Complete ===
echo.
pause
