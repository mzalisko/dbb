$PROJECT = "C:\Dev\ddbv2"
Set-Location $PROJECT

Write-Host "=== DataBridge CRM v2 - Docker Setup ===" -ForegroundColor Cyan

# Step 1: .env
if (-not (Test-Path "$PROJECT\.env")) {
    Copy-Item "$PROJECT\.env.docker.example" "$PROJECT\.env"
    Write-Host "[1] .env created" -ForegroundColor Green
} else {
    Write-Host "[1] .env exists, skipping" -ForegroundColor DarkGray
}

# Step 2: Install Laravel 12
if (-not (Test-Path "$PROJECT\artisan")) {
    Write-Host "[2] Installing Laravel 12 via Docker (2-5 min)..." -ForegroundColor Yellow
    $cmd = 'composer create-project laravel/laravel /tmp/laravel-new --prefer-dist --quiet && cp -rn /tmp/laravel-new/. /app/'
    docker run --rm --volume "${PROJECT}:/app" composer:latest sh -c $cmd
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[2] ERROR: install failed" -ForegroundColor Red
        exit 1
    }
    Write-Host "[2] Laravel 12 installed!" -ForegroundColor Green
} else {
    Write-Host "[2] artisan found, skipping" -ForegroundColor DarkGray
}

# Step 3: Start MySQL, MinIO, Mailpit
Write-Host "[3] Starting MySQL + MinIO + Mailpit..." -ForegroundColor Yellow
docker compose up -d mysql minio mailpit
if ($LASTEXITCODE -ne 0) {
    Write-Host "[3] ERROR: compose up failed" -ForegroundColor Red
    exit 1
}
Write-Host "[3] Services started" -ForegroundColor Green

# Step 4: Generate APP_KEY before app starts
Write-Host "[4] Generating APP_KEY..." -ForegroundColor Yellow
Start-Sleep 5
docker run --rm --volume "${PROJECT}:/var/www/html" -w /var/www/html serversideup/php:8.3-fpm-nginx php artisan key:generate --force
Write-Host "[4] APP_KEY done" -ForegroundColor Green

# Step 5: Start app container
Write-Host "[5] Starting app container..." -ForegroundColor Yellow
docker compose up -d app

Write-Host "    Waiting for MySQL healthcheck..." -ForegroundColor DarkGray
for ($i = 0; $i -lt 24; $i++) {
    $health = docker inspect --format="{{.State.Health.Status}}" ddbv2-mysql-1 2>$null
    if ($health -eq "healthy") {
        Write-Host "    MySQL healthy!" -ForegroundColor Green
        break
    }
    Start-Sleep 5
    Write-Host "    MySQL: $health ($($i+1)/24)..." -ForegroundColor DarkGray
}

Write-Host "    Waiting for AUTORUN (20s)..." -ForegroundColor DarkGray
Start-Sleep 20

# Step 6: Final setup
Write-Host "[6] Migrations + Livewire 3..." -ForegroundColor Yellow
docker compose exec -T app php artisan key:generate --force
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan storage:link
docker compose exec -T app composer require livewire/livewire --no-interaction --quiet

Write-Host ""
Write-Host "=== DONE ===" -ForegroundColor Green
Write-Host "  App:     http://localhost:8000"
Write-Host "  Mailpit: http://localhost:8025"
Write-Host "  MinIO:   http://localhost:9001  login: minio / minio123"
Write-Host "  Artisan: docker compose exec app php artisan [cmd]"
Write-Host "  Logs:    docker compose logs -f app"
Write-Host "  Stop:    .\scripts\stop-dev.ps1"
