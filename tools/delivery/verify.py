#!/usr/bin/env python3
"""Bounded verification for the agent delivery harness; never runs product suites."""
import argparse
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
COMMANDS = [
    ("python3", "tests/Verification/delivery_harness_001_test.py"),
    ("python3", "tests/Verification/change_verification_001_test.py"),
    ("python3", "tests/Verification/verification_ci_001_test.py"),
    ("python3", "tests/Verification/verification_inventory_001_test.py"),
    ("python3", "tests/Verification/delivery_harness_hardening_001_test.py"),
    ("python3", "tests/Verification/delivery_harness_mutation_001_test.py"),
]


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=("list", "run"), nargs="?", default="run")
    args = parser.parse_args(argv)
    if args.action == "list":
        for command in COMMANDS:
            print(" ".join(command))
        return 0
    for command in COMMANDS:
        print("HARNESS_VERIFY " + " ".join(command), flush=True)
        try:
            result = subprocess.run(command, cwd=ROOT, timeout=120)
        except (OSError, subprocess.TimeoutExpired) as error:
            print(f"SETUP_FAILURE: {' '.join(command)}: {error}", file=sys.stderr)
            return 1
        if result.returncode:
            print(f"REGRESSION_FAILURE: {' '.join(command)} exit={result.returncode}", file=sys.stderr)
            return result.returncode
    print("HARNESS_VERIFY_OK")
    return 0


if __name__ == "__main__":
    sys.exit(main())
