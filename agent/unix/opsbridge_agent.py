#!/usr/bin/env python3
import argparse
import base64
import hashlib
import json
import os
import platform
import socket
import ssl
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
import xml.etree.ElementTree as ET

AGENT_VERSION = "0.1.1-unix"
DEFAULT_CONFIG = "/etc/opsbridge-agent/config.json"


def run(command, timeout=20):
    try:
        result = subprocess.run(command, capture_output=True, text=True, timeout=timeout)
        return result.stdout.strip() if result.returncode == 0 else ""
    except Exception:
        return ""


def read_first(paths):
    for path in paths:
        try:
            with open(path, "r", encoding="utf-8", errors="ignore") as handle:
                value = handle.read().strip()
                if value:
                    return value
        except Exception:
            pass
    return ""


def load_config(path):
    with open(path, "r", encoding="utf-8") as handle:
        return json.load(handle)


def save_config(path, config):
    tmp_path = path + ".tmp"
    with open(tmp_path, "w", encoding="utf-8") as handle:
        json.dump(config, handle, indent=2)
    os.chmod(tmp_path, 0o600)
    os.replace(tmp_path, path)


def device_uuid():
    system = platform.system().lower()
    if system == "darwin":
        serial = run(["ioreg", "-rd1", "-c", "IOPlatformExpertDevice"])
        for line in serial.splitlines():
            if "IOPlatformUUID" in line:
                return line.split("=")[-1].strip().strip('"').lower()

    machine_id = read_first(["/etc/machine-id", "/var/lib/dbus/machine-id"])
    if machine_id:
        return hashlib.sha256(machine_id.encode()).hexdigest()

    fallback = f"{socket.gethostname()}|{uuid.getnode()}"
    return hashlib.sha256(fallback.encode()).hexdigest()


def os_details():
    system = platform.system()
    if system == "Darwin":
        name = "macOS"
        version = platform.mac_ver()[0] or platform.release()
    else:
        release = {}
        try:
            with open("/etc/os-release", "r", encoding="utf-8", errors="ignore") as handle:
                for line in handle:
                    if "=" in line:
                        key, value = line.strip().split("=", 1)
                        release[key] = value.strip('"')
        except Exception:
            pass
        name = release.get("PRETTY_NAME") or release.get("NAME") or system
        version = release.get("VERSION_ID") or platform.release()

    return name, version, platform.machine()


def serial_number():
    if platform.system() == "Darwin":
        text = run(["system_profiler", "SPHardwareDataType"])
        for line in text.splitlines():
            if "Serial Number" in line:
                return line.split(":", 1)[-1].strip()
    return read_first([
        "/sys/class/dmi/id/product_serial",
        "/sys/class/dmi/id/board_serial",
        "/sys/class/dmi/id/chassis_serial",
    ])


def hardware_info():
    system = platform.system()
    cpu_name = platform.processor() or run(["uname", "-p"])
    manufacturer = ""
    model = ""
    total_memory = 0

    if system == "Darwin":
        text = run(["system_profiler", "SPHardwareDataType"])
        for line in text.splitlines():
            clean = line.strip()
            if clean.startswith("Model Name:"):
                model = clean.split(":", 1)[-1].strip()
            elif clean.startswith("Chip:") or clean.startswith("Processor Name:"):
                cpu_name = clean.split(":", 1)[-1].strip()
            elif clean.startswith("Memory:"):
                total_memory = clean.split(":", 1)[-1].strip()
        manufacturer = "Apple"
    else:
        manufacturer = read_first(["/sys/class/dmi/id/sys_vendor"])
        model = read_first(["/sys/class/dmi/id/product_name"])
        meminfo = read_first(["/proc/meminfo"])
        try:
            with open("/proc/meminfo", "r", encoding="utf-8", errors="ignore") as handle:
                for line in handle:
                    if line.startswith("MemTotal:"):
                        total_memory = int(line.split()[1]) * 1024
                        break
        except Exception:
            pass

    return {
        "manufacturer": manufacturer or None,
        "model": model or None,
        "device_type": "laptop" if system == "Darwin" or os.path.isdir("/sys/class/power_supply/BAT0") else "desktop",
        "cpu": {"name": cpu_name or None},
        "memory": {"total_bytes": total_memory if isinstance(total_memory, int) else None, "label": total_memory if isinstance(total_memory, str) else None},
        "uptime_minutes": int(time.time() - ps_boot_time()) // 60,
    }


