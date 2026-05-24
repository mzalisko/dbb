# setup-git.ps1
$PROJECT = "C:\Dev\ddbv2"
$REMOTE  = "https://github.com/mzalisko/dbb.git"
$BRANCH  = "feat/livewire-migration"

Write-Host "Step 1: Remove broken .git" -ForegroundColor Cyan
if (Test-Path "$PROJECT\.git") {
    Remove-Item "$PROJECT\.git" -Recurse -Force
    Write-Host "OK: .git removed" -ForegroundColor Green
}
if (Test-Path "C:\Dev\ddbv2-clone") {
    Remove-Item "C:\Dev\ddbv2-clone" -Recurse -Force
    Write-Host "OK: ddbv2-clone removed" -ForegroundColor Green
}

Write-Host "Step 2: git init + remote" -ForegroundColor Cyan
Set-Location $PROJECT
git init
git config user.email "zaliskomykola@gmail.com"
git config user.name  "MeWeek"
git remote add origin $REMOTE
Write-Host "OK: remote added" -ForegroundColor Green

Write-Host "Step 3: Fetch from GitHub" -ForegroundColor Cyan
git fetch origin
if ($LASTEXITCODE -ne 0) {
    Write-Host "FAIL: fetch failed" -ForegroundColor Red
    exit 1
}

Write-Host "Step 4: Checkout default branch" -ForegroundColor Cyan
$heads = git branch -r
$defaultBranch = "main"
if ($heads -match "origin/master") { $defaultBranch = "master" }
Write-Host "Using: $defaultBranch"
git checkout -b $defaultBranch --track origin/$defaultBranch 2>&1
if ($LASTEXITCODE -ne 0) { git checkout $defaultBranch 2>&1 }

Write-Host "Step 5: Tag legacy state" -ForegroundColor Cyan
git tag legacy/inertia-final
Write-Host "OK: tag legacy/inertia-final" -ForegroundColor Green

Write-Host "Step 6: Create feature branch" -ForegroundColor Cyan
git checkout -b $BRANCH
Write-Host "OK: branch $BRANCH" -ForegroundColor Green

Write-Host "Step 7: Commit scaffold" -ForegroundColor Cyan
git add CLAUDE.md
git add .claude
git add scripts
git add docs
git add .ralph
git commit -m "chore: add ddbv2 scaffold (CLAUDE.md, agents, scripts)"
Write-Host "OK: scaffold committed" -ForegroundColor Green

Write-Host "Step 8: Push" -ForegroundColor Cyan
git push -u origin $BRANCH
if ($LASTEXITCODE -ne 0) {
    Write-Host "WARN: push failed - run: git push -u origin $BRANCH" -ForegroundColor Yellow
} else {
    Write-Host "OK: pushed" -ForegroundColor Green
}

git log --oneline -5
git branch -a
Write-Host "DONE" -ForegroundColor Green
