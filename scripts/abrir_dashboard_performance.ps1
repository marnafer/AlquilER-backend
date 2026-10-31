$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$dashboardUrl = 'http://127.0.0.1:8765'

Push-Location $projectRoot
try {
    & php scripts/generar_dashboard_performance.php --no-open
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo generar el dashboard de rendimiento.'
    }

    $serverReady = $false

    try {
        $response = Invoke-WebRequest `
            -Uri $dashboardUrl `
            -UseBasicParsing `
            -TimeoutSec 2
        $serverReady = $response.Content.Contains('AlquilER</title>')
    } catch {
        $serverReady = $false
    }

    if (-not $serverReady) {
        Start-Process `
            -FilePath 'php' `
            -ArgumentList @(
                '-S',
                '127.0.0.1:8765',
                'scripts/performance_dashboard_server.php'
            ) `
            -WorkingDirectory $projectRoot `
            -WindowStyle Normal

        for ($attempt = 0; $attempt -lt 20; $attempt++) {
            Start-Sleep -Milliseconds 250

            try {
                $response = Invoke-WebRequest `
                    -Uri $dashboardUrl `
                    -UseBasicParsing `
                    -TimeoutSec 2
                $serverReady = $response.Content.Contains('AlquilER</title>')

                if ($serverReady) {
                    break
                }
            } catch {
                $serverReady = $false
            }
        }
    }

    if (-not $serverReady) {
        throw 'No se pudo iniciar el servidor local del dashboard en 127.0.0.1:8765.'
    }

    Start-Process $dashboardUrl
    Write-Output "Dashboard local abierto: $dashboardUrl"
} finally {
    Pop-Location
}
