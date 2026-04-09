Param(
    [string]$XamppHtdocs = "C:\xampp\htdocs",
    [string]$LinkName = "backend"
)

$ErrorActionPreference = "Stop"

$projectRoot = $PSScriptRoot
$sourcePath = Join-Path $projectRoot "backend"
$targetPath = Join-Path $XamppHtdocs $LinkName

if (-not (Test-Path $sourcePath)) {
    throw "Source backend folder not found: $sourcePath"
}

if (-not (Test-Path $XamppHtdocs)) {
    throw "XAMPP htdocs folder not found: $XamppHtdocs"
}

if (Test-Path $targetPath) {
    $item = Get-Item -LiteralPath $targetPath -Force
    if ($item.LinkType -eq "SymbolicLink") {
        Write-Host "Symlink already exists: $targetPath"
        exit 0
    }
    throw "Target already exists and is not a symlink: $targetPath"
}

try {
    New-Item -ItemType SymbolicLink -Path $targetPath -Target $sourcePath | Out-Null
    Write-Host "Symlink created successfully."
    Write-Host "From: $targetPath"
    Write-Host "To  : $sourcePath"
}
catch {
    Write-Host "Failed to create symlink. Try running PowerShell as Administrator."
    throw
}
