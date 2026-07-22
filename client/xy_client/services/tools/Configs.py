import configparser
import os
import sys
from pathlib import Path

from xy_client.services.tools.ChromePathDetector import auto_detect_chrome_path


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
    }
}


def application_dir():
    if getattr(sys, "frozen", False):
        return Path(sys.executable).resolve().parent
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
            return os.environ[env_key]

        value = self.conf.get(section, key, fallback=fallback)
        if key in ("binary_location", "binary_location_mac") and value == "auto":
            return auto_detect_chrome_path() or ""
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
