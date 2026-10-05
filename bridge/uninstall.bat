@echo off
REM Stops the automatic attendance sync (removes the scheduled task).
REM The files in this folder are left alone.
schtasks /Delete /F /TN "TOS ZKTeco Attendance Sync"
pause
