"""
Interactive helper used by install.bat: asks for the device IP and API token
and writes them into bridge_config.json (keeping every other setting).
"""

import json
import sys
from pathlib import Path

CONFIG_PATH = Path(__file__).resolve().parent / "bridge_config.json"

DEFAULT_CONFIG = {
    "device_ip": "192.168.1.201",
    "device_port": 4370,
    "device_password": 0,
    "force_udp": False,
    "api_url": "https://tri-e.online/api/zkteco/attendance",
    "api_token": "PASTE_THE_TOKEN_FROM_BIOMETRIC_DEVICES_PAGE_HERE",
    "request_timeout_seconds": 15,
}


def ask(label: str, current: str) -> str:
    answer = input(f"{label} [{current}]: ").strip().lstrip("﻿")
    return answer or current


def main() -> None:
    config = dict(DEFAULT_CONFIG)
    if CONFIG_PATH.exists():
        config.update(json.loads(CONFIG_PATH.read_text(encoding="utf-8")))

    config["device_ip"] = ask("Device IP address", config["device_ip"])

    token = config["api_token"]
    shown = "not set" if token.startswith("PASTE_THE_TOKEN") else f"...{token[-6:]}"
    config["api_token"] = ask("Bridge API token", shown)
    if config["api_token"] == shown:
        config["api_token"] = token

    if config["api_token"].startswith("PASTE_THE_TOKEN"):
        print("[ERROR] An API token is required. Run install.bat again once you have it.")
        sys.exit(1)

    CONFIG_PATH.write_text(json.dumps(config, indent=2), encoding="utf-8")
    print(f"Saved {CONFIG_PATH.name}.")


if __name__ == "__main__":
    main()
