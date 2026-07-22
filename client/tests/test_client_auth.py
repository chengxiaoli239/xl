import base64
import json
import os
import sys
import tempfile
import unittest
import uuid
from pathlib import Path
from unittest.mock import Mock, patch

os.environ.setdefault("QT_QPA_PLATFORM", "offscreen")

from PyQt5 import QtWidgets

from xy_client.services.auth.client_auth import ClientAuth, ClientAuthError
from xy_client.services.auth.credential_cache import CredentialCache
from xy_client.services.auth.login_dialog import AccountSelectionDialog, authenticate_client
from xy_client.services.auth.account_process import launch_client_process
from xy_client.services.tools.Configs import Configs
from xy_client.services.tools.ClientFriendlyAccountManager import ClientFriendlyAccountManager
from xy_client.launcher import configure_account_environment, configure_standard_streams


class ReconfigurableStream:
    def __init__(self):
        self.options = None

    def reconfigure(self, **options):
        self.options = options


class ConsoleEncodingTest(unittest.TestCase):
    def test_unencodable_console_characters_are_escaped(self):
        stdout = ReconfigurableStream()
        stderr = ReconfigurableStream()

        with patch.object(sys, "stdout", stdout), patch.object(sys, "stderr", stderr):
            configure_standard_streams()

        self.assertEqual(stdout.options, {"errors": "backslashreplace"})
        self.assertEqual(stderr.options, {"errors": "backslashreplace"})


class CredentialCacheTest(unittest.TestCase):
    def test_round_trip_and_clear(self):
        with tempfile.TemporaryDirectory() as directory:
            cache = CredentialCache(Path(directory) / "credentials.dat")
            cache.save("https://backend.example.com/", "demo", "token-value")

            loaded = cache.load()
            self.assertEqual(loaded["robot_domain"], "https://backend.example.com")
            self.assertEqual(loaded["username"], "demo")
            self.assertEqual(loaded["access_token"], "token-value")
            self.assertTrue(loaded["account_key"])

            cache.clear()
            self.assertEqual(cache.load(), {})

    def test_legacy_single_account_cache_is_migrated(self):
        with tempfile.TemporaryDirectory() as directory:
            cache = CredentialCache(Path(directory) / "credentials.dat")
            legacy = json.dumps({
                "robot_domain": "https://backend.example.com",
                "username": "legacy-user",
                "access_token": "legacy-token",
            }).encode("utf-8")
            cache.path.write_bytes(base64.b64encode(cache._protect(legacy)))

            loaded = cache.load()
            self.assertEqual(loaded["username"], "legacy-user")
            self.assertEqual(len(cache.list_accounts()), 1)

            cache.set_active(loaded["account_key"])
            encrypted = base64.b64decode(cache.path.read_bytes())
            migrated = json.loads(cache._unprotect(encrypted).decode("utf-8"))
            self.assertEqual(migrated["version"], 2)

    def test_login_result_saves_and_removes_multiple_accounts(self):
        with tempfile.TemporaryDirectory() as directory:
            cache = CredentialCache(Path(directory) / "credentials.dat")
            profiles = cache.save_login_result(
                "https://backend.example.com/",
                "admin",
                {
                    "access_token": "token-one",
                    "accounts": [
                        {"id": 1, "username": "client-one", "access_token": "token-one"},
                        {"id": 2, "username": "client-two", "access_token": "token-two"},
                    ],
                },
            )

            self.assertEqual(len(cache.list_accounts()), 2)
            self.assertNotEqual(profiles[0]["account_key"], profiles[1]["account_key"])
            self.assertNotIn("password", cache.path.read_text(encoding="ascii"))

            cache.remove(profiles[0]["account_key"])
            remaining = cache.list_accounts()
            self.assertEqual(len(remaining), 1)
            self.assertEqual(remaining[0]["access_token"], "token-two")


class AccountSelectionDialogTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.app = QtWidgets.QApplication.instance() or QtWidgets.QApplication([])

    def test_saved_accounts_are_available_for_selection(self):
        with tempfile.TemporaryDirectory() as directory:
            cache = CredentialCache(Path(directory) / "credentials.dat")
            cache.save_login_result(
                "https://backend.example.com",
                "admin",
                {
                    "accounts": [
                        {"id": 1, "username": "client-one", "access_token": "one"},
                        {"id": 2, "username": "client-two", "access_token": "two"},
                    ]
                },
            )
            dialog = AccountSelectionDialog(
                "https://backend.example.com",
                cache=cache,
            )

            self.assertEqual(dialog.account_list.count(), 2)
            self.assertTrue(dialog.start_button.isEnabled())
            dialog.close()

    @patch("xy_client.services.auth.login_dialog.ClientAuth")
    def test_selected_account_uses_configured_server(self, auth_class):
        with tempfile.TemporaryDirectory() as directory:
            cache = CredentialCache(Path(directory) / "credentials.dat")
            profile = cache.save(
                "http://old.example.com",
                "admin",
                "token-value",
                account={"id": 1, "username": "client-one"},
            )
            auth_class.return_value.validate.return_value = {
                "account": {"id": 1, "username": "client-one"}
            }
            dialog = AccountSelectionDialog(
                "http://18.163.69.56:8090/",
                cache=cache,
            )

            dialog._start_selected()

            auth_class.assert_called_once_with("http://18.163.69.56:8090")
            self.assertEqual(
                cache.get_account(profile["account_key"])["robot_domain"],
                "http://18.163.69.56:8090",
            )
            self.assertEqual(dialog.result(), QtWidgets.QDialog.Accepted)


