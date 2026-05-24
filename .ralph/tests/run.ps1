# ralph/tests/run.ps1 — дописує PHPUnit тести до coverage >= 80%
$MAX_ITERATIONS = 200
$REPO           = "C:\Dev\ddbv2"
$MIN_COVERAGE   = 80

Set-Location $REPO
$iteration = 0

Write-Host "Ralph Tests Loop (max $MAX_ITERATIONS iterations, target $MIN_COVERAGE% coverage)" -ForegroundColor Cyan

while ($iteration -lt $MAX_ITERATIONS) {
    $iteration++
    Write-Host "`n[Iteration $iteration/$MAX_ITERATIONS]" -ForegroundColor Yellow

    # Перевір поточний coverage
    $result = docker compose exec app php artisan test --coverage-text 2>&1
    $coverageLine = $result | Select-String "Lines:\s+(\d+\.\d+)%"
    
    if ($coverageLine) {
        $coverage = [float]($coverageLine.Matches[0].Groups[1].Value)
        Write-Host "Coverage: $coverage%" -ForegroundColor $(if ($coverage -ge $MIN_COVERAGE) { 'Green' } else { 'Yellow' })
        
        if ($coverage -ge $MIN_COVERAGE) {
            Write-Host "✓ Target reached: $coverage% >= $MIN_COVERAGE%" -ForegroundColor Green
            exit 0
        }
    }

    # TODO: claude --model claude-sonnet-4-6 --print "Знайди uncovered методи і допиши тест"
    Write-Host "  → (pipe to claude sonnet для генерації тесту)"
    Start-Sleep -Seconds 2
}

Write-Host "✗ MAX_ITERATIONS досягнуто. Поточний coverage нижче $MIN_COVERAGE%." -ForegroundColor Red
Write-Host "Логуй в C:\Dev\ddbv2-vault\50-mistakes\"
exit 1
