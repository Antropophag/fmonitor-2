#!/usr/bin/env python3
"""BITRIX-ORDER-DOCUMENT-LINKS-001: local runtime accepts the Disk root in .env."""
import os
import pathlib
import subprocess
import tempfile

ROOT = pathlib.Path(__file__).resolve().parents[2]
NAME = "FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID"

example = (ROOT / ".env.example").read_text(encoding="utf-8")
validator = (ROOT / "tools/delivery/local-runtime-env").read_text(encoding="utf-8")
compose = (ROOT / "deploy/runtime/compose.yaml").read_text(encoding="utf-8")

assert example.count(NAME + "=") == 1, "INTENDED_RED: canonical .env has no Bitrix Disk root"
assert NAME in validator, "INTENDED_RED: local .env rejects the Bitrix Disk root"
assert f'{NAME}: "${{{NAME}:-}}"' in compose

values = {}
for line in example.splitlines():
    if line and not line.startswith("#"):
        key, value = line.split("=", 1)
        values[key] = value.strip("'")
values.update({
    "COMPOSE_PROJECT_NAME": "fm2-local-document-links-test",
    "FMONITOR_DB_PASSWORD": "synthetic-db-password",
    "FMONITOR_MIGRATION_DB_PASSWORD": "synthetic-root-password",
    "FMONITOR_YII_COOKIE_VALIDATION_KEY": "c" * 40,
    "FMONITOR_YII_IDENTITY_KEY": "i" * 40,
    "FMONITOR_INITIAL_OWNER_EMAIL": "owner@example.invalid",
    "FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD": "synthetic-admin-password",
    "FMONITOR_SOURCE_PASSWORD": "synthetic-source-password",
    "FMONITOR_BITRIX_WEBHOOK_URL": "https://example.invalid/rest/1/synthetic-token/",
    "FMONITOR_ERP_PASSWORD": "synthetic-erp-password",
    "FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY": "h" * 40,
    "FMONITOR_SMTP_PASSWORD": "synthetic-smtp-password",
    NAME: "1809812",
})

with tempfile.NamedTemporaryFile("w", encoding="utf-8", delete=False) as handle:
    for key, value in values.items():
        handle.write(f"{key}={value}\n")
    path = handle.name
os.chmod(path, 0o600)
try:
    environment = dict(os.environ)
    environment["FMONITOR_LOCAL_ENV_FILE"] = path
    result = subprocess.run(
        ["bash", "tools/delivery/local-runtime-env", "--validate"],
        cwd=ROOT, env=environment, text=True, capture_output=True, timeout=10,
    )
finally:
    pathlib.Path(path).unlink()
assert (result.returncode, result.stdout, result.stderr) == (0, "", ""), \
    "INTENDED_RED: configured document root is not accepted by the local runtime"

print("PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 local runtime configuration")
