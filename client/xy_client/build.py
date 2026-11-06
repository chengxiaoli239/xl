import argparse
import datetime
import hashlib
import json
import os
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path


def ensure_build_environment():
    if sys.platform not in ("win32", "darwin"):
        raise SystemExit(
            f"Lucky5 builds require Windows or macOS; current platform is {sys.platform}. "
            "PyInstaller does not cross-compile between these platforms."
        )

    try:
        import PyInstaller  # noqa: F401
    except ImportError as exc:
        raise SystemExit(
            "PyInstaller is not installed. Run: "
            "python -m pip install -r client/requirements.txt"
        ) from exc


def main():
    ensure_build_environment()
    parser = argparse.ArgumentParser(description="Build Lucky5 for the current platform")
    parser.add_argument("--name", default=None, help="Application name")
    parser.add_argument("--clean", action="store_true", help="Remove previous build output")
    parser.add_argument("--console", action="store_true", help="Show a console for startup diagnostics")
    args = parser.parse_args()
    if args.console and sys.platform == "darwin":
        parser.error("--console is only supported for Windows builds")

    client_dir = Path(__file__).resolve().parent
    project_dir = client_dir.parent
    repo_dir = project_dir.parent
    entry_point = client_dir / "launcher.py"
    icon_path = client_dir / "images" / "61.ico"
    output_name = args.name or "Lucky5_" + datetime.datetime.now().strftime("%m%d")
    build_dir = project_dir / "build"
    dist_dir = project_dir / "dist"
    commit = subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=repo_dir, text=True
    ).strip()
    dirty_client = subprocess.check_output(
        ["git", "status", "--porcelain", "--", "client"], cwd=repo_dir, text=True
    ).strip()
    if dirty_client:
        raise RuntimeError("Client source has uncommitted changes; commit and verify before building a release")

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
        "--onefile" if sys.platform == "win32" else "--onedir",
        "--console" if args.console else "--windowed",
        "--name",
        output_name,
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
        "--collect-submodules",
        "keyring.backends",
        str(entry_point),
    ]
    if sys.platform == "win32":
        command[command.index("--add-data"):command.index("--add-data")] = ["--icon", str(icon_path)]
    else:
        command[command.index(str(entry_point)):command.index(str(entry_point))] = [
            "--osx-bundle-identifier", "com.lucky5.client"
        ]
    subprocess.run(command, cwd=repo_dir, check=True)

    executable = dist_dir / (output_name + ".exe")
    app_bundle = dist_dir / (output_name + ".app")
    payload = executable if sys.platform == "win32" else app_bundle
    if not payload.exists():
        raise RuntimeError(f"Build output missing: {payload}")
    digest = hashlib.sha256()
    if sys.platform == "win32":
        with executable.open("rb") as built_file:
            if built_file.read(2) != b"MZ":
                raise RuntimeError("The build output is not a Windows PE executable")
            built_file.seek(0)
            for chunk in iter(lambda: built_file.read(1024 * 1024), b""):
                digest.update(chunk)
        manifest = dist_dir / (output_name + "-build.json")
        manifest.write_text(json.dumps({
            "product": "Lucky5",
            "source_commit": commit,
            "size": executable.stat().st_size,
            "sha256": digest.hexdigest(),
        }, indent=2) + "\n", encoding="utf-8")

    package_name = output_name + ("-Windows.zip" if sys.platform == "win32" else "-macOS.zip")
    package = dist_dir / package_name
    with tempfile.TemporaryDirectory(prefix="lucky5-package-") as staging:
        folder = Path(staging) / output_name
        folder.mkdir()
        if sys.platform == "win32":
            shutil.copy2(executable, folder / executable.name)
            shutil.copy2(manifest, folder / manifest.name)
            shutil.make_archive(str(package.with_suffix("")), "zip", staging)
        else:
            shutil.copytree(app_bundle, folder / app_bundle.name, symlinks=True)
            subprocess.run(["ditto", "-c", "-k", "--sequesterRsrc", "--keepParent",
                            str(folder), str(package)], check=True)

    with package.open("rb") as built_package:
        package_digest = hashlib.file_digest(built_package, "sha256").hexdigest()
    package_manifest = dist_dir / (output_name + "-package.json")
    package_manifest.write_text(json.dumps({
        "product": "Lucky5",
        "platform": "windows" if sys.platform == "win32" else "macos",
        "source_commit": commit,
        "file": package.name,
        "size": package.stat().st_size,
        "sha256": package_digest,
    }, indent=2) + "\n", encoding="utf-8")
    print("Built:", payload)
    print("Package:", package)
    print("Package manifest:", package_manifest)


if __name__ == "__main__":
    main()
