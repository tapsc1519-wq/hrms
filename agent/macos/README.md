# OpsBridge macOS PKG Installer

This folder contains the packaging scaffold for a signed and notarized macOS PKG installer.

The PKG build must run on macOS with Xcode command line tools installed. Signing and notarization require an Apple Developer account with a Developer ID Installer certificate.

## Build

From this directory on a Mac:

```bash
chmod +x build-pkg.sh
DEVELOPER_ID_INSTALLER="Developer ID Installer: Your Company (TEAMID)" \
NOTARY_PROFILE="opsbridge-notary" \
./build-pkg.sh
```

The script creates:

```text
agent/macos/dist/OpsBridge-Agent-Setup.pkg
```

Commit or deploy that file to the server if you want the portal to serve the PKG instead of the fallback `.command` installer.

## Apple Notary Profile

Create the notary profile once on the build Mac:

```bash
xcrun notarytool store-credentials opsbridge-notary --apple-id you@example.com --team-id TEAMID --password app-specific-password
```

## Installation Flow

The PKG installs:

- `/opt/opsbridge-agent/opsbridge_agent.py`
- `/usr/local/bin/opsbridge-agent-run`
- `/usr/local/bin/opsbridge-agent-setup`
- `/Library/LaunchDaemons/com.opsbridge.agent.plist`

After installing the PKG, run:

```bash
sudo opsbridge-agent-setup
```

The setup command asks for the portal endpoint and setup token, writes `/etc/opsbridge-agent/config.json`, and starts the LaunchDaemon.
