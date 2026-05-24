param([Parameter(Mandatory)][string]$Branch)

$repo     = Split-Path $PSScriptRoot -Parent
$worktree = "C:\Dev\worktrees\$Branch"
Set-Location $repo

Write-Host "=== diff vs feat/livewire-migration ===" -ForegroundColor Cyan
git diff "feat/livewire-migration..$Branch" --stat

$forbidden = git diff "feat/livewire-migration..$Branch" --name-only |
    Where-Object { $_ -match '^(app/Models/|app/Policies/|database/migrations/|CLAUDE\.md)' }

if ($forbidden) {
    Write-Host "✗ FORBIDDEN: $forbidden" -ForegroundColor Red
    Write-Host "Потрібен sign-off critic."
    exit 2
}

Write-Host "`n=== php artisan test ===" -ForegroundColor Cyan
docker compose exec app php artisan test --stop-on-failure
if ($LASTEXITCODE -ne 0) { Write-Host "✗ Тести впали." -ForegroundColor Red; exit 3 }

git checkout feat/livewire-migration
git merge --no-ff $Branch -m "merge: $Branch"
git branch -D $Branch
git worktree remove $worktree --force
Write-Host "✓ Merged + worktree removed" -ForegroundColor Green
