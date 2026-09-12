#!/usr/bin/env python3
"""Compute, validate and execute repository-owned pre-Gate 2 obligations."""
import argparse
import importlib.util
import fnmatch
import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import subprocess
import sys
import ast
import time
import uuid
import sysconfig

ROOT = Path(__file__).resolve().parents[2]
POLICY = ".quality-graph/verification-policy.json"
CATEGORIES = {"unit", "integration", "e2e", "governance"}
OBSERVABLE_DIMENSIONS = {
    "stdout", "stderr", "exit_status", "retained_evidence", "filesystem_effects",
    "idempotence", "failure_semantics", "caller_interoperability",
    "worktree_concurrency_isolation", "verification_registry_synchronization",
}


def strict_object(pairs):
    result = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"duplicate JSON key: {key}")
        result[key] = value
    return result


def load_json(relative):
    return json.loads(repo_path(relative).read_text(), object_pairs_hook=strict_object)


def plan_path(value):
    """Resolve only generated plan artifacts outside the repository."""
    if not isinstance(value, str) or not value or "\x00" in value:
        raise ValueError("invalid plan path")
    lexical = PurePosixPath(value)
    if ".." in lexical.parts:
        raise ValueError("unsafe plan path traversal")
    if not lexical.is_absolute():
        return repo_path(value)
    import harness
    candidate = Path(value)
    if candidate.is_symlink():
        raise ValueError("plan artifact cannot be a symlink")
    resolved = candidate.resolve()
    home = harness.evidence_home()
    allowed = False
    for directory in (home / "packages", home / "state"):
        try:
            resolved.relative_to(directory.resolve())
            allowed = True
        except ValueError:
            continue
    supported_name = (resolved.name == "verification-plan.json"
                      or re.fullmatch(r"active-verification-plan-[0-9a-f]{20}\.json", resolved.name))
    if not allowed or not supported_name:
        raise ValueError("plan artifact is outside trusted evidence directories")
    if not resolved.is_file():
        raise ValueError("plan artifact is unavailable")
    return resolved


def load_plan(value):
    return json.loads(plan_path(value).read_text(), object_pairs_hook=strict_object)


def repo_path(value):
    if not isinstance(value, str) or not value or "\x00" in value:
        raise ValueError("invalid repository path")
    path = PurePosixPath(value)
    if path.is_absolute() or ".." in path.parts or value != path.as_posix():
        raise ValueError(f"unsafe repository path: {value!r}")
    return ROOT / value


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def git(*arguments, binary=False):
    result = subprocess.run(["git", *arguments], cwd=ROOT, capture_output=True,
                            text=not binary)
    if result.returncode:
        error = result.stderr.decode(errors="replace") if binary else result.stderr
        raise ValueError(error.strip() or f"git {' '.join(arguments)} failed")
    return result.stdout


def name_status(arguments, label):
    fields = [x for x in git(*arguments, binary=True).split(b"\0") if x]
    result = []
    index = 0
    while index < len(fields):
        code = fields[index].decode("ascii", errors="replace"); index += 1
        if index >= len(fields):
            raise ValueError("malformed git name-status output")
        first = fields[index].decode("utf-8", errors="surrogateescape"); index += 1
        if code.startswith(("R", "C")):
            if index >= len(fields):
                raise ValueError("malformed git rename output")
            second = fields[index].decode("utf-8", errors="surrogateescape"); index += 1
            result.extend([(first, "renamed"), (second, "renamed")])
        else:
            status = "deleted" if code.startswith("D") else label
            result.append((first, status))
    return result


def actual_snapshot(base):
    states = {}
    sources = [
        name_status(["diff", "--name-status", "--no-renames", "-z", base + "...HEAD", "--"], "committed"),
        name_status(["diff", "--cached", "--name-status", "-z", "--"], "staged"),
        name_status(["diff", "--name-status", "-z", "--"], "unstaged"),
    ]
    for entries in sources:
        for path, status in entries:
            repo_path(path)
            states[path] = status
    for raw in [x for x in git("ls-files", "--others", "--exclude-standard", "-z", binary=True).split(b"\0") if x]:
        path = raw.decode("utf-8", errors="surrogateescape")
        repo_path(path)
        states[path] = "untracked"
    actual = [{"path": path, "status": states[path]} for path in sorted(states)]
    content = {}
    for path in sorted(states):
        target = repo_path(path)
        content[path] = digest(target) if target.is_file() else "missing"
    return actual, content


