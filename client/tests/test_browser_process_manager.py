import hashlib
import os
import tempfile
import threading
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, patch

from xy_client.launcher import configure_standard_streams

configure_standard_streams()

from xy_client.LuckyClientOP import MainWindow
from xy_client.services.tools import BrowserPortManager as browser_port_module
from xy_client.services.tools import SafeBrowserProcessManager as safe_process_module
from xy_client.services.tools import account_runtime
from xy_client.services.tools import tools as webdriver_tools
from xy_client.services.Lucky5.core.browser_manager import BrowserManager
from xy_client.services.tools.BrowserWindowManager import BrowserWindowManager
from xy_client.services.tools.SafeBrowserProcessManager import SafeBrowserProcessManager
from xy_client.services.tools.account_runtime import (
    PORT_BASE,
    PORT_PAIR_COUNT,
    browser_profile_dir,
    chrome_launch_arguments,
    debug_port_for_account,
)


class BrowserProcessMonitorTest(unittest.TestCase):
    def test_launcher_forces_selenium_manager_offline(self):
        self.assertEqual(os.environ.get("SE_OFFLINE"), "true")
        self.assertEqual(os.environ.get("SE_AVOID_STATS"), "true")

    def test_auto_driver_mode_never_invokes_selenium_manager(self):
        with patch.object(
            webdriver_tools.config,
            "get_config",
            return_value="auto",
        ), patch.object(webdriver_tools.webdriver, "Chrome") as create_driver:
            driver = webdriver_tools.getDriver("chrome", 12000)

        self.assertIsNone(driver)
        create_driver.assert_not_called()

    def test_cdp_browser_manager_never_attempts_webdriver_recovery(self):
        window = SimpleNamespace(
            runtime_mode="browser",
            _browser_automation_mode="cdp",
            driver=None,
        )
        manager = BrowserManager(window, Mock())

        with patch.object(manager, "_reconnect_to_browser") as reconnect:
            self.assertTrue(manager.check_and_recover_browser_connection())

        reconnect.assert_not_called()

    def test_account_runtime_values_are_stable(self):
        first_port = debug_port_for_account("account-one")
        second_port = debug_port_for_account("account-one")

        self.assertEqual(first_port, second_port)
        self.assertEqual(first_port % 2, 0)
        self.assertGreaterEqual(first_port, PORT_BASE)
        self.assertLess(first_port, PORT_BASE + PORT_PAIR_COUNT * 2)
        self.assertEqual(
            browser_profile_dir("account-one", system_name="Windows"),
            os.path.join(r"C:\.temp", "9222", "account-one"),
        )

    def test_account_runtime_reuses_occupied_port_for_same_profile(self):
        seed = int(hashlib.sha256(b"account-one").hexdigest()[:8], 16)
        expected_port = PORT_BASE + (seed % PORT_PAIR_COUNT) * 2

        with patch.object(
            account_runtime,
            "_debug_port_belongs_to_account",
            side_effect=lambda port, _account: port == expected_port,
        ), patch.object(account_runtime, "_port_is_free", return_value=False):
            self.assertEqual(debug_port_for_account("account-one"), expected_port)

    def test_chrome_arguments_suppress_first_run_prompts(self):
        arguments = chrome_launch_arguments(
            r"C:\Chrome\chrome.exe", 12000, r"C:\.temp\9222\account-one"
        )

        self.assertIn("--no-first-run", arguments)
        self.assertIn("--no-default-browser-check", arguments)
        self.assertIn("--disable-default-apps", arguments)
        self.assertIn("--remote-debugging-port=12000", arguments)

    def test_local_debug_http_does_not_retry_connection_failures(self):
        previous_port_session = browser_port_module._local_http
        previous_process_session = safe_process_module._local_http
        try:
            browser_port_module._local_http = None
            safe_process_module._local_http = None

            port_session = browser_port_module._get_local_http()
            process_session = safe_process_module._get_local_http()

            self.assertEqual(port_session.adapters["http://"].max_retries.total, 0)
            self.assertEqual(process_session.adapters["http://"].max_retries.total, 0)
        finally:
            browser_port_module._local_http = previous_port_session
            safe_process_module._local_http = previous_process_session

    def test_failed_login_keeps_browser_and_cached_cookie(self):
        window = SimpleNamespace(
            is_need_login=2,
            browser_cookies="session=cached",
            closeAllBrowsers=Mock(),
        )

        with patch("xy_client.LuckyClientOP.set_global_login_status"):
            MainWindow.updateLoginStatus(window, False)

        self.assertEqual(window.is_need_login, 0)
        self.assertEqual(window.browser_cookies, "session=cached")
        window.closeAllBrowsers.assert_not_called()

    def test_normal_cleanup_preserves_persistent_profile(self):
        manager = object.__new__(BrowserWindowManager)
        manager.account_id = "account-one"
        manager.driver = None
        manager.port_manager = Mock()
        manager.safe_process_manager = Mock()

        with tempfile.TemporaryDirectory() as directory:
            manager._user_data_dir = directory
            manager.cleanup_browser_resources()
            self.assertTrue(os.path.isdir(directory))

    def test_normal_chrome_children_do_not_trigger_process_cleanup(self):
        manager = object.__new__(BrowserWindowManager)
        manager.browser_type = "chrome"
        manager.safe_process_manager = Mock()
        manager.safe_process_manager.get_managed_processes.return_value = [
            {"pid": index, "name": "chrome.exe"} for index in range(1, 8)
        ]
        manager.safe_process_manager.get_user_browser_processes.return_value = []
        manager.safe_process_manager.is_browser_running.return_value = True

        status = manager.monitor_browser_processes()

        self.assertEqual(status["managed_process_count"], 7)
        self.assertTrue(status["is_healthy"])
        manager.safe_process_manager.kill_managed_processes.assert_not_called()

    def test_stale_recorded_pid_must_still_match_account_profile(self):
        manager = object.__new__(SafeBrowserProcessManager)
        manager.account_id = "account-one"
        manager._user_data_dir = r"C:\.temp\9222\account-one"
        manager._debug_port = 9123
        manager._managed_processes = {12345}
        manager._save_managed_processes = Mock()
        process = Mock()
        process.pid = 12345
        process.is_running.return_value = True
        process.cmdline.return_value = [
            "chrome.exe",
            r"--user-data-dir=C:\Users\Customer\Chrome",
        ]

        self.assertFalse(manager._is_managed_process(process))
        self.assertNotIn(12345, manager._managed_processes)

    def test_untracked_account_chrome_is_discovered_by_profile(self):
        manager = object.__new__(SafeBrowserProcessManager)
        manager.account_id = "account-one"
        manager.browser_type = "chrome"
        manager.browser_process_names = {
            "chrome": ["chrome.exe", "chrome", "chromedriver.exe", "chromedriver"]
        }
        manager._user_data_dir = r"C:\.temp\9222\account-one"
        manager._debug_port = 12000
        manager._managed_processes = set()
        manager._lock = threading.Lock()
        manager._save_managed_processes = Mock()

        process = Mock()
        process.pid = 12345
        process.info = {
            "pid": 12345,
            "name": "chrome.exe",
            "cmdline": [
                "chrome.exe",
                r"--user-data-dir=C:\.temp\9222\account-one",
                "--remote-debugging-port=12000",
            ],
        }
        process.is_running.return_value = True
        process.cmdline.return_value = process.info["cmdline"]
        process.name.return_value = "chrome.exe"

        with patch(
            "xy_client.services.tools.SafeBrowserProcessManager.psutil.process_iter",
            return_value=[process],
        ):
            processes = manager.get_managed_processes()

        self.assertEqual([item["pid"] for item in processes], [12345])
        self.assertIn(12345, manager._managed_processes)


if __name__ == "__main__":
    unittest.main()
