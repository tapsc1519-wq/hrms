[CmdletBinding()]
param(
    [Parameter(Mandatory)][string]$Endpoint,
    [Parameter(Mandatory)][string]$Token,
    [string]$AssetTag = '',
    [string]$EmployeeCode = '',
    [string]$EmployeeEmail = '',
    [ValidateRange(15, 1440)][int]$IntervalMinutes = 60
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Security
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Run this installer from an elevated PowerShell window.' }
if ($Endpoint -notmatch '^https://') { throw 'The agent endpoint must use HTTPS.' }

$root = "$env:ProgramData\OpsBridge\Agent"
New-Item -ItemType Directory -Path $root, (Join-Path $root 'logs'), (Join-Path $root 'queue') -Force | Out-Null
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'OpsBridge.Agent.ps1') -Destination (Join-Path $root 'OpsBridge.Agent.ps1') -Force

$protected = [Security.Cryptography.ProtectedData]::Protect([Text.Encoding]::UTF8.GetBytes($Token), $null, [Security.Cryptography.DataProtectionScope]::LocalMachine)
$machineGuid = (Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\Cryptography').MachineGuid
$serial = (Get-CimInstance Win32_BIOS).SerialNumber
$sha = [Security.Cryptography.SHA256]::Create()
try { $deviceUuid = ([BitConverter]::ToString($sha.ComputeHash([Text.Encoding]::UTF8.GetBytes("$machineGuid|$serial")))).Replace('-', '').ToLowerInvariant() }
finally { $sha.Dispose() }

[ordered]@{
    endpoint = $Endpoint.TrimEnd('/'); token_ciphertext = [Convert]::ToBase64String($protected)
    device_uuid = $deviceUuid; asset_tag = $AssetTag; employee_code = $EmployeeCode
    employee_email = $EmployeeEmail; sync_interval_minutes = $IntervalMinutes
} | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $root 'config.json') -Encoding UTF8

& icacls.exe $root /inheritance:r /grant:r '*S-1-5-18:(OI)(CI)F' '*S-1-5-32-544:(OI)(CI)F' | Out-Null
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File `"$root\OpsBridge.Agent.ps1`""
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes)
$commandAction = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File `"$root\OpsBridge.Agent.ps1`" -CommandsOnly"
$commandTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Minutes 5)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 10)
Register-ScheduledTask -TaskName 'OpsBridge Device Agent' -Action $action -Trigger $trigger -Settings $settings -User 'SYSTEM' -RunLevel Highest -Force | Out-Null
Register-ScheduledTask -TaskName 'OpsBridge Endpoint Commands' -Action $commandAction -Trigger $commandTrigger -Settings $settings -User 'SYSTEM' -RunLevel Highest -Force | Out-Null
Start-ScheduledTask -TaskName 'OpsBridge Device Agent'
Start-ScheduledTask -TaskName 'OpsBridge Endpoint Commands'
Write-Host "OpsBridge Device Agent installed. Inventory runs every $IntervalMinutes minutes and commands poll every 5 minutes." -ForegroundColor Green
