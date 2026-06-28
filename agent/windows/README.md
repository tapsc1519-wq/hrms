# OpsBridge Windows Device Agent

Phase 1 inventory agent for Windows 10, Windows 11, Windows Server 2019, and Windows Server 2022.

## Install with the Windows Setup

1. In the portal, open **Software > Agent Sources** and create an enrollment token.
2. Download **OpsBridge-Agent-Setup.exe**.
3. Open the installer, enter the API endpoint and enrollment token, then select **Install Device Agent**.

Windows requests administrator approval. The setup installs the agent for all users, protects its credential with machine-level DPAPI, registers the scheduled inventory task, and adds **OpsBridge Device Agent** to Windows Installed apps.

For Intune, SCCM, or another deployment platform, use silent installation:

```text
OpsBridge-Agent-Setup.exe /silent /endpoint="https://your-domain.example/api/v1/agent/check-in" /token="ops_agent_..." /interval=60
```

Optional switches are `/asset-tag`, `/employee-code`, and `/employee-email`. A successful silent installation returns exit code `0`; failure returns `1`.

## Advanced PowerShell Install

1. In the portal, open **Software > Agent Sources** and create an enrollment token.
2. Copy this folder to the Windows device.
3. Open PowerShell as Administrator.
4. Run:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\install.ps1 `
  -Endpoint "https://your-domain.example/api/v1/agent/check-in" `
  -Token "paste-the-one-time-token" `
  -AssetTag "LAP-001" `
  -EmployeeEmail "employee@company.com" `
  -IntervalMinutes 60
```

Both installation methods copy the agent to `C:\ProgramData\OpsBridge\Agent`, encrypt the enrollment token with machine-level DPAPI, restrict directory access to SYSTEM and Administrators, and create the **OpsBridge Device Agent** scheduled task. On its first successful check-in, the server replaces the enrollment token with a unique device API key. That key is also DPAPI-encrypted and can be revoked independently from the device page.

## Collected Data

- Device identity, BIOS serial, manufacturer, model, domain, and uptime
- CPU, memory, motherboard, disks, battery, and operating system
- Network adapters, IP addresses, gateways, DNS, and MAC addresses
- Antivirus, Windows Firewall, and BitLocker status where available
- Installed applications from machine and loaded user registry hives
- Coarse usage metering from process samples

Failed submissions are stored locally and retried before the next snapshot.

The agent also polls the signed command queue after a successful check-in. Version 0.1.0 accepts only RSA-signed `inventory_refresh` commands targeted to its own device UUID. Expired, altered, incorrectly targeted, and unknown commands are rejected and reported.

For a collection-only diagnostic that does not contact the server:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\ProgramData\OpsBridge\Agent\OpsBridge.Agent.ps1" -OutputPath "$env:TEMP\opsbridge-snapshot.json"
```

## Safety Boundary

Version 0.1.0 is inventory-only. It does not execute PowerShell, CMD, installers, patches, restarts, or uninstalls. Each future action type requires its own validation and safety policy before it can be added to the allowlist.

## Remove

Run `uninstall.ps1` as Administrator. Use `-KeepLogs` to preserve local logs.
