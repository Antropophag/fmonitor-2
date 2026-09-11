#!/usr/bin/env python3
"""YII2-CANONICAL-MIGRATIONS-001 A5: production callers and package closure."""
from __future__ import annotations
import pathlib
import re
import os
import secrets
import shutil
import subprocess
import tempfile
import yaml
ROOT = pathlib.Path(__file__).resolve().parents[2]
compose = yaml.safe_load((ROOT / "deploy/runtime/compose.yaml").read_text(encoding="utf-8"))
command = compose["services"]["migrate"]["command"]
assert command == ["php", "bin/yii", "schema-migrate/run", "--interactive=0"], f"INTENDED_RED: migrate service uses Yii route: {command}"
makefile = (ROOT / "Makefile").read_text(encoding="utf-8")
migrate = re.search(r"(?ms)^migrate:\n(?P<body>(?:\t.*\n)+)", makefile)
assert migrate and "bin/yii schema-migrate/run --interactive=0" in migrate.group("body"), "INTENDED_RED: make migrate uses Yii route"
for relative in ["bin/fmonitor2-yii.php", "config/yii/console.php", "app/YiiRuntime/Commands/SchemaMigrateController.php", "app/YiiRuntime/CanonicalMigrationConsole.php"]:
    source = (ROOT / relative).read_text(encoding="utf-8")
    assert "rapid-pilot" not in source and "app/demo" not in source, f"production migration startup excludes oracle/demo: {relative}"
dockerfile = (ROOT / "deploy/runtime/Dockerfile").read_text(encoding="utf-8")
assert "mysqli" in dockerfile and "composer" in dockerfile.lower(), "runtime image retains mysqli and Composer/Yii dependencies"

production_roots = [ROOT / "Makefile", ROOT / "bin", ROOT / "config", ROOT / "deploy" / "runtime", ROOT / "app" / "YiiRuntime"]
legacy_callers = []
for item in production_roots:
    files = [item] if item.is_file() else [path for path in item.rglob("*") if path.is_file()]
    for path in files:
        try:
            text = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue
        if "fmonitor2-migrate.php" in text:
            legacy_callers.append(path.relative_to(ROOT).as_posix())
assert legacy_callers == [], f"INTENDED_RED: no production caller retains the legacy migration path: {legacy_callers}"

for relative in ["app/YiiRuntime/Commands/JobsController.php", "config/yii/web.php", "public/runtime.php"]:
    text = (ROOT / relative).read_text(encoding="utf-8")
    assert "schema-migrate" not in text and "CanonicalMigrationApplication" not in text, f"ordinary runtime never invokes migrations: {relative}"

with tempfile.TemporaryDirectory(prefix="fm2-ycm-load-") as directory:
    trace = pathlib.Path(directory) / "included.json"
    wrapper = pathlib.Path(directory) / "trace.php"
    wrapper.write_text("<?php register_shutdown_function(static function(){file_put_contents(" + repr(str(trace)) + ",json_encode(get_included_files(),JSON_THROW_ON_ERROR));}); require " + repr(str(ROOT / "bin" / "yii")) + ";", encoding="utf-8")
    env = dict(os.environ)
    for name in ["FMONITOR_DB_HOST", "FMONITOR_DB_PORT", "FMONITOR_DB_NAME", "FMONITOR_DB_USER", "FMONITOR_DB_PASSWORD", "FMONITOR_PROCESS_TABLE_PREFIX"]:
        env.pop(name, None)
    result = subprocess.run(["php", str(wrapper), "schema-migrate/run", "--interactive=0"], cwd=ROOT, env=env, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=20)
    assert (result.returncode, result.stdout, result.stderr) == (64, '{"ok":false,"reason":"CONFIGURATION_INVALID"}\n', ""), "INTENDED_RED: packaged Yii command has closed bootstrap output"
    loaded = [pathlib.Path(value).as_posix() for value in __import__("json").loads(trace.read_text(encoding="utf-8"))]
    forbidden = ["/rapid-pilot/", "/app/demo/", "/app/Jobs/", "/app/PilotHttp/", "/yii/console/controllers/MigrateController.php"]
    for path in loaded:
        assert not any(marker in path for marker in forbidden), f"forbidden transitive migration load: {path}"
    for required in ["/bin/yii", "/bin/fmonitor2-yii.php", "/app/YiiRuntime/Commands/SchemaMigrateController.php", "/app/YiiRuntime/CanonicalMigrationConsole.php"]:
        assert any(path.endswith(required) for path in loaded), f"packaged load set contains {required}"

assert shutil.which("docker"), "SETUP_FAILURE: Docker is required for the production artifact check"
tag = "fmonitor2-ycm-test:" + secrets.token_hex(6)
try:
    built = subprocess.run(["docker", "build", "--quiet", "--file", "deploy/runtime/Dockerfile", "--tag", tag, "."], cwd=ROOT, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=300)
    assert built.returncode == 0, "SETUP_FAILURE: production runtime image build failed\n" + built.stderr[-2000:]
    presence = subprocess.run(["docker", "run", "--rm", "--entrypoint", "sh", tag, "-c", "test -x bin/yii && test -f app/YiiRuntime/Commands/SchemaMigrateController.php && test -f app/YiiRuntime/CanonicalMigrationConsole.php && test -f vendor/autoload.php"], text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30)
    assert presence.returncode == 0, "built image lacks executable migration closure: " + presence.stderr
    run = subprocess.run(["docker", "run", "--rm", tag, "php", "bin/yii", "schema-migrate/run", "--interactive=0"], text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30)
    assert (run.returncode, run.stdout, run.stderr) == (64, '{"ok":false,"reason":"CONFIGURATION_INVALID"}\n', ""), "built image command does not preserve closed CLI contract"
    trace_script = "register_shutdown_function(static function(){fwrite(STDERR,json_encode(get_included_files(),JSON_THROW_ON_ERROR));});$argv=['bin/yii','schema-migrate/run','--interactive=0'];require 'bin/yii';"
    traced = subprocess.run(["docker", "run", "--rm", "--entrypoint", "php", tag, "-r", trace_script], text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=30)
    assert (traced.returncode, traced.stdout) == (64, '{"ok":false,"reason":"CONFIGURATION_INVALID"}\n'), "built-image trace reaches the closed Yii command"
    image_loaded = [pathlib.Path(value).as_posix() for value in __import__("json").loads(traced.stderr)]
    for path in image_loaded:
        assert not any(marker in path for marker in forbidden), f"forbidden built-image transitive migration load: {path}"
    for required in ["/bin/yii", "/bin/fmonitor2-yii.php", "/app/YiiRuntime/Commands/SchemaMigrateController.php", "/app/YiiRuntime/CanonicalMigrationConsole.php"]:
        assert any(path.endswith(required) for path in image_loaded), f"built-image load set contains {required}"
finally:
    subprocess.run(["docker", "image", "rm", "--force", tag], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=30)
print("PASS: YII2-CANONICAL-MIGRATIONS-001 production package")
