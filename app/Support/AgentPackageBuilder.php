<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class AgentPackageBuilder
{
    public static function unixInstallerScript(): string
    {
        $agentPath = base_path('agent/unix/opsbridge_agent.py');
        abort_unless(File::isFile($agentPath), 404, 'macOS/Linux agent source is not available.');

        $agentPayload = base64_encode(File::get($agentPath));
        $template = <<<'SH'
#!/usr/bin/env bash
set -euo pipefail

ENDPOINT=""
TOKEN=""
ASSET_TAG=""
EMPLOYEE_CODE=""
EMPLOYEE_EMAIL=""
INTERVAL_MINUTES="60"
ROOT="/opt/opsbridge-agent"
CONFIG_DIR="/etc/opsbridge-agent"

while [ $# -gt 0 ]; do
  case "$1" in
    --endpoint) ENDPOINT="${2:-}"; shift 2 ;;
    --token) TOKEN="${2:-}"; shift 2 ;;
    --asset-tag) ASSET_TAG="${2:-}"; shift 2 ;;
    --employee-code) EMPLOYEE_CODE="${2:-}"; shift 2 ;;
    --employee-email) EMPLOYEE_EMAIL="${2:-}"; shift 2 ;;
    --interval) INTERVAL_MINUTES="${2:-60}"; shift 2 ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this installer with sudo." >&2
  exit 1
fi
if [ -z "$ENDPOINT" ] || [ -z "$TOKEN" ]; then
  echo "Usage: sudo ./OpsBridge-Agent-Installer.sh --endpoint https://example.com/api/v1/agent/check-in --token ops_agent_..." >&2
  exit 1
fi
if ! command -v python3 >/dev/null 2>&1; then
  echo "python3 is required." >&2
  exit 1
fi
PYTHON_BIN="$(command -v python3)"

mkdir -p "$ROOT" "$CONFIG_DIR"
"$PYTHON_BIN" - "$ROOT/opsbridge_agent.py" <<'PY'
import base64
import sys

payload = """__AGENT_PAYLOAD__"""
with open(sys.argv[1], "wb") as handle:
    handle.write(base64.b64decode(payload))
PY
chmod 755 "$ROOT/opsbridge_agent.py"

"$PYTHON_BIN" - "$CONFIG_DIR/config.json" "$ENDPOINT" "$TOKEN" "$ASSET_TAG" "$EMPLOYEE_CODE" "$EMPLOYEE_EMAIL" "$INTERVAL_MINUTES" <<'PY'
import json
import sys

config = {
    "endpoint": sys.argv[2],
    "token": sys.argv[3],
    "asset_tag": sys.argv[4],
    "employee_code": sys.argv[5],
    "employee_email": sys.argv[6],
    "sync_interval_minutes": int(sys.argv[7]),
}
with open(sys.argv[1], "w", encoding="utf-8") as handle:
    json.dump(config, handle, indent=2)
PY
chmod 600 "$CONFIG_DIR/config.json"

OS_NAME="$(uname -s)"
if [ "$OS_NAME" = "Darwin" ]; then
  PLIST="/Library/LaunchDaemons/com.opsbridge.agent.plist"
  cat > "$PLIST" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key><string>com.opsbridge.agent</string>
  <key>ProgramArguments</key>
  <array>
    <string>$PYTHON_BIN</string>
    <string>$ROOT/opsbridge_agent.py</string>
  </array>
  <key>StartInterval</key><integer>$((INTERVAL_MINUTES * 60))</integer>
  <key>RunAtLoad</key><true/>
  <key>StandardOutPath</key><string>/var/log/opsbridge-agent.log</string>
  <key>StandardErrorPath</key><string>/var/log/opsbridge-agent.err</string>
</dict>
</plist>
PLIST
  chmod 644 "$PLIST"
  launchctl unload "$PLIST" >/dev/null 2>&1 || true
  launchctl load "$PLIST"
else
  cat > /etc/systemd/system/opsbridge-agent.service <<SERVICE
[Unit]
Description=OpsBridge Device Agent

[Service]
Type=oneshot
ExecStart=$PYTHON_BIN $ROOT/opsbridge_agent.py
SERVICE
  cat > /etc/systemd/system/opsbridge-agent.timer <<TIMER
[Unit]
Description=Run OpsBridge Device Agent

[Timer]
OnBootSec=1min
OnUnitActiveSec=${INTERVAL_MINUTES}min
Persistent=true

[Install]
WantedBy=timers.target
TIMER
  systemctl daemon-reload
  systemctl enable --now opsbridge-agent.timer
  systemctl start opsbridge-agent.service || true
fi

echo "OpsBridge agent installed. Inventory runs every ${INTERVAL_MINUTES} minutes."
SH;

        return str_replace('__AGENT_PAYLOAD__', $agentPayload, $template);
    }
}
