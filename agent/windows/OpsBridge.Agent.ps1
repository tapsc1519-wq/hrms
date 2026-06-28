[CmdletBinding()]
param(
    [string]$ConfigPath = "$env:ProgramData\OpsBridge\Agent\config.json",
    [string]$OutputPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Security
$AgentVersion = '0.1.0'
$Root = Split-Path -Parent $ConfigPath
$LogRoot = Join-Path $Root 'logs'
$QueueRoot = Join-Path $Root 'queue'
$StatePath = Join-Path $Root 'usage-state.json'
New-Item -ItemType Directory -Path $LogRoot, $QueueRoot -Force | Out-Null
Get-ChildItem $LogRoot -Filter '*.log' -ErrorAction SilentlyContinue | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-30) } | Remove-Item -Force -ErrorAction SilentlyContinue

function Write-AgentLog {
    param([string]$Message, [ValidateSet('INFO','WARN','ERROR')][string]$Level = 'INFO')
    $line = '{0} [{1}] {2}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Level, $Message
    $file = Join-Path $LogRoot ((Get-Date -Format 'yyyy-MM-dd') + '.log')
    Add-Content -LiteralPath $file -Value $line -Encoding UTF8
}

function Unprotect-Token {
    param([string]$CipherText)
    $bytes = [Convert]::FromBase64String($CipherText)
    $plain = [Security.Cryptography.ProtectedData]::Unprotect($bytes, $null, [Security.Cryptography.DataProtectionScope]::LocalMachine)
    [Text.Encoding]::UTF8.GetString($plain)
}

function Get-StringHash {
    param([string]$Value)
    $sha = [Security.Cryptography.SHA256]::Create()
    try { ([BitConverter]::ToString($sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($Value)))).Replace('-', '').ToLowerInvariant() }
    finally { $sha.Dispose() }
}

function Get-OptionalProperty {
    param([object]$InputObject, [string]$Name)
    if ($InputObject -and $InputObject.PSObject.Properties.Name -contains $Name) { return $InputObject.$Name }
    return $null
}

function Get-RegistrySoftware {
    $paths = @(
        'Registry::HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*',
        'Registry::HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*'
    )
    try {
        Get-ChildItem 'Registry::HKEY_USERS' | Where-Object { $_.PSChildName -match '^S-1-5-21-' } | ForEach-Object {
            $paths += "Registry::HKEY_USERS\$($_.PSChildName)\Software\Microsoft\Windows\CurrentVersion\Uninstall\*"
            $paths += "Registry::HKEY_USERS\$($_.PSChildName)\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*"
        }
    } catch { Write-AgentLog "Unable to enumerate user registry hives: $($_.Exception.Message)" 'WARN' }

    $items = foreach ($path in $paths) {
        Get-ItemProperty -Path $path -ErrorAction SilentlyContinue |
            Where-Object { (Get-OptionalProperty $_ 'DisplayName') -and -not (Get-OptionalProperty $_ 'SystemComponent') } |
            ForEach-Object {
                $displayName = Get-OptionalProperty $_ 'DisplayName'
                $displayIcon = Get-OptionalProperty $_ 'DisplayIcon'
                $rawInstallDate = Get-OptionalProperty $_ 'InstallDate'
                $executable = $null
                if ($displayIcon) {
                    $candidate = [Environment]::ExpandEnvironmentVariables(([string]$displayIcon).Trim('"').Split(',')[0])
                    if ($candidate -match '\.exe$') { $executable = $candidate }
                }
                $installDate = $null
                if ($rawInstallDate -match '^\d{8}$') {
                    try { $installDate = [datetime]::ParseExact($rawInstallDate, 'yyyyMMdd', $null).ToString('yyyy-MM-dd') } catch { }
                }
                [pscustomobject]@{
                    raw_name = [string]$displayName; raw_publisher = [string](Get-OptionalProperty $_ 'Publisher')
                    raw_version = [string](Get-OptionalProperty $_ 'DisplayVersion'); raw_edition = $null; raw_build_number = $null
                    executable = $executable; product_code = [string]$_.PSChildName
                    install_path = [string](Get-OptionalProperty $_ 'InstallLocation'); uninstall_string = [string](Get-OptionalProperty $_ 'UninstallString')
                    install_date = $installDate
                }
            }
    }
    @($items | Group-Object { '{0}|{1}|{2}' -f $_.raw_name, $_.raw_publisher, $_.raw_version } | ForEach-Object { $_.Group[0] })
}

