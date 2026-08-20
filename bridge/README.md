# ZKTeco → TOS Attendance Bridge

Your ZKTeco terminal only has Ethernet + USB (no cloud/ADMS push), and the
TOS app is hosted online (Hostinger). This script bridges the two: it runs
on a Windows PC on the **same local network as the device**, pulls its punch
logs over the LAN, and pushes them to the app over HTTPS.

## One-time setup

1. **Register the device in TOS first.**
   Log in to the app → **Biometric Devices** → Register Device.
   Give it any Serial Number/name for now (or the real SN from
   *Menu → Comm → Ethernet* on the device) and mark it **Active**.
   Save, then re-open it — you'll see a **Bridge API Token** and
   **Bridge Endpoint URL** on the edit page. Copy both.

2. **Find the device's LAN IP.**
   On the terminal: *Menu → Comm → Ethernet* → note the IP address.
   Make sure the Windows PC that will run this script can reach that IP
   (same network/VLAN, or routed).

3. **Install Python** (3.9+) on that PC if not already installed:
   https://www.python.org/downloads/windows/ — check "Add Python to PATH"
   during install.

4. **Install dependencies.** Open a terminal in this folder and run:
   ```
   pip install -r requirements.txt
   ```

5. **Configure.** Run once:
   ```
   python zkteco_bridge.py
   ```
   It will create `bridge_config.json` and exit. Edit that file:
   - `device_ip` — the IP from step 2
   - `api_url` — the Bridge Endpoint URL from step 1
     (e.g. `https://yourdomain.com/api/zkteco/attendance`)
   - `api_token` — the Bridge API Token from step 1

6. **Test it.**
   ```
   python zkteco_bridge.py
   ```
   Check `bridge.log` in this folder for the result. If it says
   "Server accepted", check the Attendance page in TOS — punches should
   already appear against employees who have a **Biometric PIN** set on
   their user record (Users → edit employee → Biometric PIN = the number
   they enrolled with on the device keypad).

## Run it automatically (Windows Task Scheduler)

1. Open **Task Scheduler** → *Create Task* (not "Basic Task", so you get
   the full options).
2. **General tab**: name it e.g. "ZKTeco Attendance Sync". Check
   "Run whether user is logged on or not".
3. **Triggers tab**: New → *Daily*, recur every 1 day, then check
   "Repeat task every" → **5 minutes**, for a duration of **1 day**.
4. **Actions tab**: New → Action "Start a program" →
   Program/script: the full path to `run_bridge.bat` in this folder.
   Start in: this folder's path.
5. **Conditions/Settings tabs**: uncheck "Start the task only if the
   computer is on AC power" if this is a desktop that's always plugged in.
6. Save. Right-click the task → **Run** to confirm it works, then check
   `bridge.log`.

## Notes

- The script never deletes logs from the device — it only reads them, so
  it's safe to re-run and safe if a run fails partway (nothing is lost,
  and the server ignores exact duplicates automatically).
- It only uploads punches newer than the last successful sync
  (`bridge_state.json`), to keep each run small.
- If the device or the internet is briefly unreachable, the script logs
  the error and exits — the next scheduled run picks up where it left off.
- If you ever need to fully re-sync (e.g. `bridge_state.json` was deleted
  or corrupted), just delete `bridge_state.json` and run again — it's safe
  even to resend everything since the server de-duplicates.
