@echo off
setlocal
cd /d "%~dp0"

if not exist "D:\eca-xampp-space\tmp\win" (
  echo Lexar flash D:\eca-xampp-space is not connected. Plug it in before starting.
  echo C: is too full for MySQL/PHP temp files.
  exit /b 1
)
set "TMP=D:\eca-xampp-space\tmp\win"
set "TEMP=D:\eca-xampp-space\tmp\win"

echo Starting MySQL...
if exist "C:\xampp\mysql_start.bat" (
  start "" /min "C:\xampp\mysql_start.bat"
) else if exist "C:\xampp\mysql\bin\mysqld.exe" (
  start "" /min "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini
)

echo Waiting for MySQL...
timeout /t 3 /nobreak >nul

for /f "tokens=5" %%p in ('netstat -ano ^| findstr /R /C:":8765 .*LISTENING"') do (
  echo Stopping old process on port 8765: %%p
  taskkill /F /PID %%p >nul 2>&1
)

echo Starting ECA site on http://127.0.0.1:8765/
start "ECA local" /min "C:\xampp\php\php.exe" -S 0.0.0.0:8765 "%~dp0local-router.php"
timeout /t 2 /nobreak >nul
start "" "http://127.0.0.1:8765/"
echo Opened in your default browser.
endlocal
