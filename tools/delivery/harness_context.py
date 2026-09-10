#!/usr/bin/env python3
"""State, Codex hook and review-package support for the delivery harness."""

import hashlib
import argparse
import importlib.util
import json
import os
from pathlib import Path
import re
import shlex
import subprocess
import sys
import tempfile
import time
import uuid


def _helpers(value=None):
    if value is not None:
        return value
    import harness
    return harness


def _json(value):
    print(json.dumps(value, ensure_ascii=False, sort_keys=True))


def _run(root, argv, *, check=False, timeout=30):
    result = subprocess.run(argv, cwd=root, text=True, capture_output=True, timeout=timeout)
    if check and result.returncode:
        raise ValueError(result.stderr.strip() or result.stdout.strip() or "command failed")
    return result


def _now():
    return time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())


def _source(helpers, root):
    try:
        return helpers.source_identity(root)
    except TypeError:
        return helpers.source_identity()


def _state_file(helpers):
    identity = _worktree_key(helpers)
    path = helpers.evidence_home() / "state" / ("github-" + identity + ".json")
    path.parent.mkdir(parents=True, exist_ok=True)
    return path


def _ci_status(checks):
    if not checks:
        return "UNKNOWN"
    if any(item.get("status") != "COMPLETED" for item in checks):
        return "PENDING"
    conclusions = {item.get("conclusion") for item in checks}
    return "SUCCESS" if conclusions == {"SUCCESS"} else "FAILURE"