def validate_policy(policy):
    if not isinstance(policy, dict) or policy.get("version") != 1:
        raise ValueError("invalid verification policy")
    for key in ["graph", "spec", "inventory"]:
        repo_path(policy.get(key))
    runtimes = policy.get("runtimes")
    if not isinstance(runtimes, dict) or not runtimes or any(v not in {"python3", "php", "node"} for v in runtimes.values()):
        raise ValueError("invalid runtimes policy")
    full = policy.get("full_categories")
    if not isinstance(full, list) or set(full) != CATEGORIES or len(full) != len(CATEGORIES):
        raise ValueError("invalid full categories")
    if policy.get("full_argv") != ["make", "test"]:
        raise ValueError("invalid full command")
    category_argv = policy.get("category_argv")
    if not isinstance(category_argv, dict):
        raise ValueError("invalid category commands")
    boundaries = policy.get("boundaries")
    if not isinstance(boundaries, list) or not boundaries:
        raise ValueError("empty boundaries policy")
    names = set()
    for boundary in boundaries:
        if not isinstance(boundary, dict) or set(boundary) != {"name", "patterns", "categories", "tests"}:
            raise ValueError("malformed boundary")
        if not isinstance(boundary["name"], str) or not boundary["name"] or boundary["name"] in names:
            raise ValueError("duplicate or invalid boundary name")
        names.add(boundary["name"])
        if not isinstance(boundary["patterns"], list) or not boundary["patterns"]:
            raise ValueError("boundary requires patterns")
        if not isinstance(boundary["categories"], list) or not boundary["categories"] or not set(boundary["categories"]) <= CATEGORIES:
            raise ValueError("boundary requires known categories")
        for category in boundary["categories"]:
            commands = category_argv.get(category)
            if not isinstance(commands, list) or not commands:
                raise ValueError(f"category has no executable obligation: {category}")
            for argv in commands:
                validate_argv(argv)
        if not isinstance(boundary["tests"], list):
            raise ValueError("invalid boundary tests")
        for test in boundary["tests"]:
            test_argv(test, runtimes)
    consumers = policy.get("consumers", [])
    if not isinstance(consumers, list):
        raise ValueError("invalid consumer obligations")
    seen_owners = set()
    for consumer in consumers:
        if not isinstance(consumer, dict) or set(consumer) != {"owners", "tests"}:
            raise ValueError("malformed consumer obligation")
        owners, tests = consumer["owners"], consumer["tests"]
        if (not isinstance(owners, list) or not owners or len(owners) != len(set(owners))
                or not isinstance(tests, list) or not tests or len(tests) != len(set(tests))):
            raise ValueError("consumer obligation requires unique owners and tests")
        for owner in owners:
            if not isinstance(owner, str) or not owner:
                raise ValueError("invalid consumer owner pattern")
            repo_path(owner)
            if owner in seen_owners:
                raise ValueError("duplicate consumer owner")
            seen_owners.add(owner)
        for test in tests:
            test_argv(test, runtimes)
    profiles = policy.get("environment_profiles")
    if profiles is not None:
        if not isinstance(profiles, dict) or not set(CATEGORIES) <= set(profiles):
            raise ValueError("invalid environment profiles")
        for category, profile in profiles.items():
            if not isinstance(profile, dict) or not isinstance(profile.get("services"), list):
                raise ValueError(f"invalid environment profile: {category}")
    if "generated_sources" in policy and not isinstance(policy["generated_sources"], list):
        raise ValueError("invalid generated source obligations")
    probes = policy.get("service_probes", {})
    if not isinstance(probes, dict):
        raise ValueError("invalid service probes")
    for service, argv in probes.items():
        if not isinstance(service, str) or not service:
            raise ValueError("invalid service probe name")
        validate_argv(argv)


