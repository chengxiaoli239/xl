import json
import os
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, patch

os.environ.setdefault("QT_QPA_PLATFORM", "offscreen")

from xy_client.services.systems_users import SystemsUsers


class DrawSyncTest(unittest.TestCase):
    def setUp(self):
        SystemsUsers._global_push_cache = SystemsUsers.PushCache()
        SystemsUsers._push_cache_lock = SystemsUsers._global_push_cache.lock

    @patch.object(SystemsUsers, "getHeaderData")
    @patch.object(SystemsUsers.globalSession, "get")
    def test_get_user_data_uses_cached_cookie_without_driver(self, get, get_header_data):
        get_header_data.return_value = {
            "cookies": "server-cookie=old",
            "v1": "99",
            "v2": "120",
            "Referer": "https://platform.example.com/Member/Index?_",
            "Host": "platform.example.com",
            "user_agent": "test-agent",
        }
        response = Mock()
        response.apparent_encoding = "utf-8"
        get.return_value = response
        window = SimpleNamespace(
            driver=None,
            browser_cookies="session=local-cookie",
            wp_domain="https://platform.example.com",
        )

        actual, _, error = SystemsUsers.getUserData(window)

        self.assertIs(actual, response)
        self.assertEqual(error, "获取成功")
        self.assertEqual(get.call_args.kwargs["headers"]["Cookie"], "session=local-cookie")
        self.assertIn("/Member/GetMemberPrint?_", get.call_args.args[0])

    @patch.object(SystemsUsers, "pushErrorLog")
    @patch.object(SystemsUsers, "pushSyncKjData")
    @patch.object(SystemsUsers, "getUserData")
    @patch(
        "xy_client.services.Lucky5.utils.lottery_time_helper.LotteryTimeHelper.is_draw_time",
        return_value=True,
    )
    def test_background_draw_is_pushed_to_server(
        self,
        _is_draw_time,
        get_user_data,
        push_sync,
        _push_error_log,
    ):
        response = Mock()
        response.text = json.dumps({
            "Status": 1,
            "Data": {
                "previous_period_status": 3,
                "previous_period_no": "20260723001",
                "previous_draw_no": "1,2,3,4,5",
                "period_no": "20260723002",
            },
        })
        get_user_data.return_value = (response, {}, "获取成功")
        push_sync.return_value = {"data": {"num": 1, "refresh": 0}}
        window = SimpleNamespace(
            driver=None,
            browser_cookies="session=local-cookie",
            current_qihao="",
        )

        SystemsUsers.getNowKjDataTimer(window)

        push_sync.assert_called_once_with(
            SystemsUsers.access_token,
            {"expect": "20260723001", "opencode": "1,2,3,4,5"},
            SystemsUsers.lottery_type,
        )
        self.assertEqual(window.current_qihao, "20260723002")

    @patch.object(SystemsUsers, "pushErrorLog")
    @patch.object(SystemsUsers, "pushSyncKjData")
    @patch.object(SystemsUsers, "getUserData")
    @patch(
        "xy_client.services.Lucky5.utils.lottery_time_helper.LotteryTimeHelper.is_draw_time",
        return_value=True,
    )
    def test_closed_browser_falls_back_to_cached_cookie(
        self,
        _is_draw_time,
        get_user_data,
        push_sync,
        _push_error_log,
    ):
        driver = Mock()
        type(driver).current_url = property(
            lambda _driver: (_ for _ in ()).throw(RuntimeError("invalid session id"))
        )
        driver.quit.side_effect = RuntimeError("already closed")
        response = Mock()
        response.text = json.dumps({
            "Status": 1,
            "Data": {
                "previous_period_status": 3,
                "previous_period_no": "20260723003",
                "previous_draw_no": "5,4,3,2,1",
                "period_no": "20260723004",
            },
        })
        get_user_data.return_value = (response, {}, "获取成功")
        push_sync.return_value = {"data": {"num": 1, "refresh": 0}}
        browser_manager = SimpleNamespace(driver=driver)
        window = SimpleNamespace(
            driver=driver,
            browser_window_manager=browser_manager,
            browser_cookies="session=cached-cookie",
            current_qihao="",
        )

        SystemsUsers.getNowKjDataTimer(window)

        self.assertIsNone(window.driver)
        self.assertIsNone(browser_manager.driver)
        get_user_data.assert_called_once_with(
            window, cookies_str="session=cached-cookie"
        )
        push_sync.assert_called_once()


if __name__ == "__main__":
    unittest.main()
