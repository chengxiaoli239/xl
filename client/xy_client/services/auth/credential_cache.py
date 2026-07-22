import base64
import ctypes
import json
import os
from ctypes import wintypes
from pathlib import Path


class _DataBlob(ctypes.Structure):
    _fields_ = [("cbData", wintypes.DWORD), ("pbData", ctypes.POINTER(ctypes.c_byte))]


class CredentialCache:
    def __init__(self, path=None):
        base_dir = Path(os.environ.get("LOCALAPPDATA", Path.home())) / "Lucky5"
        self.path = Path(path) if path else base_dir / "credentials.dat"

    def load(self):
        if not self.path.exists():
            return {}
        try:
            encrypted = base64.b64decode(self.path.read_bytes())
            return json.loads(self._unprotect(encrypted).decode("utf-8"))
        except (OSError, ValueError, json.JSONDecodeError):
            return {}

    def save(self, robot_domain, username, access_token):
        data = json.dumps({
            "robot_domain": robot_domain.rstrip("/"),
            "username": username,
            "access_token": access_token,
        }, ensure_ascii=True).encode("utf-8")
        self.path.parent.mkdir(parents=True, exist_ok=True)
        temporary = self.path.with_suffix(".tmp")
        temporary.write_bytes(base64.b64encode(self._protect(data)))
        temporary.replace(self.path)

    def clear(self):
        try:
            self.path.unlink()
        except FileNotFoundError:
            pass

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
