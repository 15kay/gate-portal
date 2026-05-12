@echo off
echo Removing /gate-portal/ prefix from all PHP files...

cd /d c:\xampp\htdocs\gate-portal

powershell -Command "Get-ChildItem -Path . -Include *.php -Recurse | ForEach-Object { (Get-Content $_.FullName) -replace '/gate-portal/', '/' | Set-Content $_.FullName }"

echo Done! All /gate-portal/ prefixes removed.
pause
