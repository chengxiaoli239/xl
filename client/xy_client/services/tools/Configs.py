import configparser
import os
import sys
from pathlib import Path

from xy_client.services.tools.ChromePathDetector import resolve_chrome_path


DEFAULT_CONFIG = {
    "system_configs": {
        "lottery_type": "8",
        "is_test": "0",
        "robot_domain": "http://18.163.69.56:8090",
        "lt_type": "lucky5",
        "official_site": "https://web01.cc138001.com",
        "runtime_mode": "background",
        "driver_name": "chrome",
        "binary_location": "auto",
        "binary_location_mac": "auto",
        "chromedriver_path": "auto",
        "geckodriver_path": "auto",
        "preferred_browser": "chrome",
        "daily_restart_hour": "7",
        "daily_restart_minute": "45",
        # 每个盘口下注任务的总请求次数（包含首次请求），避免盘口卡顿时重复下注。
        "bet_max_attempts": "2",
        # 确认请求未送达后再次请求前等待秒数。
        "bet_retry_delay": "7",
    }
}


def application_dir():
    if getattr(sys, "frozen", False):
        executable = Path(sys.executable)
        if sys.platform == "darwin":
            bundle = next((parent for parent in executable.parents if parent.suffix == ".app"), None)
            if bundle is not None:
                return bundle.parent
        return executable.resolve().parent
    return Path(__file__).resolve().parents[2]


class Configs:
    def __init__(self):
        self.deployment_config_path = application_dir() / "systemConfigs.conf"
        if getattr(sys, "frozen", False):
            local_app_data = Path(os.environ.get("LOCALAPPDATA", Path.home()))
            self.config_path = local_app_data / "Lucky5" / "systemConfigs.conf"
        else:
            self.config_path = self.deployment_config_path

        self.conf = configparser.ConfigParser()
        self.conf.read_dict(DEFAULT_CONFIG)
        self.conf.read(self.deployment_config_path, encoding="utf-8")
        if self.config_path != self.deployment_config_path:
            self.conf.read(self.config_path, encoding="utf-8")
        self.access_token = self.get_config("access_token")

    def get_config(self, key="lottery_type", section="system_configs", fallback=""):
        env_key = "LUCKY5_" + key.upper()
        if os.environ.get(env_key):
            value = os.environ[env_key]
            if key in ("binary_location", "binary_location_mac"):
                return resolve_chrome_path(value) or ""
            return value

        value = self.conf.get(section, key, fallback=fallback)
        if key in ("binary_location", "binary_location_mac"):
            return resolve_chrome_path(value) or ""
        return value

    def set_config(self, key, value, section="system_configs"):
        if not self.conf.has_section(section):
            self.conf.add_section(section)
        self.conf.set(section, key, str(value))
        self.config_path.parent.mkdir(parents=True, exist_ok=True)

        config_to_write = self.conf
        if self.config_path != self.deployment_config_path:
            config_to_write = configparser.ConfigParser()
            config_to_write.read(self.config_path, encoding="utf-8")
            if not config_to_write.has_section(section):
                config_to_write.add_section(section)
            config_to_write.set(section, key, str(value))

        with self.config_path.open("w", encoding="utf-8") as config_file:
            config_to_write.write(config_file)
        if key == "access_token":
            self.access_token = str(value)
        return True

    def get_runtime_mode(self):
        mode = self.get_config("runtime_mode", fallback="background").strip().lower()
        return mode if mode in ("background", "browser") else "background"
