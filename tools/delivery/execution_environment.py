#!/usr/bin/env python3
"""Pinned, isolated Docker execution profiles for project verification."""

import argparse
import ast
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import signal
import subprocess
import tempfile
import time
import uuid

import harness

ROOT = Path(__file__).resolve().parents[2]
PROFILES = {"governance": (), "integration": ("mariadb",), "browser": ("mariadb", "browser")}
PROFILE_ORDER = {"governance": 0, "integration": 1, "browser": 2}


def _run(argv, **kwargs):
    return subprocess.run(argv, text=True, capture_output=True, **kwargs)


def _docker_ok():
    try:
        value = _run(["docker", "info"], timeout=15)
    except (OSError, subprocess.TimeoutExpired):
        return False
    return value.returncode == 0


def _pins():
    values = {}
    for line in (ROOT / "tools/delivery/dependencies.env").read_text().splitlines():
        if line and not line.startswith("#"):
            key, value = line.split("=", 1); values[key] = value
    return values


def _lockfiles():
    return {name: hashlib.sha256((ROOT / name).read_bytes()).hexdigest()
            for name in ("composer.lock", "uv.lock") if (ROOT / name).is_file()}


def _image_key(profile):
    digest = hashlib.sha256(profile.encode())
    for path in ("tools/delivery/dependencies.env", "tools/delivery/Dockerfile.execution.in",
                 "composer.json", "composer.lock", "uv.lock"):
        candidate = ROOT / path
        if candidate.is_file():
            digest.update(path.encode()); digest.update(candidate.read_bytes())
    digest.update(os.environ.get("DOCKER_DEFAULT_PLATFORM", "native").encode())
    return digest.hexdigest()[:24]


