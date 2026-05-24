# stop-dev.ps1 — зупиняє dev-сервер
$PROJECT = "C:\Dev\ddbv2"
Set-Location $PROJECT

Write-Host "Зупиняємо docker compose..." -ForegroundColor Yellow
docker compose down
Write-Host "Зупиняємо Vite (node)..." -ForegroundColor Yellow
Get-Process node -ErrorAction SilentlyContinue | Stop-Process -Force
Write-Host "Готово." -ForegroundColor Green
