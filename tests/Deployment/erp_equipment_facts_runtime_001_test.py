#!/usr/bin/env python3
import os
import pathlib
import subprocess
import tempfile

ROOT = pathlib.Path(__file__).resolve().parents[2]


def read(path: str) -> str:
    return (ROOT / path).read_text(encoding="utf-8")


required = [
    "FMONITOR_ERP_HOST",
    "FMONITOR_ERP_DATABASE",
    "FMONITOR_ERP_USER",
    "FMONITOR_ERP_PASSWORD",
    "FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY",
    "FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS",
    "FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS",
    "FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE",
]
for path in [".env.example", "tools/delivery/local-runtime-env", "tools/delivery/compose.runtime.yaml.in", "deploy/runtime/compose.yaml"]:
    text = read(path)
    for name in required:
        assert name in text, f"INTENDED_RED: {path} carries allowlisted {name}"

for path in [".env.example", "tools/delivery/local-runtime-env", "tools/delivery/compose.runtime.yaml.in", "deploy/runtime/compose.yaml"]:
    text = read(path)
    assert "FMONITOR_ERP_PASSWORD_FILE" not in text, f"INTENDED_RED: {path} no longer requires ERP password file"
    assert "FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY_FILE" not in text, f"INTENDED_RED: {path} no longer requires ERP HMAC file"

template = read("tools/delivery/compose.runtime.yaml.in")
generated = read("deploy/runtime/compose.yaml")
for service in ["jobs-worker:", "jobs-scheduler:"]:
    assert service in template and service in generated
assert 'profiles: ["jobs"]' not in template and 'profiles: ["jobs"]' not in generated, \
    "INTENDED_RED: ordinary Compose contour includes jobs without an opt-in profile"
for artifact in (template, generated):
    common = artifact.split("x-runtime-environment:", 1)[1].split("x-jobs-erp-environment:", 1)[0]
    jobs_erp = artifact.split("x-jobs-erp-environment:", 1)[1].split("services:", 1)[0]
    assert "FMONITOR_ERP_" not in common, "ERP authority is absent from shared HTTP/migration environment"
    jobs_keys = [name for name in required if name.startswith("FMONITOR_ERP_")]
    for key in jobs_keys:
        assert key in jobs_erp, f"jobs-only ERP environment owns {key}"
    for service in ("prepare", "local-integration", "migrate", "php", "web"):
        block = artifact.split(f"  {service}:", 1)[1].split("\n  ", 1)[0]
        assert "jobs-erp-environment" not in block and "FMONITOR_ERP_" not in block, f"{service} receives no ERP authority"
for artifact in (template, generated):
    assert "stage-runtime-secrets:" in artifact and 'user: "0:0"' in artifact
    assert 'entrypoint: ["bin/fmonitor2-stage-runtime-bitrix-config"]' in artifact
    assert artifact.count("FMONITOR_BITRIX_CONFIG_HOST_FILE") == 1, \
        "host Bitrix config is mounted only into the one-shot staging service"
    worker = artifact.split("  jobs-worker:", 1)[1].split("  jobs-scheduler:", 1)[0]
    assert "FMONITOR_BITRIX_CONFIG_HOST_FILE" not in worker
    assert "secrets:/run/fmonitor-secrets" in worker

makefile = read("Makefile")
assert "up --detach --wait php web jobs-worker jobs-scheduler" in makefile, \
    "INTENDED_RED: make up starts the complete pilot operational contour"
assert "jobs/process-health" in makefile, "make up qualifies worker/scheduler process health before success"
assert makefile.index("stage-runtime-secrets") < makefile.index("up --detach --wait php web jobs-worker jobs-scheduler"), \
    "validated Bitrix config reaches the named secret volume before jobs start"
stager = read("bin/fmonitor2-stage-runtime-bitrix-config")
for boundary in ("WorkerConfiguration::fromFile", 'mktemp "$target_directory/', "sync -f", 'chown "$runtime_uid:$runtime_gid"', "chmod 0600", "mv -f"):
    assert boundary in stager, f"runtime Bitrix staging preserves {boundary}"
