#!/usr/bin/env python3
"""Observe bounded CI service prerequisites without mutating the environment."""

import argparse
import subprocess
import sys


PROBES = {
    "mariadb": ["mysqladmin", "ping"],
    "container": ["docker", "info"],
    "browser": ["node", "-e", "require.resolve('playwright')"],
}


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("kind", choices=("service",))
    parser.add_argument("name", choices=tuple(PROBES))
    args = parser.parse_args(argv)
    try:
        result = subprocess.run(PROBES[args.name], stdout=subprocess.DEVNULL,
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
