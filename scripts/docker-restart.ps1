# docker-restart.ps1 — recover a wedged Docker Desktop and bring ddbv2 back.
# Proven sequence from the 2026-06 sessions: the engine reports
# "Docker Desktop is unable to start" / exec instances vanish / D-state procs.
# Usage:  .\scripts\docker-restart.ps1

$ErrorActionPreference = 'SilentlyContinue'
$exe = "C:\Program Files\Docker\Docker\Docker Desktop.exe"

Write-Host "[1/4] Killing Docker Desktop + backend..." -ForegroundColor Cyan
Get-Process 'Docker Desktop', 'com.docker.backend', 'com.docker.build' |
    Stop-Process -Force
Start-Sleep -Seconds 6

Write-Host "[2/4] Relaunching Docker Desktop..." -ForegroundColor Cyan
Start-Process $exe

Write-Host "[3/4] Waiting for the engine (up to ~3 min)..." -ForegroundColor Cyan
$up = $false
for ($i = 1; $i -le 30; $i++) {
    Start-Sleep -Seconds 6
    docker ps *> $null
    if ($LASTEXITCODE -eq 0) { $up = $true; break }
}
if (-not $up) {
    Write-Host "Engine still down. Open Docker Desktop manually and check its logs." -ForegroundColor Red
    exit 1
}
Write-Host "Engine is up." -ForegroundColor Green

Write-Host "[4/4] Bringing ddbv2 up (restart: unless-stopped also auto-recovers)..." -ForegroundColor Cyan
Set-Location (Split-Path $PSScriptRoot -Parent)
docker compose up -d

for ($i = 1; $i -le 40; $i++) {
    $s = docker inspect -f '{{.State.Health.Status}}' ddbv2-app-1 2>$null
    if ($s -eq 'healthy') { Write-Host "app: healthy ✔" -ForegroundColor Green; break }
    Start-Sleep -Seconds 3
}
docker compose ps
