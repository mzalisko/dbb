param(
    [Parameter(Mandatory)][string]$AgentId,
    [Parameter(Mandatory)][string]$RecipeId
)
$branch   = "wt-$($AgentId.ToLower())-$($RecipeId.ToLower())"
$worktree = "C:\Dev\worktrees\$branch"
$repo     = Split-Path $PSScriptRoot -Parent

if (Test-Path $worktree) { Write-Host "Worktree вже існує: $worktree"; exit 1 }

New-Item -ItemType Directory -Force -Path "C:\Dev\worktrees" | Out-Null
Set-Location $repo
git worktree add -b $branch $worktree HEAD

Write-Host "✓ Worktree: $worktree" -ForegroundColor Green
Write-Host "✓ Branch:   $branch" -ForegroundColor Green
Write-Host "Далі:  cd $worktree && claude --agents .claude\agents\$AgentId.md"
Write-Host "Merge: .\scripts\merge-worktree.ps1 $branch"
