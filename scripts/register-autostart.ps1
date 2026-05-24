# register-autostart.ps1 — реєструє start-dev як Windows Scheduled Task
# Запускати від імені Адміністратора один раз
$action  = New-ScheduledTaskAction -Execute "powershell.exe" `
           -Argument "-WindowStyle Hidden -ExecutionPolicy Bypass -File C:\Dev\ddbv2\scripts\start-dev.ps1"
$trigger = New-ScheduledTaskTrigger -AtLogOn
$settings= New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

Register-ScheduledTask -TaskName "ddbv2-dev-server" `
    -Action $action -Trigger $trigger -Settings $settings `
    -Description "Auto-start DataBridge CRM v2 dev server on login" `
    -RunLevel Highest -Force

Write-Host "✓ Зареєстровано. Сервер буде стартувати при вході в Windows." -ForegroundColor Green
Write-Host "Щоб прибрати: Unregister-ScheduledTask -TaskName 'ddbv2-dev-server' -Confirm:`$false"
