import json
import sys
import tempfile
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

from xy_client.services.auth.client_auth import ClientAuth, ClientAuthError
from xy_client.services.auth.credential_cache import CredentialCache
from xy_client.services.auth.login_dialog import authenticate_client
from xy_client.services.tools.Configs import Configs
from xy_client.launcher import configure_standard_streams


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

            self.assertEqual(cache.load(), {
                "robot_domain": "https://backend.example.com",
                "username": "demo",
                "access_token": "token-value",
            })

            cache.clear()
            self.assertEqual(cache.load(), {})


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
        cache.load.return_value = {
            "robot_domain": "http://47.107.58.222:8090",
            "username": "demo",
            "access_token": "token-value",
        }
        auth_class.return_value.validate.return_value = {"account": {"id": 1}}

        data = authenticate_client("http://18.163.69.56:8090/")

        auth_class.assert_called_once_with("http://18.163.69.56:8090")
        cache.save.assert_called_once_with(
            "http://18.163.69.56:8090", "demo", "token-value"
        )
        self.assertEqual(data["robot_domain"], "http://18.163.69.56:8090")


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


if __name__ == "__main__":
    unittest.main()
