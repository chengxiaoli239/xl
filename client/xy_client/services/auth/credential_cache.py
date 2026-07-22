import base64
import ctypes
import hashlib
import json
import os
import threading
import time
from ctypes import wintypes
from pathlib import Path


class _DataBlob(ctypes.Structure):
    _fields_ = [("cbData", wintypes.DWORD), ("pbData", ctypes.POINTER(ctypes.c_byte))]


class CredentialCache:
    STORE_VERSION = 2

    def __init__(self, path=None):
        base_dir = Path(os.environ.get("LOCALAPPDATA", Path.home())) / "Lucky5"
        self.path = Path(path) if path else base_dir / "credentials.dat"
        self._lock = threading.RLock()

    def load(self):
        """Return the active account for compatibility with the old cache API."""
        store = self._load_store()
        active_key = store.get("active_account", "")
        account = store.get("accounts", {}).get(active_key)
        if account:
            return dict(account)
        accounts = self.list_accounts()
        return dict(accounts[0]) if accounts else {}

    def list_accounts(self):
        store = self._load_store()
        accounts = [dict(item) for item in store.get("accounts", {}).values()]
        return sorted(
            accounts,
            key=lambda item: (
                item.get("account_key") != store.get("active_account"),
                -float(item.get("last_used", 0)),
                item.get("display_name", ""),
            ),
        )

    def get_account(self, account_key):
        account = self._load_store().get("accounts", {}).get(str(account_key or ""))
        return dict(account) if account else {}

    def save(self, robot_domain, username, access_token, account=None):
        profile = self._build_profile(
            robot_domain,
            username,
            access_token,
            account=account,
        )
        self._save_profiles([profile], active_key=profile["account_key"])
        return dict(profile)

    def save_login_result(self, robot_domain, username, auth_data):
        account_rows = auth_data.get("accounts") or []
        if not account_rows and auth_data.get("account"):
            account_rows = [auth_data["account"]]
        if not account_rows:
            account_rows = [{}]

        profiles = []
        for account in account_rows:
            token = account.get("access_token") or auth_data.get("access_token", "")
            if not token:
                continue
            profiles.append(
                self._build_profile(
                    robot_domain,
                    username,
                    token,
                    account=account,
                )
            )

        if not profiles:
            raise ValueError("后台没有返回可用的客户端账号")

        preferred_token = auth_data.get("access_token", "")
        preferred = next(
            (item for item in profiles if item["access_token"] == preferred_token),
            profiles[0],
        )
        self._save_profiles(profiles, active_key=preferred["account_key"])
        return [dict(item) for item in profiles]

    def update_account(self, account_key, **changes):
        with self._lock:
            store = self._load_store()
            account = store.get("accounts", {}).get(account_key)
            if not account:
                return {}
            for key in ("robot_domain", "username", "access_token", "account"):
                if key in changes:
                    account[key] = changes[key]
            account["display_name"] = self._display_name(account)
            account["last_used"] = time.time()
            store["active_account"] = account_key
            self._write_store(store)
            return dict(account)

    def set_active(self, account_key):
        with self._lock:
            store = self._load_store()
            if account_key not in store.get("accounts", {}):
                return False
            store["active_account"] = account_key
            store["accounts"][account_key]["last_used"] = time.time()
            self._write_store(store)
            return True

    def remove(self, account_key):
        with self._lock:
            store = self._load_store()
            removed = store.get("accounts", {}).pop(account_key, None)
            if not removed:
                return False
            if store.get("active_account") == account_key:
                remaining = list(store.get("accounts", {}))
                store["active_account"] = remaining[0] if remaining else ""
            if store.get("accounts"):
                self._write_store(store)
            else:
                self.clear()
            return True

    def clear(self):
        with self._lock:
            try:
                self.path.unlink()
            except FileNotFoundError:
                pass

    def _save_profiles(self, profiles, active_key):
        with self._lock:
            store = self._load_store()
            for profile in profiles:
                existing = store["accounts"].get(profile["account_key"], {})
                existing.update(profile)
                store["accounts"][profile["account_key"]] = existing
            store["active_account"] = active_key
            self._write_store(store)

    def _load_store(self):
        empty = {
            "version": self.STORE_VERSION,
            "active_account": "",
            "accounts": {},
        }
        if not self.path.exists():
            return empty
        try:
            encrypted = base64.b64decode(self.path.read_bytes())
            payload = json.loads(self._unprotect(encrypted).decode("utf-8"))
        except (OSError, ValueError, json.JSONDecodeError):
            return empty

        if payload.get("version") == self.STORE_VERSION and isinstance(
            payload.get("accounts"), dict
        ):
            payload.setdefault("active_account", "")
            return payload

        # Migrate the original single-account payload in memory. It is written
        # in v2 format the next time the account is used or changed.
        if payload.get("access_token"):
            profile = self._build_profile(
                payload.get("robot_domain", ""),
                payload.get("username", ""),
                payload.get("access_token", ""),
                account=payload.get("account") or {},
            )
            empty["active_account"] = profile["account_key"]
            empty["accounts"][profile["account_key"]] = profile
        return empty

    def _write_store(self, store):
        data = json.dumps(store, ensure_ascii=True).encode("utf-8")
        self.path.parent.mkdir(parents=True, exist_ok=True)
        temporary = self.path.with_name(
            f"{self.path.name}.{os.getpid()}.{threading.get_ident()}.tmp"
        )
        temporary.write_bytes(base64.b64encode(self._protect(data)))
        temporary.replace(self.path)

    @classmethod
    def _build_profile(cls, robot_domain, username, access_token, account=None):
        domain = str(robot_domain or "").strip().rstrip("/")
        token = str(access_token or "").strip()
        account = dict(account or {})
        account_key = cls._account_key(domain, token, account)
        profile = {
            "account_key": account_key,
            "robot_domain": domain,
            "username": str(username or "").strip(),
            "access_token": token,
            "account": account,
            "last_used": time.time(),
        }
        profile["display_name"] = cls._display_name(profile)
        return profile

    @staticmethod
    def _account_key(robot_domain, access_token, account):
        identity = str(access_token or "").strip()
        if not identity:
            identity = (
                f"{str(robot_domain).lower().rstrip('/')}|"
                f"id:{account.get('id', '')}"
            )
        value = identity
        return hashlib.sha256(value.encode("utf-8")).hexdigest()[:24]

    @staticmethod
    def _display_name(profile):
        account = profile.get("account") or {}
        parts = []
        for value in (
            profile.get("username"),
            account.get("username"),
            account.get("account"),
            account.get("sys_name"),
        ):
            text = str(value or "").strip()
            if text and text not in parts:
                parts.append(text)
        return " / ".join(parts) or "未命名账号"

    @staticmethod
    def _protect(data):
        if os.name != "nt":
            return data
        return CredentialCache._crypt_protect(data)

    @staticmethod
    def _unprotect(data):
        if os.name != "nt":
            return data
        return CredentialCache._crypt_unprotect(data)

    @staticmethod
    def _blob(data):
        buffer = ctypes.create_string_buffer(data)
        blob = _DataBlob(len(data), ctypes.cast(buffer, ctypes.POINTER(ctypes.c_byte)))
        return blob, buffer

    @staticmethod
    def _crypt_protect(data):
        source, source_buffer = CredentialCache._blob(data)
        output = _DataBlob()
        crypt32 = ctypes.WinDLL("crypt32", use_last_error=True)
        kernel32 = ctypes.WinDLL("kernel32", use_last_error=True)
        crypt32.CryptProtectData.argtypes = [
            ctypes.POINTER(_DataBlob), wintypes.LPCWSTR, ctypes.POINTER(_DataBlob),
            ctypes.c_void_p, ctypes.c_void_p, wintypes.DWORD, ctypes.POINTER(_DataBlob),
        ]
        crypt32.CryptProtectData.restype = wintypes.BOOL
        kernel32.LocalFree.argtypes = [ctypes.c_void_p]
        kernel32.LocalFree.restype = ctypes.c_void_p
        if not crypt32.CryptProtectData(
            ctypes.byref(source), "Lucky5", None, None, None, 0, ctypes.byref(output)
        ):
            raise ctypes.WinError()
        try:
            return ctypes.string_at(output.pbData, output.cbData)
        finally:
            kernel32.LocalFree(output.pbData)

    @staticmethod
    def _crypt_unprotect(data):
        source, source_buffer = CredentialCache._blob(data)
        output = _DataBlob()
        crypt32 = ctypes.WinDLL("crypt32", use_last_error=True)
        kernel32 = ctypes.WinDLL("kernel32", use_last_error=True)
        crypt32.CryptUnprotectData.argtypes = [
            ctypes.POINTER(_DataBlob), ctypes.c_void_p, ctypes.POINTER(_DataBlob),
            ctypes.c_void_p, ctypes.c_void_p, wintypes.DWORD, ctypes.POINTER(_DataBlob),
        ]
        crypt32.CryptUnprotectData.restype = wintypes.BOOL
        kernel32.LocalFree.argtypes = [ctypes.c_void_p]
        kernel32.LocalFree.restype = ctypes.c_void_p
        if not crypt32.CryptUnprotectData(
            ctypes.byref(source), None, None, None, None, 0, ctypes.byref(output)
        ):
            raise ctypes.WinError()
        try:
            return ctypes.string_at(output.pbData, output.cbData)
        finally:
            kernel32.LocalFree(output.pbData)
