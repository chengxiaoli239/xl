import os
import sys
from pathlib import Path


def configure_standard_streams():
    for stream in (sys.stdout, sys.stderr):
        if stream is None or not hasattr(stream, "reconfigure"):
            continue
        try:
            stream.reconfigure(errors="backslashreplace")
        except (AttributeError, OSError, TypeError, ValueError):
            pass


configure_standard_streams()

from PyQt5 import QtGui
from PyQt5.QtWidgets import QApplication

from xy_client.services.auth.login_dialog import authenticate_client
from xy_client.services.tools.Configs import Configs


def main():
    app = QApplication.instance() or QApplication(sys.argv)
    resource_root = Path(getattr(sys, "_MEIPASS", Path(__file__).resolve().parent))
    icon_path = resource_root / "images" / "61.ico"
    if icon_path.exists():
        app.setWindowIcon(QtGui.QIcon(str(icon_path)))

    config = Configs()
    auth_data = authenticate_client(config.get_config('robot_domain'), config=config)
    if not auth_data:
        return 0

    os.environ['LUCKY5_ROBOT_DOMAIN'] = auth_data['robot_domain']
    os.environ['LUCKY5_ACCESS_TOKEN'] = auth_data['access_token']

    from xy_client.LuckyClientOP import main as run_client
    run_client(existing_app=app)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
