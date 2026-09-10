#!/usr/bin/env python3
"""Compact, append-only delivery runner and measurement report."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import signal
import stat
import subprocess
import sys
import time
import uuid

ROOT = Path(__file__).resolve().parents[2]
UNKNOWN = "UNKNOWN"


class RunnerInterrupted(Exception):
    pass


def canonical(value):
    return json.dumps(value, ensure_ascii=True, sort_keys=True, separators=(",", ":"))


def evidence_home():
    configured = os.environ.get("FMONITOR_HARNESS_HOME")
    home = Path(configured).expanduser() if configured else Path.home() / ".local/share/fmonitor-2/delivery-harness"
    home = home.resolve()
    probe = home
    while not probe.exists() and probe != probe.parent:
        probe = probe.parent
    repository = subprocess.run(["git", "-C", str(probe), "rev-parse", "--show-toplevel"],
                                capture_output=True, text=True)
    if repository.returncode == 0:
        top = Path(repository.stdout.strip()).resolve()
        try:
            home.relative_to(top)
        except ValueError:
            pass
        else:
            raise ValueError("FMONITOR_HARNESS_HOME must be outside every source checkout")
    home.mkdir(parents=True, exist_ok=True, mode=0o700)
    os.chmod(home, 0o700)
    return home


def _git(*args, binary=False):
    return subprocess.run(["git", *args], cwd=ROOT, capture_output=True,
                          text=not binary, check=True).stdout


def source_details():
    """Hash artifact bytes and executable modes; report index layout separately."""
    names = set()
    for args in (("ls-files", "-z"), ("ls-files", "--others", "--exclude-standard", "-z")):
        names.update(x for x in _git(*args, binary=True).split(b"\0") if x)
    digest = hashlib.sha256()
    for raw in sorted(names):
        relative = raw.decode("utf-8", errors="surrogateescape")
        path = ROOT / relative
        digest.update(len(raw).to_bytes(8, "big")); digest.update(raw)
        if not os.path.lexists(path):
            digest.update(b"missing")
            continue
        info = path.lstat()
        if stat.S_ISLNK(info.st_mode):
            mode = b"120000"; content = os.readlink(path).encode(errors="surrogateescape")
        elif path.is_file():
            mode = b"100755" if info.st_mode & stat.S_IXUSR else b"100644"
            content = path.read_bytes()
        else:
            continue
        digest.update(mode); digest.update(len(content).to_bytes(8, "big")); digest.update(content)
    status = _git("status", "--porcelain=v1", "-z", binary=True)
    return {"digest": digest.hexdigest(), "head": _git("rev-parse", "HEAD").strip(),
            "dirty": bool(status), "status_digest": hashlib.sha256(status).hexdigest()}


def source_identity():
    return source_details()["digest"]


def _local_key(home):
    path = home / ".environment-key"
    if not path.exists():
        try:
            descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(descriptor, "wb") as stream:
                stream.write(secrets.token_bytes(32))
        except FileExistsError:
            pass
    return path.read_bytes()


def environment_identity(environment=None):
    environment = environment or os.environ
    names = sorted(name for name in environment
                   if name == "PATH" or name.startswith("FMONITOR_"))
    payload = canonical([[name, environment[name]] for name in names]).encode()
    digest = hashlib.blake2b(payload, key=_local_key(evidence_home()), digest_size=32).hexdigest()
    return {"digest": digest, "covered_keys": names,
            "limitations": ["external mutable services require an explicit fixture identity"]}


def fixture_identity(value):
    if not value:
        return UNKNOWN
    path = Path(value).expanduser().resolve()
    digest = hashlib.sha256()
    if not os.path.lexists(path):
        return "missing:" + str(path)
    paths = [path] if not path.is_dir() else sorted(p for p in path.rglob("*") if p.is_file() or p.is_symlink())
    for item in paths:
        relative = str(item.relative_to(path)) if path.is_dir() else path.name
        digest.update(relative.encode(errors="surrogateescape"))
        digest.update(os.readlink(item).encode(errors="surrogateescape") if item.is_symlink() else item.read_bytes())
    return digest.hexdigest()


def _write_json(path, value):
    temporary = path.with_suffix(path.suffix + ".tmp-" + uuid.uuid4().hex)
    temporary.write_text(canonical(value) + "\n", encoding="utf-8")
    os.chmod(temporary, 0o600)
    os.replace(temporary, path)


def append_event(kind, payload, identity):
    directory = evidence_home() / "events"
    directory.mkdir(parents=True, exist_ok=True)
    event = {"id": identity, "kind": kind, "recorded_at": time.time(), **payload}
    path = directory / (hashlib.sha256(identity.encode()).hexdigest() + ".json")
    if not path.exists():
        _write_json(path, event)
    return path


def load_events():
    values = {}
    for path in sorted((evidence_home() / "events").glob("*.json")):
        try:
            event = json.loads(path.read_text())
            values.setdefault(event["id"], event)
        except (OSError, ValueError, KeyError):
            continue
    return list(values.values())


def _records():
    records = {}
    for path in sorted((evidence_home() / "records").glob("*.json")):
        try:
            value = json.loads(path.read_text())
            records.setdefault(value["id"], value)
        except (OSError, ValueError, KeyError):
            continue
    return list(records.values())


def hydrate_summary(summary):
    """Load omitted GREEN metadata from the canonical retained record."""
    if not isinstance(summary, dict) or not all(key in summary for key in ("id", "outcome", "record_path")):
        raise ValueError("invalid harness summary")
    path = Path(summary["record_path"])
    if not path.is_absolute() or path.parent.resolve() != (evidence_home() / "records").resolve():
        raise ValueError("harness record path is outside the evidence store")
    record = json.loads(path.read_text(encoding="utf-8"))
    if record.get("id") != summary["id"] or record.get("outcome") != summary["outcome"]:
        raise ValueError("harness summary does not match retained record")
    return {**record, **summary}


def _auto_reason(argv, cwd, source, fixture, environment):
    previous = [r for r in _records() if r.get("argv") == argv and r.get("cwd") == cwd]
    if not previous:
        return "initial"
    latest = max(previous, key=lambda r: r.get("started_at", ""))
    if latest.get("source") != source:
        return "source_changed"
    if latest.get("fixture") != fixture:
        return "fixture_changed"
    if latest.get("environment") != environment:
        return "environment_changed"
    return "same_source_diagnostic"


def _excerpt(stdout, stderr, outcome, marker):
    combined = stdout + (b"\n" if stdout and stderr else b"") + stderr
    if len(combined) <= 4096:
        return combined.decode("utf-8", errors="replace")
    pieces = [combined[:1536], combined[-1536:]]
    positions = [position for _, position in explicit_outcome_markers(combined)]
    needles = [b"REGRESSION_FAILURE", b"AssertionError", b"FAIL", b"ERROR"]
    if marker:
        needles.append(marker.encode())
    for needle in needles:
        position = combined.find(needle)
        if position >= 0:
            positions.append(position)
    for position in sorted(set(positions)):
        pieces.insert(-1, combined[max(0, position - 256):position + 768])
    value = b"\n... compacted; full logs retained ...\n".join(pieces)[:4080]
    decoded = value.decode("utf-8", errors="replace")
    while len(decoded.encode("utf-8")) > 4096:
        decoded = decoded[:-1]
    return decoded


def explicit_outcome_markers(content):
    """Return protocol markers at line starts without matching domain words."""
    pattern = re.compile(br"(?m)^[ \t]*(SETUP_FAILURE|UNKNOWN)(?=[:]|[ \t]*(?:\r?$))")
    return [(match.group(1).decode("ascii"), match.start(1)) for match in pattern.finditer(content)]


def execute(argv, reason=None, fixture=None, intended_red=None, timeout=None):
    home = evidence_home()
    for name in ("records", "stdout", "stderr"):
        (home / name).mkdir(parents=True, exist_ok=True)
    identifier = f"{time.time_ns()}-{uuid.uuid4().hex}"
    stdout_path = home / "stdout" / (identifier + ".log")
    stderr_path = home / "stderr" / (identifier + ".log")
    record_path = home / "records" / (identifier + ".json")
    started_wall = time.time(); started = time.monotonic()
    source_state = source_details(); source = source_state["digest"]
    environment_coverage = environment_identity(); environment = environment_coverage["digest"]
    fixture_digest = fixture_identity(fixture)
    outcome = UNKNOWN; child_exit = 1; raw_exit = None
    process = None
    previous_handlers = {}
    def interrupted(signum, frame):
        raise RunnerInterrupted(signum)
    try:
        for signum in (signal.SIGTERM, signal.SIGINT):
            previous_handlers[signum] = signal.signal(signum, interrupted)
        process = subprocess.Popen(argv, cwd=ROOT, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                                   start_new_session=True)
        try:
            stdout, stderr = process.communicate(timeout=timeout)
            raw_exit = process.returncode
            child_exit = 128 + (-raw_exit) if raw_exit < 0 else raw_exit
            text = stdout + b"\n" + stderr
            explicit = {name for name, _ in explicit_outcome_markers(text)}
            if "SETUP_FAILURE" in explicit:
                outcome = "SETUP_FAILURE"
            elif "UNKNOWN" in explicit:
                outcome = UNKNOWN
            elif raw_exit < 0:
                outcome = "INTERRUPTED"
            elif child_exit == 0:
                outcome = "GREEN"
            elif intended_red and intended_red.encode() in text:
                outcome = "INTENDED_RED"
            else:
                outcome = "REGRESSION_FAILURE"
        except subprocess.TimeoutExpired:
            os.killpg(process.pid, signal.SIGTERM)
            try:
                stdout, stderr = process.communicate(timeout=2)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL); stdout, stderr = process.communicate()
            raw_exit = process.returncode; child_exit = 124; outcome = "INTERRUPTED"
    except (RunnerInterrupted, KeyboardInterrupt) as error:
        if process is not None and process.poll() is None:
            os.killpg(process.pid, signal.SIGTERM)
            try:
                stdout, stderr = process.communicate(timeout=2)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL); stdout, stderr = process.communicate()
            raw_exit = process.returncode
        else:
            stdout, stderr = b"", b""
        signum = error.args[0] if error.args and isinstance(error.args[0], int) else signal.SIGINT
        child_exit = 128 + signum; outcome = "INTERRUPTED"
    except OSError as error:
        stdout = b""; stderr = ("SETUP_FAILURE: " + str(error) + "\n").encode()
        child_exit = 127; outcome = "SETUP_FAILURE"
    finally:
        for signum, handler in previous_handlers.items():
            signal.signal(signum, handler)
    duration = time.monotonic() - started
    end_source_state = source_details()
    end_fixture_digest = fixture_identity(fixture)
    source_drift = end_source_state["digest"] != source or end_fixture_digest != fixture_digest
    if source_drift and outcome not in {"SETUP_FAILURE", "INTERRUPTED"}:
        outcome = UNKNOWN
    stdout_path.write_bytes(stdout); stderr_path.write_bytes(stderr)
    os.chmod(stdout_path, 0o600); os.chmod(stderr_path, 0o600)
    selected_reason = reason or _auto_reason(argv, str(ROOT), source, fixture_digest, environment)
    excerpt = _excerpt(stdout, stderr, outcome, intended_red)
    record = {"id": identifier, "argv": argv, "cwd": str(ROOT), "source": source,
              "source_state": source_state, "environment": environment,
              "end_source": end_source_state["digest"], "end_source_state": end_source_state,
              "environment_coverage": environment_coverage, "fixture": fixture_digest,
              "end_fixture": end_fixture_digest, "source_drift": source_drift,
              "reason": selected_reason, "started_at": started_wall,
              "finished_at": time.time(), "duration_seconds": duration,
              "exit_code": child_exit, "raw_child_returncode": raw_exit,
              "outcome": outcome, "stdout_path": str(stdout_path),
              "stderr_path": str(stderr_path), "output_bytes": len(stdout) + len(stderr)}
    if outcome == "GREEN":
        summary = {"id": identifier, "outcome": outcome, "record_path": str(record_path)}
    else:
        summary = {key: record[key] for key in ("id", "outcome", "exit_code", "stdout_path", "stderr_path", "output_bytes")}
        summary.update({"record_path": str(record_path), "excerpt": excerpt})
    record["summary_bytes"] = len((canonical(summary) + "\n").encode())
    _write_json(record_path, record)
    print(canonical(summary))
    return child_exit, summary


def report():
    records = _records(); events = load_events()
    tools = [e for e in events if e.get("kind") == "tool_call"]
    agents = [e for e in events if e.get("kind") == "agent_task"]
    reviews = [e for e in events if e.get("kind") == "review_return"]
    result = {
        "checks": len(records), "tool_calls": len(tools), "agent_tasks": len(agents),
        "review_returns": len(reviews),
        "observed_tool_output_bytes": sum(int(e.get("output_bytes", 0)) for e in tools),
        "token_telemetry": {"status": UNKNOWN, "total_tokens": UNKNOWN,
                            "delta_tokens": UNKNOWN,
                            "source": "installed Codex hooks",
                            "reason": "installed v1 hooks expose no supported token fields"},
        "coverage": {
            "checks": "unique runner records in this evidence home; earlier checks are not retroactive",
            "agent_tasks": "unique observed SubagentStart session/turn/agent identities; followups without an event are UNKNOWN",
            "tool_calls": "unique observed PostToolUse identities scoped by session",
            "review_returns": "unique observed CHANGES_REQUESTED SubagentStop session/turn/agent identities; unobserved rework is UNKNOWN",
            "model_output": "bytes in observed PostToolUse responses only; model-visible transport coverage is UNKNOWN",
            "token_telemetry": UNKNOWN,
        },
    }
    print(canonical(result))
    return 0


def main(argv=None):
    argv = list(sys.argv[1:] if argv is None else argv)
    if argv and argv[0] == "run":
        parser = argparse.ArgumentParser()
        parser.add_argument("--reason"); parser.add_argument("--fixture")
        parser.add_argument("--intended-red"); parser.add_argument("--timeout", type=float)
        parser.add_argument("argv", nargs=argparse.REMAINDER)
        args = parser.parse_args(argv[1:])
        command = args.argv[1:] if args.argv[:1] == ["--"] else args.argv
        if not command:
            parser.error("run requires argv after --")
        return execute(command, args.reason, args.fixture, args.intended_red, args.timeout)[0]
    if argv == ["report"]:
        return report()
    try:
        import harness_context
        return harness_context.main(argv)
    except ImportError as error:
        print(f"SETUP_FAILURE: context integration unavailable: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
