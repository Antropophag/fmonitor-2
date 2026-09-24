#!/usr/bin/env python3
"""BITRIX-DOCUMENT-RUNTIME-CONFIG-001 executable deployment contract."""
import json
import os
from pathlib import Path
import stat
import subprocess
import tempfile
from typing import Dict, List, Optional

ROOT = Path(__file__).resolve().parents[2]
HOST_STAGER = ROOT / "tools/delivery/local-integration-config"
RUNTIME_STAGER = ROOT / "bin/fmonitor2-stage-runtime-bitrix-config"
MARKER = "marker-token-252"


def run(command: List[str], environment: Optional[Dict[str, str]] = None) -> subprocess.CompletedProcess:
    return subprocess.run(
        command, cwd=ROOT, env=environment, text=True,
        stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=15,
    )


def stage_runtime(source: Path, target: Path, extra: Optional[Dict[str, str]] = None) -> subprocess.CompletedProcess:
    environment = dict(os.environ)
    environment.update({
        "FMONITOR_BITRIX_CONFIG_INPUT": str(source),
        "FMONITOR_RUNTIME_SECRETS_DIR": str(target),
        "FMONITOR_RUNTIME_SECRET_UID": str(os.getuid()),
        "FMONITOR_RUNTIME_SECRET_GID": str(os.getgid()),
    })
    if extra:
        environment.update(extra)
    return run([str(RUNTIME_STAGER)], environment)


def private_regular(path: Path) -> None:
    info = path.lstat()
    assert stat.S_ISREG(info.st_mode) and not path.is_symlink()
    assert stat.S_IMODE(info.st_mode) & 0o077 == 0, f"{path.name} is private"


def worker_probe(config: Path, state_root: Path) -> dict:
    php = r'''
require "app/autoload.php";
use FMonitor2\Jobs\YiiJobsRuntimeEnvironment;
$seen = null;
YiiJobsRuntimeEnvironment::execute("worker", function () use (&$seen): array {
    $path = (string) getenv("FMONITOR_BITRIX_TOKEN_FILE");
    $info = lstat($path);
    $seen = [
        "origin" => getenv("FMONITOR_BITRIX_ORIGIN"),
        "user" => getenv("FMONITOR_BITRIX_WEBHOOK_USER_ID"),
        "departments" => getenv("FMONITOR_BITRIX_DEPARTMENT_IDS_JSON"),
        "token" => file_get_contents($path),
        "tokenPath" => $path,
        "mode" => $info === false ? null : ($info["mode"] & 07777),
    ];
    return [0, ["ok" => true]];
});
$seen["cleaned"] = !file_exists($seen["tokenPath"]);
unset($seen["tokenPath"]);
echo json_encode($seen, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
'''
    environment = dict(os.environ)
    environment.update({
        "FMONITOR_SESSION_STATE_ROOT": str(state_root),
        "FMONITOR_PROCESS_TABLE_PREFIX": "fm2_",
        "FMONITOR_BITRIX_CONFIG": str(config),
        "FMONITOR_BITRIX_ORIGIN": "HOST_VALUE_MUST_BE_REPLACED",
        "FMONITOR_BITRIX_WEBHOOK_USER_ID": "999",
        "FMONITOR_BITRIX_TOKEN_FILE": "/dev/null",
    })
    result = run(["php", "-r", php], environment)
    assert result.returncode == 0, result.stdout + result.stderr
    assert MARKER not in result.stderr
    return json.loads(result.stdout)


def document_probe(config: Path) -> dict:
    php = r'''
require "app/autoload.php";
$value=FMonitor2\Workforce\WorkerConfiguration::fromDocumentFile($argv[1]);
echo json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),"\n";
'''
    result = run(["php", "-r", php, str(config)])
    assert result.returncode == 0, result.stdout + result.stderr
    return json.loads(result.stdout)


