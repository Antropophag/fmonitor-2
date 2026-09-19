#!/usr/bin/env python3
"""Canonical verification inventory parser, validator and registration CLI."""
import argparse
from pathlib import Path, PurePosixPath
import os
import sys
import tempfile
from typing import NamedTuple


ROOT = Path(__file__).resolve().parents[2]
MANIFEST = Path("tools/verification/suites.tsv")
DELIVERY_TESTS = ("tools/delivery/ci_launch_transport_198_test.py",)
SUITES = ("unit", "db", "characterization", "e2e")
RUNTIMES = ("php", "node", "python3")
CATEGORIES = ("unit", "integration", "e2e", "governance")
DISCOVERY = (
    "tests/InstallationProcess/*test.php",
    "tests/AssignmentOrderComposition/*test.php",
    "tests/Verification/*_test.mjs",
    "tests/Otiz/*test.php",
    "tests/Runtime/*test.php",
    "tests/Jobs/*test.php",
)
REQUIRED_DIRECTORIES = tuple(sorted({str(PurePosixPath(pattern).parent) for pattern in DISCOVERY}))


class Entry(NamedTuple):
    suite: str
    runtime: str
    path: str
    category: str


def repository_path(root, value):
    if not isinstance(value, str) or not value or "\x00" in value:
        raise ValueError(f"invalid catalog path: {value}")
    lexical = PurePosixPath(value)
    if lexical.is_absolute() or ".." in lexical.parts or value != lexical.as_posix():
        raise ValueError(f"invalid catalog path: {value}")
    if not (value.startswith("tests/") or value.startswith("rapid-pilot/")
            or value in DELIVERY_TESTS):
        raise ValueError(f"invalid catalog path: {value}")
    return root / value


def parse(content):
    entries = []
    seen = set()
    for number, raw in enumerate(content.splitlines(), 1):
        if not raw or raw.startswith("#"):
            continue
        fields = raw.split("\t")
        if len(fields) != 4:
            raise ValueError(f"invalid catalog row {number}: {raw}")
        entry = Entry(*fields)
        if entry.suite not in SUITES:
            raise ValueError(f"unknown catalog suite: {entry.suite}")
        if entry.runtime not in RUNTIMES:
            raise ValueError(f"unknown catalog runtime: {entry.runtime}")
        if entry.category not in CATEGORIES:
            raise ValueError(f"unknown catalog category: {entry.category}")
        if entry.path in seen:
            raise ValueError(f"duplicate catalog path: {entry.path}")
        seen.add(entry.path)
        entries.append(entry)
    return entries


def canonical(entries):
    return "".join("\t".join((e.suite, e.runtime, e.path, e.category)) + "\n"
                   for e in sorted(entries, key=lambda e: (e.suite, e.path, e.runtime, e.category)))


def is_canonical_test(path):
    """Candidate-aware test naming contract; support/fixture modules are excluded."""
    lexical = PurePosixPath(path)
    if not path.startswith("tests/") or len(lexical.parts) < 3 or lexical.parts[1] == "Support":
        return False
    return (path.endswith("test.php") or path.endswith("_test.mjs")
            or path.endswith("_test.py"))


def validate_entries(entries, root=ROOT, discover=True):
    paths = {entry.path for entry in entries}
    for directory in REQUIRED_DIRECTORIES:
        if not (root / directory).is_dir():
            raise ValueError(f"missing verification directory: {directory}")
    for entry in entries:
        target = repository_path(root, entry.path)
        if not target.is_file():
            raise ValueError(f"missing catalog file: {entry.path}")
    if discover:
        for pattern in DISCOVERY:
            for target in sorted(root.glob(pattern)):
                path = target.relative_to(root).as_posix()
                if target.is_file() and path not in paths:
                    raise ValueError(f"UNREGISTERED_TEST: {path}")
    by_category = {category: set() for category in CATEGORIES}
    for entry in entries:
        by_category[entry.category].add(entry.path)
    union = set().union(*by_category.values())
    if union != paths or sum(map(len, by_category.values())) != len(entries):
        raise ValueError("category inventory does not form a disjoint manifest union")
    return entries


def load(root=ROOT, manifest=MANIFEST, discover=True, canonical_order=True):
    target = root / manifest
    if not target.is_file():
        raise ValueError(f"missing verification catalog: {manifest.as_posix()}")
    content = target.read_text()
    entries = validate_entries(parse(content), root, discover)
    if canonical_order and content != canonical(entries):
        raise ValueError("verification catalog is not in canonical order")
    return entries


def validate_added_paths(paths, entries):
    registered = {entry.path for entry in entries}
    for path in sorted(set(paths)):
        if is_canonical_test(path) and path not in registered:
            raise ValueError(f"UNREGISTERED_TEST: {path}")


def register(path, category, runtime, suite, root=ROOT, manifest=MANIFEST):
    current = load(root, manifest, discover=False)
    candidate_entry = Entry(suite, runtime, path, category)
    # Parse validates every enum before any filesystem mutation.
    parse("\t".join((suite, runtime, path, category)) + "\n")
    target = repository_path(root, path)
    if not target.is_file():
        raise ValueError(f"missing catalog file: {path}")
    candidate = current + [candidate_entry]
    validate_entries(candidate, root)
    content = canonical(candidate)
    validate_entries(parse(content), root)
    catalog = root / manifest
    descriptor, temporary = tempfile.mkstemp(prefix=catalog.name + ".", dir=str(catalog.parent))
    try:
        with os.fdopen(descriptor, "w") as stream:
            stream.write(content)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(temporary, catalog)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)
    load(root, manifest)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    commands.add_parser("validate")
    commands.add_parser("canonicalize")
    listing = commands.add_parser("list")
    selector = listing.add_mutually_exclusive_group(required=True)
    selector.add_argument("--suite", choices=SUITES)
    selector.add_argument("--category", choices=CATEGORIES)
    registration = commands.add_parser("register")
    registration.add_argument("--file", required=True)
    registration.add_argument("--category", required=True)
    registration.add_argument("--runtime", required=True)
    registration.add_argument("--suite", required=True)
    args = parser.parse_args()
    try:
        if args.command == "register":
            register(args.file, args.category, args.runtime, args.suite)
            return 0
        entries = load(canonical_order=args.command != "canonicalize")
        if args.command == "canonicalize":
            sys.stdout.write(canonical(entries))
        elif args.command == "list":
            attribute = "suite" if args.suite else "category"
            value = args.suite or args.category
            for entry in entries:
                if getattr(entry, attribute) == value:
                    print(f"{entry.runtime}\t{entry.path}")
    except (OSError, ValueError) as error:
        print(f"SETUP_FAILURE: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
