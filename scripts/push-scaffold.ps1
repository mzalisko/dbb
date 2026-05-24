# push-scaffold.ps1
# One-time script: restores git from bundle and pushes to GitHub

$GITHUB_USER = "mzalisko"
$GITHUB_REPO = "dbb"
$BRANCH      = "feat/livewire-migration"
$PROJECT     = "C:\Dev\ddbv2"
$BUNDLE      = "C:\Dev\ddbv2\ddbv2-scaffold.bundle"

if (-not (Test-Path $BUNDLE)) {
    Write-Host "FAIL: bundle not found at $BUNDLE" -ForegroundColor Red
    Write-Host "Ask Claude to recreate it." -ForegroundColor Yellow
    exit 1
}

Write-Host "Enter GitHub Personal Access Token:" -ForegroundColor Cyan
Write-Host "(github.com > Settings > Developer settings > Personal access tokens > scope: repo)" -ForegroundColor Gray
$TOKEN = Read-Host -AsSecureString "Token"
$TP = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($TOKEN))
$REMOTE_AUTH = "https://${GITHUB_USER}:${TP}@github.com/${GITHUB_USER}/${GITHUB_REPO}.git"

Write-Host ""
Write-Host "Step 1: Remove broken .git" -ForegroundColor Cyan
if (Test-Path "$PROJECT\.git") {
    Remove-Item "$PROJECT\.git" -Recurse -Force
    Write-Host "OK: .git removed" -ForegroundColor Green
}

Write-Host "Step 2: Clone from bundle" -ForegroundColor Cyan
Set-Location C:\Dev
if (Test-Path "C:\Dev\ddbv2-tmp") { Remove-Item "C:\Dev\ddbv2-tmp" -Recurse -Force }
git clone $BUNDLE ddbv2-tmp
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: bundle clone failed" -ForegroundColor Red; exit 1 }
Copy-Item "C:\Dev\ddbv2-tmp\.git" "$PROJECT\.git" -Recurse -Force
Remove-Item "C:\Dev\ddbv2-tmp" -Recurse -Force
Write-Host "OK: .git restored from bundle" -ForegroundColor Green

Write-Host "Step 3: Configure remote + user" -ForegroundColor Cyan
Set-Location $PROJECT
git remote set-url origin $REMOTE_AUTH
git config user.email "zaliskomykola@gmail.com"
git config user.name "MeWeek"

# Find the commit hash from bundle refs
$COMMIT = git rev-list --all | Select-Object -First 1
Write-Host "Commit hash: $COMMIT"
if (-not $COMMIT) {
    Write-Host "FAIL: no commits found in bundle" -ForegroundColor Red; exit 1
}

# Create local branch pointing to commit
git branch -f $BRANCH $COMMIT
git symbolic-ref HEAD "refs/heads/$BRANCH"
Write-Host "OK: branch $BRANCH created at $COMMIT" -ForegroundColor Green

Write-Host "Step 4: Push to GitHub" -ForegroundColor Cyan
git push -u origin "$BRANCH`:$BRANCH"
if ($LASTEXITCODE -ne 0) {
    git remote set-url origin "https://github.com/$GITHUB_USER/$GITHUB_REPO.git"
    Write-Host "FAIL: push failed. Check token scope (needs: repo)" -ForegroundColor Red
    exit 1
}
Write-Host "OK: pushed!" -ForegroundColor Green

Write-Host "Step 5: Remove token from config" -ForegroundColor Cyan
git remote set-url origin "https://github.com/$GITHUB_USER/$GITHUB_REPO.git"
Write-Host "OK: token cleared" -ForegroundColor Green

Write-Host ""
git log --oneline -3
Write-Host ""
Write-Host "DONE! View on GitHub:" -ForegroundColor Green
Write-Host "https://github.com/$GITHUB_USER/$GITHUB_REPO/tree/$BRANCH" -ForegroundColor Cyan