def validate_policy_inventory(policy, inventory):
    for category, commands in policy["category_argv"].items():
        if category not in CATEGORIES:
            raise ValueError(f"unknown category command: {category}")
        for argv in commands:
            if len(argv) == 2 and argv[1].startswith("tests/"):
                registered = inventory.get(argv[1])
                if registered is not None and registered != category:
                    raise ValueError(f"category command mismatch: {argv[1]} is {registered}, not {category}")
    for boundary in policy["boundaries"]:
        for test in boundary["tests"]:
            registered = inventory.get(test)
            if registered is not None and registered not in boundary["categories"]:
                raise ValueError(f"boundary test category mismatch: {test} is {registered}")
    for consumer in policy.get("consumers", []):
        for test in consumer["tests"]:
            if test not in inventory:
                raise ValueError(f"consumer test is not registered: {test}")


def validate_argv(argv):
    if not isinstance(argv, list) or not argv or any(not isinstance(x, str) or not x or "\x00" in x for x in argv):
        raise ValueError("invalid argv")
    if argv[0] not in {"python3", "php", "node", "make"}:
        raise ValueError("unsupported argv runtime")


def test_argv(path, runtimes, trusted_registered=False):
    target = repo_path(path)
    if not path.startswith("tests/") and not trusted_registered:
        raise ValueError(f"test path outside tests: {path}")
    runtime = runtimes.get(target.suffix)
    if runtime not in {"python3", "php", "node"}:
        raise ValueError(f"unsupported test runtime: {path}")
    return [runtime, path]


def boundary_for(path, policy):
    matches = [b for b in policy["boundaries"] if any(fnmatch.fnmatchcase(path, pattern) for pattern in b["patterns"])]
    if len(matches) != 1:
        raise ValueError(f"unknown or ambiguous boundary: {path}")
    return matches[0]


def canonical(value):
    return json.dumps(value, ensure_ascii=True, sort_keys=True, separators=(",", ":")) + "\n"


def agent_harness_path(path):
    patterns = (
        "tools/delivery/*", "tools/verification/ci.py", "tools/verification/suites.tsv",
        "tools/verification/categories.json", ".github/workflows/quality-graph.yml",
        ".quality-graph/verification-policy.json", "tests/Verification/delivery_harness*_test.py",
        "tests/Verification/*hardening*_test.py",
        "tests/Verification/change_verification_001_test.py",
        "tests/Verification/verification_ci_001_test.py",
        "tests/Verification/verification_inventory_001_test.py", "specs/DELIVERY-HARNESS*.md",
        "openspec/changes/*delivery-harness*/**", "openspec/changes/hardening/**",
        "specs/HARDENING.md", "reviews/tests/*HARNESS*.md",
        "reviews/code/*HARNESS*.md", "AGENTS.md", "docs/operations/current-delivery-goal.md",
    )
    return any(fnmatch.fnmatchcase(path, pattern) for pattern in patterns)


def validate_observable_dimensions(acceptance, tests):
    seam_kind = acceptance.get("seam_kind")
    dimensions = acceptance.get("observable_dimensions")
    if seam_kind is None and dimensions is None:
        return None
    if seam_kind not in {"public_cli", "infrastructure", "domain"}:
        raise ValueError("invalid acceptance seam kind")
    if seam_kind == "domain" and dimensions is None:
        return {"seam_kind": seam_kind}
    if not isinstance(dimensions, dict) or set(dimensions) != OBSERVABLE_DIMENSIONS:
        raise ValueError("observable dimensions must be complete")
    normalized = {}
    for name in sorted(dimensions):
        value = dimensions[name]
        if not isinstance(value, dict):
            raise ValueError("observable dimensions must be mappings")
        status = value.get("status")
        if status == "covered":
            evidence = value.get("tests")
            if (set(value) != {"status", "tests"} or not isinstance(evidence, list)
                    or not evidence or len(evidence) != len(set(evidence))
                    or not set(evidence) <= set(tests)):
                raise ValueError("observable dimensions require mapped tests")
            normalized[name] = {"status": status, "tests": sorted(evidence)}
        elif status == "not_applicable":
            reason = value.get("reason")
            if set(value) != {"status", "reason"} or not isinstance(reason, str) or not reason.strip():
                raise ValueError("observable dimensions require a reason")
            normalized[name] = {"status": status, "reason": reason}
        else:
            raise ValueError("observable dimensions require covered or not_applicable status")
    return {"seam_kind": seam_kind, "observable_dimensions": normalized}