def delivery_composition_probe(config: Path, root: str = "1809812") -> dict:
    php = r'''
require "app/autoload.php";
$entered=false;
$result=FMonitor2\InstallationProcess\BitrixOrderDocumentDelivery::runFromEnvironment(
    static function(object $config)use(&$entered):array{$entered=true;return["status"=>"witness","user"=>$config->webhookUserId,"root"=>$config->rootFolderId,"token"=>$config->token];}
);
echo json_encode(["entered"=>$entered,"result"=>$result],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),"\n";
'''
    environment = dict(os.environ)
    environment.update({"FMONITOR_BITRIX_CONFIG": str(config), "FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID": root})
    result = run(["php", "-r", php], environment)
    assert result.returncode == 0, result.stdout + result.stderr
    return json.loads(result.stdout)


with tempfile.TemporaryDirectory() as temporary:
    work = Path(temporary)
    expected_document = {
        "baseUrl": f"https://tenant.example.invalid/rest/7/{MARKER}",
        "departments": [71],
        "documentBaseUrl": "https://tenant.example.invalid/rest/8/document-token-252",
    }
    results = []
    for index, webhook in enumerate((
        expected_document["baseUrl"] + "/",
        "'" + expected_document["baseUrl"] + "/'",
        '"' + expected_document["baseUrl"] + '/"',
    )):
        env_file = work / f"input-{index}.env"
        env_file.write_text(
            f"FMONITOR_BITRIX_WEBHOOK_URL={webhook}\n"
            "FMONITOR_BITRIX_ORDER_DOCUMENT_WEBHOOK_URL='https://tenant.example.invalid/rest/8/document-token-252/'\n"
            "FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID=1809812\n"
            "FMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[71]'\n",
            encoding="utf-8",
        )
        env_file.chmod(0o600)
        private_input = work / f"private-{index}.json"
        host = run([str(HOST_STAGER), "stage", "bitrix", str(env_file), str(private_input)])
        assert (host.returncode, host.stdout, host.stderr) == (0, "", "")
        private_regular(private_input)

        target = work / f"runtime-{index}"
        target.mkdir(mode=0o700)
        staged = stage_runtime(private_input, target)
        assert (staged.returncode, staged.stdout, staged.stderr) == (0, "", ""), \
            "canonical runtime staging accepts the host-staged private config"
        assert sorted(path.name for path in target.iterdir()) == ["bitrix-config.json"], \
            "INTENDED_RED: runtime staging must publish one atomic secret source only"
        published = target / "bitrix-config.json"
        private_regular(published)
        assert json.loads(published.read_text()) == expected_document
        assert document_probe(published) == {"origin": "https://tenant.example.invalid", "webhookUserId": 8, "token": "document-token-252"}
        assert delivery_composition_probe(published) == {"entered": True, "result": {"status": "witness", "user": 8, "root": 1809812, "token": "document-token-252"}}
        observed = worker_probe(published, work)
        assert observed == {
            "origin": "https://tenant.example.invalid",
            "user": "7",
            "departments": "[71]",
            "token": MARKER,
            "mode": 0o600,
            "cleaned": True,
        }, "real worker bootstrap owns exact config translation and token cleanup"
        results.append((published.read_bytes(), observed))
    assert results[0] == results[1] == results[2], "plain and quoted .env inputs are equivalent"

    disabled_env = work / "disabled.env"
    disabled_env.write_text(f"FMONITOR_BITRIX_WEBHOOK_URL=https://tenant.example.invalid/rest/7/{MARKER}/\nFMONITOR_BITRIX_DEPARTMENT_IDS_JSON=[71]\n")
    disabled_env.chmod(0o600)
    disabled_config = work / "disabled.json"
    disabled = run([str(HOST_STAGER), "stage", "bitrix", str(disabled_env), str(disabled_config)])
    assert disabled.returncode == 0 and "documentBaseUrl" not in json.loads(disabled_config.read_text())
    for suffix in ("FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID=1809812\n", "FMONITOR_BITRIX_ORDER_DOCUMENT_WEBHOOK_URL=https://tenant.example.invalid/rest/8/document-token-252/\n"):
        partial = work / "partial.env"
        partial.write_text(disabled_env.read_text() + suffix); partial.chmod(0o600)
        assert run([str(HOST_STAGER), "stage", "bitrix", str(partial), str(work / "partial.json")]).returncode != 0
    equal = work / "equal.env"
    equal.write_text(disabled_env.read_text() + f"FMONITOR_BITRIX_ORDER_DOCUMENT_WEBHOOK_URL=https://tenant.example.invalid/rest/7/{MARKER}/\nFMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID=1809812\n"); equal.chmod(0o600)
    assert run([str(HOST_STAGER), "stage", "bitrix", str(equal), str(work / "equal.json")]).returncode != 0

    source = work / "rotation.json"
    source.write_text(json.dumps(expected_document) + "\n", encoding="utf-8")
    source.chmod(0o600)
    target = work / "rotation-target"
    target.mkdir(mode=0o700)
    assert stage_runtime(source, target).returncode == 0
    published = target / "bitrix-config.json"
    before = published.read_bytes()
    replay = stage_runtime(source, target)
    assert (replay.returncode, replay.stdout, replay.stderr) == (0, "", "")
    assert published.read_bytes() == before, "valid replay is byte-equivalent"

    rotated_document = {"baseUrl": "https://rotated.example.invalid/rest/8/rotated-token-252", "departments": [72], "documentBaseUrl": "https://rotated.example.invalid/rest/9/document-token-252"}
    source.write_text(json.dumps(rotated_document) + "\n", encoding="utf-8")
    rotated = stage_runtime(source, target)
    assert (rotated.returncode, rotated.stdout, rotated.stderr) == (0, "", "")
    rotated_observed = worker_probe(published, work)
    assert rotated_observed == {"origin": "https://rotated.example.invalid", "user": "8", "departments": "[72]", "token": "rotated-token-252", "mode": 0o600, "cleaned": True}
    rotated_bytes = published.read_bytes()

    fake_bin = work / "fake-bin"
    fake_bin.mkdir()
    fake_mv = fake_bin / "mv"
    fake_mv.write_text("#!/bin/sh\nexit 73\n", encoding="utf-8")
    fake_mv.chmod(0o700)
    pending_document = {"baseUrl": "https://pending.example.invalid/rest/9/pending-token-252", "departments": [73], "documentBaseUrl": "https://pending.example.invalid/rest/10/document-token-252"}
    source.write_text(json.dumps(pending_document) + "\n", encoding="utf-8")
    failed_publish = stage_runtime(source, target, {"PATH": str(fake_bin) + os.pathsep + os.environ["PATH"]})
    assert (failed_publish.returncode, failed_publish.stdout, failed_publish.stderr) == (74, "", "RUNTIME_SECRET_STAGING_FAILED\n")
    assert published.read_bytes() == rotated_bytes
    assert not list(target.glob(".*.tmp-*"))

    source.write_text(json.dumps(expected_document) + "\n", encoding="utf-8")
    before = rotated_bytes

    invalid_cases = []
    missing = work / "missing.json"
    invalid_cases.append(missing)
    empty = work / "empty.json"
    empty.write_bytes(b"")
    empty.chmod(0o600)
    invalid_cases.append(empty)
    malformed = work / "malformed.json"
    malformed.write_text('{"baseUrl":"https://tenant.example.invalid/rest/7/"}\n')
    malformed.chmod(0o600)
    invalid_cases.append(malformed)
    linked = work / "linked.json"
    linked.symlink_to(source)
    invalid_cases.append(linked)
    directory = work / "directory.json"
    directory.mkdir()
    invalid_cases.append(directory)
    public = work / "public.json"
    public.write_bytes(source.read_bytes())
    public.chmod(0o644)
    invalid_cases.append(public)
    unreadable = work / "unreadable.json"
    unreadable.write_bytes(source.read_bytes())
    unreadable.chmod(0o000)
    invalid_cases.append(unreadable)
    malformed_document = work / "malformed-document.json"
    malformed_document.write_text(json.dumps({**expected_document,"documentBaseUrl":"http://bad.invalid/rest/8/token"})+"\n");malformed_document.chmod(0o600)
    invalid_cases.append(malformed_document)
    identical_document = work / "identical-document.json"
    identical_document.write_text(json.dumps({**expected_document,"documentBaseUrl":expected_document["baseUrl"]})+"\n");identical_document.chmod(0o600)
    invalid_cases.append(identical_document)
    for invalid in invalid_cases:
        failed = stage_runtime(invalid, target)
        assert (failed.returncode, failed.stdout, failed.stderr) == (
            74, "", "RUNTIME_SECRET_STAGING_FAILED\n"
        ), f"{invalid.name} fails closed without disclosure"
        assert MARKER not in failed.stdout + failed.stderr
        assert published.read_bytes() == before
        assert sorted(path.name for path in target.iterdir()) == ["bitrix-config.json"]
        assert not list(target.glob(".*.tmp-*")), "temporary files are removed"

    missing_document = work / "missing-document.json"
    missing_document.write_text(json.dumps({"baseUrl":expected_document["baseUrl"],"departments":[71]})+"\n");missing_document.chmod(0o600)
    assert delivery_composition_probe(missing_document) == {"entered": False, "result": {"status": "failed", "reason": "CONFIGURATION_UNAVAILABLE"}}
    assert delivery_composition_probe(malformed_document) == {"entered": False, "result": {"status": "failed", "reason": "CONFIGURATION_UNAVAILABLE"}}

