import json
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, patch

from xy_client.services.Lucky5.core import login_status_monitor, platform_api
from xy_client.services.systems_users import SystemsUsers


class PlatformLoginStatusTest(unittest.TestCase):
    def _window(self):
        return SimpleNamespace(
            browser_cookies="session=local-cookie",
            browser_user_agent="CDP Chrome",
            wp_domain="https://platform.example.com",
        )

    @patch.object(platform_api, "getHeaderData", return_value={})
    @patch.object(platform_api.globalSession, "get")
    def test_fresh_cdp_session_does_not_require_backend_cookie(
        self, get, _get_header_data
    ):
        response = Mock(
            status_code=200,
            text=json.dumps({"Status": 1, "Data": {}}),
            apparent_encoding="utf-8",
        )
        get.return_value = response

        status = platform_api.check_login_status_by_api(self._window())

        self.assertIs(status, True)
        headers = get.call_args.kwargs["headers"]
        self.assertEqual(headers["Cookie"], "session=local-cookie")
        self.assertEqual(headers["Host"], "platform.example.com")
        self.assertEqual(headers["User-Agent"], "CDP Chrome")
        self.assertTrue(headers["Referer"].startswith("https://platform.example.com/"))

    @patch.object(platform_api.globalSession, "get")
    def test_network_failure_is_indeterminate(self, get):
        get.side_effect = TimeoutError("temporary Wi-Fi interruption")

        status = platform_api.check_login_status_by_api(self._window())

        self.assertIsNone(status)

    @patch.object(platform_api.globalSession, "get")
    def test_unauthorized_response_confirms_logged_out(self, get):
        get.return_value = Mock(
            status_code=401,
            text="",
            apparent_encoding="utf-8",
        )

        status = platform_api.check_login_status_by_api(self._window())

        self.assertIs(status, False)


class LoginStatusMonitorTest(unittest.TestCase):
    def _monitor(self):
        window = SimpleNamespace(
            browser_cookies="session=local-cookie",
            is_need_login=1,
        )
        return login_status_monitor.LoginStatusMonitor(window), window

    @patch.object(login_status_monitor, "check_login_status_by_api", return_value=None)
    def test_indeterminate_probe_preserves_logged_in_state(self, _check):
        monitor, window = self._monitor()
        monitor._trigger_auto_login = Mock()

        monitor._check_login_status()

        self.assertEqual(window.is_need_login, 1)
        self.assertEqual(monitor._consecutive_login_failures, 0)
        monitor._trigger_auto_login.assert_not_called()

    @patch.object(login_status_monitor, "check_login_status_by_api", return_value=False)
    def test_one_confirmed_failure_does_not_trigger_login(self, _check):
        monitor, window = self._monitor()
        monitor._trigger_auto_login = Mock()

        monitor._check_login_status()

        self.assertEqual(window.is_need_login, 1)
        self.assertEqual(monitor._consecutive_login_failures, 1)
        monitor._trigger_auto_login.assert_not_called()

    @patch.object(login_status_monitor, "check_login_status_by_api", return_value=False)
    def test_two_confirmed_failures_trigger_login(self, _check):
        monitor, window = self._monitor()
        monitor._trigger_auto_login = Mock()

        monitor._check_login_status()
        monitor._check_login_status()

        self.assertEqual(window.is_need_login, 0)
        monitor._trigger_auto_login.assert_called_once_with(force_login=True)

    @patch.object(login_status_monitor, "check_login_status_by_api")
    def test_success_resets_confirmed_failure_count(self, check):
        monitor, window = self._monitor()
        monitor._trigger_auto_login = Mock()
        check.side_effect = [False, True, False]

        monitor._check_login_status()
        monitor._check_login_status()
        monitor._check_login_status()

        self.assertEqual(window.is_need_login, 1)
        self.assertEqual(monitor._consecutive_login_failures, 1)
        monitor._trigger_auto_login.assert_not_called()

    @patch.object(login_status_monitor, "check_login_status_by_api")
    def test_indeterminate_probe_breaks_failure_sequence(self, check):
        monitor, window = self._monitor()
        monitor._trigger_auto_login = Mock()
        check.side_effect = [False, None, False]

        monitor._check_login_status()
        monitor._check_login_status()
        monitor._check_login_status()

        self.assertEqual(window.is_need_login, 1)
        self.assertEqual(monitor._consecutive_login_failures, 1)
        monitor._trigger_auto_login.assert_not_called()

    def test_legacy_login_job_keeps_cookie_on_indeterminate_probe(self):
        window = SimpleNamespace(
            browser_cookies="session=local-cookie",
            is_need_login=0,
        )
        with patch(
            "xy_client.services.Lucky5.core.platform_api.check_login_status_by_api",
            return_value=None,
        ):
            result = SystemsUsers.checkUserLoginJob(window)

        self.assertTrue(result)
        self.assertEqual(window.is_need_login, 1)


if __name__ == "__main__":
    unittest.main()
