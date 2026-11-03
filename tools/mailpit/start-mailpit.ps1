$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$mailpit = Join-Path $root 'mailpit.exe'
$database = Join-Path $root 'mailpit.db'
$log = Join-Path $root 'mailpit.log'

Start-Process `
    -FilePath $mailpit `
    -ArgumentList @(
        '--disable-version-check',
        '--smtp', '127.0.0.1:1025',
        '--listen', '127.0.0.1:8025',
        '--database', $database,
        '--log-file', $log
    ) `
    -WindowStyle Hidden

Start-Sleep -Seconds 2

Get-Process |
    Where-Object { $_.ProcessName -eq 'mailpit' } |
    Select-Object Id, ProcessName, Path

Write-Host 'Mailpit UI: http://127.0.0.1:8025'
