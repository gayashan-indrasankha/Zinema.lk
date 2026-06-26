param(
    [switch]$ConfirmDrop
)

$ErrorActionPreference = "Stop"

if (-not $ConfirmDrop) {
    Write-Error "This command drops the local development database. Re-run with -ConfirmDrop to continue."
}

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..")

Push-Location $repoRoot
try {
    dotnet ef database drop --force `
        --project backend/src/Zinema.Infrastructure `
        --startup-project backend/src/Zinema.Api `
        --context AppDbContext
}
finally {
    Pop-Location
}
