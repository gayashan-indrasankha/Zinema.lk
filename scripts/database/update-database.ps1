$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..")

Push-Location $repoRoot
try {
    dotnet ef database update `
        --project backend/src/Zinema.Infrastructure `
        --startup-project backend/src/Zinema.Api `
        --context AppDbContext
}
finally {
    Pop-Location
}
