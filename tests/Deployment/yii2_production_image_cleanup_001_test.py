#!/usr/bin/env python3
"""YII2-PRODUCTION-IMAGE-001: production artifact contains only Yii2 runtime."""
from __future__ import annotations

import pathlib
import secrets
import shutil
import subprocess

ROOT = pathlib.Path(__file__).resolve().parents[2]
recipes = [ROOT / "tools/delivery/Dockerfile.runtime.in", ROOT / "deploy/runtime/Dockerfile"]
for recipe in recipes:
    source = recipe.read_text(encoding="utf-8")
    assert "rapid-pilot" not in source, f"INTENDED_RED: production recipe retains rapid-pilot: {recipe.relative_to(ROOT)}"

render = subprocess.run(
    ["python3", "tools/delivery/render-dependencies.py", "--check"],
    cwd=ROOT, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30,
)
assert render.returncode == 0, "generated production recipe drift: " + render.stderr
assert shutil.which("docker"), "SETUP_FAILURE: Docker is required"
tag = "fmonitor2-production-cleanup:" + secrets.token_hex(6)
try:
    build = subprocess.run(
        ["docker", "build", "--quiet", "--file", "deploy/runtime/Dockerfile", "--tag", tag, "."],
        cwd=ROOT, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=360,
    )
    assert build.returncode == 0, "SETUP_FAILURE: production image build failed\n" + build.stderr[-3000:]
    inspect = subprocess.run(
        ["docker", "run", "--rm", "--entrypoint", "sh", tag, "-c",
         "test \"$(id -u)\" = 10001 && test ! -e rapid-pilot && test -x bin/yii && "
         "test -f public/runtime.php && test -f vendor/autoload.php && test -f app/YiiRuntime/Assets/pilot.css && "
         "for p in tests reviews specs docs tools .git .local; do test ! -e \"$p\" || exit 41; done && "
         "! find . -path ./vendor -prune -o \\( -name '.env*' -o -name auth.json -o -name '*.dump' "
         "-o -name '*.sql.gz' -o -name '*.bak' -o -name '*.key' -o -name '*.pem' -o -name '*.p12' "
         "-o -name '*.pfx' -o -name '*.pk8' -o -name '*.crt' -o -name '*.cer' -o -name '*.der' "
         "-o -name '*.p7b' -o -name '*.p7c' -o -name '*.msg' \\) -print -quit | grep -q ."],
        text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30,
    )
    assert inspect.returncode == 0, "production image closure failed: " + inspect.stderr
    cli = subprocess.run(
        ["docker", "run", "--rm", "--entrypoint", "php", tag, "bin/yii", "schema-migrate/run", "--interactive=0"],
        text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30,
    )
    assert (cli.returncode, cli.stdout, cli.stderr) == (64, '{"ok":false,"reason":"CONFIGURATION_INVALID"}\n', ""), "packaged Yii console contract drifted"
    live = subprocess.run(
        ["docker", "run", "--rm", "--entrypoint", "sh",
         "--env", "FMONITOR_YII_COOKIE_VALIDATION_KEY=fixture-only-production-image-contract-key-2026",
         tag, "-c",
         "php -S 127.0.0.1:8099 public/runtime.php >/tmp/fm2-live.out 2>/tmp/fm2-live.err & pid=$!; "
         "trap 'kill $pid 2>/dev/null || true' EXIT; n=0; "
         "until curl --silent --output /dev/null --header 'Host: localhost' http://127.0.0.1:8099/health/live; do "
         "n=$((n+1)); test $n -lt 50 || { cat /tmp/fm2-live.err >&2; exit 42; }; sleep 0.1; done; "
         "curl --fail-with-body --silent --show-error --header 'Host: localhost' http://127.0.0.1:8099/health/live"],
        text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30,
    )
    assert live.returncode == 0 and live.stdout == '{"ok":true}\n', "packaged Yii live contract drifted"
finally:
    subprocess.run(["docker", "image", "rm", "--force", tag], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=30)

print("PASS: YII2-PRODUCTION-IMAGE-001 production artifact closure")