def _compute_state(helpers):
    root = helpers.ROOT
    checked_at = _now()
    head = _run(root, ["git", "rev-parse", "HEAD"], check=True).stdout.strip()
    dirty = bool(_run(root, ["git", "status", "--porcelain=v1", "--untracked-files=all"], check=True).stdout)
    source = _source(helpers, root)
    cache_path = _state_file(helpers)
    previous = {}
    try:
        previous = json.loads(cache_path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        pass
    github = {"state": "UNKNOWN", "headRefOid": "UNKNOWN", "checked_at": checked_at,
              "last_success_at": previous.get("last_success_at", "UNKNOWN")}
    ci = {"status": "UNKNOWN", "source": "UNKNOWN"}
    try:
        response = _run(root, ["gh", "pr", "view", "--json",
                               "number,state,headRefOid,mergeCommit,url,statusCheckRollup"], timeout=3)
    except (OSError, subprocess.TimeoutExpired):
        response = None
    if response is not None and response.returncode == 0:
        try:
            live = json.loads(response.stdout)
            if live.get("state") not in {"OPEN", "CLOSED", "MERGED"} or not isinstance(live.get("headRefOid"), str):
                raise ValueError("malformed GitHub state")
            github.update({key: live.get(key) for key in ("number", "state", "headRefOid", "mergeCommit", "url")})
            github["checked_at"] = checked_at
            github["last_success_at"] = checked_at
            checks = live.get("statusCheckRollup") or []
            if not dirty and live.get("headRefOid") == head:
                ci = {"status": _ci_status(checks), "source": head}
            cache_path.write_text(json.dumps({"last_success_at": checked_at}) + "\n", encoding="utf-8")
        except (OSError, TypeError, json.JSONDecodeError):
            pass
    state = {"source": source, "head": head, "dirty": dirty, "github": github,
             "ci": ci, "deployment": "UNKNOWN", "active_binding": _active_binding(helpers)}
    state["next_action"] = ("merged; await the next owner task" if github.get("state") == "MERGED"
                            else "prepare/review exact source before publication")
    live_path = helpers.evidence_home() / "state" / ("live-" + _worktree_key(helpers) + ".json")
    live_path.write_text(json.dumps(state, ensure_ascii=False, sort_keys=True) + "\n", encoding="utf-8")
    return state


def command_state(args, helpers):
    _json(_compute_state(helpers))
    return 0


def _event_identity(event):
    session = str(event.get("session_id", "UNKNOWN"))
    name = str(event.get("hook_event_name", "UNKNOWN"))
    if name == "PostToolUse":
        return f"{session}:tool:{event.get('tool_use_id', 'UNKNOWN')}"
    if name in {"SubagentStart", "SubagentStop"}:
        return f"{session}:agent:{event.get('turn_id', 'UNKNOWN')}:{event.get('agent_id', 'UNKNOWN')}:{name}"
    if name == "SessionStart":
        return f"{session}:session:{name}:{event.get('source', 'UNKNOWN')}"
    return f"{session}:{event.get('turn_id', 'session')}:{name}"


def _integration_identity(root):
    digest = hashlib.sha256()
    for relative in (".codex/hooks.json", "tools/delivery/harness.py", "tools/delivery/harness_context.py"):
        path = root / relative
        digest.update(relative.encode())
        digest.update(path.read_bytes() if path.is_file() else b"MISSING")
    common = _run(root, ["git", "rev-parse", "--path-format=absolute", "--git-common-dir"], check=True).stdout.strip()
    return str(Path(common).resolve()), digest.hexdigest()


def _append_hook_event(helpers, event):
    name = str(event.get("hook_event_name", "UNKNOWN"))
    repository, fingerprint = _integration_identity(helpers.ROOT)
    payload = {"event": name, "session_id": event.get("session_id"), "observed_at": _now(),
               "repository": repository, "cwd": event.get("cwd"),
               "integration_fingerprint": fingerprint}
    if name == "PostToolUse":
        response = event.get("tool_response", "")
        if not isinstance(response, str):
            response = json.dumps(response, ensure_ascii=False, sort_keys=True)
        payload.update(tool_name=event.get("tool_name"), tool_use_id=event.get("tool_use_id"),
                       output_bytes=len(response.encode("utf-8")))
    elif name in {"SubagentStart", "SubagentStop"}:
        payload.update(turn_id=event.get("turn_id"), agent_id=event.get("agent_id"),
                       agent_type=event.get("agent_type"))
        if name == "SubagentStop":
            message = event.get("last_assistant_message")
            if isinstance(message, str) and re.search(r"\bCHANGES_REQUESTED\b", message):
                payload["verdict"] = "CHANGES_REQUESTED"
    elif name == "SessionStart":
        payload["source"] = event.get("source")
    identity = _event_identity(event)
    helpers.append_event("hook_receipt", payload, identity=identity)
    if name == "PostToolUse":
        helpers.append_event("tool_call", payload, identity=identity + ":measurement")
    elif name == "SubagentStart":
        helpers.append_event("agent_task", payload, identity=identity.rsplit(":", 1)[0])
    elif name == "SubagentStop" and payload.get("verdict") == "CHANGES_REQUESTED":
        helpers.append_event("review_return", payload, identity=identity.rsplit(":", 1)[0] + ":return")


def _worktree_realpath(helpers):
    return str(Path(helpers.ROOT).resolve())


def _worktree_key(helpers):
    return hashlib.sha256(_worktree_realpath(helpers).encode()).hexdigest()[:20]


def _binding_path(helpers):
    return helpers.evidence_home() / "state" / ("active-binding-" + _worktree_key(helpers) + ".json")


def _active_binding(helpers):
    path = _binding_path(helpers)
    try:
        value = json.loads(path.read_text(encoding="utf-8"))
        if value.get("worktree") == _worktree_realpath(helpers):
            return value
    except (OSError, TypeError, json.JSONDecodeError):
        pass
    return None


def _refresh_active_binding(helpers):
    active = _active_binding(helpers)
    if not active:
        return None
    module = _load_change_verification(helpers.ROOT)
    plan = module.build(active["base"], active["input"])
    state_dir = helpers.evidence_home() / "state"
    refreshed = state_dir / ("active-verification-plan-" + _worktree_key(helpers) + ".json")
    refreshed.write_text(module.canonical(plan), encoding="utf-8")
    active.update(source=_source(helpers, helpers.ROOT), plan=str(refreshed),
                  contracts=sorted({item["spec_path"] for item in plan["acceptances"]}),
                  obligation_count=len(plan["commands"]), refreshed_at=_now())
    binding_path = _binding_path(helpers)
    temporary = binding_path.with_suffix(".tmp-" + uuid.uuid4().hex)
    temporary.write_text(json.dumps(active, ensure_ascii=False, sort_keys=True) + "\n", encoding="utf-8")
    os.replace(temporary, binding_path)
    return active


def _context(helpers, role="root"):
    root = helpers.ROOT
    goal = root / "docs/operations/current-delivery-goal.md"
    prefix = (f"FMonitor delivery context ({role}). Read {goal.relative_to(root)} and AGENTS.md. "
            "Use tools/delivery/harness.py state for exact source/PR/CI and prepare role packages. "
            "Follow existing Gates 1–5; root owns scope/spec/tests, executor implements, independent "
            "reviewers decide Gates 3/5. Preserve WIP, authorization and history. Full logs/evidence "
            "remain outside the checkout; UNKNOWN is not approval or GREEN.")
    try:
        active = _refresh_active_binding(helpers)
    except (OSError, TypeError, ValueError, subprocess.SubprocessError) as error:
        active = _active_binding(helpers)
        prefix += f" ACTIVE_PLAN_REFRESH_FAILED={type(error).__name__}."
    if not active:
        return prefix + " ROOT_SCOPE_REQUIRED: bind the owner's issue to an OpenSpec/verification input once before implementation."
    role_route = {"reviewer": "Reviewer independently checks the supplied gate/source/evidence and returns a verdict; preparation is not approval.",
                  "executor": "Executor changes only the bound scope and records focused verification through harness run.",
                  "root": "Root resolves scope/spec/tests and dispatches separate executor and independent reviews."}.get(role, "Use only this role's bounded package.")
    return (prefix + " " + role_route + f" Active binding: input={active['input']}; base={active['base']}; "
            f"contracts={','.join(active.get('contracts', []))}; source_at_prepare={active['source']}; "
            f"obligations={active.get('obligation_count', 'UNKNOWN')}; plan={active['plan']}; package={active['package_path']}.")


def _task_prompt(prompt):
    lowered = prompt.casefold()
    patterns = (r"\bреализ", r"\bпродолж", r"\bimplement\b", r"\bcontinue\b")
    return any(re.search(pattern, lowered) for pattern in patterns)


def command_hook(args, helpers):
    try:
        event = json.load(sys.stdin)
    except (json.JSONDecodeError, TypeError) as error:
        print(f"invalid hook input: {error}", file=sys.stderr)
        return 1
    if not isinstance(event, dict) or not isinstance(event.get("hook_event_name"), str):
        print("invalid hook input", file=sys.stderr)
        return 1
    _append_hook_event(helpers, event)
    name = event["hook_event_name"]
    if name == "SessionStart":
        state = _compute_state(helpers)
        summary = (f" Exact state: head={state['head']}; source={state['source']}; dirty={state['dirty']}; "
                   f"PR={state['github']['state']}; CI={state['ci']['status']}; next={state['next_action']}.")
        output = {"hookSpecificOutput": {"hookEventName": name,
                  "additionalContext": _context(helpers) + summary}}
        _json(output)
    elif name == "UserPromptSubmit" and _task_prompt(str(event.get("prompt", ""))):
        text = _context(helpers) + " This is a delivery task: route it through the applicable Gate and prepared package."
        _json({"hookSpecificOutput": {"hookEventName": name, "additionalContext": text}})
    elif name == "SubagentStart":
        role = str(event.get("agent_type") or "executor")
        _json({"hookSpecificOutput": {"hookEventName": name, "additionalContext": _context(helpers, role)}})
    elif name == "SubagentStop":
        _json({})
    return 0


def command_doctor(args, helpers):
    config = helpers.ROOT / ".codex/hooks.json"
    configured = False
    limitations = ["Hook inputs expose no supported token telemetry; token counters remain UNKNOWN.",
                   "Hosted tools are outside PostToolUse coverage."]
    try:
        value = json.loads(config.read_text(encoding="utf-8"))
        configured = all(name in value.get("hooks", {}) for name in
                         ("SessionStart", "UserPromptSubmit", "PostToolUse", "SubagentStart", "SubagentStop"))
    except (OSError, TypeError, json.JSONDecodeError):
        limitations.append("Repository hook configuration is missing or invalid.")
    loader = getattr(helpers, "load_unique_events", None) or helpers.load_events
    events = loader()
    repository, fingerprint = _integration_identity(helpers.ROOT)
    observed = sorted({item.get("event") for item in events
                       if item.get("kind") == "hook_receipt" and item.get("event")
                       and item.get("repository") == repository
                       and item.get("integration_fingerprint") == fingerprint})
    required = {"SessionStart", "UserPromptSubmit"}
    user_config = Path.home() / ".codex/hooks.json"
    user_installed = False
    try:
        installed = json.loads(user_config.read_text(encoding="utf-8"))
        user_installed = any("hook-dispatcher.py" in handler.get("command", "")
                             for groups in installed.get("hooks", {}).values() for group in groups
                             for handler in group.get("hooks", []) if isinstance(handler, dict))
    except (OSError, TypeError, json.JSONDecodeError):
        pass
    integration = "OBSERVED" if user_installed and required <= set(observed) else ("CONFIGURED" if configured or user_installed else "MISSING")
    if integration != "OBSERVED":
        limitations.append("Actual Codex startup/task routing has not both been observed in local receipts.")
    _json({"integration": integration, "config_path": str(config), "configured": configured,
           "user_config_path": str(user_config), "user_installed": user_installed,
           "repository": repository, "integration_fingerprint": fingerprint,
           "observed_events": observed, "limitations": limitations,
           "token_telemetry": {"status": "UNKNOWN", "source": "unsupported_hook_interface"}})
    return 0


_DISPATCHER = r'''#!/usr/bin/env python3
"""Dispatch FMonitor hooks only inside explicitly registered Git repositories."""
import json
import os
from pathlib import Path
import subprocess
import sys

def git(cwd, *args):
    result = subprocess.run(["git", *args], cwd=cwd, text=True, capture_output=True)
    return result.stdout.strip() if result.returncode == 0 else None

def main():
    raw = sys.stdin.read()
    try:
        event = json.loads(raw)
        cwd = Path(event["cwd"]).expanduser().resolve()
        registry = json.loads(Path(__file__).with_name("dispatcher-registry.json").read_text())
    except (OSError, KeyError, TypeError, ValueError, json.JSONDecodeError):
        return 0
    top = git(cwd, "rev-parse", "--show-toplevel")
    common = git(cwd, "rev-parse", "--path-format=absolute", "--git-common-dir")
    if not top or not common or str(Path(common).resolve()) not in registry.get("git_common_dirs", []):
        return 0
    harness = Path(top).resolve() / "tools/delivery/harness.py"
    if not harness.is_file():
        name = event.get("hook_event_name")
        if name in {"SessionStart", "UserPromptSubmit", "SubagentStart"}:
            print(json.dumps({"hookSpecificOutput": {"hookEventName": name,
                  "additionalContext": "FMonitor delivery harness is registered but unavailable in this checkout; stop delivery and restore the harness before continuing."}}))
        elif name == "SubagentStop":
            print("{}")
        return 0
    result = subprocess.run(["/usr/bin/python3", str(harness), "hook"], input=raw, text=True)
    return result.returncode

if __name__ == "__main__":
    raise SystemExit(main())
'''


def _atomic_text(path, text, mode=0o600):
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(path.suffix + ".tmp-" + uuid.uuid4().hex)
    descriptor = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL, mode)
    with os.fdopen(descriptor, "w", encoding="utf-8") as stream:
        stream.write(text)
    os.replace(temporary, path)
    os.chmod(path, mode)