targets = {}
current = None
for line in makefile.splitlines():
    if line and not line.startswith((" ", "\t")) and line.endswith(":"):
        current = line[:-1]
        targets[current] = []
    elif current is not None:
        targets[current].append(line)
assert "--volumes" not in "\n".join(targets["down"]), "ordinary down path must preserve volumes"
assert "--volumes" in "\n".join(targets["reset"]), "explicit reset remains the only volume-deleting target"

example = read(".env.example")
for forbidden in ["1c-erp-password", "real-password", "production-secret"]:
    assert forbidden not in example.lower(), ".env.example contains placeholders only"

base_values = {
    "COMPOSE_PROJECT_NAME": "fm2-local-erp-test",
    "FMONITOR_RUNTIME_IMAGE": "fmonitor2-runtime:test",
    "FMONITOR_HTTP_PORT": "18093",
    "FMONITOR_DB_NAME": "fmonitor2",
    "FMONITOR_DB_USER": "runtime",
    "FMONITOR_DB_PASSWORD": "synthetic-db-password",
    "FMONITOR_MIGRATION_DB_USER": "root",
    "FMONITOR_MIGRATION_DB_PASSWORD": "synthetic-root-password",
    "FMONITOR_PROCESS_TABLE_PREFIX": "fm2_",
    "FMONITOR_LEGACY_TABLE_PREFIX": "fm2_",
    "FMONITOR_SESSION_INSTANCE": "erp-test",
    "FMONITOR_YII_COOKIE_VALIDATION_KEY": "c" * 40,
    "FMONITOR_YII_IDENTITY_KEY": "i" * 40,
    "FMONITOR_TRUSTED_REQUEST_HOST": "127.0.0.1:18093",
    "FMONITOR_TRUSTED_REQUEST_SCHEME": "http",
    "FMONITOR_INITIAL_OWNER_EMAIL": "owner@example.invalid",
    "FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD": "synthetic-admin-password",
    "FMONITOR_RUNTIME_ENV": "development",
    "FMONITOR_PUBLIC_BASE_URL": "https://fmonitor.example.invalid",
    "FMONITOR_SMTP_HOST": "smtp.example.invalid",
    "FMONITOR_SMTP_PORT": "587",
    "FMONITOR_SMTP_ENCRYPTION": "tls",
    "FMONITOR_SMTP_USERNAME": "fmonitor@example.invalid",
    "FMONITOR_SMTP_PASSWORD": "synthetic-smtp-password",
    "FMONITOR_SMTP_FROM_ADDRESS": "fmonitor@example.invalid",
    "FMONITOR_SMTP_FROM_NAME": "FMonitor",
    "FMONITOR_SMTP_TIMEOUT_SECONDS": "10",
    "FMONITOR_SMTP_VERIFY_PEER": "true",
    "FMONITOR_ERP_HOST": "erp.example.invalid",
    "FMONITOR_ERP_DATABASE": "1c-erp",
    "FMONITOR_ERP_USER": "reader",
    "FMONITOR_ERP_PASSWORD": "synthetic-erp-password",
    "FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY": "h" * 40,
    "FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS": "7",
    "FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS": "500",
    "FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE": "100",
    "FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID": "1809812",
}


def validate(values: dict[str, str]) -> subprocess.CompletedProcess[str]:
    with tempfile.NamedTemporaryFile("w", encoding="utf-8", delete=False) as handle:
        for key, value in values.items():
            handle.write(f"{key}={value}\n")
        path = handle.name
    os.chmod(path, 0o600)
    try:
        env = dict(os.environ)
        env["FMONITOR_LOCAL_ENV_FILE"] = path
        return subprocess.run(["bash", str(ROOT / "tools/delivery/local-runtime-env"), "--validate"],
                              cwd=ROOT, env=env, text=True, capture_output=True, timeout=10)
    finally:
        pathlib.Path(path).unlink()


