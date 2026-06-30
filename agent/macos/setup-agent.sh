#!/usr/bin/env bash
set -euo pipefail

ENDPOINT=""
TOKEN=""
ASSET_TAG=""
EMPLOYEE_CODE=""
EMPLOYEE_EMAIL=""
INTERVAL_MINUTES="60"
CONFIG_DIR="/etc/opsbridge-agent"
PLIST="/Library/LaunchDaemons/com.opsbridge.agent.plist"

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
  exec sudo "$0" "$@"
fi

if ! command -v python3 >/dev/null 2>&1; then
  echo "python3 is required. Install Python 3 before configuring the agent." >&2
  exit 1
fi

if [ -z "$ENDPOINT" ]; then
  read -r -p "Agent API Endpoint: " ENDPOINT
fi
if [ -z "$TOKEN" ]; then
  read -r -p "Setup or enrollment token: " TOKEN
fi
if [ -z "$ENDPOINT" ] || [ -z "$TOKEN" ]; then
  echo "Endpoint and token are required." >&2
  exit 1
fi

mkdir -p "$CONFIG_DIR"
"$(command -v python3)" - "$CONFIG_DIR/config.json" "$ENDPOINT" "$TOKEN" "$ASSET_TAG" "$EMPLOYEE_CODE" "$EMPLOYEE_EMAIL" "$INTERVAL_MINUTES" <<'PY'
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
chown root:wheel "$CONFIG_DIR/config.json"
chown root:wheel "$PLIST"
chmod 644 "$PLIST"

launchctl bootout system "$PLIST" >/dev/null 2>&1 || true
launchctl bootstrap system "$PLIST"
launchctl enable system/com.opsbridge.agent
launchctl kickstart -k system/com.opsbridge.agent >/dev/null 2>&1 || true

echo "OpsBridge agent configured. Inventory runs every ${INTERVAL_MINUTES} minutes."
