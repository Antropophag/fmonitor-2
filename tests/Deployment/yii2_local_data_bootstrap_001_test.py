#!/usr/bin/env python3
"""YII2-LOCAL-DATA-BOOTSTRAP-001: public Make/native ownership contract."""
import json, os, subprocess, tempfile
from pathlib import Path

root = Path(__file__).resolve().parents[2]
makefile = (root / "Makefile").read_text()
targets = {}
current = None
for line in makefile.splitlines():
    if line and not line.startswith(("\t", " ")) and line.endswith(":"):
        current = line[:-1]; targets[current] = []
    elif current is not None:
        targets[current].append(line)
for name in ("import-legacy", "sync-workforce", "up-with-data"):
    assert name in targets, f"MISSING_TARGET:{name}"
legacy = "\n".join(targets["import-legacy"])
workforce = "\n".join(targets["sync-workforce"])
combined = "\n".join(targets["up-with-data"])
assert "legacy-import/run" in legacy and "rapid-pilot" not in legacy
assert ".local/legacy-source.env" in legacy
assert "workforce-sync/run" in workforce and "rapid-pilot" not in workforce
assert ".local/bitrix-workforce.json" in workforce
positions = [combined.find(name) for name in ("up", "import-legacy", "sync-workforce")]
assert min(positions) >= 0 and positions == sorted(positions), "BOOTSTRAP_ORDER_INVALID"
controller = root / "app/YiiRuntime/Commands/LegacyImportController.php"
application = root / "app/YiiRuntime/LegacyImportConsole.php"
assert controller.is_file() and application.is_file(), "NATIVE_IMPORT_MISSING"
assert "rapid-pilot" not in controller.read_text() + application.read_text()
assert "legacy-import" in (root / "config/yii/console.php").read_text()

validator = root / "tools/delivery/local-integration-config"
assert validator.is_file(), "INTEGRATION_VALIDATOR_MISSING"
with tempfile.TemporaryDirectory() as raw:
    tmp = Path(raw); legacy_file = tmp / "legacy.env"; bitrix_file = tmp / "bitrix.json"
    legacy_secret = "LEGACY_SECRET_CANARY"
    bitrix_secret = "BITRIX_SECRET_CANARY"
    legacy_file.write_text("FMONITOR_SOURCE_HOST=source.example\nFMONITOR_SOURCE_PORT=3306\nFMONITOR_SOURCE_NAME=legacy\nFMONITOR_SOURCE_USER=reader\nFMONITOR_SOURCE_PASSWORD='" + legacy_secret + "'\nFMONITOR_MIGRATION_CUTOFF=\n")
    bitrix_file.write_text(json.dumps({"baseUrl":"https://example.invalid/rest/7/" + bitrix_secret,"departments":[71,72]}))
    legacy_file.chmod(0o600); bitrix_file.chmod(0o600)
    def validate(kind, path):
        return subprocess.run([str(validator), kind, str(path)], text=True, capture_output=True)
    assert validate("legacy", legacy_file).returncode == 0
    assert validate("bitrix", bitrix_file).returncode == 0
    for kind, good in (("legacy", legacy_file), ("bitrix", bitrix_file)):
        missing = validate(kind, tmp / "missing")
        directory = validate(kind, tmp)
        link = tmp / (kind + ".link"); link.symlink_to(good)
        symlink = validate(kind, link)
        good.chmod(0o644); permissive = validate(kind, good); good.chmod(0o600)
        for result in (missing, directory, symlink, permissive):
            assert result.returncode == 64
            text = result.stdout + result.stderr
            assert "LOCAL_INTEGRATION_CONFIG_INVALID" in text
            assert legacy_secret not in text and bitrix_secret not in text
print("PASS: YII2-LOCAL-DATA-BOOTSTRAP-001 Make/native ownership")