def prepare(profile):
    if profile not in PROFILES:
        raise ValueError("UNKNOWN_PROFILE: " + profile)
    if not _docker_ok():
        raise ValueError("DOCKER_UNAVAILABLE")
    tag = "fmonitor2-verification:" + _image_key(profile)
    inspected = _run(["docker", "image", "inspect", tag])
    installs = 0
    if inspected.returncode:
        pins = _pins()
        command = ["docker", "build", "--file", "tools/delivery/Dockerfile.execution.in",
                   "--tag", tag, "--build-arg", "PROFILE=" + profile]
        for key in ("PHP_IMAGE", "NODE_IMAGE", "NODE_EXECUTION_IMAGE", "PYTHON_IMAGE", "GIT_IMAGE", "DOCKER_CLI_IMAGE",
                    "COMPOSER_VERSION", "UV_VERSION", "SHLZ_UI_REVISION", "PLAYWRIGHT_VERSION"):
            if key in pins:
                command += ["--build-arg", key + "=" + pins[key]]
        command.append(".")
        built = subprocess.run(command, cwd=ROOT)
        if built.returncode:
            raise ValueError("DEPENDENCY_UNAVAILABLE: image build failed")
        installs = 1
    raw = json.loads(_run(["docker", "image", "inspect", tag], check=True).stdout)[0]
    expected_platform = os.environ.get("DOCKER_DEFAULT_PLATFORM")
    if not expected_platform:
        server = _run(["docker", "version", "--format", "{{.Server.Os}}/{{.Server.Arch}}"], check=True)
        expected_platform = server.stdout.strip()
    observed_platform = raw["Os"] + "/" + raw["Architecture"]
    recipe_platforms = re.findall(r'^FROM\s+--platform=([^\s]+)',
                                  (ROOT / "tools/delivery/Dockerfile.execution.in").read_text(), re.M | re.I)
    if recipe_platforms and recipe_platforms[-1] != expected_platform:
        raise ValueError(f"PLATFORM_MISMATCH: expected {expected_platform}, recipe targets {recipe_platforms[-1]}")
    if observed_platform != expected_platform:
        raise ValueError(f"PLATFORM_MISMATCH: expected {expected_platform}, observed {observed_platform}")
    pins = _pins()
    probes = {
        "python": ["python3", "-c", "import platform;print(platform.python_version())"],
        "node": ["node", "-p", "process.versions.node"],
        "php": ["php", "-r", "echo PHP_VERSION;"],
    }
    versions = {}
    for name, probe in probes.items():
        value = _run(["docker", "run", "--rm", tag, *probe])
        if value.returncode:
            raise ValueError(f"RUNTIME_UNAVAILABLE: {name}")
        versions[name] = value.stdout.strip()
        expected = pins[name.upper() + "_VERSION"]
        if not versions[name].startswith(expected):
            raise ValueError(f"RUNTIME_MISMATCH: {name} expected {expected}, observed {versions[name]}")
    readiness = [
        ("PHP extensions", ["php", "-r", "$m=[];foreach(explode(',',getenv('EXPECTED')) as $e)if(!extension_loaded($e))$m[]=$e;exit($m?1:0);"], {"EXPECTED": pins["PHP_EXTENSIONS"]}),
        ("locked Python packages", ["sh", "-c", "test -x /opt/fmonitor-dependencies/python/bin/python && /opt/fmonitor-dependencies/python/bin/python -c 'import importlib.metadata as m; m.version(\"quality-graph-cli\")'"], {}),
        ("locked Composer packages", ["sh", "-c", "test -f /opt/fmonitor-dependencies/vendor/autoload.php && composer check-platform-reqs --no-dev --working-dir=/opt/fmonitor-dependencies"], {}),
    ]
    if profile == "browser":
        readiness.append(("browser assets", ["sh", "-c", "test -d /workspace/shlz-ui && test -d /root/.cache/ms-playwright && node -e \"require('/opt/fmonitor-dependencies/node_modules/playwright')\""], {}))
    for label, command, extra_env in readiness:
        argv = ["docker", "run", "--rm"]
        for key, value in extra_env.items():
            argv += ["-e", key + "=" + value]
        checked = _run([*argv, tag, *command])
        if checked.returncode:
            raise ValueError("DEPENDENCY_MISMATCH: " + label)
    return {"outcome": "GREEN", "profile": profile, "image_id": raw["Id"],
            "image": tag, "platform": observed_platform,
            "lockfiles": _lockfiles(), "runtimes": versions,
            "dependency_installs": installs}


def _copy_snapshot(destination):
    destination.mkdir()
    names = set()
    for args in (("ls-files", "-z"), ("ls-files", "--others", "--exclude-standard", "-z")):
        names.update(item for item in harness._git(*args, binary=True).split(b"\0") if item)
    for raw in sorted(names):
        relative = Path(raw.decode("utf-8", errors="surrogateescape"))
        source = ROOT / relative
        target = destination / relative
        if not os.path.lexists(source) or source.is_dir():
            continue
        target.parent.mkdir(parents=True, exist_ok=True)
        if source.is_symlink():
            target.symlink_to(os.readlink(source))
        else:
            shutil.copy2(source, target)
    # Preserve the public Composer bootstrap location without copying or mounting
    # dependencies from any checkout.
    (destination / "vendor").symlink_to("/opt/fmonitor-dependencies/vendor", target_is_directory=True)


def _category_for(command):
    policy = json.loads((ROOT / ".quality-graph/verification-policy.json").read_text())
    inventory = json.loads((ROOT / policy["inventory"]).read_text())
    category = None
    if command[:2] == ["make", "test"] or (command[:1] == ["make"] and any(x.startswith("CATEGORY=") for x in command)):
        category = next((x.split("=", 1)[1] for x in command if x.startswith("CATEGORY=")), None)
    elif command[:1] == ["make"]:
        category = {"unit-test": "unit", "db-test": "integration", "characterization-test": "governance",
                    "e2e-test": "e2e", "architecture-check": "governance", "lint": "governance",
                    "test-db-reset": "integration", "migrate": "integration"}.get(command[-1])
    elif command:
        category = next((inventory[item] for item in command[1:] if item in inventory), None)
    if category is None:
        raise ValueError("UNREGISTERED_COMMAND: " + (command[-1] if command else ""))
    return category


