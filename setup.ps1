[CmdletBinding()]
param(
    [switch]$NoBrowser,
    [switch]$Recreate
)

$ErrorActionPreference = 'Stop'
$challengeDir = $PSScriptRoot
$targetUrl = 'http://127.0.0.1:44158/'
$statusUrl = 'http://127.0.0.1:44159/status.php'

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker Desktop was not found in PATH.'
}

function Wait-Http {
    param([string]$Uri, [string]$Label)
    for ($i = 1; $i -le 40; $i++) {
        try {
            $response = Invoke-WebRequest -UseBasicParsing -Uri $Uri -TimeoutSec 3
            if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) { return }
        } catch { }
        Start-Sleep -Seconds 1
    }
    throw "Endpoint '$Label' did not become ready: $Uri"
}

Set-Location -LiteralPath $challengeDir
$envFile = Join-Path $challengeDir '.env'
$envExample = Join-Path $challengeDir '.env.example'
if (-not (Test-Path -LiteralPath $envFile) -and (Test-Path -LiteralPath $envExample)) {
    Copy-Item -LiteralPath $envExample -Destination $envFile
    Write-Host 'Created .env from .env.example.' -ForegroundColor DarkCyan
}
$composeArgs = @('compose', 'up', '-d')
if ($Recreate) { $composeArgs += '--force-recreate' }
& docker @composeArgs
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed.' }

Wait-Http -Uri $targetUrl -Label 'challenge'
Wait-Http -Uri $statusUrl -Label 'proof status'

Write-Host 'PHP2Shell HARD is ready.' -ForegroundColor Green
Write-Host "Challenge: $targetUrl"
Write-Host "Status:    $statusUrl"
Write-Host 'Configure the platform-provided verifier values in .env for nickname/ranking submission.' -ForegroundColor Yellow

if (-not $NoBrowser) { Start-Process $targetUrl }
