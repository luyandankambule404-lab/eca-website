@echo off
setlocal
cd /d "%~dp0"

if not exist "D:\eca-xampp-space\tmp\win" (
  echo Lexar flash D:\eca-xampp-space is not connected. Plug it in before setup.
  echo C: is too full for MySQL/PHP temp files.
  pause
  exit /b 1
)
set "TMP=D:\eca-xampp-space\tmp\win"
set "TEMP=D:\eca-xampp-space\tmp\win"

if exist "C:\xampp\mysql\bin\mysqld.exe" (
  echo Making sure MySQL is running...
  start "" /min "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini
  timeout /t 3 /nobreak >nul
)

set PHP=php
if exist "C:\xampp\php\php.exe" set PHP=C:\xampp\php\php.exe

echo Creating local ECA databases...
"%PHP%" "%~dp0local-db\setup-local-db.php" %*
if errorlevel 1 (
  echo.
  echo Setup failed. Start MySQL, then run this file again.
  pause
  exit /b 1
)

echo.
pause
endlocal
