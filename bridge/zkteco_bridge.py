"""
ZKTeco -> TOS attendance bridge.

Runs on a Windows PC on the SAME LOCAL NETWORK as the ZKTeco terminal
(the device only talks Ethernet/LAN + USB, it has no cloud/ADMS push of
its own). This script:

  1. Connects to the device over the LAN (TCP port 4370 by default).
  2. Reads its punch logs.
  3. Sends any punches not sent before to the TOS app's HTTPS API.
  4. Remembers what it already sent (bridge_state.json) so re-runs don't
     resubmit old punches. The server also de-duplicates by
     device+PIN+timestamp, so it's safe even if this file is lost.

Intended to run on a schedule (every 2-5 minutes) via Windows Task
Scheduler -- see README.md in this folder for setup steps.

Requirements (install once):
    pip install pyzk requests
"""

import json
import logging
import sys
from datetime import datetime
from pathlib import Path

import requests
from zk import ZK

# ---------------------------------------------------------------------------
# Configuration is loaded from bridge_config.json next to this script.
# Run the script once with no config file present and it will create a
# template for you to fill in.
# ---------------------------------------------------------------------------

SCRIPT_DIR = Path(__file__).resolve().parent
CONFIG_PATH = SCRIPT_DIR / "bridge_config.json"
STATE_PATH = SCRIPT_DIR / "bridge_state.json"
LOG_PATH = SCRIPT_DIR / "bridge.log"

DEFAULT_CONFIG = {
    "device_ip": "192.168.1.201",
    "device_port": 4370,
    "device_password": 0,
    "force_udp": False,
    "api_url": "https://yourdomain.com/api/zkteco/attendance",
    "api_token": "PASTE_THE_TOKEN_FROM_BIOMETRIC_DEVICES_PAGE_HERE",
    "request_timeout_seconds": 15,
}

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_PATH, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
log = logging.getLogger("zkteco_bridge")


def load_config() -> dict:
    if not CONFIG_PATH.exists():
        CONFIG_PATH.write_text(json.dumps(DEFAULT_CONFIG, indent=2), encoding="utf-8")
        log.error(
            "No bridge_config.json found -- created a template at %s. "
            "Fill in device_ip and api_token, then run this script again.",
            CONFIG_PATH,
        )
        sys.exit(1)

    config = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))

    if config.get("api_token", "").startswith("PASTE_THE_TOKEN"):
        log.error("bridge_config.json still has the placeholder api_token. Edit it first.")
        sys.exit(1)

    return config


def load_state() -> dict:
    if STATE_PATH.exists():
        return json.loads(STATE_PATH.read_text(encoding="utf-8"))
    return {"last_synced_timestamp": None}


def save_state(state: dict) -> None:
    STATE_PATH.write_text(json.dumps(state, indent=2, default=str), encoding="utf-8")


def fetch_punches(config: dict):
    zk = ZK(
        config["device_ip"],
        port=config.get("device_port", 4370),
        timeout=10,
        password=config.get("device_password", 0),
        force_udp=config.get("force_udp", False),
        ommit_ping=False,
    )

    conn = None
    try:
        conn = zk.connect()
        conn.disable_device()  # pause the device's keypad while we read, avoids write races
        records = conn.get_attendance()
        return records
    finally:
        if conn is not None:
            try:
                conn.enable_device()
                conn.disconnect()
            except Exception:  # noqa: BLE001 - best effort cleanup
                pass


def to_attlog_line(record) -> str:
    # PIN, "Y-m-d H:i:s", punch-type (0 check-in / 1 check-out / ...), verify-method
    ts = record.timestamp
    if isinstance(ts, datetime):
        ts = ts.strftime("%Y-%m-%d %H:%M:%S")
    punch = getattr(record, "punch", 0) or 0
    return f"{record.user_id}\t{ts}\t{punch}\t1"


def main() -> None:
    config = load_config()
    state = load_state()

    last_synced = state.get("last_synced_timestamp")
    last_synced_dt = datetime.fromisoformat(last_synced) if last_synced else None

    log.info("Connecting to device at %s:%s ...", config["device_ip"], config.get("device_port", 4370))

    try:
        records = fetch_punches(config)
    except Exception:
        log.exception("Could not read attendance from the device. Is it powered on and reachable on the LAN?")
        sys.exit(1)

    log.info("Device returned %d total log entries.", len(records))

    new_records = [
        r for r in records
        if last_synced_dt is None or r.timestamp > last_synced_dt
    ]

    if not new_records:
        log.info("Nothing new since last sync (%s). Done.", last_synced or "never")
        return

    new_records.sort(key=lambda r: r.timestamp)

    # The server caps a single request at 2000 lines (see ZkBridgeController).
    # A device that's never synced before (or was offline a long time) can
    # easily hold more than that, so send it in batches -- and save the
    # watermark after each successful batch, so a failure partway through
    # doesn't force re-sending punches that already made it in.
    BATCH_SIZE = 1000
    batches = [new_records[i:i + BATCH_SIZE] for i in range(0, len(new_records), BATCH_SIZE)]

    for batch_num, batch in enumerate(batches, start=1):
        lines = [to_attlog_line(r) for r in batch]

        log.info(
            "Sending batch %d/%d (%d punch(es)) to %s ...",
            batch_num, len(batches), len(lines), config["api_url"],
        )

        try:
            response = requests.post(
                config["api_url"],
                json={"lines": lines},
                headers={"Authorization": f"Bearer {config['api_token']}"},
                timeout=config.get("request_timeout_seconds", 15),
            )
            response.raise_for_status()
        except requests.RequestException:
            log.exception(
                "Failed to reach the TOS app on batch %d/%d. Will retry from here on next "
                "scheduled run without losing data already sent.",
                batch_num, len(batches),
            )
            sys.exit(1)

        result = response.json()
        log.info("Server accepted: %s", result)

        # Only advance the watermark after a confirmed successful upload.
        state["last_synced_timestamp"] = batch[-1].timestamp.isoformat()
        save_state(state)

    log.info("Sync complete. Watermark advanced to %s.", state["last_synced_timestamp"])


if __name__ == "__main__":
    main()
