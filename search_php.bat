@echo off
echo === Searching for PHP Installation ===
echo.

echo Checking common locations...
echo.

if exist "C:\xampp\php\php.exe" (
    echo [FOUND] C:\xampp\php\php.exe
    set FOUND_PHP=C:\xampp\php
)

if exist "C:\PHP\php.exe" (
    echo [FOUND] C:\PHP\php.exe
    set FOUND_PHP=C:\PHP
)

if exist "C:\Program Files\PHP\php.exe" (
    echo [FOUND] C:\Program Files\PHP\php.exe
    set FOUND_PHP=C:\Program Files\PHP
)

if exist "C:\Program Files (x86)\PHP\php.exe" (
    echo [FOUND] C:\Program Files (x86)\PHP\php.exe
    set FOUND_PHP=C:\Program Files (x86)\PHP
)

if exist "C:\wamp\bin\php\php8.0.30\php.exe" (
    echo [FOUND] C:\wamp\bin\php\php8.0.30\php.exe
    set FOUND_PHP=C:\wamp\bin\php\php8.0.30
)

if exist "C:\wamp64\bin\php\php8.0.30\php.exe" (
    echo [FOUND] C:\wamp64\bin\php\php8.0.30\php.exe
    set FOUND_PHP=C:\wamp64\bin\php\php8.0.30
)

if exist "C:\inetpub\php\php.exe" (
    echo [FOUND] C:\inetpub\php\php.exe
    set FOUND_PHP=C:\inetpub\php
)

echo.
echo Searching entire C: drive for php.exe...
echo This may take a minute...
echo.

for /f "delims=" %%i in ('dir /s /b C:\php.exe 2^>nul') do (
    echo [FOUND] %%i
)

echo.
echo === Search Complete ===
echo.

if defined FOUND_PHP (
    echo.
    echo PHP is installed at: %FOUND_PHP%
    echo.
    echo Next: Check IIS PHP Handler configuration
    echo Go to: IIS Manager ^> Handler Mappings ^> PHP
    echo This will show the actual PHP path being used
) else (
    echo.
    echo Could not find PHP in common locations.
    echo.
    echo Please check:
    echo 1. IIS Manager ^> Handler Mappings ^> Look for PHP handler
    echo 2. The path shown there is where PHP is actually installed
)

echo.
pause