def _strict_json_bytes(raw):
    def unique(pairs):
        value = {}
        for key, item in pairs:
            if key in value:
                raise ValueError(f"duplicate JSON key: {key}")
            value[key] = item
        return value
    return json.loads(raw, object_pairs_hook=unique)


def command_install(args, helpers):
    config = (Path(args.config).expanduser() if args.config else Path.home() / ".codex/hooks.json").resolve()
    if config.exists():
        original = config.read_bytes()
        try:
            value = _strict_json_bytes(original)
        except (UnicodeDecodeError, json.JSONDecodeError) as error:
            raise ValueError(f"invalid existing hooks config: {error}") from error
        if not isinstance(value, dict) or not isinstance(value.get("hooks"), dict):
            raise ValueError("invalid existing hooks config structure")
    else:
        original = None
        value = {"hooks": {}}

    home = helpers.evidence_home()
    dispatcher = home / "hook-dispatcher.py"
    registry = home / "dispatcher-registry.json"
    common = _run(helpers.ROOT, ["git", "rev-parse", "--path-format=absolute", "--git-common-dir"], check=True).stdout.strip()
    common = str(Path(common).resolve())
    registered = []
    if registry.exists():
        try:
            current = json.loads(registry.read_text(encoding="utf-8"))
            registered = current.get("git_common_dirs", [])
        except (OSError, TypeError, json.JSONDecodeError):
            raise ValueError("invalid dispatcher registry")
    registered = sorted(set(registered) | {common})
    _atomic_text(dispatcher, _DISPATCHER, 0o700)
    _atomic_text(registry, json.dumps({"version": 1, "git_common_dirs": registered}, sort_keys=True) + "\n")

    definition = json.loads((helpers.ROOT / ".codex/hooks.json").read_text(encoding="utf-8"))
    command = f"/usr/bin/python3 {shlex.quote(str(dispatcher))}"
    hooks = value["hooks"]
    changed = original is None
    for event, groups in definition["hooks"].items():
        destination = hooks.setdefault(event, [])
        own = []
        for group in groups:
            clone = json.loads(json.dumps(group))
            for handler in clone["hooks"]:
                handler["command"] = command
                handler.pop("statusMessage", None)
            own.append(clone)
        filtered = []
        for group in destination:
            if not isinstance(group, dict) or not isinstance(group.get("hooks"), list):
                raise ValueError(f"invalid existing {event} hook group")
            retained = [handler for handler in group["hooks"]
                        if not isinstance(handler, dict) or handler.get("command") != command]
            if retained:
                filtered.append({**group, "hooks": retained})
        replacement = filtered + own
        if replacement != destination:
            hooks[event] = replacement
            changed = True
    rendered = json.dumps(value, ensure_ascii=False, sort_keys=True, indent=2) + "\n"
    if changed or original is None:
        _atomic_text(config, rendered)
    _json({"config": str(config), "dispatcher": str(dispatcher), "registry": str(registry),
           "git_common_dir": common, "changed": changed})
    return 0


