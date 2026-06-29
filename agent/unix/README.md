# OpsBridge macOS and Linux Device Agent

This package installs the OpsBridge inventory agent for macOS and Linux devices.

## Requirements

- Python 3
- Root/admin privileges for installation
- HTTPS access to the portal API endpoint

## Install

Create an enrollment token in the portal, then run:

```bash
sudo ./install.sh \
  --endpoint "https://your-domain.example/api/v1/agent/check-in" \
  --token "ops_agent_..." \
  --interval 60
```

Optional employee and asset hints:

```bash
sudo ./install.sh \
  --endpoint "https://your-domain.example/api/v1/agent/check-in" \
  --token "ops_agent_..." \
  --asset-tag "LAP-001" \
  --employee-email "employee@company.com" \
  --interval 60
```

Employee self-service setup tokens are already linked to the employee account, so employee email/code is optional in that flow.

## What It Collects

- Device UUID, hostname, serial number, OS, version, and architecture
- Basic hardware summary
- Network adapter identifiers
- Installed applications/packages

On Linux, package discovery uses `dpkg-query` or `rpm` when available. On macOS, app discovery reads `.app` bundles from `/Applications` and `~/Applications`.

## Scheduling

- Linux: installs a `systemd` timer named `opsbridge-agent.timer`
- macOS: installs a LaunchDaemon named `com.opsbridge.agent`

After the first successful check-in, the server replaces the enrollment token with a unique device API key.

## Remove

```bash
sudo ./uninstall.sh
```

Configuration remains in `/etc/opsbridge-agent` so logs and credentials can be reviewed before manual deletion.