class AccountProcessTest(unittest.TestCase):
    @patch("xy_client.services.auth.account_process.subprocess.Popen")
    def test_selected_account_is_passed_to_new_process(self, popen):
        with patch.object(sys, "frozen", False, create=True), patch.dict(
            os.environ,
            {
                "LUCKY5_ACCESS_TOKEN": "old-token",
                "LUCKY5_ACCOUNT_ID": "old-account",
                "LUCKY5_ROBOT_DOMAIN": "http://old.example.com",
                "LUCKY5_IS_LOCAL_BET": "1",
                "LUCKY5_IS_AUTO_LOGIN": "0",
                "LUCKY5_IS_AUTO_BET": "1",
            },
            clear=True,
        ):
            launch_client_process("new-account")

        command = popen.call_args.args[0]
        options = popen.call_args.kwargs
        self.assertEqual(command[-2:], ["-m", "xy_client.launcher"])
        self.assertEqual(options["env"]["LUCKY5_ACCOUNT_KEY"], "new-account")
        self.assertNotIn("LUCKY5_ACCESS_TOKEN", options["env"])
        self.assertNotIn("LUCKY5_ACCOUNT_ID", options["env"])
        self.assertNotIn("LUCKY5_ROBOT_DOMAIN", options["env"])
        self.assertNotIn("LUCKY5_IS_LOCAL_BET", options["env"])
        self.assertNotIn("LUCKY5_IS_AUTO_LOGIN", options["env"])
        self.assertNotIn("LUCKY5_IS_AUTO_BET", options["env"])


class LauncherEnvironmentTest(unittest.TestCase):
    def test_account_switches_are_scoped_to_selected_account(self):
        auth_data = {
            "robot_domain": "http://18.163.69.56:8090",
            "access_token": "token-value",
            "account_key": "account-key",
            "username": "admin",
            "display_name": "admin / client-one",
            "account": {
                "is_local_bet": 1,
                "is_auto_login": 0,
                "is_auto_bet": 1,
            },
        }

        with patch.dict(os.environ, {}, clear=True):
            values = configure_account_environment(auth_data)

            self.assertEqual(os.environ["LUCKY5_IS_LOCAL_BET"], "1")
            self.assertEqual(os.environ["LUCKY5_IS_AUTO_LOGIN"], "0")
            self.assertEqual(os.environ["LUCKY5_IS_AUTO_BET"], "1")
            self.assertEqual(values["LUCKY5_ACCOUNT_KEY"], "account-key")


class AccountInstanceLockTest(unittest.TestCase):
    def test_same_account_cannot_acquire_two_instance_locks(self):
        account_id = "test-" + uuid.uuid4().hex
        first = ClientFriendlyAccountManager()
        second = ClientFriendlyAccountManager()
        try:
            self.assertTrue(first._acquire_instance_lock(account_id))
            self.assertFalse(second._acquire_instance_lock(account_id))
        finally:
            first._release_instance_lock()
            second._release_instance_lock()

    def test_different_accounts_can_run_together(self):
        first = ClientFriendlyAccountManager()
        second = ClientFriendlyAccountManager()
        try:
            self.assertTrue(first._acquire_instance_lock("first-" + uuid.uuid4().hex))
            self.assertTrue(second._acquire_instance_lock("second-" + uuid.uuid4().hex))
        finally:
            first._release_instance_lock()
            second._release_instance_lock()

    def test_hidden_instance_can_be_restarted_after_user_confirmation(self):
        manager = ClientFriendlyAccountManager()
        manager._current_account_id = "hidden-account"
        manager._running_accounts = {
            "hidden-account": {"pid": 12345, "executable": "Lucky5_Debug.exe"}
        }

        with patch.object(
            manager, "_acquire_instance_lock", side_effect=[False, True]
        ), patch.object(
            manager, "_confirm_restart_hidden_instance", return_value=True
        ), patch.object(
            manager, "_close_existing_process", return_value=True
        ) as close_process, patch.object(
            manager, "_save_account_configs"
        ), patch.object(
            manager, "_register_current_process"
        ) as register_process:
            account_id = manager.check_and_register_account()

        self.assertEqual(account_id, "hidden-account")
        close_process.assert_called_once_with(12345, "Lucky5_Debug.exe")
        register_process.assert_called_once()


