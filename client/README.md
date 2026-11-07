# Lucky5 desktop client

The client authenticates with the same username and password as the backend. The
password is never written to disk. After a successful login, the selected local
betting account token is cached with Windows DPAPI under `%LOCALAPPDATA%\Lucky5`
or in the macOS Keychain. macOS migrates an older local cache into Keychain on
first use and removes the old cache file after a successful migration.

## Runtime modes

- `background` (default): restores the platform cookie from the backend and sends
  betting requests directly from the customer's computer. Chrome and a driver are
  not started.
- `browser`: starts Chrome only when a platform login must be renewed. With
  `chromedriver_path = auto` (the default), the client uses Chrome DevTools
  Protocol and does not download or require ChromeDriver. Chrome is detected in
  common per-user, system, registry, PATH, and portable installation locations.
  An explicit `chromedriver_path` switches to the legacy Selenium mode.

If the background session expires, select `打开浏览器登录` in the client and click
the member login button. A successful browser login uploads the refreshed cookie,
after which the client can return to background mode.

For a four-fixed import plan, the backend can explicitly select `盘口 TXT 导入`.
The plan's TXT file is read in the browser and its four-digit numbers are saved
with the plan; the plan's single-bet amount applies to every number. Only local
client accounts can enable this mode. The client uploads a generated TXT to the
platform for preview, checks every number and amount, then submits one
`BatchBet` request. An unknown confirmation result is never retried automatically.
Existing import plans keep their original request path.

## Configuration

The executable includes the current backend address and defaults to background
mode. An optional `systemConfigs.conf` beside the executable can override defaults
for a special deployment:

```ini
robot_domain = https://backend.example.com
runtime_mode = background
```

Do not put an account token or backend password in that file.

The `后台地址` field in the login window can also select a server. A successful
login saves that address for later launches. `LUCKY5_ROBOT_DOMAIN` is available
as an environment override for managed deployments.

## Build

From the repository root:

```powershell
python -m pip install -r .\client\requirements.txt
python .\client\xy_client\build.py --clean --name Lucky5
```

For startup diagnostics, build a console variant without replacing the release
executable:

```powershell
python .\client\xy_client\build.py --name Lucky5_Debug --console
```

Distribute `client/dist/Lucky5-Windows.zip`. It contains one `Lucky5/` folder
with `Lucky5.exe` and its build manifest. The customer extracts the folder and
starts the EXE; neither Python nor a separate configuration file is required.
The uncompressed EXE remains available for existing deployments.

On macOS, run the build with Python 3.11 on a Mac matching the target CPU. The
script records the architecture and produces one of these packages:

```bash
python client/xy_client/build.py --clean --name Lucky5 --architecture arm64
# client/dist/Lucky5-arm64-macOS.zip

python client/xy_client/build.py --clean --name Lucky5 --architecture x86_64
# client/dist/Lucky5-x86_64-macOS.zip
```

`--architecture auto` (the default) uses the current Python/runner
architecture. The script rejects a mismatched request, so an Intel package must
be built on an Intel macOS runner (or an Intel Python under Rosetta). CI uses
the `macos-15-intel` runner for that package. Each ZIP
contains `Lucky5/Lucky5.app`; choose the Apple Silicon package for M-series Macs
and the Intel package for Intel Macs. Distribution to other Macs requires Apple
Developer ID signing and notarization to avoid Gatekeeper blocking the downloaded
app; a local ad-hoc build is for testing only.

## Windows release and S3 publication

Build and publish on a Windows machine with the project dependencies installed.
The build script refuses to run on macOS or Linux because PyInstaller does not
cross-compile a Windows executable:

```powershell
python -m pip install -r .\client\requirements.txt
python .\client\xy_client\build.py --clean --name Lucky5
```

For an S3 release, set `AWS_S3_CLIENT_BUCKET` and optionally
`AWS_S3_CLIENT_REGION`, `AWS_S3_CLIENT_PREFIX`, and
`AWS_S3_CLIENT_BASE_URL`, then run:

```powershell
.\client\publish-windows-client.ps1
```

If Windows builds the EXE remotely, retrieve both `Lucky5.exe` and
`Lucky5-build.json` into the same directory on macOS, then publish and verify
them from the Mac:

```bash
AWS_S3_CLIENT_BUCKET=msll-app-releases-289069331089 \
AWS_S3_CLIENT_BASE_URL=https://d2syxruhedjsow.cloudfront.net \
bash ./client/publish-built-client.sh /path/to/Lucky5.exe
```

The Windows publisher uses `lottery_xl/windows`; the package publisher uses
`lottery_xl/macos` for macOS and `lottery_xl/windows` for Windows. The
CloudFront origin must be allowed to read the chosen prefix before publishing.

For a verified folder ZIP, publish from the matching source commit. Run the
command once for each architecture:

```bash
AWS_S3_CLIENT_BUCKET=msll-app-releases-289069331089 \
AWS_S3_CLIENT_BASE_URL=https://d2syxruhedjsow.cloudfront.net \
bash ./client/publish-package.sh client/dist/Lucky5-arm64-macOS.zip
# or: bash ./client/publish-package.sh client/dist/Lucky5-x86_64-macOS.zip
```

The package publisher verifies the ZIP's source manifest, S3 metadata and
CloudFront download bytes. It writes the Mac package under `lottery_xl/macos/`
and the Windows package under `lottery_xl/windows/`; CloudFront must have read
permission for the chosen prefix. The backend reads
`LUCKY5_CLIENT_MAC_ARM64_ZIP_URL`, `LUCKY5_CLIENT_MAC_X86_64_ZIP_URL`, and
`LUCKY5_CLIENT_WINDOWS_ZIP_URL` for platform downloads. The personal
information page shows separate Apple Silicon and Intel links when those URLs
are configured. The legacy `LUCKY5_CLIENT_MAC_ZIP_URL` remains supported for a
single existing Mac package. Until a Mac URL is configured, the Mac links stay
hidden.

The script checks the PE header, records the source commit, size, and SHA-256,
uploads both a versioned object and `Lucky5-latest.exe`, and verifies both S3
objects with `head-object`. AWS credentials must come from the local short-lived
AWS CLI session or an approved instance role; no credentials belong in the
repository.

When `LUCKY5_CLIENT_DOWNLOAD_URL` is set on the backend to the verified HTTPS
latest URL, the personal information page downloads from S3 or
CloudFront. Without that setting, the endpoint falls back to
`backend/web/downloads/Lucky5.exe`.

## Backend deployment

Deploy `ClientAuthController.php` and `ClientAuthService.php` before distributing
the executable. The client requires these endpoints:

- `POST /api/client-auth/login`
- `POST /api/client-auth/validate`

Use HTTPS in production because the login request contains the backend password.
