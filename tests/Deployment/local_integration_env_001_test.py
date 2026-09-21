#!/usr/bin/env python3
"""LOCAL-INTEGRATION-ENV-001 A1-A3/A6: one .env and fail-closed public seams."""
import json
import os
import shutil
import subprocess
import tempfile
from pathlib import Path

root = Path(__file__).resolve().parents[2]
tool = root / "tools/delivery/local-integration-config"
template = (root / ".env.example").read_text()
required = (
    "FMONITOR_SOURCE_HOST", "FMONITOR_SOURCE_PORT", "FMONITOR_SOURCE_NAME",
    "FMONITOR_SOURCE_USER", "FMONITOR_SOURCE_PASSWORD", "FMONITOR_MIGRATION_CUTOFF",
    "FMONITOR_BITRIX_WEBHOOK_URL", "FMONITOR_BITRIX_DEPARTMENT_IDS_JSON",
)
for name in required:
    assert template.count(name + "=") == 1, f"INTENDED_RED: .env.example missing exact {name}"
for forbidden in ("SECRET_TOKEN", "PRIVATE_PASSWORD", "shlz.ru/rest/"):
    assert forbidden not in template

legacy_secret = "LEGACY_CANARY_149_DO_NOT_LEAK"
bitrix_secret = "BITRIX_CANARY_149_DO_NOT_LEAK"
base = """FMONITOR_SOURCE_HOST=legacy-db.example
FMONITOR_SOURCE_PORT=43149
FMONITOR_SOURCE_NAME=SOURCE_DATABASE_CANARY_149
FMONITOR_SOURCE_USER=SOURCE_USER_CANARY_149
FMONITOR_SOURCE_PASSWORD='{legacy}'
FMONITOR_MIGRATION_CUTOFF=2026-09-17 00:00:00
FMONITOR_BITRIX_WEBHOOK_URL='https://portal.example/rest/7/{bitrix}/'
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[72,71]'
""".format(legacy=legacy_secret, bitrix=bitrix_secret)

