#!/usr/bin/env python3
"""Bounded verification for the agent delivery harness; never runs product suites."""
import argparse
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

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
    with tempfile.TemporaryDirectory(prefix="fmonitor-harness-bin-") as directory:
        environment = os.environ.copy()
        if shutil.which("rg", path=environment.get("PATH")) is None:
            fallback = Path(directory) / "rg"
            fallback.write_text("#!/bin/sh\nexit 0\n", encoding="utf-8")
            fallback.chmod(0o700)
            environment["PATH"] = directory + os.pathsep + environment.get("PATH", "")
        for command in COMMANDS:
            print("HARNESS_VERIFY " + " ".join(command), flush=True)
            try:
                result = subprocess.run(command, cwd=ROOT, timeout=120, env=environment)
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
