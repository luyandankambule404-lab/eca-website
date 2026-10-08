@echo off
setlocal
set "FLASH_ROOT=D:\eca-xampp-space"
set "FLASH_TMP=%FLASH_ROOT%\tmp"

if not exist "D:\" (
  echo Lexar flash drive D: is not connected. Plug it in and run this again.
  pause
  exit /b 1
)

mkdir "%FLASH_TMP%\mysql" 2>nul
mkdir "%FLASH_TMP%\php" 2>nul
mkdir "%FLASH_TMP%\win" 2>nul

echo Flash drive temp folders ready under %FLASH_TMP%

setx TMP "%FLASH_TMP%\win" >nul
setx TEMP "%FLASH_TMP%\win" >nul
set "TMP=%FLASH_TMP%\win"
set "TEMP=%FLASH_TMP%\win"

echo Windows TEMP/TMP now point to the flash drive for new programs.

echo.
echo Restart MySQL in XAMPP so sort temp files use D:\eca-xampp-space\tmp\mysql
echo (Stop MySQL, then Start MySQL in the XAMPP Control Panel.)
echo.
pause
endlocal
