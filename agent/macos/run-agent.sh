#!/usr/bin/env bash
set -euo pipefail

if [ ! -f /etc/opsbridge-agent/config.json ]; then
  echo "OpsBridge agent is not configured. Run: sudo opsbridge-agent-setup" >&2
  exit 0
fi

if ! command -v python3 >/dev/null 2>&1; then
  echo "python3 is required to run the OpsBridge agent." >&2
  exit 1
fi

exec "$(command -v python3)" /opt/opsbridge-agent/opsbridge_agent.py
