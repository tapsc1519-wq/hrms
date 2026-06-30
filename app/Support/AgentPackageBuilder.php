<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class AgentPackageBuilder
{
    public static function macosPkgPath(): string
    {
        return base_path('agent/macos/dist/OpsBridge-Agent-Setup.pkg');
    }

    public static function hasMacosPkg(): bool
    {
        return File::isFile(self::macosPkgPath());
    }

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

prompt_value() {
  local title="$1"
  local message="$2"
  local hidden="${3:-false}"
  local value=""
  if [ "$(uname -s)" = "Darwin" ] && command -v osascript >/dev/null 2>&1; then
    if [ "$hidden" = "true" ]; then
      value="$(osascript -e "text returned of (display dialog \"$message\" default answer \"\" with title \"$title\" with hidden answer buttons {\"Cancel\", \"Continue\"} default button \"Continue\")" 2>/dev/null)" || return 1
    else
      value="$(osascript -e "text returned of (display dialog \"$message\" default answer \"\" with title \"$title\" buttons {\"Cancel\", \"Continue\"} default button \"Continue\")" 2>/dev/null)" || return 1
    fi
    printf '%s' "$value"
    return 0
  fi
  return 1
}

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

if [ -z "$ENDPOINT" ]; then
  ENDPOINT="$(prompt_value "OpsBridge Agent Setup" "Enter Agent API Endpoint")" || read -r -p "Agent API Endpoint: " ENDPOINT
fi
if [ -z "$TOKEN" ]; then
  TOKEN="$(prompt_value "OpsBridge Agent Setup" "Enter Setup or Enrollment Token" true)" || read -r -p "Setup or enrollment token: " TOKEN
fi
if [ -z "$ENDPOINT" ] || [ -z "$TOKEN" ]; then
  echo "Endpoint and token are required." >&2
  exit 1
fi
if [ "$(id -u)" -ne 0 ]; then
  if ! command -v sudo >/dev/null 2>&1; then
    echo "Administrator permission is required, but sudo is not available." >&2
    exit 1
  fi
  echo "Administrator permission is required to install the OpsBridge agent."
  exec sudo bash "$0" --endpoint "$ENDPOINT" --token "$TOKEN" --asset-tag "$ASSET_TAG" --employee-code "$EMPLOYEE_CODE" --employee-email "$EMPLOYEE_EMAIL" --interval "$INTERVAL_MINUTES"
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
  COMMAND_PLIST="/Library/LaunchDaemons/com.opsbridge.agent.commands.plist"
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
  cat > "$COMMAND_PLIST" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key><string>com.opsbridge.agent.commands</string>
  <key>ProgramArguments</key>
  <array>
    <string>$PYTHON_BIN</string>
    <string>$ROOT/opsbridge_agent.py</string>
    <string>--commands-only</string>
  </array>
  <key>StartInterval</key><integer>300</integer>
  <key>RunAtLoad</key><true/>
  <key>StandardOutPath</key><string>/var/log/opsbridge-agent-commands.log</string>
  <key>StandardErrorPath</key><string>/var/log/opsbridge-agent-commands.err</string>
</dict>
</plist>
PLIST
  chmod 644 "$PLIST"
  chmod 644 "$COMMAND_PLIST"
  launchctl unload "$PLIST" >/dev/null 2>&1 || true
  launchctl unload "$COMMAND_PLIST" >/dev/null 2>&1 || true
  launchctl load "$PLIST"
  launchctl load "$COMMAND_PLIST"
else
  cat > /etc/systemd/system/opsbridge-agent.service <<SERVICE
[Unit]
Description=OpsBridge Device Agent

[Service]
Type=oneshot
ExecStart=$PYTHON_BIN $ROOT/opsbridge_agent.py
SERVICE
  cat > /etc/systemd/system/opsbridge-agent-commands.service <<SERVICE
[Unit]
Description=OpsBridge Device Agent Command Poller

[Service]
Type=oneshot
ExecStart=$PYTHON_BIN $ROOT/opsbridge_agent.py --commands-only
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
  cat > /etc/systemd/system/opsbridge-agent-commands.timer <<TIMER
[Unit]
Description=Run OpsBridge Device Agent Command Poller

[Timer]
OnBootSec=2min
OnUnitActiveSec=5min
Persistent=true

[Install]
WantedBy=timers.target
TIMER
  systemctl daemon-reload
  systemctl enable --now opsbridge-agent.timer
  systemctl enable --now opsbridge-agent-commands.timer
  systemctl start opsbridge-agent.service || true
  systemctl start opsbridge-agent-commands.service || true
fi

echo "OpsBridge agent installed. Inventory runs every ${INTERVAL_MINUTES} minutes; commands poll every 5 minutes."
SH;

        return str_replace('__AGENT_PAYLOAD__', $agentPayload, $template);
    }
}
