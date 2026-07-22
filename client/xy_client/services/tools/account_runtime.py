import hashlib
import socket


PORT_BASE = 10000
PORT_PAIR_COUNT = 20000


def debug_port_for_account(account_id):
    """Return an available, deterministic debug-port pair for an account."""
    value = str(account_id or "default_account")
    seed = int(hashlib.sha256(value.encode("utf-8")).hexdigest()[:8], 16)
    start = seed % PORT_PAIR_COUNT

    for offset in range(PORT_PAIR_COUNT):
        pair_index = (start + offset) % PORT_PAIR_COUNT
        port = PORT_BASE + pair_index * 2
        if _port_is_free(port) and _port_is_free(port + 1):
            return port
    raise RuntimeError("No available Chrome debug port pair")


def _port_is_free(port):
    try:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
            sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            sock.bind(("127.0.0.1", int(port)))
        return True
    except OSError:
        return False
