import hashlib
import os
import platform
import socket

import psutil


PORT_BASE = 10000
PORT_PAIR_COUNT = 20000


def debug_port_for_account(account_id):
    """Return a reusable debug-port pair without crossing account profiles."""
    value = str(account_id or "default_account")
    seed = int(hashlib.sha256(value.encode("utf-8")).hexdigest()[:8], 16)
    start = seed % PORT_PAIR_COUNT

    for offset in range(PORT_PAIR_COUNT):
        pair_index = (start + offset) % PORT_PAIR_COUNT
        port = PORT_BASE + pair_index * 2
        if _debug_port_belongs_to_account(port, value):
            return port
        if _port_is_free(port) and _port_is_free(port + 1):
            return port
    raise RuntimeError("No available Chrome debug port pair")


def browser_profile_dir(account_id, system_name=None):
    """Return the persistent Chrome profile directory for an account."""
    value = str(account_id or "default_account")
    current_system = system_name or platform.system()
    if current_system == "Windows":
        return os.path.join("C:\\.temp", "9222", value)
    return os.path.join("/tmp", "9222", value)


def chrome_launch_arguments(binary_path, debug_port, profile_dir):
    """Build the shared Chrome command used by every browser launcher."""
    return [
        str(binary_path),
        f"--remote-debugging-port={int(debug_port)}",
        "--remote-allow-origins=*",
        f"--user-data-dir={profile_dir}",
        "--no-first-run",
        "--no-default-browser-check",
        "--disable-default-apps",
    ]


def _debug_port_belongs_to_account(port, account_id):
    expected_port = f"--remote-debugging-port={int(port)}"
    expected_profile = browser_profile_dir(account_id).replace('"', '').lower()
    for proc in psutil.process_iter(['name', 'cmdline']):
        try:
            proc_name = str(proc.info.get('name') or '').lower()
            if 'chrome' not in proc_name and 'chromedriver' not in proc_name:
                continue
            cmdline = ' '.join(proc.info.get('cmdline') or [])
            normalized_cmdline = cmdline.replace('"', '').lower()
            if expected_port in normalized_cmdline and expected_profile in normalized_cmdline:
                return True
        except (psutil.NoSuchProcess, psutil.AccessDenied, AttributeError):
            continue
    return False


def _port_is_free(port):
    try:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
            sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            sock.bind(("127.0.0.1", int(port)))
        return True
    except OSError:
        return False