def _profile_for(command):
    category = _category_for(command)
    mapping = {"unit": "governance", "governance": "governance",
               "integration": "integration", "e2e": "browser"}
    if category not in mapping:
        raise ValueError("UNREGISTERED_COMMAND: " + command[-1])
    return mapping[category]


def registered_command(command):
    try:
        _profile_for(command)
        return True
    except (OSError, KeyError, ValueError, json.JSONDecodeError):
        return False


def project_execution_available():
    return (ROOT / "composer.json").is_file() and (ROOT / "composer.lock").is_file()


def _python_dependencies(path, seen=None):
    seen = seen or set()
    path = path.resolve()
    if path in seen or not path.is_file():
        return []
    seen.add(path)
    try:
        tree = ast.parse(path.read_text())
    except (SyntaxError, UnicodeDecodeError):
        return []
    result = [path]
    for node in ast.walk(tree):
        names = []
        if isinstance(node, ast.Import):
            names = [alias.name.split(".")[0] for alias in node.names]
        elif isinstance(node, ast.ImportFrom) and node.module:
            names = [node.module.split(".")[0]]
        for name in names:
            for candidate in (path.parent / (name + ".py"), ROOT / (name + ".py")):
                if candidate.is_file():
                    result.extend(_python_dependencies(candidate, seen))
    return result


def _python_uses_docker(path, seen=None):
    seen = seen or set()
    path = path.resolve()
    if path in seen or not path.is_file():
        return False
    seen.add(path)
    try:
        tree = ast.parse(path.read_text())
    except (SyntaxError, UnicodeDecodeError):
        return False
    if any(isinstance(node, ast.Call) and isinstance(node.func, ast.Attribute)
           and node.func.attr in {"run_profile", "cli"}
           and isinstance(node.func.value, ast.Name) and node.func.value.id == "self"
           for node in ast.walk(tree)):
        return True
    functions = {node.name: node for node in tree.body if isinstance(node, (ast.FunctionDef, ast.AsyncFunctionDef))}
    pending = [node for node in tree.body if not isinstance(node, (ast.FunctionDef, ast.AsyncFunctionDef, ast.ClassDef))]
    called = set()
    while pending:
        node = pending.pop()
        for child in ast.walk(node):
            if isinstance(child, ast.Call):
                if isinstance(child.func, ast.Name) and child.func.id in functions and child.func.id not in called:
                    called.add(child.func.id); pending.extend(functions[child.func.id].body)
                if (isinstance(child.func, ast.Attribute) and child.func.attr in {"run_profile", "cli"}
                        and isinstance(child.func.value, ast.Name) and child.func.value.id == "self"):
                    return True
                if child.args and isinstance(child.args[0], (ast.List, ast.Tuple)):
                    first = child.args[0].elts[:1]
                    if first and isinstance(first[0], ast.Constant) and first[0].value == "docker":
                        return True
    for dependency in _python_dependencies(path):
        if dependency != path and _python_uses_docker(dependency, seen):
            return True
    return False


def _php_uses_docker(path, seen=None):
    seen = seen or set(); path = path.resolve()
    if path in seen or not path.is_file(): return False
    seen.add(path)
    content = path.read_text(errors="ignore")
    for match in re.finditer(r'(?:require|include)(?:_once)?\s*(?:\(?\s*)?__DIR__\s*\.\s*["\']([^"\']+)', content):
        if _php_uses_docker((path.parent / match.group(1).lstrip("/")).resolve(), seen): return True
    uncommented = re.sub(r'/\*.*?\*/|//[^\n]*|#[^\n]*', '', content, flags=re.S)
    return bool(re.search(r'\b(?:passthru|exec|system|shell_exec|proc_open|popen)\s*\([^)]*["\'][^"\']*\bdocker\b', uncommented, re.I))


