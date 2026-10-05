@echo off
setlocal EnableExtensions
REM One-click setup for the store PC:
REM   1. makes sure Python is installed
REM   2. installs the script's dependencies
REM   3. asks for the device IP + API token and saves them to bridge_config.json
REM   4. runs one test sync
REM   5. schedules the sync to run every 5 minutes (Windows Task Scheduler)
REM Safe to run again any time (e.g. to change the token or IP).

cd /d "%~dp0"
set "TASK_NAME=TOS ZKTeco Attendance Sync"

echo ============================================
echo   TOS ZKTeco Attendance Bridge - Setup
echo ============================================
echo.

REM --- 1. Python -------------------------------------------------------------
call :find_python
if not defined PY (
    echo Python was not found. Trying to install it with winget...
    winget install -e --id Python.Python.3.12 --scope user --accept-package-agreements --accept-source-agreements
    call :find_python
)
if not defined PY (
    echo.
    echo [ERROR] Python is still not available.
    echo Install it from https://www.python.org/downloads/windows/
    echo and tick "Add Python to PATH" during install, then run this file again.
    pause
    exit /b 1
)
echo Using Python: %PY%

REM --- 2. Dependencies -------------------------------------------------------
echo.
echo Installing dependencies...
"%PY%" -m pip install --upgrade --quiet -r "%~dp0requirements.txt"
if errorlevel 1 (
    echo [ERROR] Could not install dependencies. Check the internet connection and try again.
    pause
    exit /b 1
)

REM --- 3. Config -------------------------------------------------------------
echo.
echo Find the device IP on the terminal: Menu ^> Comm ^> Ethernet.
echo Find the API token at https://tri-e.online ^> Biometric Devices ^> edit the device
echo ^> "Local Bridge Access" ^> Bridge API Token.
echo Press Enter on either question to keep the current value.
echo.
"%PY%" "%~dp0configure.py"
if errorlevel 1 (
    pause
    exit /b 1
)

REM --- 4. Test run -----------------------------------------------------------
echo.
echo Running a test sync...
echo --------------------------------------------
"%PY%" "%~dp0zkteco_bridge.py"
set "TEST_RESULT=%ERRORLEVEL%"
echo --------------------------------------------
if not "%TEST_RESULT%"=="0" (
    echo [WARNING] The test sync failed - see the messages above or bridge.log.
    echo The automatic sync will still be scheduled; fix the problem and it will catch up.
)

REM --- 5. Schedule -----------------------------------------------------------
REM pythonw.exe runs without opening a console window every 5 minutes.
set "PYW=%PY:python.exe=pythonw.exe%"
if not exist "%PYW%" set "PYW=%PY%"

echo.
echo Scheduling "%TASK_NAME%" every 5 minutes...
schtasks /Create /F /TN "%TASK_NAME%" /SC MINUTE /MO 5 /TR "\"%PYW%\" \"%~dp0zkteco_bridge.py\"" >nul
if errorlevel 1 (
    echo [ERROR] Could not create the scheduled task. Right-click install.bat ^> Run as administrator.
    pause
    exit /b 1
)

echo.
echo ============================================
echo   Done! Attendance will sync every 5 minutes
echo   while this PC is on and logged in.
echo   Log file: %~dp0bridge.log
echo ============================================
pause
exit /b 0


:find_python
set "PY="
for /f "delims=" %%P in ('python -c "import sys; print(sys.executable)" 2^>nul') do set "PY=%%P"
if not defined PY (
    for /f "delims=" %%P in ('py -3 -c "import sys; print(sys.executable)" 2^>nul') do set "PY=%%P"
)
exit /b 0