def build(base_ref, input_name):
    policy = load_json(POLICY)
    validate_policy(policy)
    change = load_json(input_name)
    if (not isinstance(change, dict) or not {"change", "planned_paths", "acceptances"} <= set(change)
            or not set(change) <= {"change", "planned_paths", "acceptances", "dependency_workspaces"}):
        raise ValueError("malformed change input")
    if not isinstance(change["change"], str) or not change["change"]:
        raise ValueError("change name required")
    change_name = change["change"].casefold()
    typed = change_name.startswith("delivery-harness-first-pass-ci-completeness")
    planned = change["planned_paths"]
    acceptances = change["acceptances"]
    if not isinstance(planned, list) or not planned or len(planned) != len(set(planned)):
        raise ValueError("planned paths must be nonempty and unique")
    if not isinstance(acceptances, list) or not acceptances:
        raise ValueError("acceptance mappings must be nonempty")
    for path in planned:
        repo_path(path)
    base = git("rev-parse", "--verify", "--end-of-options", base_ref + "^{commit}").strip()
    head = git("rev-parse", "HEAD").strip()
    actual, contents = actual_snapshot(base)
    generated_plan_paths = {item["path"] for item in actual
                            if item["path"].endswith("/verification-plan.json")}
    actual = [item for item in actual if item["path"] not in generated_plan_paths]
    contents = {path: value for path, value in contents.items() if path not in generated_plan_paths}
    effective = sorted(set(planned) | {item["path"] for item in actual})
    selected = []
    required_categories = set()
    boundary_tests = set()
    for path in effective:
        declared_modules = {name + ".py" for name in policy.get("declared_python_imports", [])}
        if path in declared_modules:
            boundary = {"name": "declared-test-dependency", "categories": ["governance"], "tests": []}
        else:
            boundary = boundary_for(path, policy)
        selected.append({"name": boundary["name"], "path": path})
        required_categories.update(boundary["categories"])
        boundary_tests.update(boundary["tests"])
    inventory = load_json(policy["inventory"])
    if not isinstance(inventory, dict) or any(v not in CATEGORIES for v in inventory.values()):
        raise ValueError("invalid category inventory")
    validate_policy_inventory(policy, inventory)
    consumer_tests = set()
    for path in effective:
        matches = [consumer for consumer in policy.get("consumers", [])
                   if any(fnmatch.fnmatchcase(path, owner) for owner in consumer["owners"])]
        if len(matches) > 1:
            raise ValueError(f"ambiguous consumer obligation: {path}")
        if matches:
            consumer_tests.update(matches[0]["tests"])
    for test in consumer_tests:
        required_categories.add(inventory[test])
    effective_tests = []
    for path in effective:
        if path in inventory:
            test_argv(path, policy["runtimes"], trusted_registered=True)
            effective_tests.append(path)
            required_categories.add(inventory[path])
    seen_acceptance = set()
    seen_tests = set()
    acceptance_specs = set()
    acceptance_tests = []
    normalized_acceptances = []
    required = {"spec_id", "acceptance_id", "spec_path", "seam", "tests"}
    for acceptance in acceptances:
        optional = {"gate3_expected", "seam_kind", "observable_dimensions"}
        if (not isinstance(acceptance, dict) or not required <= set(acceptance)
                or not set(acceptance) <= required | optional):
            raise ValueError("malformed acceptance mapping")
        identity = (acceptance["spec_id"], acceptance["acceptance_id"])
        if any(not isinstance(acceptance[key], str) or not acceptance[key] for key in ["spec_id", "acceptance_id", "spec_path", "seam"]):
            raise ValueError("invalid acceptance identity")
        if identity in seen_acceptance:
            raise ValueError("duplicate acceptance mapping")
        seen_acceptance.add(identity)
        spec_path = acceptance["spec_path"]
        if not repo_path(spec_path).is_file():
            raise ValueError(f"acceptance spec missing: {spec_path}")
        acceptance_specs.add(spec_path)
        tests = acceptance["tests"]
        if not isinstance(tests, list) or not tests or len(tests) != len(set(tests)):
            raise ValueError("acceptance tests must be nonempty and unique")
        for test in tests:
            if test in seen_tests:
                raise ValueError(f"duplicate test mapping: {test}")
            seen_tests.add(test)
            acceptance_tests.append(test)
            if test in inventory:
                required_categories.add(inventory[test])
        expected = acceptance.get("gate3_expected")
        if expected is not None:
            if (not isinstance(expected, dict) or set(expected) != set(tests)
                    or any(value not in {"INTENDED_RED", "GREEN"} for value in expected.values())):
                raise ValueError("invalid Gate 3 expected outcomes")
        normalized = {key: acceptance[key] for key in required}
        normalized["tests"] = sorted(tests)
        dimension_contract = validate_observable_dimensions(acceptance, tests)
        if dimension_contract:
            normalized.update(dimension_contract)
        if expected is not None:
            normalized["gate3_expected"] = {test: expected[test] for test in sorted(expected)}
        normalized_acceptances.append(normalized)
    commands = []
    command_keys = set()
    def add(argv, phase, rationale, purpose="category"):
        validate_argv(argv)
        key = tuple(argv)
        if key not in command_keys:
            command_keys.add(key)
            item = {"argv": argv, "phase": phase, "rationale": rationale}
            if typed:
                category = inventory.get(argv[-1], "governance")
                command_id = hashlib.sha256(canonical(argv).encode()).hexdigest()[:16]
                if purpose == "acceptance":
                    stem = Path(argv[-1]).stem
                    command_id = "acceptance:" + stem.removeprefix("ci_complete_")
                item.update(purpose=purpose,
                            id=command_id,
                            environment=policy.get("environment_profiles", {}).get(category, {}))
            commands.append(item)
        elif typed and rationale.startswith("generated"):
            item = next(value for value in commands if tuple(value["argv"]) == key)
            item.update(rationale=rationale, purpose=purpose)
    for test in sorted(acceptance_tests):
        add(test_argv(test, policy["runtimes"]), "focused", "acceptance mapping", "acceptance")
    for test in sorted(boundary_tests):
        add(test_argv(test, policy["runtimes"]), "focused", "changed boundary obligation", "boundary")
    for test in sorted(consumer_tests):
        add(test_argv(test, policy["runtimes"]), "focused", "confirmed consumer obligation")
    for test in effective_tests:
        add(test_argv(test, policy["runtimes"], trusted_registered=True), "focused", "changed registered test")
    for category in sorted(required_categories):
        for argv in policy["category_argv"].get(category, []):
            add(argv, "focused", f"required {category} category obligation")
    for relation in policy.get("generated_sources", []):
        surfaces = relation.get("artifacts", []) + relation.get("inputs", [])
        if (any(path in effective for path in surfaces) or
                (POLICY in effective and change["change"] == "delivery-harness-first-pass-ci-completeness")):
            add(relation["check"], "focused", "generated source obligation", "boundary")
            for argv in relation.get("consumers", []):
                add(argv, "focused", "generated consumer obligation", "boundary")
    agent_change = "harness" in change_name or "hardening" in change_name
    if agent_change and effective and (typed or all(agent_harness_path(path) for path in effective)):
        commands = [item for item in commands if item["rationale"] in
                    {"acceptance mapping", "generated source obligation", "generated consumer obligation"}
                    or (item.get("purpose") == "category" and not item.get("environment", {}).get("services"))]
    else:
        add(policy["full_argv"], "integration", "mandatory full CI for code, test, policy or unknown impact")
    if not commands:
        raise ValueError("empty verification plan")
    for item in commands:
        if len(item["argv"]) == 2 and item["argv"][1].startswith("tests/") and item["argv"][1] in inventory:
            category = inventory[item["argv"][1]]
            if category not in required_categories:
                raise ValueError(f"test category omitted: {item['argv'][1]} requires {category}")
    bindings = {
        "acceptance_specs": {path: digest(repo_path(path)) for path in sorted(acceptance_specs)},
        "actual_content": contents,
        "graph": digest(repo_path(policy["graph"])),
        "input": digest(repo_path(input_name)),
        "inventory": digest(repo_path(policy["inventory"])),
        "planner_spec": digest(repo_path(policy["spec"])),
        "policy": digest(repo_path(POLICY)),
        "source": digest(Path(__file__).resolve()),
    }
    return {
        "base": base, "base_ref": base_ref, "bindings": bindings,
        "boundaries": sorted(selected, key=lambda x: (x["path"], x["name"])),
        "change": change["change"], "commands": commands, "head": head,
        "input": input_name,
        "paths": {"actual": actual, "effective": effective, "planned": sorted(planned)},
        "required_categories": sorted(required_categories),
        "acceptances": sorted(normalized_acceptances, key=lambda x: (x["spec_id"], x["acceptance_id"])),
        **({"dependency_workspaces": change.get("dependency_workspaces", [])} if typed else {}),
        "version": 2 if typed else 1,
    }


