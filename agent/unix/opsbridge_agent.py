#!/usr/bin/env python3
import argparse
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
import urllib.request
import uuid

AGENT_VERSION = "0.1.0-unix"
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
            "Content-Type": "application/json",
            "X-Agent-Version": AGENT_VERSION,
        },
        method="POST",
    )
    context = ssl.create_default_context()
    with urllib.request.urlopen(request, timeout=120, context=context) as response:
        return json.loads(response.read().decode("utf-8"))


def main():
    parser = argparse.ArgumentParser(description="OpsBridge macOS/Linux device agent")
    parser.add_argument("--config", default=DEFAULT_CONFIG)
    parser.add_argument("--once", action="store_true")
    args = parser.parse_args()

    config = load_config(args.config)
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

    response = send_snapshot(config, payload)
    if response.get("device_api_key"):
        config["token"] = response["device_api_key"]
        config["device_uuid"] = payload["device_uuid"]
        save_config(args.config, config)
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
