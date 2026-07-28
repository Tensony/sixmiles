@echo off
:: Check if Apache is already running
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe" >NUL
if "%ERRORLEVEL%"=="1" (
    :: Apache is not running, so start XAMPP
    start "" "C:\xampp\xampp_start.exe"
    :: Wait a brief moment for services to initialize
    timeout /t 3 /nobreak >nul
)

:: Open the web browser directly to the port 8080 project URL
start "" "http://localhost:8080/sixmiles/"