def _load_change_verification(root):
    path = root / "tools/delivery/change-verification.py"
    spec = importlib.util.spec_from_file_location("fmonitor_change_verification", path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def _mapped_commands(plan):
    return {tuple(item["argv"]) for item in plan.get("commands", [])
            if item.get("rationale") == "acceptance mapping"}


def _gate_expectations(plan, gate):
    if gate == "5":
        return {command: "GREEN" for command in _mapped_commands(plan)}
    by_test = {}
    for acceptance in plan.get("acceptances", []):
        declared = acceptance.get("gate3_expected")
        for test in acceptance.get("tests", []):
            by_test[test] = declared[test] if declared is not None else "INTENDED_RED"
    return {tuple(item["argv"]): by_test[item["argv"][-1]]
            for item in plan.get("commands", []) if item.get("rationale") == "acceptance mapping"}


def _normalized_argv(argv):
    if not argv:
        return tuple()
    first = Path(argv[0]).name
    if first.startswith("python"):
        first = "python3"
    return (first, *argv[1:])


def _validate_evidence(path, source, expectations):
    try:
        record = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as error:
        raise ValueError(f"evidence is unreadable: {error}") from error
    if record.get("source") != source:
        raise ValueError("evidence source does not match current source")
    try:
        current_environment = _helpers().environment_identity()["digest"]
    except (AttributeError, TypeError):
        current_environment = None
    if current_environment is not None and record.get("environment") != current_environment:
        raise ValueError("evidence environment does not match current environment")
    argv = _normalized_argv(record.get("argv", []))
    expected = expectations.get(argv)
    if expected is None:
        raise ValueError("evidence does not cover a mapped acceptance")
    if record.get("outcome") != expected:
        raise ValueError(f"evidence outcome must be {expected} for this mapped acceptance")
    return {"record": str(path), "argv": record["argv"], "outcome": record["outcome"], "source": source}


def _snapshot(root, output):
    result = _run(root, [sys.executable, "tools/delivery/review-source.py", "capture",
                         "--repo", str(root), "--output", str(output)])
    if result.returncode:
        raise ValueError(result.stderr.strip() or "review snapshot failed")
    return json.loads(result.stdout)


def _delta(root, previous, current, output):
    with tempfile.TemporaryDirectory(prefix="harness-delta-") as directory:
        old = Path(directory) / "old"
        new = Path(directory) / "new"
        try:
            trees = []
            for snapshot, target in ((previous, old), (current, new)):
                result = _run(root, [sys.executable, "tools/delivery/review-source.py", "restore",
                                     "--snapshot", str(snapshot), "--output", str(target)])
                if result.returncode:
                    raise ValueError(result.stderr.strip() or "snapshot restore failed")
                trees.append(_run(target, ["git", "write-tree"], check=True).stdout.strip())
            diff = _run(root, ["git", "diff", "--binary", "--full-index", trees[0], trees[1], "--"], check=True)
            output.write_text(diff.stdout, encoding="utf-8")
        finally:
            for target in (old, new):
                subprocess.run(["git", "-C", str(root), "worktree", "remove", "--force", str(target)],
                               capture_output=True)


def _check_all_whitespace(root):
    descriptor, index_name = tempfile.mkstemp(prefix="harness-whitespace-index-")
    os.close(descriptor)
    Path(index_name).unlink(missing_ok=True)
    environment = os.environ.copy()
    environment["GIT_INDEX_FILE"] = index_name
    try:
        for argv in (["git", "read-tree", "HEAD"], ["git", "add", "-A", "--", "."]):
            result = subprocess.run(argv, cwd=root, text=True, capture_output=True, env=environment)
            if result.returncode:
                raise ValueError(result.stderr.strip() or "whitespace preflight setup failed")
        result = subprocess.run(["git", "diff", "--cached", "--check"], cwd=root,
                                text=True, capture_output=True, env=environment)
        if result.returncode:
            raise ValueError("whitespace errors must be fixed before snapshot: " + result.stdout.strip())
    finally:
        Path(index_name).unlink(missing_ok=True)


def command_prepare(args, helpers):
    root = helpers.ROOT
    _check_all_whitespace(root)
    module = _load_change_verification(root)
    plan_value = module.build(args.base, args.input)
    package_dir = helpers.evidence_home() / "packages" / (time.strftime("%Y%m%dT%H%M%SZ", time.gmtime()) + "-" + uuid.uuid4().hex[:10])
    package_dir.mkdir(parents=True, exist_ok=False)
    plan_path = package_dir / "verification-plan.json"
    plan_path.write_text(module.canonical(plan_value), encoding="utf-8")
    source = _source(helpers, root)
    missing = sorted({test for item in plan_value["acceptances"] for test in item["tests"]
                      if not (root / test).is_file()})
    evidence = []
    expectations = _gate_expectations(plan_value, args.gate)
    mapped = set(expectations)
    for value in getattr(args, "evidence", None) or []:
        evidence.append(_validate_evidence(Path(value).expanduser().resolve(), source, expectations))
    covered = {_normalized_argv(item["argv"]) for item in evidence}
    if args.role == "reviewer" and mapped - covered:
        raise ValueError("reviewer evidence does not cover every mapped acceptance test")
    if args.role == "reviewer" and (missing or not evidence):
        raise ValueError("reviewer package requires existing mapped tests and current Gate evidence")
    if args.role == "reviewer" and args.gate == "3" and "INTENDED_RED" not in expectations.values():
        raise ValueError("Gate 3 requires at least one intended RED acceptance")
    if getattr(args, "previous", None) and not getattr(args, "findings", None):
        raise ValueError("previous snapshot requires findings")
    snapshot_path = package_dir / "snapshot"
    captured = _snapshot(root, snapshot_path)
    if _source(helpers, root) != source:
        raise ValueError("source changed while preparing review package")
    contracts = sorted({item["spec_path"] for item in plan_value["acceptances"]})
    rules = [value for value in ("AGENTS.md", "docs/development-process.md") if (root / value).is_file()]
    sources = sorted({path for path in plan_value["paths"]["effective"] if (root / path).exists()})
    result = {"package_path": str(package_dir / "package.json"), "snapshot": str(snapshot_path),
              "plan": str(plan_path), "plan_sha256": hashlib.sha256(plan_path.read_bytes()).hexdigest(),
              "role": args.role, "approval": "NOT_REVIEWED", "missing_tests": missing,
              "contracts": contracts, "rules": rules, "sources": sources, "evidence": evidence,
              "previous": getattr(args, "previous", None), "findings": getattr(args, "findings", None)}
    if result["previous"]:
        previous = Path(result["previous"]).expanduser().resolve()
        findings = Path(result["findings"]).expanduser().resolve()
        if not findings.is_file():
            raise ValueError("findings are unavailable")
        result["findings"] = str(findings)
        result["previous"] = str(previous)
        delta = package_dir / "delta.patch"
        _delta(root, previous, snapshot_path, delta)
        result["delta"] = str(delta)
    Path(result["package_path"]).write_text(json.dumps(result, ensure_ascii=False, sort_keys=True, indent=2) + "\n", encoding="utf-8")
    binding_path = _binding_path(helpers)
    binding_path.parent.mkdir(parents=True, exist_ok=True)
    binding = {"repository": str(root), "worktree": _worktree_realpath(helpers),
               "input": args.input, "base": args.base,
               "source": source, "plan": str(plan_path), "package_path": result["package_path"],
               "contracts": contracts, "prepared_at": _now()}
    temporary = binding_path.with_suffix(".tmp-" + uuid.uuid4().hex)
    temporary.write_text(json.dumps(binding, ensure_ascii=False, sort_keys=True) + "\n", encoding="utf-8")
    os.replace(temporary, binding_path)
    _json(result)
    return 0


def _parse(argv):
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    commands.add_parser("state")
    commands.add_parser("doctor")
    commands.add_parser("hook")
    install = commands.add_parser("install")
    install.add_argument("--config")
    prepare = commands.add_parser("prepare")
    prepare.add_argument("--input", required=True)
    prepare.add_argument("--base", required=True)
    prepare.add_argument("--role", required=True, choices=("root", "executor", "reviewer"))
    prepare.add_argument("--gate", choices=("3", "5"), default="5")
    prepare.add_argument("--previous")
    prepare.add_argument("--findings")
    prepare.add_argument("--evidence", action="append")
    return parser.parse_args(argv)


def main(args, helpers=None):
    helpers = _helpers(helpers)
    if isinstance(args, (list, tuple)):
        args = _parse(args)
    try:
        if args.command == "state":
            return command_state(args, helpers)
        if args.command == "hook":
            return command_hook(args, helpers)
        if args.command == "doctor":
            return command_doctor(args, helpers)
        if args.command == "prepare":
            return command_prepare(args, helpers)
        if args.command == "install":
            return command_install(args, helpers)
        raise ValueError(f"unsupported context command: {args.command}")
    except (OSError, TypeError, ValueError, subprocess.SubprocessError) as error:
        print(f"SETUP_FAILURE: {error}", file=sys.stderr)
        return 1