valid = validate(base_values)
assert valid.returncode == 0 and valid.stdout == "" and valid.stderr == "", \
    "INTENDED_RED: complete direct ERP environment validates without disclosure"
example_values = {}
for line in example.splitlines():
    if not line or line.startswith("#"):
        continue
    key, value = line.split("=", 1)
    example_values[key] = value.strip("'")
example_values.update(base_values)
example_values.update({
    "FMONITOR_SOURCE_PASSWORD": "synthetic-source-password",
    "FMONITOR_BITRIX_WEBHOOK_URL": "https://example.invalid/rest/1/synthetic-token/",
    "FMONITOR_INITIAL_OWNER_EMAIL": "owner@example.invalid",
})
example_result = validate(example_values)
assert example_result.returncode == 0 and example_result.stdout == "" and example_result.stderr == "", \
    "canonical .env.example passes after replacing placeholders"
for name in ["FMONITOR_ERP_PASSWORD", "FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY"]:
    case = dict(base_values)
    case.pop(name)
    result = validate(case)
    assert (result.returncode, result.stdout, result.stderr) == (64, "", "LOCAL_CONFIG_INVALID\n"), \
        f"missing {name} fails closed without disclosure"
    case[name] = ""
    result = validate(case)
    assert (result.returncode, result.stdout, result.stderr) == (64, "", "LOCAL_CONFIG_INVALID\n"), \
        f"empty {name} fails closed without disclosure"
for name, value in [("FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS", "0"),
                    ("FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS", "0"),
                    ("FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE", "0")]:
    case = dict(base_values)
    case[name] = value
    result = validate(case)
    assert (result.returncode, result.stdout, result.stderr) == (64, "", "LOCAL_CONFIG_INVALID\n"), \
        f"invalid bound {name} fails closed"
for changes in [
    {"FMONITOR_RUNTIME_ENV": ""},
    {"FMONITOR_PUBLIC_BASE_URL": "http://fmonitor.example.invalid"},
    {"FMONITOR_SMTP_PORT": "0"},
    {"FMONITOR_SMTP_PORT": "65536"},
    {"FMONITOR_SMTP_ENCRYPTION": "none"},
    {"FMONITOR_SMTP_FROM_ADDRESS": "other@example.invalid"},
    {"FMONITOR_SMTP_TIMEOUT_SECONDS": "0"},
    {"FMONITOR_SMTP_TIMEOUT_SECONDS": "121"},
    {"FMONITOR_SMTP_VERIFY_PEER": "false"},
]:
    case = dict(base_values)
    case.update(changes)
    result = validate(case)
    assert (result.returncode, result.stdout, result.stderr) == (64, "", "LOCAL_CONFIG_INVALID\n"), \
        f"invalid SMTP/runtime configuration fails closed: {tuple(changes)}"
production_test_recipient = dict(base_values)
production_test_recipient.update({"FMONITOR_RUNTIME_ENV": "production", "FMONITOR_SMTP_TEST_RECIPIENT": "test@example.invalid"})
result = validate(production_test_recipient)
assert (result.returncode, result.stdout, result.stderr) == (64, "", "LOCAL_CONFIG_INVALID\n"), \
    "production rejects SMTP test recipient"

parity = subprocess.run(["python3", "tools/delivery/render-dependencies.py", "--check"], cwd=ROOT,
                        text=True, capture_output=True, timeout=30)
assert parity.returncode == 0, f"generated runtime artifacts match canonical templates: {parity.stderr}"

dry_up = subprocess.run(["make", "-n", "up"], cwd=ROOT, text=True, capture_output=True, timeout=10)
assert dry_up.returncode == 0
assert "jobs-worker jobs-scheduler" in dry_up.stdout and "jobs/process-health" in dry_up.stdout, \
    "INTENDED_RED: ordinary deployment starts and qualifies the complete jobs contour"
assert "down --volumes" not in dry_up.stdout, "ordinary state-preserving deployment never deletes volumes"

print("PASS: ERP-EQUIPMENT-FACTS-001 direct env and complete runtime contour")