def ps_boot_time():
    if platform.system() == "Darwin":
        text = run(["sysctl", "-n", "kern.boottime"])
        digits = "".join(ch if ch.isdigit() else " " for ch in text).split()
        return int(digits[0]) if digits else int(time.time())
    try:
        with open("/proc/stat", "r", encoding="utf-8", errors="ignore") as handle:
            for line in handle:
                if line.startswith("btime "):
                    return int(line.split()[1])
    except Exception:
        pass
    return int(time.time())


def network_info():
    adapters = []
    if platform.system() == "Darwin":
        names = run(["networksetup", "-listallhardwareports"])
        current = {}
        for line in names.splitlines():
            if line.startswith("Hardware Port:"):
                current = {"description": line.split(":", 1)[-1].strip()}
            elif line.startswith("Device:"):
                current["name"] = line.split(":", 1)[-1].strip()
            elif line.startswith("Ethernet Address:"):
                current["mac_address"] = line.split(":", 1)[-1].strip()
                adapters.append(current)
    else:
        for name in os.listdir("/sys/class/net") if os.path.isdir("/sys/class/net") else []:
            if name == "lo":
                continue
            adapters.append({
                "name": name,
                "mac_address": read_first([f"/sys/class/net/{name}/address"]),
                "description": name,
            })
    return {"adapters": adapters}


def linux_packages():
    packages = []
    dpkg = run(["dpkg-query", "-W", "-f=${Package}\t${Version}\n"], timeout=60)
    if dpkg:
        for line in dpkg.splitlines():
            name, _, version = line.partition("\t")
            if name:
                packages.append({"raw_name": name, "raw_publisher": "dpkg", "raw_version": version})
        return packages

    rpm = run(["rpm", "-qa", "--qf", "%{NAME}\t%{VERSION}-%{RELEASE}\n"], timeout=60)
    for line in rpm.splitlines():
        name, _, version = line.partition("\t")
        if name:
            packages.append({"raw_name": name, "raw_publisher": "rpm", "raw_version": version})
    return packages


def mac_apps():
    apps = []
    roots = ["/Applications", os.path.expanduser("~/Applications")]
    for root in roots:
        if not os.path.isdir(root):
            continue
        for name in os.listdir(root):
            if not name.endswith(".app"):
                continue
            plist = os.path.join(root, name, "Contents", "Info.plist")
            version = run(["/usr/libexec/PlistBuddy", "-c", "Print CFBundleShortVersionString", plist]) if os.path.isfile(plist) else ""
            publisher = run(["/usr/libexec/PlistBuddy", "-c", "Print NSHumanReadableCopyright", plist]) if os.path.isfile(plist) else ""
            apps.append({
                "raw_name": name[:-4],
                "raw_publisher": publisher[:500] if publisher else "macOS app",
                "raw_version": version,
                "install_path": os.path.join(root, name),
            })
    return apps


def software_inventory():
    if platform.system() == "Darwin":
        return mac_apps()[:5000]
    return linux_packages()[:5000]


def send_snapshot(config, payload):
    endpoint = config["endpoint"].rstrip("/")
    token = config["token"]
    data = json.dumps(payload).encode("utf-8")
    request = urllib.request.Request(
        endpoint,
        data=data,
        headers={
            "Authorization": f"Bearer {token}",
            "Accept": "application/json",
            "Content-Type": "application/json; charset=utf-8",
            "User-Agent": f"OpsBridge-Agent/{AGENT_VERSION}",
            "X-Agent-Version": AGENT_VERSION,
        },
        method="POST",
    )
    context = ssl.create_default_context()
    with urllib.request.urlopen(request, timeout=120, context=context) as response:
        return json.loads(response.read().decode("utf-8"))