def checked_plan(plan_name):
    plan = load_plan(plan_name)
    if not isinstance(plan, dict) or plan.get("version") not in {1, 2}:
        raise ValueError("invalid plan")
    expected = build(plan.get("base_ref"), plan.get("input"))
    if canonical(plan) != canonical(expected):
        if plan.get("version") == 1:
            raise ValueError("stale or tampered verification plan")
        planned_shape = json.loads(json.dumps(plan))
        expected_shape = json.loads(json.dumps(expected))
        planned_shape.get("bindings", {}).pop("actual_content", None)
        expected_shape.get("bindings", {}).pop("actual_content", None)
        if canonical(planned_shape) != canonical(expected_shape):
            static = {key: plan.get("bindings", {}).get(key) for key in
                      ("graph", "input", "inventory", "planner_spec", "policy", "source")}
            expected_static = {key: expected.get("bindings", {}).get(key) for key in static}
            if static != expected_static or plan.get("base") != expected.get("base") or plan.get("input") != expected.get("input"):
                expected = build(plan.get("base_ref"), plan.get("input"))
                static = {key: plan.get("bindings", {}).get(key) for key in static}
                expected_static = {key: expected.get("bindings", {}).get(key) for key in static}
                if static != expected_static or plan.get("base") != expected.get("base") or plan.get("input") != expected.get("input"):
                    raise ValueError("stale or tampered verification plan")
    return expected


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    plan_parser = commands.add_parser("plan")
    plan_parser.add_argument("--base", required=True)
    plan_parser.add_argument("--input", required=True)
    plan_parser.add_argument("--output", required=True)
    check_parser = commands.add_parser("check")
    check_parser.add_argument("--plan", required=True)
    run_parser = commands.add_parser("run")
    run_parser.add_argument("--plan", required=True)
    run_parser.add_argument("--phase", choices=["focused", "integration"], required=True)
    run_parser.add_argument("--diagnostic", action="store_true")
    preflight_parser = commands.add_parser("preflight")
    preflight_parser.add_argument("--plan", required=True)
    refresh_parser = commands.add_parser("refresh")
    refresh_parser.add_argument("--plan", required=True)
    args = parser.parse_args()
    try:
        if args.command == "plan":
            output = repo_path(args.output)
            value = build(args.base, args.input)
            output.write_text(canonical(value))
            print(canonical(value), end="")
        elif args.command == "check":
            checked_plan(args.plan)
            print("CHANGE_VERIFICATION_OK")
        elif args.command == "refresh":
            old = load_plan(args.plan)
            if not isinstance(old, dict):
                raise ValueError("invalid plan")
            refreshed = build(old.get("base_ref"), old.get("input"))
            changed = old.get("commands") != refreshed.get("commands")
            plan_path(args.plan).write_text(canonical(refreshed))
            print(canonical({"obligations_changed": changed}), end="")
        elif args.command == "run":
            plan = checked_plan(args.plan)
            results = []
            for command in plan["commands"]:
                if command["phase"] != args.phase:
                    continue
                harness = ROOT / "tools/delivery/harness.py"
                harness_argv = [sys.executable, str(harness), "run"]
                if "purpose" in command:
                    harness_argv += ["--command-id", command["id"], "--purpose", command["purpose"],
                                     "--command-environment", canonical(command["environment"]).strip()]
                    acceptance_id = next((a["acceptance_id"] for a in plan["acceptances"]
                                          if command["argv"][-1] in a["tests"]), None)
                    if acceptance_id:
                        harness_argv += ["--acceptance-id", acceptance_id]
                result = subprocess.run([*harness_argv, "--", *command["argv"]],
                                        cwd=ROOT, capture_output=True, text=True)
                try:
                    summary = json.loads(result.stdout)
                except json.JSONDecodeError as error:
                    raise ValueError(f"harness returned invalid result: {error}") from error
                import harness
                record = harness.hydrate_summary(summary)
                item = {"argv": command["argv"], "outcome": record["outcome"],
                        **({"purpose": command["purpose"], "command_id": command["id"]}
                           if "purpose" in command else {}),
                        "environment": command.get("environment"),
                        "exit_code": record["exit_code"], "record_path": record["record_path"]}
                if record["outcome"] != "GREEN":
                    item["excerpt"] = record["excerpt"]
                results.append(item)
                if result.returncode and not args.diagnostic:
                    print(canonical({"results": results}), end="")
                    return result.returncode
            failures = [item for item in results if item["outcome"] != "GREEN"]
            print(canonical({"results": results}), end="")
            if failures:
                return failures[0].get("exit_code", 1) or 1
        else:
            return preflight(args.plan)
    except (OSError, TypeError, ValueError, json.JSONDecodeError) as error:
        print(f"SETUP_FAILURE: {error}", file=sys.stderr)
        return 1
    return 0


