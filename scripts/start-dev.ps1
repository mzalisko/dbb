# start-dev.ps1 — DataBridge CRM v2 dev server
# Запускає Docker + Vite + відкриває браузер
# Використання: .\scripts\start-dev.ps1

$PROJECT = "C:\Dev\ddbv2"
$SITE    = "http://localhost:8000"

Set-Location $PROJECT

Write-Host "=== DataBridge CRM v2 — Dev Start ===" -ForegroundColor Cyan

# 1. Перевірити Docker Desktop
Write-Host "`n[1/4] Перевірка Docker Desktop..." -ForegroundColor Yellow
$dockerRunning = $false
for ($i = 0; $i -lt 12; $i++) {
    try {
        docker info 2>$null | Out-Null
        if ($LASTEXITCODE -eq 0) { $dockerRunning = $true; break }
    } catch {}

    if ($i -eq 0) {
        Write-Host "     Docker не запущений — запускаємо..." -ForegroundColor DarkYellow
        Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe" -ErrorAction SilentlyContinue
    }
    Write-Host "     Чекаємо Docker... ($($i+1)/12)" -ForegroundColor DarkGray
    Start-Sleep 5
}

if (-not $dockerRunning) {
    Write-Host "ПОМИЛКА: Docker не запустився за 60 сек. Запусти Docker Desktop вручну." -ForegroundColor Red
    exit 1
}
Write-Host "     Docker OK" -ForegroundColor Green

# 2. Підняти контейнери
Write-Host "`n[2/4] docker compose up -d..." -ForegroundColor Yellow
docker compose up -d
if ($LASTEXITCODE -ne 0) { Write-Host "ПОМИЛКА: docker compose up failed" -ForegroundColor Red; exit 2 }

# Чекати поки app-контейнер стане healthy/running
Write-Host "     Чекаємо контейнер app..." -ForegroundColor DarkGray
for ($i = 0; $i -lt 20; $i++) {
    $status = docker inspect --format='{{.State.Status}}' ddbv2-app-1 2>$null
    if ($status -eq "running") { break }
    Start-Sleep 3
}
Write-Host "     Контейнери OK" -ForegroundColor Green

# 3. Запустити Vite у фоні (новий процес)
Write-Host "`n[3/4] npm run dev..." -ForegroundColor Yellow
$viteJob = Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PROJECT'; npm run dev" -PassThru
Write-Host "     Vite запущений (PID $($viteJob.Id))" -ForegroundColor Green

# Чекати поки порт 8000 відповідає
Write-Host "     Чекаємо http://localhost:8000..." -ForegroundColor DarkGray
for ($i = 0; $i -lt 15; $i++) {
    try {
        $resp = Invoke-WebRequest -Uri $SITE -TimeoutSec 2 -UseBasicParsing -ErrorAction Stop
        if ($resp.StatusCode -lt 500) { break }
    } catch {}
    Start-Sleep 2
}

# 4. Відкрити браузер
Write-Host "`n[4/4] Відкриваємо $SITE ..." -ForegroundColor Yellow
Start-Process $SITE

Write-Host "`n=== Готово! ===" -ForegroundColor Green
Write-Host "Сайт:    $SITE" -ForegroundColor White
Write-Host "Mailpit: http://localhost:8025" -ForegroundColor White
Write-Host "MinIO:   http://localhost:9001" -ForegroundColor White
Write-Host "`nЗупинити: .\scripts\stop-dev.ps1" -ForegroundColor DarkGray
