#!/usr/bin/env python3
"""Observe bounded CI service prerequisites without mutating the environment."""

import argparse
import os
import subprocess
import sys


PROBES = {
    "mariadb": lambda: ["mysqladmin", "--host", os.environ.get("FMONITOR_TEST_DB_HOST", "127.0.0.1"),
                         "--port", os.environ.get("FMONITOR_TEST_DB_PORT", "3306"),
                         "--user", os.environ.get("FMONITOR_TEST_DB_ADMIN_USER", "root"),
                         "--password=" + os.environ.get("FMONITOR_TEST_DB_ADMIN_PASSWORD", "fmonitor2_test_root_local"),
                         "ping", "--silent"],
    "container": ["docker", "info"],
    "browser": ["node", "-e", "require.resolve('playwright')"],
}


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("kind", choices=("service",))
    parser.add_argument("name", choices=tuple(PROBES))
    args = parser.parse_args(argv)
    try:
        argv = PROBES[args.name]() if callable(PROBES[args.name]) else PROBES[args.name]
        result = subprocess.run(argv, stdout=subprocess.DEVNULL,
                                stderr=subprocess.DEVNULL, timeout=5)
    except (OSError, subprocess.TimeoutExpired):
        result = None
    if result is not None and result.returncode == 0:
        print(f"{args.name}=AVAILABLE")
        return 0
    print(f"{args.name}=UNAVAILABLE", file=sys.stderr)
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
