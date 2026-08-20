@echo off
REM Launcher for Windows Task Scheduler. Assumes Python is on PATH.
REM If you used a virtualenv, change the line below to call that venv's
REM python.exe instead, e.g.:
REM   "C:\path\to\venv\Scripts\python.exe" "%~dp0zkteco_bridge.py"

cd /d "%~dp0"
python "%~dp0zkteco_bridge.py"