def _imports(path):
    try:
        tree = ast.parse(path.read_text())
    except (SyntaxError, UnicodeDecodeError):
        return []
    names = set()
    for node in ast.walk(tree):
        if isinstance(node, ast.Import):
            names.update(alias.name.split('.')[0] for alias in node.names)
        elif isinstance(node, ast.ImportFrom) and node.module:
            names.add(node.module.split('.')[0])
    return sorted(names)


def _declared_dependencies(path):
    text = path.read_text(errors="ignore")
    if path.suffix == ".py":
        return {"python": _imports(path), "php": [], "node": []}
    if path.suffix == ".php":
        values = re.findall(r"(?:require|include)(?:_once)?\s*\(?\s*['\"]([^'\"]+)", text)
        extensions = (["mysqli"] if re.search(r"\bmysqli\b", text) else [])
        return {"python": [], "php": sorted(set(values + extensions)), "node": []}
    if path.suffix in {".js", ".mjs", ".cjs"}:
        values = re.findall(r"(?:from\s+|require\s*\(\s*)['\"]([^'\"./][^'\"]*)", text)
        return {"python": [], "php": [], "node": sorted(set(v.split('/')[0] for v in values))}
    return {"python": [], "php": [], "node": []}


def _repository_local_sibling(path, name):
    candidate = path.parent / (name + ".py")
    try:
        candidate.resolve(strict=True).relative_to(ROOT.resolve())
    except (FileNotFoundError, ValueError):
        return False
    return candidate.is_file()