def _needs_docker(command):
    try:
        policy = json.loads((ROOT / ".quality-graph/verification-policy.json").read_text())
        inventory = json.loads((ROOT / policy["inventory"]).read_text())
        paths = []
        if command[:2] == ["make", "test"] or command[:1] == ["make"]:
            category = next((item.split("=", 1)[1] for item in command if item.startswith("CATEGORY=")), None)
            if category is None and command:
                category = {"unit-test": "unit", "db-test": "integration",
                            "characterization-test": "governance", "e2e-test": "e2e"}.get(command[-1])
            if category:
                paths = [path for path, value in inventory.items() if value == category]
        elif command:
            paths = [item for item in command[1:] if item in inventory]
        for path in paths:
            target = ROOT / path
            if target.suffix == ".py" and _python_uses_docker(target): return True
            if target.suffix == ".php" and _php_uses_docker(target): return True
        return False
    except (OSError, KeyError, IndexError, TypeError, json.JSONDecodeError):
        return False


def execute(profile, command, fixture, task, run_id, legacy):
    try:
        required_profile = _profile_for(command)
    except ValueError:
        if not profile:
            raise
        required_profile = profile
    profile = profile or required_profile
    if PROFILE_ORDER.get(profile, -1) < PROFILE_ORDER[required_profile]:
        return _setup_failure_record(profile, command, fixture, task, run_id, legacy,
                                     "CATEGORY_ENVIRONMENT_MISMATCH")
    environment = prepare(profile)
    source_before = harness.source_details()
    vendor = ROOT / "vendor"
    foreign_autoload = os.path.lexists(vendor)
    fixture_before = harness.fixture_identity(fixture)
    home = harness.evidence_home()
    owned = home / "runs" / (str(task or "task") + "-" + str(run_id or "run") + "-" + uuid.uuid4().hex)
    owned.mkdir(parents=True, exist_ok=False)
    snapshot = owned / "snapshot"; artifacts = owned / "artifacts"; artifacts.mkdir()
    nested_home = artifacts / "evidence"; nested_home.mkdir()
    git_dir = owned / "git"
    nested_docker = _needs_docker(command)
    source_path = str(snapshot) if nested_docker else "/workspace/source"
    git_path = str(git_dir) if nested_docker else "/run/fmonitor-git-metadata"
    artifact_path = str(artifacts) if nested_docker else "/workspace/artifacts"
    _copy_snapshot(snapshot)
    subprocess.run(["git", "clone", "--mirror", "--no-hardlinks", str(ROOT), str(git_dir)],
                   check=True, capture_output=True)
    subprocess.run(["git", f"--git-dir={git_dir}", "config", "core.bare", "false"], check=True)
    subprocess.run(["git", f"--git-dir={git_dir}", "config", "core.worktree", str(snapshot)], check=True)
    subprocess.run(["git", f"--git-dir={git_dir}", f"--work-tree={snapshot}", "reset", "--mixed", "HEAD"],
                   check=True, capture_output=True)
    info = git_dir / "info"; info.mkdir(exist_ok=True)
    with (info / "exclude").open("a", encoding="utf-8") as stream:
        stream.write("\n/vendor\n")
    (snapshot / ".git").write_text("gitdir: " + str(git_dir) + "\n", encoding="utf-8")
    # Project bootstrap keeps its public vendor/autoload.php path, while the
    # symlink target remains an immutable image layer outside the source tree.
    observed = _run(["python3", str(snapshot / "tools/delivery/harness.py"), "state"], cwd=snapshot,
                    env={**os.environ, "FMONITOR_HARNESS_HOME": str(nested_home)})
    if observed.returncode:
        raise ValueError("SNAPSHOT_IDENTITY_UNAVAILABLE: " + observed.stderr.strip())
    snapshot_identity = json.loads(observed.stdout)["source"]
    subprocess.run(["git", f"--git-dir={git_dir}", "config", "core.worktree", source_path], check=True)
    (snapshot / ".git").write_text("gitdir: " + git_path + "\n", encoding="utf-8")
    token = hashlib.sha256((str(ROOT.resolve()) + str(owned)).encode()).hexdigest()[:16]
    network = "fm2v-net-" + token; database = "fm2v-db-" + token; runner = "fm2v-run-" + token
    resources = {"containers": [], "networks": [], "volumes": []}
    process = None; stdout = b""; stderr = b""; raw = 1; interrupted = False; borrowed_db = False
    records = home / "records"; outdir = home / "stdout"; errdir = home / "stderr"
    for directory in (records, outdir, errdir): directory.mkdir(parents=True, exist_ok=True)
    identifier = f"{time.time_ns()}-{uuid.uuid4().hex}"; started = time.time()
    database_values = {key: os.environ.get("FMONITOR_TEST_DB_" + key) for key in
                       ("HOST", "PORT", "NAME", "USER", "PASSWORD")}
    previous_handlers = {}
    def stop(signum, frame):
        raise harness.RunnerInterrupted(signum)
    try:
        for signum in (signal.SIGTERM, signal.SIGINT):
            previous_handlers[signum] = signal.signal(signum, stop)
        if foreign_autoload:
            raise ValueError("AUTOLOAD_ORIGIN: checkout vendor is not trusted")
        inherited_envelope = os.environ.get("FMONITOR_EXECUTION_DB_ENVELOPE")
        current_envelope = harness.canonical(database_values).strip()
        explicit_db_operation = command[:2] in (["make", "migrate"], ["make", "test-db-reset"])
        borrowed_db = ("mariadb" in PROFILES[profile] and all(database_values.values())
                       and (inherited_envelope is None or inherited_envelope != current_envelope
                            or explicit_db_operation
                            or os.environ.get("FMONITOR_EXECUTION_BORROW_DB") == "1"))
        if borrowed_db and Path("/.dockerenv").is_file() and os.environ.get("HOSTNAME"):
            run_network = "container:" + os.environ["HOSTNAME"]
        else:
            subprocess.run(["docker", "network", "create", network], check=True, capture_output=True)
            resources["networks"].append(network)
            run_network = network
        if "mariadb" in PROFILES[profile] and not borrowed_db:
            pins = _pins()
            subprocess.run(["docker", "run", "-d", "--name", database, "--network", network,
                "-e", "MARIADB_ROOT_PASSWORD=fmonitor2_test_root_local", "-e", "MARIADB_DATABASE=fmonitor2_test",
                "-e", "MARIADB_USER=fmonitor2_test", "-e", "MARIADB_PASSWORD=fmonitor2_test_local",
                pins["MARIADB_IMAGE"]], check=True, capture_output=True)
            resources["containers"].append(database)
            for _ in range(60):
                ready = _run(["docker", "exec", database, "healthcheck.sh", "--connect", "--innodb_initialized"])
                if ready.returncode == 0: break
                time.sleep(.5)
            else: raise ValueError("SERVICE_UNAVAILABLE: mariadb")
        mounts = ["--mount", f"type=bind,src={snapshot},dst={source_path},readonly",
                  "--mount", f"type=bind,src={git_dir},dst={git_path}",
                  "--mount", f"type=bind,src={artifacts},dst={artifact_path}",
                  "--mount", f"type=bind,src={nested_home},dst={nested_home}"]
        if nested_docker:
            mounts += ["--mount", "type=bind,src=/var/run/docker.sock,dst=/var/run/docker.sock",
                       "--mount", f"type=bind,src={snapshot},dst=/workspace/source,readonly"]
        if fixture:
            mounts += ["--mount", f"type=bind,src={Path(fixture).resolve()},dst=/workspace/fixture"]
        if "mariadb" in PROFILES[profile] and not borrowed_db:
            run_network = "container:" + database
        if not borrowed_db:
            database_values.update(HOST="127.0.0.1", PORT="3306", NAME="fmonitor2_test",
                                   USER="fmonitor2_test", PASSWORD="fmonitor2_test_local")
        docker_command = ["docker", "run", "--name", runner, "--network", run_network, "--workdir", source_path,
            *mounts, "-e", "FMONITOR_EXECUTION_ACTIVE=" + source_path,
            "-e", "FMONITOR_HARNESS_HOME=" + str(nested_home),
            "-e", "PYTHONDONTWRITEBYTECODE=1",
            "-e", "TMPDIR=" + artifact_path,
            "-e", "FMONITOR_TEST_PLAYWRIGHT_MODULE=/opt/fmonitor-dependencies/node_modules/playwright",
            "-e", "FMONITOR_SHLZ_UI_ROOT=/workspace/shlz-ui", "-e", "FMONITOR_EXECUTION_FIXTURE_ROOT=/workspace/fixture",
            environment["image"], *command]
        image_index = docker_command.index(environment["image"])
        if "mariadb" in PROFILES[profile]:
            database_env = []
            for key, value in database_values.items():
                database_env += ["-e", "FMONITOR_TEST_DB_" + key + "=" + value]
            database_env += ["-e", "FMONITOR_EXECUTION_DB_ENVELOPE="
                             + harness.canonical(database_values).strip()]
            docker_command[image_index:image_index] = database_env
        process = subprocess.Popen(docker_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, start_new_session=True)
        resources["containers"].append(runner)
        try:
            stdout, stderr = process.communicate(); raw = process.returncode
        except KeyboardInterrupt:
            interrupted = True; process.terminate(); stdout, stderr = process.communicate(timeout=10); raw = process.returncode
    except harness.RunnerInterrupted:
        interrupted = True
    except KeyboardInterrupt:
        interrupted = True
    except (OSError, subprocess.SubprocessError, ValueError) as error:
        stderr += ("SETUP_FAILURE: " + str(error) + "\n").encode(); raw = 1
    finally:
        if process is not None and process.poll() is None: process.terminate()
        subprocess.run(["docker", "rm", "-f", runner], capture_output=True)
        subprocess.run(["docker", "rm", "-f", database], capture_output=True)
        subprocess.run(["docker", "network", "rm", network], capture_output=True)
        for signum, handler in previous_handlers.items(): signal.signal(signum, handler)
    source_after = harness.source_details(); fixture_after = harness.fixture_identity(fixture)
    # The explicit fixture root is the run's mutable handshake/artifact input;
    # retain its initial identity but do not reinterpret expected mutations as
    # candidate-source drift.
    stale = source_after["digest"] != source_before["digest"]
    combined = stdout + b"\n" + stderr
    verdict = "INTERRUPTED" if interrupted or raw in (-signal.SIGTERM, 143) else ("GREEN" if raw == 0 else "SETUP_FAILURE" if b"SETUP_FAILURE" in stderr else "REGRESSION_FAILURE")
    if raw != 0 and legacy.intended_red and legacy.intended_red.encode() in combined:
        verdict = "INTENDED_RED"
    if legacy.purpose == "diagnostic" and verdict == "GREEN":
        verdict = "DIAGNOSTIC"
    record_path = records / (identifier + ".json"); stdout_path = outdir / (identifier + ".log"); stderr_path = errdir / (identifier + ".log")
    stdout_path.write_bytes(stdout); stderr_path.write_bytes(stderr)
    record = {"id": identifier, "argv": command, "cwd": str(ROOT), "source": source_before["digest"],
        "candidate_source": source_before["digest"], "executable_source": source_before["executable_digest"],
        "end_source": source_after["digest"], "source_drift": stale, "task": task, "run_id": run_id,
        "command_id": legacy.command_id, "purpose": legacy.purpose,
        "command_environment": json.loads(legacy.command_environment) if legacy.command_environment else None,
        "acceptance_id": legacy.acceptance_id, "started_at": started, "finished_at": time.time(),
        "exit_code": 143 if verdict == "INTERRUPTED" else raw, "raw_child_returncode": raw,
        "outcome": "UNKNOWN" if stale and verdict == "GREEN" else verdict, "command_verdict": verdict,
        "applicability": "STALE" if stale else "APPLICABLE", "stdout_path": str(stdout_path), "stderr_path": str(stderr_path),
        "snapshot": {"identity": snapshot_identity, "path": str(snapshot), "container_path": source_path},
        "execution": {**{k: environment[k] for k in ("profile", "image_id", "platform", "lockfiles", "runtimes", "dependency_installs")},
            "dependency_root": "/opt/fmonitor-dependencies",
            "service_identity": ("borrowed:" + hashlib.sha256(harness.canonical(database_values).encode()).hexdigest()[:16]
                                 if borrowed_db else token),
            "borrowed_services": (["mariadb"] if borrowed_db else []),
            "fixture_identity": fixture_before,
            "resource_ids": resources}}
    harness._write_json(record_path, record)
    summary = {"id": identifier, "outcome": record["outcome"], "record_path": str(record_path)}
    print(harness.canonical(summary))
    return 0 if record["outcome"] in {"GREEN", "DIAGNOSTIC"} else (143 if verdict == "INTERRUPTED" else 1)


