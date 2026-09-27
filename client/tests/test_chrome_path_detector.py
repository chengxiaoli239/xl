import os
import unittest
from unittest.mock import patch

from xy_client.services.tools.BrowserDetector import BrowserDetector
from xy_client.services.tools.ChromePathDetector import ChromePathDetector, resolve_chrome_path
from xy_client.services.tools.Configs import Configs


class ChromePathDetectorTest(unittest.TestCase):
    def test_windows_detector_checks_per_user_and_program_files_locations(self):
        detector = ChromePathDetector()
        detector.system = "Windows"
        expected = os.path.join(
            r"D:\Users\customer\AppData\Local",
            "Google\\Chrome",
            "Application",
            "chrome.exe",
        )

        with patch.dict(
            os.environ,
            {
                "LOCALAPPDATA": r"D:\Users\customer\AppData\Local",
                "ProgramW6432": r"D:\Program Files",
                "PROGRAMFILES": r"D:\Program Files",
                "PROGRAMFILES(X86)": r"D:\Program Files (x86)",
                "PATH": "",
            },
            clear=False,
        ), patch.object(
            detector,
            "_detect_windows_registry_chrome",
            return_value=[],
        ), patch.object(
            detector,
            "_find_chrome_in_path",
            return_value=[],
        ), patch.object(
            detector,
            "_is_valid_chrome_path",
            side_effect=lambda path: path == expected,
        ):
            self.assertEqual(detector.detect_chrome_paths(), [expected])

    def test_stale_configured_path_falls_back_to_detected_path(self):
        detector = ChromePathDetector()
        detected = r"C:\Users\customer\AppData\Local\Google\Chrome\Application\chrome.exe"

        with patch(
            "xy_client.services.tools.ChromePathDetector.get_chrome_detector",
            return_value=detector,
        ), patch.object(detector, "_is_valid_chrome_path", side_effect=lambda path: path == detected), patch.object(
            detector, "get_best_chrome_path", return_value=detected
        ):
            self.assertEqual(resolve_chrome_path(r"C:\old\chrome.exe"), detected)

    def test_browser_detector_uses_shared_chrome_detector(self):
        detector = BrowserDetector()
        with patch(
            "xy_client.services.tools.BrowserDetector.auto_detect_chrome_path",
            return_value=r"C:\Chrome\chrome.exe",
        ):
            self.assertEqual(
                detector._detect_chrome_windows(),
                (True, r"C:\Chrome\chrome.exe"),
            )

    def test_cached_path_falls_back_when_browser_is_removed(self):
        detector = ChromePathDetector()
        old_path = r"C:\Old\chrome.exe"
        new_path = r"D:\Chrome\chrome.exe"
        detector.detected_paths = [old_path]
        with patch.object(detector, "_is_valid_chrome_path", side_effect=lambda path: path == new_path), patch.object(
            detector, "detect_chrome_paths", return_value=[new_path]
        ):
            self.assertEqual(detector.get_best_chrome_path(), new_path)

    def test_environment_auto_path_is_resolved(self):
        config = Configs()
        with patch.dict(os.environ, {"LUCKY5_BINARY_LOCATION": "auto"}), patch(
            "xy_client.services.tools.Configs.resolve_chrome_path",
            return_value=r"D:\Chrome\chrome.exe",
        ):
            self.assertEqual(config.get_config("binary_location"), r"D:\Chrome\chrome.exe")


if __name__ == "__main__":
    unittest.main()