def api_request(url, token, method="GET", payload=None, timeout=60):
    data = json.dumps(payload).encode("utf-8") if payload is not None else None
    headers = {
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        "User-Agent": f"OpsBridge-Agent/{AGENT_VERSION}",
        "X-Agent-Version": AGENT_VERSION,
    }
    if payload is not None:
        headers["Content-Type"] = "application/json; charset=utf-8"
    request = urllib.request.Request(url, data=data, headers=headers, method=method)
    context = ssl.create_default_context()
    with urllib.request.urlopen(request, timeout=timeout, context=context) as response:
        return json.loads(response.read().decode("utf-8"))


def verify_command_signature(command, public_key_xml, device_uuid_value):
    try:
        if command.get("device_uuid") != device_uuid_value:
            return False
        if int(command.get("expires_at") or 0) <= int(time.time()):
            return False

        root = ET.fromstring(public_key_xml)
        modulus = int.from_bytes(base64.b64decode(root.findtext("Modulus") or ""), "big")
        exponent = int.from_bytes(base64.b64decode(root.findtext("Exponent") or ""), "big")
        signature = base64.b64decode(command.get("signature") or "")
        key_bytes = (modulus.bit_length() + 7) // 8
        verified = pow(int.from_bytes(signature, "big"), exponent, modulus).to_bytes(key_bytes, "big")
        canonical = "|".join([
            str(command.get("command_uuid") or ""),
            str(command.get("device_uuid") or ""),
            str(command.get("command_type") or ""),
            str(int(command.get("issued_at") or 0)),
            str(int(command.get("expires_at") or 0)),
            str(command.get("payload_base64") or ""),
        ])
        digest_info = bytes.fromhex("3031300d060960864801650304020105000420") + hashlib.sha256(canonical.encode("utf-8")).digest()
        return verified.startswith(b"\x00\x01") and verified.endswith(digest_info) and b"\x00" in verified[2:-len(digest_info)]
    except Exception:
        return False


def command_payload(command):
    encoded = command.get("payload_base64") or ""
    if not encoded:
        return {}
    return json.loads(base64.b64decode(encoded).decode("utf-8") or "{}")


def send_command_result(poll_url, token, device_uuid_value, command_uuid, status, message):
    url = poll_url.rstrip("/") + "/" + urllib.parse.quote(command_uuid) + "/result"
    return api_request(url, token, "POST", {
        "device_uuid": device_uuid_value,
        "status": status,
        "result": {"message": message},
        "error_message": message if status == "failed" else None,
    })


def lock_session():
    system = platform.system()
    if system == "Darwin":
        user = run(["stat", "-f", "%Su", "/dev/console"])
        if not user or user == "root":
            raise RuntimeError("No active macOS console user was found to lock.")
        uid = run(["id", "-u", user])
        if not uid:
            raise RuntimeError(f"Could not resolve uid for macOS console user {user}.")
        result = subprocess.run([
            "launchctl", "asuser", uid,
            "/System/Library/CoreServices/Menu Extras/User.menu/Contents/Resources/CGSession",
            "-suspend",
        ], capture_output=True, text=True, timeout=20)
        if result.returncode != 0:
            raise RuntimeError((result.stderr or result.stdout or "macOS rejected the lock request.").strip())
        return "The active macOS session was locked."

    for command in (["loginctl", "lock-sessions"], ["dm-tool", "lock"]):
        try:
            result = subprocess.run(command, capture_output=True, text=True, timeout=20)
            if result.returncode == 0:
                return "The active Linux session lock was requested."
        except Exception:
            pass
    raise RuntimeError("No supported Linux session lock command was available.")


def restart_device(payload):
    delay = max(1, min(60, int(payload.get("delay_minutes") or 1)))
    message = str(payload.get("message") or "Administrator-requested restart.")[:180]
    if platform.system() == "Darwin":
        command = ["shutdown", "-r", f"+{delay}", message]
    else:
        command = ["shutdown", "-r", f"+{delay}", message]
    result = subprocess.run(command, capture_output=True, text=True, timeout=20)
    if result.returncode != 0:
        raise RuntimeError((result.stderr or result.stdout or "The operating system rejected the restart request.").strip())
    return f"Restart scheduled in {delay} minutes."


