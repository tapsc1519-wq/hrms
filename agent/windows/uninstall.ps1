[CmdletBinding()]
param([switch]$KeepLogs)

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Run this uninstaller from an elevated PowerShell window.' }
Unregister-ScheduledTask -TaskName 'OpsBridge Device Agent' -Confirm:$false -ErrorAction SilentlyContinue
Unregister-ScheduledTask -TaskName 'OpsBridge Endpoint Commands' -Confirm:$false -ErrorAction SilentlyContinue
Remove-Item 'HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\OpsBridgeDeviceAgent' -Recurse -Force -ErrorAction SilentlyContinue
$root = "$env:ProgramData\OpsBridge\Agent"
if ($KeepLogs -and (Test-Path "$root\logs")) {
    Remove-Item "$root\config.json", "$root\OpsBridge.Agent.ps1", "$root\usage-state.json", "$root\queue" -Recurse -Force -ErrorAction SilentlyContinue
} else { Remove-Item $root -Recurse -Force -ErrorAction SilentlyContinue }
Write-Host 'OpsBridge Device Agent removed.' -ForegroundColor Green
