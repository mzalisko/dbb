# ralph/mistakes/run.ps1 — аналізує логи і додає уроки в CLAUDE.md §9
$REPO   = "C:\Dev\ddbv2"
$VAULT  = "C:\Dev\ddbv2-vault"
$DATE   = Get-Date -Format "yyyy-MM-dd"

Set-Location $REPO

# Збирає останні помилки з docker logs
$logs = docker compose logs --since=1h app 2>&1 | Select-String "ERROR|Exception|Fatal"

if (-not $logs) {
    Write-Host "Нема нових помилок в логах." -ForegroundColor Green
    exit 0
}

Write-Host "Знайдено помилок: $($logs.Count)" -ForegroundColor Yellow
$logs | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }

# TODO: pipe через claude haiku → генерує lesson
# Результат → $VAULT\50-mistakes\$DATE-<slug>.md
# + додає рядок в CLAUDE.md §9
Write-Host "`n→ (pipe to claude haiku для аналізу і запису уроку)"
