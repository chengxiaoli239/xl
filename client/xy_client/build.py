import argparse
import datetime
import os
import shutil
import subprocess
import sys
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description="Build the Lucky5 Windows client")
    parser.add_argument("--name", default=None, help="Executable name without .exe")
    parser.add_argument("--clean", action="store_true", help="Remove previous build output")
    parser.add_argument("--console", action="store_true", help="Show a console for startup diagnostics")
    args = parser.parse_args()

    client_dir = Path(__file__).resolve().parent
    project_dir = client_dir.parent
    repo_dir = project_dir.parent
    entry_point = client_dir / "launcher.py"
    icon_path = client_dir / "images" / "61.ico"
    output_name = args.name or "Lucky5_" + datetime.datetime.now().strftime("%m%d")
    build_dir = project_dir / "build"
    dist_dir = project_dir / "dist"

    if args.clean:
        for path in (build_dir, dist_dir):
            if path.exists():
                shutil.rmtree(path)

    command = [
        sys.executable,
        "-m",
        "PyInstaller",
        "--noconfirm",
        "--clean",
        "--onefile",
        "--console" if args.console else "--windowed",
        "--name",
        output_name,
        "--icon",
        str(icon_path),
        "--add-data",
        str(icon_path) + os.pathsep + "images",
        "--paths",
        str(project_dir),
        "--distpath",
        str(dist_dir),
        "--workpath",
        str(build_dir),
        "--specpath",
        str(project_dir),
        "--collect-submodules",
        "xy_client",
        str(entry_point),
    ]
    subprocess.run(command, cwd=repo_dir, check=True)

    print("Built:", dist_dir / (output_name + ".exe"))


if __name__ == "__main__":
    main()