for compose_path in ("deploy/runtime/compose.yaml", "tools/delivery/compose.runtime.yaml.in"):
    compose = (ROOT / compose_path).read_text(encoding="utf-8")
    worker = compose.split("  jobs-worker:", 1)[1].split("  jobs-scheduler:", 1)[0]
    for forbidden in (
        "FMONITOR_BITRIX_TOKEN_HOST_FILE", "FMONITOR_BITRIX_RUNTIME_CONFIG_FILE",
        "FMONITOR_BITRIX_TOKEN_FILE:", "FMONITOR_BITRIX_ORIGIN:",
        "FMONITOR_BITRIX_WEBHOOK_USER_ID:", "target: /run/fmonitor-secrets/bitrix-token",
    ):
        assert forbidden not in worker, f"INTENDED_RED: worker duplicates canonical config via {forbidden}"
    assert "FMONITOR_BITRIX_CONFIG: /run/fmonitor-secrets/bitrix-config.json" in worker
    assert "secrets:/run/fmonitor-secrets" in worker
    assert MARKER not in compose

makefile = (ROOT / "Makefile").read_text(encoding="utf-8")
assert makefile.index("stage-runtime-secrets") < makefile.index("up --detach --wait php web jobs-worker jobs-scheduler")

documentation = "\n".join((ROOT / path).read_text(encoding="utf-8") for path in (
    "docs/bitrix-startup.md", "docs/operations/runtime-recovery-runbook.md",
))
env_example = (ROOT / ".env.example").read_text(encoding="utf-8")
runtime_sources = env_example + (ROOT / "Makefile").read_text(encoding="utf-8") + RUNTIME_STAGER.read_text(encoding="utf-8") + HOST_STAGER.read_text(encoding="utf-8")
for required in ("FMONITOR_BITRIX_ORDER_DOCUMENT_WEBHOOK_URL", "documentBaseUrl"):
    assert required in runtime_sources, f"INTENDED_RED: separate document webhook staging omits {required}"
for phrase in ("FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID", "bitrix-config.json", "ротац"):
    assert phrase.lower() in documentation.lower(), f"INTENDED_RED: operator docs omit {phrase}"
assert "не создавайте token-файл вручную" in documentation.lower()
for unsafe in ("cat $FMONITOR_BITRIX", "echo $FMONITOR_BITRIX", "printenv FMONITOR_BITRIX"):
    assert unsafe.lower() not in documentation.lower()
assert MARKER not in documentation

delivery = run(["php", "tests/InstallationProcess/bitrix_order_document_links_delivery_001_test.php"])
assert delivery.returncode == 0, delivery.stdout + delivery.stderr
assert MARKER not in delivery.stdout + delivery.stderr

print("PASS: BITRIX-DOCUMENT-RUNTIME-CONFIG-001 .env to worker runtime contract")
