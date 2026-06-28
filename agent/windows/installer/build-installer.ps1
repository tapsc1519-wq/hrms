[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$sourceRoot = Split-Path $PSScriptRoot -Parent
$compiler = "$env:WINDIR\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
if (-not (Test-Path $compiler)) { throw 'The .NET Framework C# compiler was not found.' }

$output = Join-Path $sourceRoot 'dist\OpsBridge-Agent-Setup.exe'
New-Item -ItemType Directory -Path (Split-Path $output -Parent) -Force | Out-Null
$manifest = Join-Path $PSScriptRoot 'OpsBridge.Agent.Setup.manifest'
$source = Join-Path $PSScriptRoot 'OpsBridge.Agent.Setup.cs'
$agent = Join-Path $sourceRoot 'OpsBridge.Agent.ps1'
$uninstaller = Join-Path $sourceRoot 'uninstall.ps1'

& $compiler /nologo /target:winexe /platform:anycpu /optimize+ `
    "/out:$output" `
    "/win32manifest:$manifest" `
    /reference:System.dll `
    /reference:System.Core.dll `
    /reference:System.Drawing.dll `
    /reference:System.Management.dll `
    /reference:System.Security.dll `
    /reference:System.Web.Extensions.dll `
    /reference:System.Windows.Forms.dll `
    "/resource:$agent,OpsBridge.Agent.ps1" `
    "/resource:$uninstaller,uninstall.ps1" `
    $source

if ($LASTEXITCODE -ne 0 -or -not (Test-Path $output)) { throw 'Windows installer compilation failed.' }
Write-Host "Built $output" -ForegroundColor Green