with tempfile.TemporaryDirectory() as raw:
    box = Path(raw)
    env_file = box / ".env"
    env_file.write_text(base)
    env_file.chmod(0o600)

    def stage(kind: str, text: str = base):
        env_file.write_text(text)
        destination = box / ".local" / ("legacy-source.env" if kind == "legacy" else "bitrix-workforce.json")
        result = subprocess.run(
            [str(tool), "stage", kind, str(env_file), str(destination)],
            text=True, capture_output=True,
            env={**os.environ, "FMONITOR_SOURCE_PASSWORD": "AMBIENT_LEGACY", "FMONITOR_BITRIX_WEBHOOK_URL": "AMBIENT_BITRIX"},
        )
        return result, destination

    env_file.unlink()
    missing = subprocess.run([str(tool), "stage", "legacy", str(env_file), str(box / ".local/legacy-source.env")],
                             text=True, capture_output=True)
    assert missing.returncode == 64 and "LOCAL_INTEGRATION_CONFIG_INVALID" in missing.stdout + missing.stderr
    env_file.write_text(base); env_file.chmod(0o600)

    legacy, legacy_path = stage("legacy")
    assert legacy.returncode == 0, (legacy.stdout, legacy.stderr)
    assert legacy_path.read_text() == (
        "FMONITOR_SOURCE_HOST=legacy-db.example\nFMONITOR_SOURCE_PORT=43149\n"
        "FMONITOR_SOURCE_NAME=SOURCE_DATABASE_CANARY_149\nFMONITOR_SOURCE_USER=SOURCE_USER_CANARY_149\n"
        f"FMONITOR_SOURCE_PASSWORD='{legacy_secret}'\n"
        "FMONITOR_MIGRATION_CUTOFF=2026-09-17 00:00:00\n"
    )
    bitrix, bitrix_path = stage("bitrix")
    assert bitrix.returncode == 0, (bitrix.stdout, bitrix.stderr)
    document = json.loads(bitrix_path.read_text())
    assert document == {"baseUrl": f"https://portal.example/rest/7/{bitrix_secret}", "departments": [72, 71]}

    # Existing legacy consumer grammar preserves values containing either quote kind.
    single_quote_password = base.replace(f"FMONITOR_SOURCE_PASSWORD='{legacy_secret}'", 'FMONITOR_SOURCE_PASSWORD="a\'b"')
    only_single, single_path = stage("legacy", single_quote_password)
    assert only_single.returncode == 0 and 'FMONITOR_SOURCE_PASSWORD="a\'b"\n' in single_path.read_text()
    double_quote_password = base.replace(f"FMONITOR_SOURCE_PASSWORD='{legacy_secret}'", "FMONITOR_SOURCE_PASSWORD='a\"b'")
    only_double, double_path = stage("legacy", double_quote_password)
    assert only_double.returncode == 0 and "FMONITOR_SOURCE_PASSWORD='a\"b'\n" in double_path.read_text()

    token_256 = "A" * 256
    max_token_input = base.replace(bitrix_secret, token_256)
    max_token, max_token_path = stage("bitrix", max_token_input)
    assert max_token.returncode == 0
    assert json.loads(max_token_path.read_text())["baseUrl"] == f"https://portal.example/rest/7/{token_256}"

    # Each standalone seam validates only its own integration set.
    legacy_only = "\n".join(line for line in base.splitlines() if "BITRIX_" not in line) + "\n"
    assert stage("legacy", legacy_only)[0].returncode == 0
    bitrix_only = "\n".join(line for line in base.splitlines() if "SOURCE_" not in line and "MIGRATION_CUTOFF" not in line) + "\n"
    assert stage("bitrix", bitrix_only)[0].returncode == 0

    invalid_legacy = (
        "",
        "\n".join(line for line in base.splitlines() if not line.startswith("FMONITOR_SOURCE_HOST=")) + "\n",
        base.replace("FMONITOR_SOURCE_HOST=legacy-db.example", "FMONITOR_SOURCE_HOST="),
        base.replace("FMONITOR_SOURCE_NAME=SOURCE_DATABASE_CANARY_149", "FMONITOR_SOURCE_NAME="),
        base.replace("FMONITOR_SOURCE_USER=SOURCE_USER_CANARY_149", "FMONITOR_SOURCE_USER="),
        base.replace(f"FMONITOR_SOURCE_PASSWORD='{legacy_secret}'", "FMONITOR_SOURCE_PASSWORD=''") ,
        base.replace("FMONITOR_SOURCE_PORT=43149", "FMONITOR_SOURCE_PORT=043149"),
        base.replace("FMONITOR_SOURCE_PORT=43149", "FMONITOR_SOURCE_PORT=0"),
        base.replace("FMONITOR_SOURCE_PORT=43149", "FMONITOR_SOURCE_PORT=65536"),
        base.replace("FMONITOR_SOURCE_PORT=43149", "FMONITOR_SOURCE_PORT=abc"),
        base.replace("2026-09-17 00:00:00", "17.09.2026"),
        base + "FMONITOR_SOURCE_USER=duplicate\n",
        base.replace("FMONITOR_SOURCE_HOST=legacy-db.example", "MALFORMED_LINE"),
        base.replace(f"'{legacy_secret}'", "'unterminated"),
        base.replace(f"'{legacy_secret}'", "$(id)"),
        base.replace(f"'{legacy_secret}'", "${HOME}"),
        base.replace(f"'{legacy_secret}'", "$HOME"),
        base.replace(f"'{legacy_secret}'", "$1"),
        base.replace(f"'{legacy_secret}'", "`id`"),
        base.replace(f"'{legacy_secret}'", '"\'a"b\'"'),
    )
    invalid_bitrix = (
        "\n".join(line for line in base.splitlines() if not line.startswith("FMONITOR_BITRIX_WEBHOOK_URL=")) + "\n",
        base.replace("https://portal.example", "http://portal.example"),
        base.replace("https://portal.example", "HTTPS://portal.example"),
        base.replace("https://portal.example", "https://user@portal.example"),
        base.replace("/rest/7/", "/other/7/"),
        base.replace(f"/{bitrix_secret}/", "/token.with.dot/"),
        base.replace(bitrix_secret, "A" * 257),
        base.replace(f"/{bitrix_secret}/", "//"),
        base.replace("portal.example", "bad authority"),
        base.replace("'[72,71]'", "'[]'"),
        base.replace("'[72,71]'", "'[71,71]'"),
        base.replace("'[72,71]'", "'[0]'"),
        base.replace("'[72,71]'", "'[-1]'"),
        base.replace("'[72,71]'", "'[true]'"),
        base.replace("'[72,71]'", "'[\"71\"]'"),
        base.replace("'[72,71]'", "'{}'"),
        base + "FMONITOR_BITRIX_WEBHOOK_URL=https://duplicate.invalid/rest/1/x/\n",
    )
    for kind, cases in (("legacy", invalid_legacy), ("bitrix", invalid_bitrix)):
        for case_index, value in enumerate(cases):
            result, _ = stage(kind, value)
            output = result.stdout + result.stderr
            assert result.returncode == 64, ("INTENDED_RED: prohibited integration input accepted", kind, case_index, output)
            assert "LOCAL_INTEGRATION_CONFIG_INVALID" in output
            assert legacy_secret not in output and bitrix_secret not in output

    # Execute the public Make seams in an isolated checkout with downstream witnesses.
    checkout = box / "checkout"
    checkout.mkdir()
    for rel in ("Makefile", ".env.example", "tools/delivery/local-runtime-env", "tools/delivery/local-integration-config"):
        destination = checkout / rel
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(root / rel, destination)
    for rel in ("deploy/runtime/compose.yaml", "deploy/runtime/Dockerfile"):
        destination = checkout / rel
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(root / rel, destination)
    runtime = """COMPOSE_PROJECT_NAME=fm2-local-issue149
FMONITOR_RUNTIME_IMAGE=fmonitor2-runtime:test
FMONITOR_HTTP_PORT=18093
FMONITOR_DB_NAME=fmonitor2
FMONITOR_DB_USER=runtime
FMONITOR_DB_PASSWORD=DB_CANARY_149
FMONITOR_MIGRATION_DB_USER=root
FMONITOR_MIGRATION_DB_PASSWORD=MIGRATION_CANARY_149
FMONITOR_PROCESS_TABLE_PREFIX=fm2_
FMONITOR_LEGACY_TABLE_PREFIX=fm2_
FMONITOR_SESSION_INSTANCE=test
FMONITOR_YII_COOKIE_VALIDATION_KEY=cccccccccccccccccccccccccccccccc
FMONITOR_YII_IDENTITY_KEY=iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii
FMONITOR_TRUSTED_REQUEST_HOST=127.0.0.1:18093
FMONITOR_TRUSTED_REQUEST_SCHEME=http
FMONITOR_INITIAL_OWNER_EMAIL=owner@example.test
FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD=OWNER_CANARY_149
FMONITOR_ERP_HOST=erp.example.invalid
FMONITOR_ERP_DATABASE=legacy-stage
FMONITOR_ERP_USER=reader
FMONITOR_ERP_PASSWORD=ERP_CANARY_149
FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY=hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh
FMONITOR_ERP_EQUIPMENT_FACTS_MAX_ROWS=500
FMONITOR_ERP_EQUIPMENT_FACTS_TIMEOUT_SECONDS=5
FMONITOR_ERP_EQUIPMENT_FACTS_CHUNK_SIZE=100
"""
    (checkout / ".env").write_text(runtime + base)
    (checkout / ".env").chmod(0o600)
    fake_bin = box / "bin"; fake_bin.mkdir()
    trace = box / "make-trace.jsonl"
    docker = fake_bin / "docker"
    docker.write_text("""#!/usr/bin/env python3
import json,os,sys
a=sys.argv[1:]; joined=' '.join(a); stage='docker'
if a==['info']: stage='up'
elif 'legacy-import/run' in joined: stage='legacy'
elif 'workforce-sync/run' in joined: stage='workforce'
record={'stage':stage,'argv':a,'integration_env':{k:v for k,v in os.environ.items() if k.startswith('FMONITOR_SOURCE_') or k.startswith('FMONITOR_BITRIX_')}}
open(os.environ['TRACE'],'a').write(json.dumps(record)+'\\n')
if os.environ.get('FAIL_STAGE')==stage: sys.exit(42)
if 'config --quiet' in joined: print('services: private-file-only')
sys.exit(0)
""")
    docker.chmod(0o755)
    curl = fake_bin / "curl"; curl.write_text("#!/bin/sh\nexit 0\n"); curl.chmod(0o755)
    make_env = {**os.environ, "PATH": f"{fake_bin}:{os.environ['PATH']}", "TRACE": str(trace)}

    def make(target: str, fail: str = ""):
        trace.write_text("")
        return subprocess.run(["make", "--no-print-directory", target], cwd=checkout,
                              env={**make_env, "FAIL_STAGE": fail}, text=True, capture_output=True)

    def events():
        return [json.loads(line) for line in trace.read_text().splitlines()]

    legacy_make = make("import-legacy")
    assert legacy_make.returncode == 0
    legacy_events = events(); assert [event["stage"] for event in legacy_events] == ["legacy"]
    workforce_make = make("sync-workforce")
    assert workforce_make.returncode == 0
    workforce_events = events(); assert [event["stage"] for event in workforce_events] == ["workforce"]
    combined = make("up-with-data")
    assert combined.returncode == 0 and "FMonitor Yii2 with production data: ready" in combined.stdout
    combined_stages = [event["stage"] for event in events()]
    assert combined_stages.count("up") == 1 and combined_stages.count("legacy") == 1 and combined_stages.count("workforce") == 1
    assert combined_stages.index("up") < combined_stages.index("legacy") < combined_stages.index("workforce")
    for failed, later in (("up", ("legacy", "workforce")), ("legacy", ("workforce",)), ("workforce", ())):
        result = make("up-with-data", failed)
        assert result.returncode != 0 and "FMonitor Yii2 with production data: ready" not in result.stdout + result.stderr
        observed = [event["stage"] for event in events()]
        assert observed.count(failed) == 1
        assert all(stage not in observed for stage in later)

    # Invalid input reaches no Docker/owner witness even when an old private file exists.
    old_private = checkout / ".local/legacy-source.env"
    old_private.write_text("old-private-snapshot\n"); old_private.chmod(0o600)
    current = (checkout / ".env").read_text()
    (checkout / ".env").write_text(current.replace("FMONITOR_SOURCE_PORT=43149", "FMONITOR_SOURCE_PORT=bad"))
    invalid_make = make("import-legacy")
    assert invalid_make.returncode != 0 and events() == [] and old_private.read_text() == "old-private-snapshot\n"
    (checkout / ".env").write_text(current)

    (checkout / ".env").unlink()
    missing_make = make("import-legacy")
    missing_output = missing_make.stdout + missing_make.stderr
    assert missing_make.returncode != 0 and events() == []
    assert "LOCAL_INTEGRATION_CONFIG_INVALID" in missing_output
    assert "Traceback" not in missing_output and legacy_secret not in missing_output and bitrix_secret not in missing_output
    assert old_private.read_text() == "old-private-snapshot\n"
    (checkout / ".env").write_text(current); (checkout / ".env").chmod(0o600)

    # The actual handoff is a read-only file path; integration secrets never enter argv/env/output/config.
    all_events = legacy_events + workforce_events
    flat = json.dumps(all_events) + legacy_make.stdout + legacy_make.stderr + workforce_make.stdout + workforce_make.stderr
    assert legacy_secret not in flat and bitrix_secret not in flat
    assert all(event["integration_env"] == {} for event in all_events)
    assert any(".local/legacy-source.env:/run/fmonitor-input/config:ro" in " ".join(event["argv"]) for event in legacy_events)
    assert any(".local/bitrix-workforce.json:/run/fmonitor-input/config:ro" in " ".join(event["argv"]) for event in workforce_events)
    assert all(" local-integration " in " " + " ".join(event["argv"]) + " " for event in all_events)

    # Inspect real Compose rendering, not the fake downstream witness.
    rendered = subprocess.run(
        ["bash", "tools/delivery/local-runtime-env", "--", "docker", "compose", "--env-file", "@env-file",
         "-f", "deploy/runtime/compose.yaml", "config"],
        cwd=checkout, text=True, capture_output=True,
    )
    assert rendered.returncode == 0, rendered.stderr
    rendered_text = rendered.stdout + rendered.stderr
    private_values = (
        "legacy-db.example", "43149", "SOURCE_DATABASE_CANARY_149", "SOURCE_USER_CANARY_149",
        legacy_secret, "2026-09-17 00:00:00", f"https://portal.example/rest/7/{bitrix_secret}/",
    )
    for value in private_values:
        assert value not in rendered_text, value

    makefile = (checkout / "Makefile").read_text()
    assert "local-integration-config stage legacy .env .local/legacy-source.env" in makefile
    assert "local-integration-config stage bitrix .env .local/bitrix-workforce.json" in makefile
    assert "bin/yii legacy-import/run --interactive=0" in makefile
    assert "bin/yii workforce-sync/run --interactive=0" in makefile
    up_data = makefile.split("up-with-data:", 1)[1].split("\n\n", 1)[0]
    assert up_data.index(" up") < up_data.index("import-legacy") < up_data.index("sync-workforce")

docs = (root / "docs/bitrix-startup.md").read_text()
assert "make up-with-data" in docs
assert "make import-legacy" in docs and "make sync-workforce" in docs
assert "make reset" in docs and ("не нужен" in docs or "не требуется" in docs)
for manual in ("создайте `.local/legacy-source.env`", "создайте `.local/bitrix-workforce.json`"):
    assert manual not in docs.lower()
configured_port = next(line.split("=", 1)[1] for line in template.splitlines() if line.startswith("FMONITOR_HTTP_PORT="))
assert f"http://127.0.0.1:{configured_port}/" in docs

print("PASS: LOCAL-INTEGRATION-ENV-001 one env and preflight")
