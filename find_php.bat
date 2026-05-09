@echo off
echo === Finding PHP Installation ===
echo.

REM Check common PHP locations
if exist "C:\xampp\php\php.exe" (
    echo Found PHP at: C:\xampp\php
    echo.
    echo Testing PHP version:
    C:\xampp\php\php.exe -v
    echo.
    echo Testing SQL Server extensions:
    C:\xampp\php\php.exe -m | findstr sqlsrv
    goto :end
)

if exist "C:\PHP\php.exe" (
    echo Found PHP at: C:\PHP
    echo.
    echo Testing PHP version:
    C:\PHP\php.exe -v
    echo.
    echo Testing SQL Server extensions:
    C:\PHP\php.exe -m | findstr sqlsrv
    goto :end
)

if exist "C:\Program Files\PHP\php.exe" (
    echo Found PHP at: C:\Program Files\PHP
    echo.
    echo Testing PHP version:
    "C:\Program Files\PHP\php.exe" -v
    echo.
    echo Testing SQL Server extensions:
    "C:\Program Files\PHP\php.exe" -m | findstr sqlsrv
    goto :end
)

if exist "C:\wamp64\bin\php\php8.0.30\php.exe" (
    echo Found PHP at: C:\wamp64\bin\php\php8.0.30
    echo.
    echo Testing PHP version:
    C:\wamp64\bin\php\php8.0.30\php.exe -v
    echo.
    echo Testing SQL Server extensions:
    C:\wamp64\bin\php\php8.0.30\php.exe -m | findstr sqlsrv
    goto :end
)

echo ERROR: Could not find PHP installation
echo.
echo Please check these locations manually:
echo - C:\xampp\php\
echo - C:\PHP\
echo - C:\Program Files\PHP\
echo - C:\wamp64\bin\php\

:end
echo.
pause
