#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(cd "$ROOT_DIR/../.." && pwd)"
VERSION="${VERSION:-0.1.0}"
IDENTIFIER="${IDENTIFIER:-in.opsbridge.agent}"
PKG_NAME="OpsBridge-Agent-Setup.pkg"
BUILD_DIR="$ROOT_DIR/build"
PAYLOAD_ROOT="$BUILD_DIR/root"
COMPONENT_PKG="$BUILD_DIR/OpsBridge-Agent-component.pkg"
DIST_DIR="$ROOT_DIR/dist"
FINAL_PKG="$DIST_DIR/$PKG_NAME"

if [ "$(uname -s)" != "Darwin" ]; then
  echo "This PKG builder must run on macOS." >&2
  exit 1
fi

for tool in pkgbuild productbuild xcrun; do
  if ! command -v "$tool" >/dev/null 2>&1; then
    echo "$tool is required. Install Xcode command line tools first." >&2
    exit 1
  fi
done

rm -rf "$BUILD_DIR"
mkdir -p "$PAYLOAD_ROOT/opt/opsbridge-agent"
mkdir -p "$PAYLOAD_ROOT/usr/local/bin"
mkdir -p "$PAYLOAD_ROOT/Library/LaunchDaemons"
mkdir -p "$DIST_DIR"

cp "$PROJECT_DIR/agent/unix/opsbridge_agent.py" "$PAYLOAD_ROOT/opt/opsbridge-agent/opsbridge_agent.py"
cp "$ROOT_DIR/run-agent.sh" "$PAYLOAD_ROOT/usr/local/bin/opsbridge-agent-run"
cp "$ROOT_DIR/setup-agent.sh" "$PAYLOAD_ROOT/usr/local/bin/opsbridge-agent-setup"
cp "$ROOT_DIR/com.opsbridge.agent.plist" "$PAYLOAD_ROOT/Library/LaunchDaemons/com.opsbridge.agent.plist"

chmod 755 "$ROOT_DIR/scripts/preinstall" "$ROOT_DIR/scripts/postinstall"
chmod 755 "$PAYLOAD_ROOT/opt/opsbridge-agent/opsbridge_agent.py"
chmod 755 "$PAYLOAD_ROOT/usr/local/bin/opsbridge-agent-run"
chmod 755 "$PAYLOAD_ROOT/usr/local/bin/opsbridge-agent-setup"
chmod 644 "$PAYLOAD_ROOT/Library/LaunchDaemons/com.opsbridge.agent.plist"

pkgbuild \
  --root "$PAYLOAD_ROOT" \
  --scripts "$ROOT_DIR/scripts" \
  --identifier "$IDENTIFIER" \
  --version "$VERSION" \
  --install-location / \
  "$COMPONENT_PKG"

if [ -n "${DEVELOPER_ID_INSTALLER:-}" ]; then
  productbuild --sign "$DEVELOPER_ID_INSTALLER" --timestamp --package "$COMPONENT_PKG" "$FINAL_PKG"
else
  echo "DEVELOPER_ID_INSTALLER is not set. Building unsigned PKG for local testing." >&2
  productbuild --package "$COMPONENT_PKG" "$FINAL_PKG"
fi

if [ -n "${NOTARY_PROFILE:-}" ]; then
  xcrun notarytool submit "$FINAL_PKG" --keychain-profile "$NOTARY_PROFILE" --wait
  xcrun stapler staple "$FINAL_PKG"
else
  echo "NOTARY_PROFILE is not set. Skipping notarization." >&2
fi

echo "Built $FINAL_PKG"
