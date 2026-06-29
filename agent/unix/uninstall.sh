#!/usr/bin/env bash
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this uninstaller with sudo." >&2
  exit 1
fi

OS_NAME="$(uname -s)"
if [ "$OS_NAME" = "Darwin" ]; then
  PLIST="/Library/LaunchDaemons/com.opsbridge.agent.plist"
  launchctl unload "$PLIST" >/dev/null 2>&1 || true
  rm -f "$PLIST"
else
  systemctl disable --now opsbridge-agent.timer >/dev/null 2>&1 || true
  rm -f /etc/systemd/system/opsbridge-agent.service /etc/systemd/system/opsbridge-agent.timer
  systemctl daemon-reload >/dev/null 2>&1 || true
fi

rm -rf /opt/opsbridge-agent
echo "OpsBridge agent removed. Configuration remains in /etc/opsbridge-agent."
