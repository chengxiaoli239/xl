import unittest
from unittest.mock import Mock

from xy_client.services.tools.BrowserWindowManager import BrowserWindowManager
from xy_client.services.tools.SafeBrowserProcessManager import SafeBrowserProcessManager


class BrowserProcessMonitorTest(unittest.TestCase):
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


if __name__ == "__main__":
    unittest.main()
