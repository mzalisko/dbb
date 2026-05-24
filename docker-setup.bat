@echo off
echo Starting DataBridge CRM v2 Docker Setup...
powershell.exe -ExecutionPolicy Bypass -File "%~dp0scripts\docker-setup.ps1"
pause
