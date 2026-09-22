# Mihomo account proxy handoff — 2026-09-22

- Checkout: `/Users/wangyegao/.codex/worktrees/lottery-xl-mihomo-20260921`, branch
  `codex/lottery-xl-mihomo`, base `1d0895ab`. Task changes uncommitted; no push or deployment.
- Production `/www/wwwroot/lt/lottery_xl/xl`: preserve existing edits in
  `deploy/scripts/backup-production.sh`, `install-runtime-dirs.sh`, `restore-target.sh`,
  and `yii`. No live source/config/account/database changes or restarts in proxy work.
  Latest read-only local HTTP check on cp01 returned 302; PHP CLI is 7.4.33.
- Admin UID 1 imports selected nodes from HTTPS Clash YAML direct URL or YAML upload.
  Only `proxies:` entries are imported; raw node URIs/provider-only YAML not supported.
  Loopback manager 17990; immutable per-node listeners 18100–18115; max16 stored/8 running.
  Independent processes and account bindings; intentional same-node sharing allowed.
  No TUN, system proxy, OS routing or DNS modifications. See `deploy/mihomo/README.md`.
- Additive account `proxy_node_port` defaults0; provider4 uses bound port or fails closed.
  Existing providers/scenes retained; supported server-side Lucky5 sites9/10 only.
  Legacy Seven calls for provider4 explicitly stop. No automatic real login or betting.
- Generic retry now identifies exact uid+site, skips ambiguous accounts/provider4, and
  Lucky5 propagates site identity. It never clears a sibling purchased-IP pool.
- Security review fixes: new management paths enforce CSRF and suppress request-body
  logging; subscription HTTPS is DNS-pinned; imported node dial IP is pinned with TLS
  hostname preserved. Registry directory fsync and orphan-port avoidance prevent reuse.
- Verification: PHP32 routing/authorization/fail-closed assertions +10 retry assertions
  pass locally and under cp01 PHP7.4 in isolated `/tmp/xl-mihomo-regression.Ki9oZt`.
  Changed PHP files lint clean. Local Python8 tests pass with actual Mihomo v1.19.31;
  cp01 Python7 input tests pass, core test skipped because Linux core is not installed.
  Real dual-node fixture verifies upstream separation, import/start/failure isolation.
  Synthetic Yii browser verified link preview/import/start/bind/save; local HTTP verifies
  YAML multipart upload and CSRF rejection. No production DB or credentials used.
- Read-only PHP/UI/Python reviews completed, identified blockers fixed. git diff --check
  passes. No actual HK node or authenticated remote portal test yet.
- Deployment outstanding: explicit authorization/window, reviewed release, Linux core,
  service credential/install, additive migration/schema refresh, PHP and workers adopting
  code before account activation. Keep all accounts unchanged initially, then explicitly
  test one selected account after user supplies private YAML and verifies HK egress.