function Update-UsageState {
    param([array]$Software, [int]$IntervalMinutes)
    $state = @{}
    if (Test-Path $StatePath) {
        try { (Get-Content $StatePath -Raw | ConvertFrom-Json).PSObject.Properties | ForEach-Object { $state[$_.Name] = $_.Value } }
        catch { Write-AgentLog "Usage state was reset: $($_.Exception.Message)" 'WARN' }
    }
    $running = @(Get-Process -ErrorAction SilentlyContinue | ForEach-Object { try { if ($_.Path) { $_.Path.ToLowerInvariant() } } catch { } })
    foreach ($item in $Software) {
        $key = Get-StringHash ('{0}|{1}|{2}' -f $item.raw_name, $item.raw_publisher, $item.raw_version)
        if (-not $state.ContainsKey($key)) { $state[$key] = [pscustomobject]@{ launches = 0; runtime = 0; last_used = $null; running = $false } }
        $entry = $state[$key]
        $active = $false
        if ($item.executable) { $active = $running -contains ([string]$item.executable).ToLowerInvariant() }
        elseif ($item.install_path) {
            $prefix = ([string]$item.install_path).TrimEnd('\').ToLowerInvariant() + '\'
            $active = $null -ne ($running | Where-Object { $_.StartsWith($prefix) } | Select-Object -First 1)
        }
        if ($active) {
            if (-not $entry.running) { $entry.launches = [int]$entry.launches + 1 }
            $entry.runtime = [int]$entry.runtime + $IntervalMinutes
            $entry.last_used = Get-Date -Format 'yyyy-MM-dd'
        }
        $entry.running = $active
        $item | Add-Member -NotePropertyName last_used_date -NotePropertyValue $entry.last_used -Force
        $item | Add-Member -NotePropertyName usage_count -NotePropertyValue ([int]$entry.launches) -Force
        $item | Add-Member -NotePropertyName total_runtime_minutes -NotePropertyValue ([int]$entry.runtime) -Force
    }
    $state | ConvertTo-Json -Depth 5 | Set-Content $StatePath -Encoding UTF8
    @($Software)
}

function Get-HardwareInventory {
    $computer = Get-CimInstance Win32_ComputerSystem
    $cpu = Get-CimInstance Win32_Processor | Select-Object -First 1
    $bios = Get-CimInstance Win32_BIOS
    $board = Get-CimInstance Win32_BaseBoard | Select-Object -First 1
    $os = Get-CimInstance Win32_OperatingSystem
    $battery = Get-CimInstance Win32_Battery -ErrorAction SilentlyContinue | Select-Object -First 1
    [pscustomobject]@{
        manufacturer = $computer.Manufacturer; model = $computer.Model
        device_type = if ($battery) { 'laptop' } else { 'desktop' }; domain = $computer.Domain
        cpu = [pscustomobject]@{ name = $cpu.Name; manufacturer = $cpu.Manufacturer; architecture = [string]$cpu.Architecture; physical_cores = $cpu.NumberOfCores; logical_cores = $cpu.NumberOfLogicalProcessors }
        memory = [pscustomobject]@{ total_bytes = [int64]$computer.TotalPhysicalMemory; available_bytes = [int64]$os.FreePhysicalMemory * 1024 }
        motherboard = [pscustomobject]@{ manufacturer = $board.Manufacturer; model = $board.Product; serial = $board.SerialNumber }
        bios = [pscustomobject]@{ version = $bios.SMBIOSBIOSVersion; release_date = if ($bios.ReleaseDate) { ([datetime]$bios.ReleaseDate).ToString('yyyy-MM-dd') } else { $null } }
        disks = @(Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3" | ForEach-Object { [pscustomobject]@{ name = $_.DeviceID; capacity_bytes = [int64]$_.Size; free_bytes = [int64]$_.FreeSpace } })
        battery = if ($battery) { [pscustomobject]@{ status = $battery.Status; charge_percent = $battery.EstimatedChargeRemaining } } else { $null }
        last_boot_time = ([datetime]$os.LastBootUpTime).ToString('o'); uptime_minutes = [int]((Get-Date) - [datetime]$os.LastBootUpTime).TotalMinutes
    }
}

function Get-NetworkInventory {
    [pscustomobject]@{ adapters = @(Get-CimInstance Win32_NetworkAdapterConfiguration -Filter 'IPEnabled=True' | ForEach-Object { [pscustomobject]@{ description = $_.Description; mac_address = $_.MACAddress; ip_addresses = @($_.IPAddress); gateways = @($_.DefaultIPGateway); dns_servers = @($_.DNSServerSearchOrder) } }) }
}

function Get-SecurityInventory {
    $av = @(); $firewall = @(); $bitLocker = @()
    try { $av = @(Get-CimInstance -Namespace 'root/SecurityCenter2' -ClassName AntiVirusProduct | ForEach-Object { [pscustomobject]@{ name = $_.displayName; state = $_.productState } }) } catch { }
    try { $firewall = @(Get-NetFirewallProfile | ForEach-Object { [pscustomobject]@{ profile = $_.Name; enabled = [bool]$_.Enabled } }) } catch { }
    try { $bitLocker = @(Get-BitLockerVolume | ForEach-Object { [pscustomobject]@{ volume = $_.MountPoint; protection = [string]$_.ProtectionStatus; encryption = [string]$_.VolumeStatus } }) } catch { }
    [pscustomobject]@{ antivirus = $av; firewall = $firewall; bitlocker = $bitLocker }
}

function Send-Snapshot {
    param([string]$Json, [string]$Endpoint, [string]$Token)
    Invoke-RestMethod -Uri $Endpoint -Method Post -Headers @{ Authorization = "Bearer $Token"; 'X-Agent-Version' = $AgentVersion } -ContentType 'application/json' -Body $Json -TimeoutSec 120
}

function Save-AgentConfig {
    param([object]$Config)
    $Config | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $ConfigPath -Encoding UTF8
}

function Set-ConfigValue {
    param([object]$Config, [string]$Name, [object]$Value)
    if ($Config.PSObject.Properties.Name -contains $Name) { $Config.$Name = $Value }
    else { $Config | Add-Member -NotePropertyName $Name -NotePropertyValue $Value }
}

function Test-AgentCommandSignature {
    param([object]$Command, [string]$PublicKeyXml, [string]$DeviceUuid)
    if (-not $PublicKeyXml -or $Command.device_uuid -ne $DeviceUuid) { return $false }
    if ([int64]$Command.expires_at -le [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()) { return $false }
    $culture = [Globalization.CultureInfo]::InvariantCulture
    $canonical = [string]::Join('|', @(
        [string]$Command.command_uuid,
        [string]$Command.device_uuid,
        [string]$Command.command_type,
        ([int64]$Command.issued_at).ToString($culture),
        ([int64]$Command.expires_at).ToString($culture),
        [string]$Command.payload_base64
    ))
    $rsa = New-Object Security.Cryptography.RSACryptoServiceProvider
    try {
        $rsa.FromXmlString($PublicKeyXml)
        return $rsa.VerifyData([Text.Encoding]::UTF8.GetBytes($canonical), [Security.Cryptography.CryptoConfig]::MapNameToOID('SHA256'), [Convert]::FromBase64String([string]$Command.signature))
    } catch {
        Write-AgentLog "Command signature validation error: $($_.Exception.Message)" 'ERROR'
        return $false
    } finally { $rsa.Dispose() }
}

function Send-CommandResult {
    param([string]$PollUrl, [string]$Token, [string]$DeviceUuid, [string]$CommandUuid, [string]$Status, [string]$Message)
    $uri = $PollUrl.TrimEnd('/') + '/' + $CommandUuid + '/result'
    $body = @{ device_uuid = $DeviceUuid; status = $Status; result = @{ message = $Message }; error_message = if ($Status -eq 'failed') { $Message } else { $null } } | ConvertTo-Json -Depth 5 -Compress
    Invoke-RestMethod -Uri $uri -Method Post -Headers @{ Authorization = "Bearer $Token"; 'X-Agent-Version' = $AgentVersion } -ContentType 'application/json' -Body $body -TimeoutSec 60 | Out-Null
}

function Invoke-AgentCommands {
    param([object]$Config, [string]$Token)
    if (-not (Get-OptionalProperty $Config 'command_poll_url') -or -not (Get-OptionalProperty $Config 'command_signing_public_key_xml')) { return }
    $pollUrl = [string]$Config.command_poll_url
    $separator = if ($pollUrl.Contains('?')) { '&' } else { '?' }
    $response = Invoke-RestMethod -Uri ($pollUrl + $separator + 'device_uuid=' + [Uri]::EscapeDataString([string]$Config.device_uuid)) -Method Get -Headers @{ Authorization = "Bearer $Token"; 'X-Agent-Version' = $AgentVersion } -TimeoutSec 60
    foreach ($command in @($response.commands)) {
        if (-not (Test-AgentCommandSignature $command $Config.command_signing_public_key_xml $Config.device_uuid)) {
            Write-AgentLog "Rejected command $($command.command_uuid): invalid signature, target, or expiry." 'ERROR'
            Send-CommandResult $pollUrl $Token $Config.device_uuid $command.command_uuid 'failed' 'Command signature, device target, or expiry validation failed.'
            continue
        }
        switch ([string]$command.command_type) {
            'inventory_refresh' {
                Send-CommandResult $pollUrl $Token $Config.device_uuid $command.command_uuid 'completed' 'Inventory snapshot completed during this agent run.'
                Write-AgentLog "Completed signed inventory refresh command $($command.command_uuid)."
            }
            default {
                Send-CommandResult $pollUrl $Token $Config.device_uuid $command.command_uuid 'failed' "Command type '$($command.command_type)' is not allowlisted by agent $AgentVersion."
                Write-AgentLog "Rejected non-allowlisted command type $($command.command_type)." 'ERROR'
            }
        }
    }
}

$mutex = New-Object Threading.Mutex($false, 'Global\OpsBridgeAgentInventory')
if (-not $mutex.WaitOne(0)) { exit 0 }
$agentError = $null
try {
    if (-not (Test-Path $ConfigPath)) { throw "Configuration file not found: $ConfigPath" }
    $config = Get-Content $ConfigPath -Raw | ConvertFrom-Json
    $token = Unprotect-Token $config.token_ciphertext
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    if (-not $OutputPath) {
        Get-ChildItem $QueueRoot -Filter '*.json' | Sort-Object CreationTime | Select-Object -First 20 | ForEach-Object {
            try { Send-Snapshot (Get-Content $_.FullName -Raw) $config.endpoint $token | Out-Null; Remove-Item $_.FullName -Force }
            catch { Write-AgentLog "Queued snapshot remains pending: $($_.Exception.Message)" 'WARN'; return }
        }
    }
    $os = Get-CimInstance Win32_OperatingSystem; $computer = Get-CimInstance Win32_ComputerSystem; $bios = Get-CimInstance Win32_BIOS
    $software = @(Update-UsageState @(Get-RegistrySoftware) ([int]$config.sync_interval_minutes))
    $payload = [ordered]@{
        device_uuid = [string]$config.device_uuid; hostname = $env:COMPUTERNAME; serial_number = [string]$bios.SerialNumber
        asset_tag = [string]$config.asset_tag; os_name = [string]$os.Caption; os_version = [string]$os.Version
        architecture = [string]$os.OSArchitecture; agent_version = $AgentVersion; sync_interval_minutes = [int]$config.sync_interval_minutes
        employee_code = [string]$config.employee_code; employee_email = [string]$config.employee_email; snapshot_complete = $true
        user = [ordered]@{ username = [string]$computer.UserName; domain = [string]$computer.Domain }
        hardware = Get-HardwareInventory; network = Get-NetworkInventory; security = Get-SecurityInventory; software = $software
    }
    $json = $payload | ConvertTo-Json -Depth 12 -Compress
    if ($OutputPath) {
        Set-Content -LiteralPath $OutputPath -Value $json -Encoding UTF8
        Write-AgentLog "Diagnostic inventory written to $OutputPath"
    } else {
        try {
            $response = Send-Snapshot $json $config.endpoint $token
            $configChanged = $false
            if ($response.device_api_key) {
                $deviceTokenBytes = [Text.Encoding]::UTF8.GetBytes([string]$response.device_api_key)
                $protectedDeviceToken = [Security.Cryptography.ProtectedData]::Protect($deviceTokenBytes, $null, [Security.Cryptography.DataProtectionScope]::LocalMachine)
                Set-ConfigValue $config 'token_ciphertext' ([Convert]::ToBase64String($protectedDeviceToken))
                $token = [string]$response.device_api_key
                $configChanged = $true
                Write-AgentLog 'Enrollment completed. This device will use its own API credential for future communication.'
            }
            if ($response.command_poll_url -and $response.command_signing_public_key_xml) {
                Set-ConfigValue $config 'command_poll_url' ([string]$response.command_poll_url)
                Set-ConfigValue $config 'command_signing_public_key_xml' ([string]$response.command_signing_public_key_xml)
                $configChanged = $true
            }
            if ($configChanged) { Save-AgentConfig $config }
            Write-AgentLog "Inventory accepted. Device ID $($response.device_agent_id); software $($response.software_received); mapped $($response.software_mapped)."
            Invoke-AgentCommands $config $token
        } catch {
            Set-Content (Join-Path $QueueRoot ((Get-Date -Format 'yyyyMMdd-HHmmss-fff') + '.json')) $json -Encoding UTF8
            Get-ChildItem $QueueRoot -Filter '*.json' | Sort-Object CreationTime -Descending | Select-Object -Skip 100 | Remove-Item -Force -ErrorAction SilentlyContinue
            throw
        }
    }
} catch { $agentError = $_.Exception; Write-AgentLog $_.Exception.ToString() 'ERROR' }
finally { try { $mutex.ReleaseMutex() } catch { }; $mutex.Dispose() }
if ($agentError) { throw $agentError }