def _setup_failure_record(profile, command, fixture, task, run_id, legacy, reason):
    home = harness.evidence_home()
    for name in ("records", "stdout", "stderr"):
        (home / name).mkdir(parents=True, exist_ok=True)
    identifier = f"{time.time_ns()}-{uuid.uuid4().hex}"
    stderr_path = home / "stderr" / (identifier + ".log")
    stdout_path = home / "stdout" / (identifier + ".log")
    record_path = home / "records" / (identifier + ".json")
    stderr_path.write_text("SETUP_FAILURE: " + reason + "\n")
    stdout_path.write_text("")
    source = harness.source_details()
    record = {"id": identifier, "argv": command, "cwd": str(ROOT), "source": source["digest"],
              "candidate_source": source["digest"], "executable_source": source["executable_digest"],
              "end_source": source["digest"], "source_drift": False, "task": task, "run_id": run_id,
              "command_id": legacy.command_id, "purpose": legacy.purpose, "command_environment": None,
              "acceptance_id": legacy.acceptance_id, "exit_code": 1, "raw_child_returncode": 1,
              "outcome": "SETUP_FAILURE", "command_verdict": "SETUP_FAILURE", "applicability": "APPLICABLE",
              "stdout_path": str(stdout_path), "stderr_path": str(stderr_path), "execution": {"profile": profile}}
    harness._write_json(record_path, record)
    print(harness.canonical({"id": identifier, "outcome": "SETUP_FAILURE", "record_path": str(record_path)}))
    return 1


def main(argv):
    if argv[0] == "environment":
        parser = argparse.ArgumentParser(); parser.add_argument("--profile", required=True); parser.add_argument("--prepare", action="store_true")
        args = parser.parse_args(argv[1:]); value = prepare(args.profile); print(harness.canonical(value)); return 0
    parser = argparse.ArgumentParser(); parser.add_argument("--profile"); parser.add_argument("--fixture"); parser.add_argument("--task"); parser.add_argument("--run-id")
    parser.add_argument("--reason"); parser.add_argument("--timeout"); parser.add_argument("--intended-red")
    parser.add_argument("--command-id"); parser.add_argument("--purpose", choices=("acceptance", "boundary", "category", "diagnostic"))
    parser.add_argument("--command-environment"); parser.add_argument("--acceptance-id"); parser.add_argument("argv", nargs=argparse.REMAINDER)
    args = parser.parse_args(argv[1:]); command = args.argv[1:] if args.argv[:1] == ["--"] else args.argv
    return execute(args.profile, command, args.fixture, args.task, args.run_id, args)
