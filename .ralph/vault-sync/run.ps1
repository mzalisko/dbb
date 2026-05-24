# ralph/vault-sync/run.ps1 — синхронізує git log → vault progress notes
$REPO      = "C:\Dev\ddbv2"
$VAULT     = "C:\Dev\ddbv2-vault"
$MAX_ITER  = 50
$DATE      = Get-Date -Format "yyyy-MM-dd"

Set-Location $REPO

$commits = git log --since="1 day ago" --oneline 2>&1
if (-not $commits) {
    Write-Host "Нема нових комітів за останній день." -ForegroundColor Yellow
    exit 0
}

Write-Host "Знайдено комітів: $($commits.Count)" -ForegroundColor Cyan

$progressFile = "$VAULT\40-progress\$DATE.md"
$content = "# Progress $DATE`n`n## Коміти`n`n"
$commits | ForEach-Object { $content += "- $_`n" }

Set-Content -Path $progressFile -Value $content
Write-Host "✓ Збережено: $progressFile" -ForegroundColor Green
