import argparse
import datetime
import hashlib
import json
import os
import shutil
import subprocess
import sys
from pathlib import Path


def ensure_windows_build_environment():
    """Fail early instead of producing a non-Windows binary named .exe."""
    if sys.platform != "win32":
        raise SystemExit(
            "Lucky5 Windows build must run on Windows. "
            "PyInstaller does not cross-compile a Windows EXE from "
            f"{sys.platform}; run this script on a Windows build host."
        )

    try:
        import PyInstaller  # noqa: F401
    except ImportError as exc:
        raise SystemExit(
            "PyInstaller is not installed. Run: "
            "python -m pip install -r client\\requirements.txt"
        ) from exc


def main():
    ensure_windows_build_environment()
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

    executable = dist_dir / (output_name + ".exe")
    digest = hashlib.sha256()
    with executable.open("rb") as built_file:
        if built_file.read(2) != b"MZ":
            raise RuntimeError("The build output is not a Windows PE executable")
        built_file.seek(0)
        for chunk in iter(lambda: built_file.read(1024 * 1024), b""):
            digest.update(chunk)
    checksum = digest.hexdigest()

    commit = subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=repo_dir, text=True
    ).strip()
    dirty_client = subprocess.check_output(
        ["git", "status", "--porcelain", "--", "client"], cwd=repo_dir, text=True
    ).strip()
    if dirty_client:
        raise RuntimeError("Client source has uncommitted changes; cannot label build with a source commit")

    manifest = dist_dir / (output_name + "-build.json")
    manifest.write_text(json.dumps({
        "product": "Lucky5",
        "source_commit": commit,
        "size": executable.stat().st_size,
        "sha256": checksum,
    }, indent=2) + "\n", encoding="utf-8")
    print("Built:", executable)
    print("Manifest:", manifest)


if __name__ == "__main__":
    main()
