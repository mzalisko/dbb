# ralph/icon-port/run.ps1 — конвертує JSX-іконки в Blade components
$MAX_ITERATIONS = 45
$SOURCE_DIR     = "C:\Dev\ddb-staging\resources\js\components\icons"
$TARGET_DIR     = "C:\Dev\ddbv2\resources\views\components\icon"
$REPO           = "C:\Dev\ddbv2"

if (-not (Test-Path $SOURCE_DIR)) {
    Write-Host "✗ Джерело не знайдено: $SOURCE_DIR" -ForegroundColor Red
    Write-Host "  Переконайся що C:\Dev\ddb-staging\ існує і містить JSX-іконки."
    exit 1
}

$icons = Get-ChildItem "$SOURCE_DIR\*.jsx" | Select-Object -First $MAX_ITERATIONS
$count = 0

New-Item -ItemType Directory -Force $TARGET_DIR | Out-Null

foreach ($icon in $icons) {
    $count++
    $name = $icon.BaseName -replace 'Icon$','' -replace '([A-Z])', '-$1' -replace '^-','' 
    $name = $name.ToLower()
    $target = "$TARGET_DIR\$name.blade.php"

    Write-Host "[$count/$($icons.Count)] $($icon.Name) → $name.blade.php" -ForegroundColor Cyan

    $prompt = @"
Конвертуй цей JSX-компонент іконки в Blade-компонент.
Правила:
- Зберігай SVG path data без змін
- Props: size (default 24), class (default '')
- Формат: <svg {{ \$attributes->merge(['class' => \$class, 'width' => \$size, 'height' => \$size]) }}>...</svg>
- Без JS, без React
- Тільки Blade синтаксис

Файл: $($icon.FullName)
Вміст:
$(Get-Content $icon.FullName -Raw)
"@

    Set-Content -Path $target -Value $prompt
    # TODO: pipe через claude haiku
    # $prompt | claude --model claude-haiku-4-5-20251001 --print > $target
}

Write-Host "`n✓ Оброблено $count іконок" -ForegroundColor Green
Write-Host "Результат: $TARGET_DIR"