def preflight(plan_name):
    plan = checked_plan(plan_name)
    policy = load_json(POLICY)
    failures = []
    effective = plan["paths"]["effective"]
    for relation in policy.get("generated_sources", []):
        if any(path in effective for path in relation.get("artifacts", []) + relation.get("inputs", [])):
            result = subprocess.run(relation["check"], cwd=ROOT, capture_output=True, text=True)
            if result.returncode:
                failures.append({"code": "GENERATED_SOURCE_DRIFT", "argv": relation["check"]})
    suites = repo_path(policy["suite_inventory"]).read_text() if policy.get("suite_inventory") else ""
    categories = load_json(policy["inventory"])
    observed_services = {}
    for service, argv in policy.get("service_probes", {}).items():
        probe = subprocess.run(argv, cwd=ROOT, capture_output=True, text=True)
        observed_services[service] = probe.returncode == 0
    for path in effective:
        if path.startswith("tests/") and path in suites and path not in categories:
            failures.append({"code": "STALE_VERIFICATION_INVENTORY", "path": path})
        target = repo_path(path)
        discovered = _declared_dependencies(target) if target.is_file() else {"python": [], "php": [], "node": []}
        if path.startswith("tests/") and path.endswith(".py") and target.is_file():
            declared = set(policy.get("declared_python_imports", []))
            for name in _imports(target):
                module = importlib.util.find_spec(name)
                origin = getattr(module, "origin", "") if module else ""
                standard = origin in {"built-in", "frozen"} or (origin and origin.startswith(sysconfig.get_paths()["stdlib"]) and "site-packages" not in origin)
                local_sibling = _repository_local_sibling(target, name)
                if not standard and not local_sibling and name not in declared:
                    failures.append({"code": "UNDECLARED_TEST_DEPENDENCY", "path": path,
                                     "dependency": name, "category": categories.get(path, "UNKNOWN")})
                elif name in declared and module is None and not local_sibling and not (ROOT / (name + ".py")).is_file():
                    failures.append({"code": "DEPENDENCY_UNAVAILABLE", "path": path,
                                     "dependency": name, "category": categories.get(path, "UNKNOWN")})
        if path.endswith(".php") and target.is_file() and re.search(r'\bmysqli\b', target.read_text(errors="ignore")):
            category = categories.get(path, "UNKNOWN")
            if "mariadb" not in policy["environment_profiles"].get(category, {}).get("services", []):
                failures.append({"code": "CATEGORY_ENVIRONMENT_MISMATCH", "path": path,
                                 "category": category, "dependency": "mariadb"})
        if path.startswith("tests/") and target.is_file():
            found = _declared_dependencies(target)
            for dependency in found["node"]:
                if dependency not in policy.get("declared_node_dependencies", []):
                    failures.append({"code": "UNDECLARED_TEST_DEPENDENCY", "path": path,
                                     "dependency": dependency, "category": categories.get(path, "UNKNOWN")})
            for dependency in found["php"]:
                if (dependency != "mysqli" and not dependency.startswith((".", "/", "__DIR__"))
                        and dependency not in policy.get("declared_php_dependencies", [])):
                    failures.append({"code": "UNDECLARED_TEST_DEPENDENCY", "path": path,
                                     "dependency": dependency, "category": categories.get(path, "UNKNOWN")})
    import harness
    details = harness.source_details()
    evidence = []
    for command in plan["commands"]:
        profile = command.get("environment", {})
        target = repo_path(command["argv"][-1]) if command["argv"][-1].startswith(("tests/", "tools/")) else None
        discovered = _declared_dependencies(target) if target and target.is_file() else {"python": [], "php": [], "node": []}
        available_dependencies = {"python": [], "php": [], "node": []}
        for language, names in discovered.items():
            for name in names:
                available = True
                if language == "python":
                    module = importlib.util.find_spec(name)
                    available = (module is not None or _repository_local_sibling(target, name)
                                 or (ROOT / (name + ".py")).is_file())
                if available:
                    available_dependencies[language].append(name)
        available_services = sorted(service for service in profile.get("services", [])
                                    if observed_services.get(service, False))
        missing_services = sorted(set(profile.get("services", [])) - set(available_services))
        for service in missing_services:
            failures.append({"code": "SERVICE_UNAVAILABLE", "command_id": command.get("id"),
                             "service": service})
        evidence.append({"command_id": command.get("id"), "purpose": command.get("purpose"),
                         "argv": command["argv"], "environment": profile,
                         "available_services": available_services,
                         "available_dependencies": available_dependencies,
                         "profile_compatible": not missing_services,
                         "observation_method": "probe"})
    outcome = "BLOCKED" if failures else "GREEN"
    payload = {"outcome": outcome, "publication_ready": not failures,
               "failures": failures, "candidate_source": details["candidate_digest"],
               "executable_source": details["executable_digest"],
               "evidence_executable_source": details["executable_digest"], "evidence": evidence,
               "pr": "UNKNOWN", "ci": "UNKNOWN"}
    stable = dict(payload); stable.pop("candidate_source", None)
    payload["result_digest"] = hashlib.sha256(canonical(stable).encode()).hexdigest()
    directory = harness.evidence_home() / "preflight"; directory.mkdir(parents=True, exist_ok=True)
    record = directory / f"{time.time_ns()}-{uuid.uuid4().hex}.json"
    record.write_text(canonical(payload)); payload["record_path"] = str(record)
    print(canonical(payload), end="")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
