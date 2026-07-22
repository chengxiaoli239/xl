import os
import subprocess
import sys
from pathlib import Path


ACCOUNT_ENV_KEYS = (
    "LUCKY5_ROBOT_DOMAIN",
    "LUCKY5_ACCESS_TOKEN",
    "LUCKY5_ACCOUNT_ID",
    "LUCKY_ACCOUNT_ID",
    "LUCKY5_BACKEND_USERNAME",
    "LUCKY5_ACCOUNT_DISPLAY_NAME",
)


def launch_client_process(account_key=""):
    env = os.environ.copy()
    for key in ACCOUNT_ENV_KEYS:
        env.pop(key, None)

    if account_key:
        env["LUCKY5_ACCOUNT_KEY"] = str(account_key)
    else:
        env.pop("LUCKY5_ACCOUNT_KEY", None)

    if getattr(sys, "frozen", False):
        command = [sys.executable]
        working_dir = str(Path(sys.executable).resolve().parent)
    else:
        command = [sys.executable, "-m", "xy_client.launcher"]
        working_dir = str(Path(__file__).resolve().parents[3])

    creationflags = 0
    if os.name == "nt":
        creationflags = subprocess.CREATE_NEW_PROCESS_GROUP

    return subprocess.Popen(
        command,
        cwd=working_dir,
        env=env,
        creationflags=creationflags,
        close_fds=True,
    )