class ConfigTest(unittest.TestCase):
    @patch("xy_client.services.tools.Configs.application_dir")
    def test_defaults_allow_single_executable_distribution(self, application_dir):
        with tempfile.TemporaryDirectory() as directory:
            application_dir.return_value = Path(directory)
            config = Configs()

        self.assertEqual(config.get_config("robot_domain"), "http://18.163.69.56:8090")
        self.assertEqual(config.get_runtime_mode(), "background")

    @patch("xy_client.services.tools.Configs.application_dir")
    def test_deployment_config_can_specify_server(self, application_dir):
        with tempfile.TemporaryDirectory() as directory:
            application_dir.return_value = Path(directory)
            (Path(directory) / "systemConfigs.conf").write_text(
                "[system_configs]\nrobot_domain = http://custom-server:8090/\n",
                encoding="utf-8",
            )
            config = Configs()

        self.assertEqual(config.get_config("robot_domain"), "http://custom-server:8090/")


class LoginBootstrapTest(unittest.TestCase):
    @patch("xy_client.services.auth.login_dialog.ClientAuth")
    @patch("xy_client.services.auth.login_dialog.CredentialCache")
    def test_configured_server_replaces_old_cached_server(self, cache_class, auth_class):
        cache = cache_class.return_value
        profile = {
            "account_key": "account-key",
            "robot_domain": "http://47.107.58.222:8090",
            "username": "demo",
            "access_token": "token-value",
            "account": {"id": 1, "username": "client-one"},
            "display_name": "demo / client-one",
        }
        cache.list_accounts.return_value = [profile]
        cache.update_account.return_value = dict(
            profile,
            robot_domain="http://18.163.69.56:8090",
        )
        auth_class.return_value.validate.return_value = {"account": {"id": 1}}

        with patch.dict(os.environ, {}, clear=True):
            data = authenticate_client("http://18.163.69.56:8090/")

        auth_class.assert_called_once_with("http://18.163.69.56:8090")
        cache.update_account.assert_called_once_with(
            "account-key",
            robot_domain="http://18.163.69.56:8090",
            account={"id": 1},
        )
        self.assertEqual(data["robot_domain"], "http://18.163.69.56:8090")

    @patch("xy_client.services.auth.login_dialog.ClientAuth")
    @patch("xy_client.services.auth.login_dialog.CredentialCache")
    def test_requested_account_uses_configured_server(self, cache_class, auth_class):
        cache = cache_class.return_value
        profile = {
            "account_key": "requested-account",
            "robot_domain": "http://old.example.com",
            "username": "demo",
            "access_token": "token-value",
            "account": {"id": 1},
            "display_name": "demo",
        }
        cache.get_account.return_value = profile
        cache.update_account.return_value = dict(
            profile,
            robot_domain="http://18.163.69.56:8090",
        )
        auth_class.return_value.validate.return_value = {"account": {"id": 1}}

        with patch.dict(
            os.environ,
            {"LUCKY5_ACCOUNT_KEY": "requested-account"},
            clear=True,
        ):
            data = authenticate_client("http://18.163.69.56:8090/")

        auth_class.assert_called_once_with("http://18.163.69.56:8090")
        cache.update_account.assert_called_once_with(
            "requested-account",
            robot_domain="http://18.163.69.56:8090",
            account={"id": 1},
        )
        self.assertEqual(data["account_key"], "requested-account")


class ClientAuthTest(unittest.TestCase):
    @patch("xy_client.services.auth.client_auth.requests.post")
    def test_login_posts_backend_credentials(self, post):
        response = Mock()
        response.json.return_value = {
            "status": 200,
            "data": {"access_token": "token-value"},
        }
        post.return_value = response

        data = ClientAuth("https://backend.example.com/").login(" demo ", "password")

        self.assertEqual(data["access_token"], "token-value")
        post.assert_called_once_with(
            "https://backend.example.com/api/client-auth/login",
            json={"username": "demo", "password": "password"},
            timeout=(4, 12),
        )

    @patch("xy_client.services.auth.client_auth.requests.post")
    def test_login_surfaces_backend_error(self, post):
        response = Mock()
        response.json.return_value = {"status": 401, "msg": "后台账号或密码错误"}
        post.return_value = response

        with self.assertRaises(ClientAuthError) as error:
            ClientAuth("https://backend.example.com").login("demo", "bad-password")

        self.assertEqual(error.exception.status, 401)

    @patch("xy_client.services.auth.client_auth.requests.post")
    def test_enable_local_betting_uses_current_access_token(self, post):
        response = Mock()
        response.json.return_value = {
            "status": 200,
            "data": {
                "account": {
                    "is_local_bet": 1,
                    "is_auto_login": 0,
                    "is_auto_bet": 1,
                }
            },
        }
        post.return_value = response

        data = ClientAuth("http://18.163.69.56:8090/").enable_local_betting(
            "token-value"
        )

        self.assertEqual(data["account"]["is_local_bet"], 1)
        post.assert_called_once_with(
            "http://18.163.69.56:8090/api/client-auth/enable-local-betting",
            json={"access_token": "token-value"},
            timeout=(4, 12),
        )


if __name__ == "__main__":
    unittest.main()
