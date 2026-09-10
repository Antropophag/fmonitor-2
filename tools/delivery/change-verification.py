#!/usr/bin/env python3
"""Compute, validate and execute repository-owned pre-Gate 2 obligations."""
import argparse
import fnmatch
import hashlib
import json
from pathlib import Path, PurePosixPath
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
POLICY = ".quality-graph/verification-policy.json"
CATEGORIES = {"unit", "integration", "e2e", "governance"}


def strict_object(pairs):
    result = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"duplicate JSON key: {key}")
        result[key] = value
    return result


def load_json(relative):
    return json.loads(repo_path(relative).read_text(), object_pairs_hook=strict_object)


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


def build(base_ref, input_name):
    policy = load_json(POLICY)
    validate_policy(policy)
    change = load_json(input_name)
    if not isinstance(change, dict) or set(change) != {"change", "planned_paths", "acceptances"}:
        raise ValueError("malformed change input")
    if not isinstance(change["change"], str) or not change["change"]:
        raise ValueError("change name required")
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
    effective = sorted(set(planned) | {item["path"] for item in actual})
    selected = []
    required_categories = set()
    boundary_tests = set()
    for path in effective:
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
        if not isinstance(acceptance, dict) or set(acceptance) != required:
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
        normalized_acceptances.append({**acceptance, "tests": sorted(tests)})
    commands = []
    command_keys = set()
    def add(argv, phase, rationale):
        validate_argv(argv)
        key = tuple(argv)
        if key not in command_keys:
            command_keys.add(key)
            commands.append({"argv": argv, "phase": phase, "rationale": rationale})
    for test in sorted(acceptance_tests):
        add(test_argv(test, policy["runtimes"]), "focused", "acceptance mapping")
    for test in sorted(boundary_tests):
        add(test_argv(test, policy["runtimes"]), "focused", "changed boundary obligation")
    for test in sorted(consumer_tests):
        add(test_argv(test, policy["runtimes"]), "focused", "confirmed consumer obligation")
    for test in effective_tests:
        add(test_argv(test, policy["runtimes"], trusted_registered=True), "focused", "changed registered test")
    for category in sorted(required_categories):
        for argv in policy["category_argv"].get(category, []):
            add(argv, "focused", f"required {category} category obligation")
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
        "version": 1,
    }


def checked_plan(plan_name):
    plan = load_json(plan_name)
    if not isinstance(plan, dict) or plan.get("version") != 1:
        raise ValueError("invalid plan")
    expected = build(plan.get("base_ref"), plan.get("input"))
    if canonical(plan) != canonical(expected):
        raise ValueError("stale or tampered verification plan")
    return plan


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
            old = load_json(args.plan)
            if not isinstance(old, dict):
                raise ValueError("invalid plan")
            refreshed = build(old.get("base_ref"), old.get("input"))
            changed = old.get("commands") != refreshed.get("commands")
            repo_path(args.plan).write_text(canonical(refreshed))
            print(canonical({"obligations_changed": changed}), end="")
        else:
            plan = checked_plan(args.plan)
            results = []
            for command in plan["commands"]:
                if command["phase"] != args.phase:
                    continue
                harness = ROOT / "tools/delivery/harness.py"
                result = subprocess.run([sys.executable, str(harness), "run", "--", *command["argv"]],
                                        cwd=ROOT, capture_output=True, text=True)
                try:
                    summary = json.loads(result.stdout)
                except json.JSONDecodeError as error:
                    raise ValueError(f"harness returned invalid result: {error}") from error
                results.append({"argv": command["argv"], "outcome": summary["outcome"],
                                "exit_code": summary["exit_code"], "record_path": summary["record_path"],
                                "excerpt": summary["excerpt"]})
                if result.returncode and not args.diagnostic:
                    print(canonical({"results": results}), end="")
                    return result.returncode
            failures = [item for item in results if item["outcome"] != "GREEN"]
            print(canonical({"results": results}), end="")
            if failures:
                return failures[0]["exit_code"] or 1
    except (OSError, TypeError, ValueError, json.JSONDecodeError) as error:
        print(f"SETUP_FAILURE: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