def invoke_agent_commands(config):
    poll_url = config.get("command_poll_url")
    public_key_xml = config.get("command_signing_public_key_xml")
    device_uuid_value = config.get("device_uuid")
    token = config.get("token")
    if not poll_url or not public_key_xml or not device_uuid_value or not token:
        return

    separator = "&" if "?" in poll_url else "?"
    response = api_request(poll_url + separator + "device_uuid=" + urllib.parse.quote(device_uuid_value), token)
    for command in response.get("commands", []):
        command_uuid = str(command.get("command_uuid") or "")
        if not verify_command_signature(command, public_key_xml, device_uuid_value):
            send_command_result(poll_url, token, device_uuid_value, command_uuid, "failed", "Command signature, device target, or expiry validation failed.")
            continue
        try:
            payload = command_payload(command)
            command_type = str(command.get("command_type") or "")
            if command_type == "inventory_refresh":
                message = "Inventory snapshot completed during this agent run."
            elif command_type == "lock_session":
                message = lock_session()
            elif command_type == "restart_device":
                message = restart_device(payload)
            else:
                raise RuntimeError(f"Command type '{command_type}' is not supported by agent {AGENT_VERSION}.")
            send_command_result(poll_url, token, device_uuid_value, command_uuid, "completed", message)
        except Exception as exc:
            send_command_result(poll_url, token, device_uuid_value, command_uuid, "failed", str(exc))


def main():
    parser = argparse.ArgumentParser(description="OpsBridge macOS/Linux device agent")
    parser.add_argument("--config", default=DEFAULT_CONFIG)
    parser.add_argument("--once", action="store_true")
    parser.add_argument("--commands-only", action="store_true")
    args = parser.parse_args()

    config = load_config(args.config)
    if args.commands_only:
        invoke_agent_commands(config)
        return

    os_name, os_version, arch = os_details()
    payload = {
        "device_uuid": config.get("device_uuid") or device_uuid(),
        "hostname": socket.gethostname(),
        "serial_number": serial_number() or None,
        "asset_tag": config.get("asset_tag") or None,
        "employee_code": config.get("employee_code") or None,
        "employee_email": config.get("employee_email") or None,
        "os_name": os_name,
        "os_version": os_version,
        "architecture": arch,
        "agent_version": AGENT_VERSION,
        "sync_interval_minutes": int(config.get("sync_interval_minutes") or 60),
        "hardware": hardware_info(),
        "network": network_info(),
        "security": {},
        "user": {"login": os.environ.get("USER") or os.environ.get("LOGNAME")},
        "snapshot_complete": True,
        "software": software_inventory(),
    }

    try:
        response = send_snapshot(config, payload)
    except urllib.error.HTTPError as exc:
        if exc.code != 406:
            raise
        sys.stderr.write("Server security filter blocked full inventory; retrying device enrollment without software inventory.\n")
        payload["software"] = []
        payload["snapshot_complete"] = False
        response = send_snapshot(config, payload)

    if response.get("device_api_key"):
        config["token"] = response["device_api_key"]
        config["device_uuid"] = payload["device_uuid"]
    if response.get("command_poll_url"):
        config["command_poll_url"] = response["command_poll_url"]
    if response.get("command_signing_public_key_xml"):
        config["command_signing_public_key_xml"] = response["command_signing_public_key_xml"]
    save_config(args.config, config)
    invoke_agent_commands(config)
    print(json.dumps({"message": response.get("message"), "device_agent_id": response.get("device_agent_id")}))


if __name__ == "__main__":
    try:
        main()
    except urllib.error.HTTPError as exc:
        sys.stderr.write(exc.read().decode("utf-8", errors="ignore") + "\n")
        sys.exit(1)
    except Exception as exc:
        sys.stderr.write(str(exc) + "\n")
        sys.exit(1)
