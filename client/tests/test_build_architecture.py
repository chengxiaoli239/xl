import importlib.util
from pathlib import Path
import unittest


BUILD_PATH = Path(__file__).parents[1] / "xy_client" / "build.py"
SPEC = importlib.util.spec_from_file_location("lucky5_build", BUILD_PATH)
BUILD = importlib.util.module_from_spec(SPEC)
assert SPEC.loader is not None
SPEC.loader.exec_module(BUILD)


class BuildArchitectureTests(unittest.TestCase):
    def test_normalizes_supported_mac_architecture_names(self):
        self.assertEqual(BUILD.normalize_macos_architecture("arm64"), "arm64")
        self.assertEqual(BUILD.normalize_macos_architecture("aarch64"), "arm64")
        self.assertEqual(BUILD.normalize_macos_architecture("x86_64"), "x86_64")
        self.assertEqual(BUILD.normalize_macos_architecture("AMD64"), "x86_64")

    def test_rejects_unknown_mac_architecture(self):
        with self.assertRaises(SystemExit):
            BUILD.normalize_macos_architecture("universal2")


if __name__ == "__main__":
    unittest.main()
